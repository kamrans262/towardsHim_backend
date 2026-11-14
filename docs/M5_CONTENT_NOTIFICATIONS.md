# Toward HIM — M5: Welcome/About and member notifications

## Admin
- GET /admin/content: Hope UI / Toward HIM branded form for Welcome Heading, Introduction Text, About Information.
- PATCH /admin/content: CSRF-protected save; only active authenticated admins. Two admins have the same role/permissions.
- Defaults match the approved Flutter copy. Onboarding text, contact cards, logos, mission/vision/values remain unchanged.
- GET /api/v1/content public for guests and signed-in members; no new app screen.

## In-app notifications
- First publication of podcast, meditation or prayer produces exactly one audio_publications row, via AudioPublished event.
- Editing an already published item does not generate another notification. If unpublished and republished, unique audio id prevents duplicates.
- GET /api/v1/notifications authenticated, paginated newest first. Item includes audio ID, category, title, description, artwork, publication time, read state.
- PATCH /api/v1/notifications/{publication}/read marks read for one member only.
- DELETE /api/v1/notifications/{publication} hides for one member only, permanently server-side.
- Deleting an audio item cascades its publication notification. Draft/unpublished audio is never visible.
- Notification tap routes to existing Player using GET /api/v1/audio/{id} and marks read.

## Firebase push readiness (connect provider later)
- Flutter push initialization is guarded behind --dart-define=TOWARD_HIM_PUSH_ENABLED=true after platform Firebase configuration exists.
- Authenticated POST /api/v1/devices/push and DELETE /api/v1/devices/push manage device tokens.
- Backend job SendAudioPush sends Firebase HTTP v1 via a private service-account file when FIREBASE_PUSH_ENABLED=true, FIREBASE_PROJECT_ID and FIREBASE_SERVICE_ACCOUNT_PATH are set.
- Default is disabled. Do not enable until Firebase apps, platform credential files, queue worker and provider authorization have been tested.
- Service-account file must live outside public_html and must never be committed.
- QUEUE_CONNECTION=database and running queue:work are required for asynchronous delivery when enabled.
- Apple/Google social authentication remains prepared from M1; no credentials are invented.

## Release hold
- No Hostinger deployment before review.
- Run: php artisan test --filter=M5ContentNotificationsTest and Flutter targeted tests/analyze.
- On Hostinger: back up DB, git pull, run Composer, migrate with PHP 8.5, synchronize backend/public to effective public_html, php artisan optimize:clear.
- Verify two member accounts independently read/delete notifications, and that edits to published audio create no duplicates.
- Verify admin content edits show on Welcome and About without layout changes.


## Existing merchandise store tab
- Configure TOWARD_HIM_STORE_URL with the real HTTPS store URL provided by the client.
- GET /api/v1/content returns optional store_url for the existing Flutter Store bottom-tab action.
- App opens this HTTPS URL in the phone's external browser. No in-app products/cart/checkout UI.
- Until configured, the approved placeholder notice is shown; no store URL was guessed.
