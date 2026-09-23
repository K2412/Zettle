---
paths:
  - app/Http/Middleware/EnsureEmailIsAllowed.php
---

# Middleware

## Auth is locked to a single allowlisted email
Sign-in (`login.store`) and sign-up (`register.store`) are gated by `EnsureEmailIsAllowed` middleware, wired via `config/fortify.php` `middleware`. Any email other than `config('auth.allowed_email')` (backed by the `AUTH_ALLOWED_EMAIL` env var) is redirected to `route('home')` instead of authenticating/registering. The real address is never committed — it lives in the local `.env` and the deployment env; `.env.example` ships a blank placeholder. When `AUTH_ALLOWED_EMAIL` is unset the middleware fails closed (blocks everyone). Tests set a dummy `allowed@example.com` via `phpunit.xml`, so use that in auth tests rather than a real address. To change who can access the app, set `AUTH_ALLOWED_EMAIL`.
