<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends RestController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $users = User::all();

        return $this->response()->data($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        return $this->response()->data(User::factory()->create());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     *
     * @throws EntityNotFoundException
     * @throws GatePolicy
     * @throws BindingResolutionException
     */
    public function show($id)
    {
        $user = User::find($id);

        $this->gate('access', $user);

        return $this->response()->model($user)->toResponse($this->request());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            /**
             * @var User
             */
            $user = User::find($id);
            $user->fill($request->only($user->getFillable()));
            $this->gate('access', $user);

            return $this->respond($user);
        } catch (EntityNotFoundException|GatePolicy|BindingResolutionException $e) {
            return $this->response()
                ->addError('Entity not found.')
                ->missing()
                ->respond();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     *
     * @throws BindingResolutionException
     * @throws EntityNotFoundException
     * @throws GatePolicy
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $this->gate('access', $user);
        $user->delete();

        return $this->message('User deleted.')->toResponse($this->request());
    }
}
