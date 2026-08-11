# 🗓️ Capylendar

Capylendar is a private application for collaborative planning for two. It is built on Laravel 13, Inertia.js 3, and Vue 3, and alongside the web app, it offers a simple Wear OS client.

For maintainers and coding agents, [APPLICATION.md](APPLICATION.md) is the internal authoritative behavior contract. It is not user documentation; this README covers the public project overview, setup, and operations.

## Technologies

- PHP 8.4, Laravel 13, and Fortify
- Inertia.js 3, Vue 3, and TypeScript
- Tailwind CSS 4 and Nuxt UI
- PostgreSQL 18
- Vite, Wayfinder, Pest, PHPStan/Larastan, Pint, ESLint, and Prettier
- Laravel Sail for local development and Laravel Cloud for web deployment
- Native Wear OS app in a separate repository `capylendar-wear`

Telescope is not a runtime component of the application. Queue workers are not needed for notifications.

## Features

- Shared and private events and tasks with priorities, labels, and filtering.
- Collaborative documents and shared tags.
- Chat between users with push notifications to the recipient.
- Web Push for newly created shared events, tasks, and documents, plus morning and evening event summaries.
- Event history with Inertia infinite loading of 20 items.
- Event images, automatic map previews, and native sharing.
- Service-worker based Web Push notifications.
- Wear OS pairing via short-lived code; the watch stores the token encrypted in the Android Keystore.

Todo completion is intentionally optimistic: the card stays on the page until the next refresh, so an accidental tap can be undone.

## Local Setup

```bash
vendor/bin/sail up -d
vendor/bin/sail composer install
vendor/bin/sail npm ci
vendor/bin/sail artisan key:generate
vendor/bin/sail npm run dev
```

Run all PHP, Composer, Artisan, and Node commands in this project via Sail.

## Push Notifications

Generate VAPID keys:

```bash
vendor/bin/sail artisan webpush:vapid
```

Set them in your environment:

```env
VAPID_PUBLIC_KEY=<public-key>
VAPID_PRIVATE_KEY=<private-key>
VAPID_SUBJECT=mailto:your@email.com
NOTIFICATION_WAKE_TOKEN=<random-long-secret>
```

An external cron can call `POST /api/wake?type=morning` and `POST /api/wake?type=evening` with the header `Authorization: Bearer <NOTIFICATION_WAKE_TOKEN>`. Each type is idempotent on the server for a given calendar day. Chat and newly created shared-item notifications are deferred until after the HTTP response using Laravel `defer`, without a queue worker.

## Quality Checks

Full local check:

```bash
vendor/bin/sail composer check
vendor/bin/sail npm run typecheck
vendor/bin/sail npm run lint
vendor/bin/sail npm run format:check
vendor/bin/sail npm run build:ssr
```

GitHub CI also checks synchronized Wayfinder files and runs `composer audit` and `npm audit`. Weekly dependency audits and Dependabot monitor Composer, npm, and GitHub Actions. A release is created only after a successful CI run; first, the `VERSION` file is committed, then the same commit is tagged and a GitHub Release is created.

## Deployment

The web app is deployed on [Laravel Cloud](https://capylendar.laravel.cloud). The production database is used neither during local tests nor audits; the test suite runs on an isolated in-memory SQLite database.

The Wear OS app can be distributed directly as a debug APK via a laptop. Play Store signing or store release automation is not required for this distribution.

Made with ❤️ and 🦦 by Vojtěch Koubek
