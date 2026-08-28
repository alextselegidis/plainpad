# Release Notes 

## [Unreleased]

### Added

- Add quick search spotlight overlay triggered with Ctrl/Cmd + K
- Offer to create a note from the quick search keyword when nothing matches
- Remember the open state of the sidebar and aside menus between sessions
- Add DemoSeeder for populating the database with realistic notes

### Fixed

- Enforce TLS verification and trusted origins in the auto update service
- Rate limit login attempts on `POST /v1/sessions`
- Fix password-reset expiry and related auth weaknesses
- Fix account lockout via the token-based password reset flow
- Block `setup.php` after install to prevent XSS via unescaped POST values

### Changed

- Start the CRA dev server automatically with the docker stack, skipping installs when the dependency folders
  already exist
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
    
