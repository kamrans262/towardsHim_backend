# Toward HIM - Optional Demo Seeder

Only run this intentionally. The default DatabaseSeeder remains unchanged.

## Hostinger SSH steps

1. Back up the production database first. The sample content is visible through public/mobile endpoints, so do not use it for a live audience without approval.
2. Keep kamrans262@gmail.com as the mobile member account. Create a separate active admin account, for example kamrans262+admin@gmail.com, using the secure toward:admin-create command. The seeder uses the oldest active administrator and does not change any account's password or privileges.
3. From /home/u777477188/domains/chocolate-goldfish-405384.hostingersite.com/backend run:

    git pull --ff-only origin main && /opt/alt/php85/usr/bin/php artisan toward:admin-create kamrans262+admin@gmail.com --name="Administrator"

   After creating the admin with a new password, run:

    /opt/alt/php85/usr/bin/php artisan db:seed --class=TowardHimDemoSeeder --force

4. Because Hostinger serves a separate public_html directory, ensure it exposes the Laravel public storage directory:

    test -e ../public_html/storage || test -L ../public_html/storage || ln -s "$(pwd)/storage/app/public" ../public_html/storage

5. Verify the three published audio demo clips through the public /api/v1/audio endpoint and check Admin Dashboard / Members / Challenges / Audio / Community.

## Seeded content

- Eight fictional members with reserved example.test addresses, randomly generated unguessable passwords (cannot sign into these demo accounts), including one inactive member.
- Three published seven-day challenges with sample progress plus one draft challenge.
- Eight community posts and sixteen replies, all text-only.
- Three published original instrumental PCM WAV test clips (one per Podcast/Meditation/Prayer). These are not actual spoken prayers or podcasts; replace them before launch.
- Six draft audio records with realistic titles and descriptions, pending authentic uploads.
- Three in-app new-audio publication records. No external push, email or SMS are dispatched by the seeder.
- Default Welcome/About content only if no admin-customized record exists.

Every sample audio and challenge title begins with [Demo] or [Demo Draft].

## Safety

- Existing member/admin records and existing admin-curated content are not modified.
- A reserved sentinel demo email prevents the seeder from making changes on repeated runs; it also prevents resurrecting deleted/moderated demo posts.
- Demo content is publicly accessible via the relevant app APIs, so review and remove it before production launch.
- Do not run migrate:fresh on Hostinger and do not remove actual user data as part of a demo cleanup.
