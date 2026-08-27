# CLAUDE.md

Guidance for AI coding agents working in this repository.

## Commits

- Do not add a `Co-Authored-By: Claude ...` trailer to commit messages.
- Do not reference Claude, or any AI assistant, as an author or co-author of a commit.

## Branches

- Do not create a new branch unless the maintainer explicitly asks for one. Commit the work to the branch that is
  already checked out.

## Changelog

- Wrap `CHANGELOG.md` lines at 120 characters maximum. Break longer entries onto continuation lines.

## Project

Plainpad is a self-hosted note taking app: a Laravel 12 JSON API (`server/`) plus a Create React App SPA
(`client/`), shipped as a single zip built by `build.sh` into `build/`.

- GPLv3. Every source file carries the license header — keep it when creating files.
- Version lives in `client/.env` (`REACT_APP_VERSION`) and the server config.

## Layout

```
client/          React 16 + MobX SPA (Create React App, CoreUI 2 + Bootstrap 4, SCSS)
  src/stores/    MobX stores (account, application, notes, profile, settings, users) — app state lives here
  src/http/      Fetch wrappers, one per resource; HttpClient adds the Bearer token and throws OfflineError
  src/views/     Screens (Notes, Settings, About, Help, Pages/Login, RecoverPassword, ResetPassword, 404, 500)
  src/containers/NoteLayout/  App shell (header, aside, footer)
  src/lang/      i18n messages per locale: en-US, de-DE, fr-FR, sv-SE, zh-CN
  src/storage.js localforage wrapper, one IndexedDB instance per version + user, for offline notes
server/          Laravel 12 / PHP 8.2+
  app/Http/Controllers/V1/  ApplicationController, NotesController, SessionsController, SettingsController,
                            UsersController
  app/Http/Middleware/      auth (custom token Authenticate), admin
  app/Models/    Note, Session, Setting, User
  app/Services/AutoUpdateService.php  Downloads and applies releases from APP_REPOSITORY
  routes/api.php All endpoints, prefixed `v1`, served through `public/api.php`
  tests/         PHPUnit (Feature + Unit)
docker/          nginx, php-fpm, mysql config for the dev stack
docs/            User facing docs (installation, docker, api, upgrade)
build/           Generated bundle — never edit by hand, it is recreated by build.sh
```

## Architecture notes

- The SPA calls the API at `api.php/v1/...` (`REACT_APP_BASE_URL`). nginx maps `/api.php/` to the server and
  reverse proxies everything else to the CRA dev server on port 3000.
- Auth is a custom bearer token stored in the `sessions` table, not Sanctum/Passport. `SessionsController::create`
  is rate limited via `throttle:login`; password recovery via `throttle:5,15`.
- Admin-only routes (settings, user management, app update/refresh) sit behind the `admin` middleware group.
- Notes sync offline-first: the notes store writes to localforage and reconciles with the API, generating UUIDs
  client side.
- New API routes must be registered in `server/routes/api.php` inside the matching middleware group, and get a
  matching HTTP client method in `client/src/http/`.
- New user facing strings must be added to every locale under `client/src/lang/`.

## Commands

```bash
docker compose up -d                      # Start the dev stack (nginx, php-fpm, mysql, phpmyadmin, mailpit)
./build.sh                                # Produce build/ and build.zip for release

cd client && npm start                    # CRA dev server on port 3000
cd client && npm run build                # Production client bundle
cd client && npm test                     # Jest / react-scripts tests

cd server && php artisan migrate:fresh --seed
cd server && ./vendor/bin/phpunit         # Server tests
cd server && ./vendor/bin/pint            # PHP formatting (Laravel Pint)
```

Ports come from the root `.env` (`NGINX_PORT`, `MYSQL_PORT`, `PHPMYADMIN_PORT`, `MAILPIT_HTTP_PORT`,
`MAILPIT_SMTP_PORT`). The server needs its own `server/.env`, copied from `server/.env.example`.

## Conventions

- PHP: Laravel Pint (PSR-12). Controllers stay thin, validation via `$request->validate()`.
- JS: 2 space indent, single quotes, MobX `decorate(...)` classes, no TypeScript.
- Keep changes scoped to `client/` or `server/`; `build/` is output only.
