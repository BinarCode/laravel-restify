<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Notifications\VerifyEmail;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateController extends RepositoryController
{
    /**
     * The attributes written, with their rules, when the users repository does not declare profileUpdateRules().
     * It is the default of the UserProfile trait.
     */
    private const array DEFAULT_ATTRIBUTE_RULES = [
        'name' => ['sometimes', 'required', 'string', 'max:255'],
    ];

    public function __invoke(RepositoryShowRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($repository = $this->guessRepository($request)) {
            $repository->allowToUpdate($request)->update($request, $repository::profileKey($user));

            return data($repository->serializeForShow($request));
        }

        if (! $user instanceof Model) {
            abort(JsonResponse::HTTP_UNAUTHORIZED);
        }

        $this->updateWithoutRepository($request, $user);

        return data($user->fresh());
    }

    public function guessRepository(RestifyRequest $request): ?Repository
    {
        $repository = $request->repository('users');

        if (! $repository) {
            return null;
        }

        if (method_exists($repository, 'canUseForProfileUpdate')) {
            if (! call_user_func([$repository, 'canUseForProfileUpdate'], $request)) {
                return null;
            }
        }

        // The row is the authenticated user itself, matched on its primary key. A user that is not a row of the
        // repository, such as an admin of another guard, is updated as a model instead: its id names no row here.
        $key = $repository::profileKey($request->user());

        if ($key === null) {
            return null;
        }

        $repository->withResource(
            $repository::query($request)->whereKey($key)->first()
        );

        return $repository;
    }

    /**
     * Update the profile when the users repository is not used for it.
     *
     * Only validated input is written: the attributes the repository lists in profileUpdateRules(), as far as the
     * model has them fillable, plus the email and the password. Those two are the credentials the user signs in
     * and resets the password with, so they only change together with the current password.
     */
    private function updateWithoutRepository(RestifyRequest $request, Model&Authenticatable $user): void
    {
        $attributeRules = $this->attributeRules($request, $user);

        $changesEmail = $this->changesEmail($request, $user);
        $changesPassword = $request->has('password');

        $rules = $attributeRules;

        if ($changesEmail) {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique($user::class, 'email')->ignoreModel($user)];
        }

        if ($changesPassword) {
            $rules['password'] = ['required', 'string', 'confirmed', Password::defaults()];
        }

        if ($changesEmail || $changesPassword) {
            $rules['current_password'] = ['bail', 'required', 'string', 'current_password'];
        }

        $validated = Validator::make($request->all(), $rules)->validate();

        $writable = array_map(fn (string $attribute): string => Str::before($attribute, '.'), array_keys($attributeRules));

        if ($changesEmail) {
            $writable[] = 'email';
        }

        $attributes = [];

        foreach ($writable as $attribute) {
            if (array_key_exists($attribute, $validated) && $user->isFillable($attribute)) {
                $attributes[$attribute] = $validated[$attribute];
            }
        }

        $user->fill($attributes);

        // The address the user verified is no longer the one on the account.
        $emailNeedsVerification = $user->isDirty('email') && $user instanceof MustVerifyEmail;

        if ($emailNeedsVerification) {
            $user->forceFill(['email_verified_at' => null]);
        }

        if ($changesPassword) {
            $user->forceFill([
                $user->getAuthPasswordName() => Hash::make($request->string('password')->toString()),
            ]);
        }

        $user->save();

        // The same notification registration sends. Without its route the application verifies emails itself.
        if ($emailNeedsVerification && (Route::has('restify.verify') || VerifyEmail::$createUrlCallback !== null)) {
            Notification::send($user, new VerifyEmail);
        }
    }

    /**
     * The rules of the attributes the profile may write, by attribute.
     *
     * The key, the password, the remember token and the email are dropped from what the repository lists: the
     * first three are never profile data, and the email and the password are validated and written by this
     * controller only.
     *
     * @return array<string, mixed>
     */
    private function attributeRules(RestifyRequest $request, Model&Authenticatable $user): array
    {
        $repository = Restify::repositoryClassForKey('users');

        /** @var array<string, mixed> $rules */
        $rules = $repository !== null && method_exists($repository, 'profileUpdateRules')
            ? $repository::profileUpdateRules($request)
            : self::DEFAULT_ATTRIBUTE_RULES;

        // Compared lowercased: column names are case-insensitive in SQL.
        $reserved = array_map(Str::lower(...), [
            $user->getKeyName(),
            $user->getAuthPasswordName(),
            $user->getRememberTokenName(),
            'email',
            'password',
            'password_confirmation',
            'current_password',
        ]);

        return Arr::where(
            $rules,
            fn (mixed $rule, string $attribute): bool => ! in_array(Str::lower(Str::before($attribute, '.')), $reserved, true),
        );
    }

    /**
     * Whether the request names another email than the one on the account. The email is compared and stored
     * lowercased, as registration stores it, so sending the current address back in any casing changes nothing.
     */
    private function changesEmail(RestifyRequest $request, Model $user): bool
    {
        if (! $request->has('email')) {
            return false;
        }

        $email = $request->input('email');

        if (! is_string($email)) {
            return true;
        }

        $request->merge(['email' => $email = Str::lower($email)]);

        $current = $user->getAttribute('email');

        return ! is_string($current) || Str::lower($current) !== $email;
    }
}
