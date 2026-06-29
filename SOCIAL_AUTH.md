# Restify Social Auth (OAuth) — proposal 🔐

Adds social login (GitHub, Google, Atlassian/Jira…) to Restify via Laravel Socialite — same style as `restifyAuth`.

**Why:** many apps we build re-implement the same "Connect with GitHub/Atlassian" plumbing by hand (Socialite + redirect/callback controllers + linked-accounts table + find-or-create + Sanctum token). This makes it a one-command package feature instead.

```bash
php artisan restify:social --providers=github,atlassian
```
→ publishes `social_accounts` migration, adds `Route::restifySocialAuth();`, prints the exact `.env` + `services.php` per provider. Then:
```
GET /api/auth/social/github/redirect  → { "url": "https://github.com/.../authorize" }
GET /api/auth/social/github/callback  → find-or-create user, link account, return token
```

**Overridable** at every level: config (providers/scopes), `Restify::resolveSocialUserUsing(...)` hook, or `--publish` to own the controllers.

**Why it's good for the package:** kills per-project boilerplate, one tested impl instead of N variants, multi-provider linking out of the box, stays lean (Socialite optional), feels native (same token shape, config under `restify.auth.social`). Fully tested. Branch `feature/social-auth-providers`, not pushed — open for review.
