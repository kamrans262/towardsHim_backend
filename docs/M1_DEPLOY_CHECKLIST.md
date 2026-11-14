# M1 deployment checklist — only after review

1. Back up production database and .env. Never commit .env.
2. Confirm PHP 8.5 CLI is used for artisan.
3. git pull --ff-only origin main; composer2 install --no-dev --optimize-autoloader
4. /opt/alt/php85/usr/bin/php artisan migrate --force
5. /opt/alt/php85/usr/bin/php artisan optimize:clear
6. /opt/alt/php85/usr/bin/php artisan toward:admin-create admin@example.com --name="Admin"
7. Sync backend/public with Hostinger's separate public_html; keep modified public_html/index.php pointing to backend/vendor and backend/bootstrap.
8. Upload Hope UI v4 CSS to public_html/admin-assets/css/hope-ui.min.css (from the supplied Hope UI ZIP). It is deliberately not committed as a huge vendor file.
9. Verify / resolves to admin login, /admin/dashboard requires admin, /admin/members lists members, /api/v1/auth/register and login work.
10. Set MAIL_MAILER to actual configured SMTP before verifying OTP delivery. Set GOOGLE_CLIENT_IDS / APPLE_CLIENT_IDS only when provider setup is complete.

This project uses the existing Hope UI admin template classes and Toward HIM brand colors. All other admin menus are placeholders until their modules are implemented. No demo user accounts are created.
