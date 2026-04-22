# Release Notes 

## [Unreleased]

### Security

- Fix privilege-escalation vulnerability allowing any authenticated user to grant themselves admin (#138)
- Prevent account enumeration and unauthenticated account-lockout abuse on the password recovery endpoint
- Whitelist sortable columns and sort direction on user and note list endpoints to prevent unsafe `ORDER BY` input

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
    
