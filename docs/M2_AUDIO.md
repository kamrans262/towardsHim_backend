# M2 - Audio Content

Three categories: podcasts, meditations, prayers.

- Hope UI admin: /admin/audio/podcasts, /admin/audio/meditations, /admin/audio/prayers
- Admin supports list, add/edit, upload/replace audio, optional artwork, draft/publish, confirmed removal.
- Guest/mobile JSON API: GET /api/v1/audio?category=podcast|meditation|prayer, GET /api/v1/audio/{id}.
- Only published records with audio file and published_at are visible.
- New publication dispatches AudioPublished event for M5 notification delivery; editing published audio creates no new event.
- Audio files are on Laravel PUBLIC disk to enable native HTTP streaming/range requests. The effective webroot needs a symlink to backend/storage/app/public.
- Optional duration_minutes field aids list labels. Mobile player uses actual audio duration when known.
- Flutter Listen/player retain their existing design and use just_audio for published audio.
- No server-generated podcast demo content or internal downloads.

## Release checklist (after review)
1. Verify both main commits; run Laravel M2 tests and Flutter targeted tests and analyze.
2. Back up production database and deploy code; run Composer and php artisan migrate --force with PHP 8.5.
3. Verify Hostinger upload_max_filesize and post_max_size are large enough (e.g. 110M and 115M) for desired uploads.
4. Ensure backend public files are synced to Hostinger public_html and its storage symlink targets backend/storage/app/public (not private storage).
5. Copy original approved Hope UI 4 CSS from the supplied ZIP into public_html/admin-assets/css/hope-ui.min.css if missing.
6. Verify listing API, admin upload, playback with pause/seek, no access to drafts.
