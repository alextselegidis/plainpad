# Release Notes 

## [Unreleased]

### Added

- Add quick search spotlight overlay triggered with Ctrl/Cmd + K
- Offer to create a note from the quick search keyword when nothing matches
- Remember the open state of the sidebar and aside menus between sessions
- Add DemoSeeder for populating the database with realistic notes

### Fixed

- Enforce TLS verification and trusted origins in the auto update service
- Rate limit login attempts on `POST /v1/sessions`, keyed on the matched account (GHSA-qg7c-2q9c-v7hg)
- Fix password-reset expiry and related auth weaknesses
- Fix account lockout via the token-based password reset flow
- Invalidate the previous password reset token when a new one is requested (GHSA-wgcw-8jm6-f847)
- Revoke existing sessions on password change (except the current one) and on password reset (GHSA-fx8g-533q-jp2w)
- Block `setup.php` after install to prevent XSS via unescaped POST values
- Fail the auto update request when installing the update fails, instead of reporting success
- Keep interrupted auto updates retryable and stop browsers from caching `index.html` after an update
- Fix the installation failing on an empty database because the token check queried the missing
  `sessions` table
- Keep the encryption and admin flags when a user update omits them, editing an encrypted user from the
  users screen made their notes unreadable
- Scroll to the top when switching between notes
- Show the login error on a wrong password instead of requesting an undefined user and reloading the page
- Allow special characters like `(` and `[` in the note search
- Add the missing password reset messages to the de-DE, fr-FR, sv-SE and zh-CN translations
- Stop shipping local logs, sessions and cache files in the release zip
- Wait for the server to revoke the session on logout
- Keep notes that are pinned or unpinned before their first sync, they were deleted on the next sync
- Stop showing the previous user's notes after logging in as another user in the same tab, and remove the local
  notes on logout once they are synced
- Keep syncing the other changes when one fails, allow notes with empty content and retry sync after repeated
  failures instead of stopping until a reload
- Generate a random application key during setup instead of one derived from the install time
- Quote and validate the database values written to `.env` by `setup.php`, escape the values it echoes and build
  the application URL from the request host instead of the `Origin` header
- Show the correct default admin password after setup and ask to change it
- Run the note encryption toggle in a database transaction so a failure cannot leave notes partly encrypted
- Require the current password to change your own email or password
- Never store the plain password in the browser and stop re-sending it on every profile update
- Limit failed logins per account and address, so a single attacker cannot lock the account owner out
- Reject auto update archives with entries outside the install directory
- Sign release archives with Ed25519 and refuse auto updates without a valid `<archive>.sig` signature, a backup
  key is accepted as well
- Refuse update archives that are not the version the update server offers them as (rollback protection)
- Run the setup migrations in process instead of an HTTP request built from the `Host` header, and allow running
  the setup again when the database step fails
- Send the password recovery email after the response and hide mail errors, so neither reveals registered emails
- Take the same time for unknown and known emails on login
- Require passwords of at least 8 characters when creating or updating users
- Answer every API error with JSON, also without an `Accept: application/json` header: invalid input is a 422 instead
  of a redirect to the app page, a failed login says why, and other errors no longer render HTML pages
- Stop crashing (500) on login requests with a non string email
- Validate the locale, view, line, sort and theme values, an invalid locale made every request of that user fail,
  and the note and user list parameters
- Do all password recovery work after the response, so its timing does not reveal registered emails
- Refuse to install into a database that already contains tables, the installation dropped all of them
- Send headers that stop other sites from framing the app (clickjacking), add `TRUSTED_PROXIES` for reverse
  proxies and stop recommending `chmod 777` in the docs
- Keep the app info request working when the update check cache directory is not writable
- Refresh the sidebar without re-reading every note after a save, read the local notes once per sync, page the
  first sync, keep the server copy of uploaded notes (no repeated uploads) and keep the local note cache across
  releases
- Lock the admin rows while demoting or deleting an admin, so two admins cannot remove each other at once
- Wait for a running sync before removing the local notes on logout
- Fetch all notes in one request on the first sync, it ran into the API rate limit with a few hundred notes
- Time out update checks after a few seconds and cache them for an hour, an unreachable update server made the app
  hang for admins
- Stop rewriting every note on each profile save, load the note list without reading all contents, sort notes
  without re-parsing dates and add database indexes for sessions and users
- Prevent demoting the last admin
- Answer 404 instead of 401 for another user's note, so the client does not log out
- Check only the configured database for existing tables during setup, a database user that can see other
  databases was always told the database is not empty
- Stop printing the default admin login hint into the `setup.php` page
- Show setup errors as readable alerts instead of raw PHP warnings and say which directory must be writable

### Changed

- Start the CRA dev server automatically with the docker stack, skipping installs when the dependency
  folders already exist
- Raise the nginx `client_max_body_size` value


## [1.1.1] - 2026-04-23

### Fixed

- Fix privilege-escalation vulnerability allowing any authenticated user to grant themselves admin (#138)
- Prevent account enumeration and unauthenticated account-lockout abuse on the password recovery endpoint
- Whitelist sortable columns and sort direction on user and note list endpoints to prevent unsafe `ORDER BY` input
- Add form validation rules to the user modal


## [1.1.0] - 2026-02-03

### Added

- Add support for PHP 8.2+ / upgrade to Laravel 12 (#64)
- Make sure that the tab title has the note title (#105)
- Add tab key press support (insert 4 spaces) (#63)
- Add character count to note editor (-)
- Add right click custom context menu for the note editor (#53)


## [1.0.0] - 2024-12-13 

This is the first release including the initial set of features for the app. Multiple
user support, note-taking, application settings, authorization and authentication.
    
