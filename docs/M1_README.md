# Toward HIM — M1: Authentication and Members

The Laravel 13 backend serves both the mobile app API and Hope UI styled admin pages.

## Web routes
- GET / — administrator sign-in (root domain)
- POST / — admin sign-in
- GET /admin/dashboard — summary (admin only)
- GET /admin/members — list/search/filter member accounts
- GET /admin/members/{member} — member details
- PATCH /admin/members/{member}/status — activate/deactivate member
- POST /admin/logout — sign out

## Mobile API (JSON, prefix /api/v1)
- POST /auth/register (name, email, password, password_confirmation)
- POST /auth/login (email, password)
- POST /auth/google (identity_token) — Google ID token verification; requires GOOGLE_CLIENT_IDS
- POST /auth/apple (identity_token, optional nonce) — Apple ID token signature verification; requires APPLE_CLIENT_IDS
- POST /auth/password/send-code (email)
- POST /auth/password/verify-code (email, code) — returns short-lived reset_ticket
- POST /auth/password/reset (email, reset_ticket, password, password_confirmation)
- GET /auth/me (Bearer token)
- PATCH /auth/profile (Bearer token; name, email, bio)
- POST /auth/logout (Bearer token)
- POST /auth/logout-all (Bearer token)
- DELETE /auth/account (Bearer token and password)

Passwords are hashed. API bearer tokens are random, SHA-256 hashed in storage, expire after 30 days, and revoked on deactivation. API endpoints never issue administrator tokens. Admin pages use CSRF-protected Laravel browser sessions. There is no public administrator signup route. Social identities never auto-link to an existing email/password account.

## Server setup (after code review)
1. Deploy main, run: composer2 install --no-dev --optimize-autoloader
2. Run: /opt/alt/php85/usr/bin/php artisan migrate --force
3. Create administrator interactively: /opt/alt/php85/usr/bin/php artisan toward:admin-create admin@example.com --name="Admin Name"
4. Configure real MAIL_* SMTP credentials before testing password-recovery delivery. With MAIL_MAILER=log, codes go to server logs and not inboxes.
5. Later set GOOGLE_CLIENT_IDS and APPLE_CLIENT_IDS (comma-separated exact app client/bundle identifiers), then clear configuration cache.
6. Copy the original Hope UI v4 assets from the uploaded template's dist/assets/css/hope-ui.min.css to public/admin-assets/css/hope-ui.min.css, or deploy the attached asset package. The committed Bootstrap fallback and brand CSS remain usable even before that optional asset is copied.
7. If Hostinger public_html is a separate public directory, sync the updated backend/public files there on deployment. Never expose the backend root, .env or private storage.

The existing mobile design is unchanged. Apple and Google authentication require valid provider configuration on the devices and server. Password reset code verification returns a ticket, but changing the password requires sending a new password to /auth/password/reset; this must be surfaced through an approved existing UI interaction rather than a new designed screen.
