# Backlog

Tracked items that are real but not being worked on right now. Each entry has
enough context to pick back up cold — what's wrong, why it matters, and where
to look.

---

## Security

### No rate limiting on `POST /login`

**Where:** `routes/web.php` — the `Route::post('/login', ...)` inside the
`guest` middleware group; handled by `App\Http\Controllers\Auth\AuthController::login()`.

**What's wrong:** There is no throttle middleware on the login route at all,
so login attempts are unlimited. CN_ADMIN uses Laravel Fortify, which
throttles login by default; CN_LMS's custom `AuthController` has no
equivalent.

**Why it matters:** Real student accounts are exposed to unlimited
brute-force password guessing with no lockout or backoff.

**Suggested fix:** Add Laravel's built-in `throttle` middleware to the login
route (e.g. `throttle:5,1` keyed by email+IP, matching Fortify's default
behaviour) — no new package needed, just middleware on the existing route.

---

## Issues

### Student profile avatar is broken (shows a broken image icon)

**Where:** `resources/views/layouts/sections/navbar/navbar-partial.blade.php`
(the account dropdown avatar, both the small navbar icon and the dropdown
header) — `Auth::user()->profile_photo_url`.

**What's wrong:** `profile_photo_url` isn't a real attribute or accessor on
`App\Models\User` — it just returns `null`, so the `<img>` tag renders a
broken image icon instead of a picture or initials fallback.

**Why it matters:** Every student sees a broken image in the top-right corner
on every page.

**Suggested fix:** Two parts, both already scoped when the profile page was
built:

1. Short term — replace the broken `<img>` with an initials fallback (same
   pattern already used on `/profile` and the dashboard header: a circle with
   the student's initials), so nothing looks broken even without a real photo.
2. Real fix — needs `users.avatar_path` (added by CN_ADMIN's
   `2026_08_02_200010_add_avatar_and_account_audits.php` migration, not yet
   run against the shared database) plus an `avatarUrl()`/`initials()` helper
   on `User`, then wiring up actual avatar upload (skipped earlier alongside
   the rest of that migration — see the profile page work in conversation
   history for the full context).

---

### Inactive students can still log in and use the portal

**Where:** `App\Http\Controllers\Auth\AuthController::login()` — after
`Auth::attempt()` it only checks `auth()->user()->student_id`, never the
student's `status`.

**What's wrong:** A student marked **Inactive** in CN_ADMIN can still sign in and
use everything (homework, calendar, marketplace, AI Tutor). Checked 2026-10-11:
25 inactive students all still have a portal login and are still assigned to a
class; 19 of those are in classes with homework assigned.

**Why it matters:** Access that should have ended hasn't, and each of them can
spend AI Tutor credit (up to 30 messages/day, roughly $12–54/month worst case
with ~67 usable students instead of 48 active).

**Suggested fix:** In `login()`, also reject a student whose `student.status` is
not active (same case-insensitive `LOWER(status) = 'active'` check the
leaderboard already uses), with a clear message like "This account is inactive —
please contact your tutor." Also consider ending sessions of students who become
inactive while logged in (middleware check), and confirm with the centre first
whether inactive students are _meant_ to keep read access (e.g. to old homework).

---

## Features

### 1. Chat system — students can message anyone

Direct/group messaging inside the portal. Open questions to settle before
building: who is "anyone" (just other students in the same class? tutors?
school-wide?), moderation/safety review given the userbase is minors, whether
it's real-time (websockets/Reverb) or polling, and message retention.

### 2. AI assistant per subject — BUILT, waiting on an API key

Implemented as the **AI Tutor** page (`/ai-tutor`): subject picker → per-subject
chat, one shared prompt template filled with each course's name/level, history
in `cn_lms.ai_chat_messages`, daily cap (`AI_TUTOR_DAILY_LIMIT`, default 30) and
replay window (`AI_TUTOR_HISTORY_TURNS`, default 20). Tested end to end against a
fake API; **never run against the real Claude API yet**.

To switch it on: fix the `.env`-is-tracked item above first, then set
`ANTHROPIC_API_KEY` in `.env` and run `php artisan config:clear`.

Default model is `claude-haiku-5-5` (cheapest; ~$1–16/month for 100 students at
typical use). Its tutoring quality is unverified — when the key arrives, run a
few real student-style questions through it and `claude-sonnet-5-5` and compare
before letting students use it. Switching is just `ANTHROPIC_MODEL` in `.env`.

Still open: per-course tuned prompts, a guardrail check beyond the prompt,
Markdown/code-block rendering in replies (plain text for now), and the widget
version if students use the page.

### 3. Sync local files to the cloud LMS

Ties into the in-browser Code Editor (`App\Http\Controllers\Student\IdeController`,
`app/Models/Ide/*`) — likely means letting a student push/pull files between
a local dev environment and their saved IDE project in CN_LMS, or auto-saving
local edits up to the cloud. Needs scoping: what "local" means here (a
desktop app? browser file access?), conflict handling, and storage limits.

---
