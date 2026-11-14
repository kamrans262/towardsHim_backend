# M4 — Community

Scope: signed-in members publish 100-character text posts and discuss them with replies. Guests are directed to the existing Login/Create Account screens. Private messaging, uploads and new mobile designs are not included.

## Member JSON API (authenticated Bearer token, /api/v1)
- GET community/posts?mine=0|1&page=N&per_page=25 — newest-first paginated feed; author name/avatar, date and reply count
- POST community/posts {body} — required trimmed string, max 100 characters; rate limited
- GET community/posts/{id}?page=N&per_page=50 — discussion with paginated replies
- POST community/posts/{id}/replies {body} — required trimmed string, max 1000 characters; rate limited
- DELETE community/posts/{id} — author only; cascades replies
- DELETE community/posts/{postId}/replies/{replyId} — reply author only; verifies parent

## Hope UI web admin (existing authenticated admin role)
- /admin/community — all member posts with author, date, count and Review
- /admin/community/{post} — original post, replies, Remove Post, and Remove Reply buttons
- Both deletions require browser confirmation and server-side admin authorization.
- Removing a post cascades its replies; removing one reply does not affect others.

## Mobile integration
- Keep the approved Community, Create Post, Post Replies, cards, orange button, and All/My Posts segmented control unchanged.
- Replace demo posts/replies with live API results.
- Existing popup: 'Hide post/reply' hides someone else's content only for the current screen/session. 'Remove post/reply' deletes ONLY your own content on the server.
- No fake default posts or replies are shown to real members.
- The Community tab stays restricted to authenticated members.

## Release hold
- Do not deploy before PHP and Flutter checks, and review of M1–M3 pending deployment.
- Run PHP targeted tests: php artisan test --filter=M4CommunityTest
- Run Flutter tests and flutter analyze.
- Back up MySQL, deploy backend, run php artisan migrate --force, inspect web/admin access, and test with two distinct member accounts.
