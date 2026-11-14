# M3 - Weekly Challenges & Individual Progress

Based on the Toward Him Web Application Scope: admins manually publish a new seven-day challenge; latest published is current, all older challenges remain accessible; member checkboxes auto-save independently; editing text never resets completed day records.

- Admin: /admin/challenges -> list current/previous/drafts, create/edit, manually publish.
- Admin form: title, instructions, Day 1...Day 7 activity text, Save Draft or Publish. Once published, edits do not unpublish.
- Mobile authenticated API: GET /api/v1/challenges; GET /api/v1/challenges/{id}; PUT /api/v1/challenges/{id}/days/{1..7} {"completed":true|false}.
- No public challenge API; guests are required to sign in.
- The completion table has UNIQUE(user_id, weekly_challenge_day_id) to prevent duplicates, and updates use stable day row IDs.
- Publication time orders current first; earlier challenges remain in previous indefinitely.
- Progress meter is completed_days out of 7 based on authenticated member.
- No real content is seeded automatically. Actual challenge text/rules must be entered by administrators.

RELEASE: After targeted PHPUnit and Flutter tests pass, deploy backend, run artisan migrate --force on PHP 8.5, verify with two different member accounts, and keep existing Flutter UI unchanged. No deployment was performed from GitHub.
