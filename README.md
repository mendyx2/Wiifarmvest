# Wiifarmvest

A Laravel 11 application (member MLM platform, shop, stockists, savings and
loans) with a React 19 + TypeScript front end through Inertia.js.

## Layout

```
/                       the web root (the hosts serve from here)
├── index.php           front controller: hands every request to Laravel in core/
├── server.php          router for PHP's built-in server (Procfile, nixpacks)
├── api/index.php       entry point on Vercel (vercel.json); same file rules as server.php
├── assets/             public files: theme CSS/JS/images, uploads that are public
├── build/              the compiled React app (npm run build; not in git)
├── docs/               internal documents: never deployed or served
│   ├── frontend-notes.md       step-by-step log of the front-end migration and later features
│   ├── remediation-notes.md    log of the back-end/financial remediation
│   ├── remediation-action-plan.md
│   └── audit/                  the executive audit pack (docx, html, pdf)
├── vercel.json, composer.vercel.json, .vercelignore   Vercel (demo hosting)
├── nixpacks.toml, Procfile                            container hosting
└── core/               the Laravel application
    ├── app/
    │   ├── Http/Controllers/   Admin/, User/ (User/Stockist/, User/Auth/), Gateway/<provider>/, Shop/
    │   ├── Services/           business logic (wallet, bonuses, matrix, Usdt/ ...)
    │   ├── Support/            presenters and helpers that shape data for the React pages
    │   ├── Models/, Jobs/, Console/Commands/, Mail/, Notify/, Lib/, Traits/
    │   └── Http/Helpers/helpers.php   global helper functions
    ├── config/          includes admin_access.php (admin permissions) and usdt.php
    ├── database/
    │   ├── migrations/  schema changes (php artisan migrate)
    │   ├── seeders/
    │   └── sql/         the same changes as plain MySQL scripts, for servers without artisan
    ├── resources/
    │   ├── js/          the React app: Pages/ (Admin, User, Auth, Shop, Public),
    │   │                Layouts/, Components/, lib/, types/
    │   ├── css/         dashboard.css (member area)
    │   └── views/       Blade: the two Inertia root views, public site, e-mails, PDFs, gateway pages
    ├── routes/          web.php, user.php, admin.php, ipn.php (payment callbacks), console.php (schedule)
    ├── storage/         logs, caches, private uploads (storage/app/private): not in git
    └── tests/           PHPUnit tests
```

Members' documents (KYC files, payment proofs, ticket attachments) are kept
in `core/storage/app/private/uploads` and only served through signed-in
routes (`App\Support\PrivateFiles`), never as public files.

## Working on it

```bash
cd core
composer install
npm ci
npm run dev          # Vite dev server for the React pages
npm run build        # production build into /build
npm run typecheck    # TypeScript check
php vendor/bin/phpunit  # tests
php artisan migrate
```

The server must run `php artisan schedule:run` every minute: payments,
awards, matching and leadership bonuses, the quarterly Global Share Pool, the
USDT scan and backups all run from it (`core/routes/console.php`).

## Where to read more

- `docs/frontend-notes.md`: Part 1 explains Inertia, Part 4 how to work on
  the front end; every change since the migration started is a numbered
  step, newest last.
- `docs/remediation-notes.md`: the compensation-plan and ledger work.
