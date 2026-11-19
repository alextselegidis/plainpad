# End-to-end (e2e) tests for web

Browser tests written in plain English for [Kane CLI](https://www.testmuai.com/kane-cli/).

| Test | What it covers |
|---|---|
| `note_test.md` | Writing a note, pinning it, and finding it with the sidebar filter |
| `user_test.md` | Adding a user as admin and logging in as that user |

## Run

Start the Docker stack with `NGINX_PORT=8099` in the root `.env`, create `server/.env` from `server/.env.example`, and reset the database so the default admin (`admin@example.org` / `12345678`) exists:

```bash
docker compose up -d
docker compose exec php-fpm sh -c "cd server && php artisan migrate:fresh --seed --force && php artisan cache:clear"
npm install -g @testmuai/kane-cli
kane-cli login
kane-cli testmd run e2e/kane/note_test.md
```

Run the reset before each test.
