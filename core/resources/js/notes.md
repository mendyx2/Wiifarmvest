# Front-end Migration Notes: Blade → Laravel + Inertia.js + React + TypeScript

This file records every front-end migration change, step by step, in the
order it was made. Add a new entry at the end each time a page or feature is
migrated.

The back-end remediation log is a separate file: `/notes.md` at the
repository root.

---

## Part 1: What Inertia.js is (read this first)

You have never used Inertia, so here is the short version.

**Inertia is not a framework and has no API of its own.** It connects
Laravel to React so you can build a single-page app **without writing a REST
API**.

| Before (Blade) | After (Inertia + React) |
|---|---|
| Controller: `return view('user.password', [...])` | Controller: `return Inertia::render('User/ChangePassword', [...])` |
| A `.blade.php` file renders HTML on the server | A `.tsx` React component renders in the browser |
| jQuery adds behaviour to the page afterwards | React owns the page; state and behaviour live in the component |
| Every link or form causes a full page reload | Links and forms send a small XHR request; only the page component changes |

How a request works:

1. **First visit** (typing the URL or refreshing). Laravel returns a normal
   HTML page: the "root view". That page contains
   `<div id="app" data-page="{...json...}">`. The JSON holds the component
   name and its props. React starts and renders that component.
2. **Every visit after that** (clicking an Inertia `<Link>` or submitting a
   `useForm`). The browser sends the request with an `X-Inertia: true`
   header. Laravel runs **the same route and the same controller**, but
   returns **only the JSON** (`{component, props, url, version}`). React
   swaps the page without a full reload.
3. **Validation, redirects, sessions, auth, middleware and CSRF all work the
   same as before.** `$request->validate()` errors appear automatically in
   `form.errors`, and `return back()` works.

What we keep: all routes, controllers, middleware, models, the money logic,
auth guards and the database. Only the **view layer** (`.blade.php` + jQuery)
gets replaced, one page at a time.

---

## Part 2: Migration strategy

### Guiding principles

1. **Strangler pattern, not a big-bang rewrite.** There are about 440 Blade
   views: 191 user/front-end, 186 admin and 63 shared (emails, errors,
   components). We migrate them one page at a time. Blade and React pages
   live side by side, and the site stays deployable after every step.
2. **React pages render inside the existing dashboard shell.** The Inertia
   root view (`resources/views/inertia/user.blade.php`) *extends the current
   Blade layout* (`layouts.master`). The sidebar, header, styles, toasts,
   Tawk chat and push notifications stay exactly as they are. Users see no
   visual change while pages move to React underneath.
3. **No business logic moves to the front end.** Commissions, ledgers,
   balances and payouts stay in PHP. React only displays data and sends
   forms.
4. **Keep the old Blade view until the React page is verified in
   production.** Rolling back means changing one line in the controller.
5. **One translation source.** React reads the same
   `core/resources/lang/{locale}.json` files that `@lang()` uses. The admin
   Language manager translates both.
6. **TypeScript `strict` mode everywhere.** `npm run build` refuses to build
   if there are type errors.

### Roadmap

| Phase | Scope | Status |
|---|---|---|
| **0. Foundation** | Vite, React 19, TypeScript, Inertia (server and client), root view inside the Blade layout, shared props, i18n bridge, CSRF, flash toasts, Railway build | ✅ Done |
| **1. Pilot page** | Change Password (`/user/change-password`) | ✅ Done |
| **2. Simple user pages** | Profile settings, two-factor, notifications, transactions, deposit/withdraw history, PV/BV logs, referrals (tables, filters, pagination) | Next |
| **3. Shared component library** | `DataTable`, `Pagination`, `Modal`, `FormField`, `Select`, `FileUpload`, `StatCard`, `Badge`, `EmptyState`; move the inline `sl-*`/`bk-*` CSS from `layouts/master.blade.php` into a stylesheet imported by Vite | Alongside phase 2 |
| **4. Complex user flows** | Deposit and payment gateways (external redirects use `Inertia::location()`), withdraw, investments/projects, shop/cart/orders, stockist area, KYC, support tickets, genealogy and binary trees | Later |
| **5. Layout to React** | Port the sidebar/header (`partials/dashboard*.blade.php`) to `Layouts/DashboardLayout.tsx` as a persistent layout; switch the root view to a bare HTML shell. After this, user-area navigation becomes fully SPA. | Once most user pages are in React |
| **6. Auth and public pages** | Login, register, password reset and authorization; home, blog and FAQ need SEO, so either keep them on Blade or turn on Inertia SSR | Later |
| **7. Admin panel** | 186 views. Use a separate root view (`inertia.admin`) and `AdminLayout`, applying the same recipe | Last |
| **8. Clean-up** | Delete the migrated Blade views and the jQuery plugins nothing uses any more; add Ziggy for typed `route()` in TypeScript | Final |

Order within each phase: low-risk, read-only pages first; pages that move
money last, each with manual QA on staging.

---

## Part 3: Change log (step by step)

### Step 1: JavaScript tooling (Vite + React + TypeScript)

**New files**

- `core/package.json`: JavaScript dependencies and scripts.
  - Runtime: `react@19`, `react-dom@19`, `@inertiajs/react@2`.
  - Build: `vite@6`, `laravel-vite-plugin@1`, `@vitejs/plugin-react@4`,
    `typescript@5`, plus the `@types/*` packages.
  - Scripts:
    - `npm run dev`: dev server with hot reload.
    - `npm run build`: type-check, then production build.
    - `npm run typecheck`: type-check only.
- `core/package-lock.json`: exact dependency versions, so every machine and
  Railway install the same thing (`npm ci`).
- `core/vite.config.ts`: the Vite build configuration.
  - Entry point: `resources/js/app.tsx`.
  - **Important, specific to this project:** the web root is the
    *repository root* (where `index.php` and `server.php` live), not
    `core/public`. Vite therefore writes the build to **`<repo>/build`**
    (`publicDirectory: '..'`), which the web server serves as
    `/build/...`.
  - The dev-server "hot" file goes to `core/public/hot`, which is where
    Laravel looks for it.
  - `@/` is an alias for `core/resources/js/`, so imports look like
    `import X from '@/Components/X'`.
- `core/tsconfig.json`: TypeScript in `strict` mode, the same `@/*` alias,
  and JSX for React.

**Why:** Vite is Laravel's standard asset bundler. It compiles TypeScript and
JSX into plain JavaScript that browsers can run.

### Step 2: Inertia on the Laravel side

**Changed:** `core/composer.json` and `composer.lock` now require
`inertiajs/inertia-laravel ^2.0` (v2.0.28 installed).

**New:** `core/app/Http/Middleware/HandleInertiaRequests.php`

- `$rootView = 'inertia.user'`: the Blade file used for the first, full
  page load.
- `version()`: a hash of `<repo>/build/manifest.json`. After each deploy
  the hash changes, and Inertia forces the browser to do one full reload, so
  nobody runs old JavaScript. This is verified: a stale version returns HTTP
  409.
- `share()`: props that **every** React page receives:

  | Prop | Contents |
  |---|---|
  | `auth.user` | `id`, `username`, `fullname` only. Shared props are embedded in the HTML, so never add balances, emails or other private data here. |
  | `siteName` | `gs('site_name')` |
  | `csrfToken` | See Step 4 |
  | `locale` | Current language |
  | `translations` | Contents of `lang/{locale}.json` (an object) |
  | `notify` | Messages flashed with `->withNotify()` |
  | `errors` | Validation errors (added by Inertia itself) |

**Changed:** `core/bootstrap/app.php`. `HandleInertiaRequests` is appended to
the `web` middleware group, after `ActiveTemplateMiddleware`, because the
root view needs `$activeTemplate`. It does nothing to normal Blade
responses.

### Step 3: Make Laravel's `@vite` read the build from `<repo>/build`

**New:** `core/app/Support/Vite.php` extends Laravel's `Vite` class and
overrides only `manifestPath()`, so the manifest is read from
`<repo>/build/manifest.json` instead of `core/public/build`.

**Changed:** `core/app/Providers/AppServiceProvider.php` binds
`Illuminate\Foundation\Vite` to that class, so `@vite(...)` uses it.

**Verified:** `@vite('resources/js/app.tsx')` outputs
`<script type="module" src="http://localhost/build/assets/app-xxxx.js">`.

**Why not change `public_path()` instead?** Existing code
(`UserController`, `StockistController`, `AdMediaService`) depends on what
`public_path()` currently returns. Changing it globally could break
attachments and uploads.

### Step 4: Root view inside the existing dashboard layout

**New:** `core/resources/views/inertia/user.blade.php`

```blade
@extends($activeTemplate . 'layouts.master')
@push('style')  @viteReactRefresh  @vite('resources/js/app.tsx')  @endpush
@section('content')  @inertia  @endsection
```

The whole existing chrome (sidebar, header, CSS, toasts, chat widget) still
comes from Blade. React renders only the page body, where `@yield('content')`
used to be.

**Changed:** `core/resources/views/templates/basic/layouts/app.blade.php`,
line 6: `<title>` became `<title inertia>`. Inertia only replaces a title
that carries this attribute; without it, React's `<Head title>` added a
second title. Blade pages are not affected.

### Step 5: React entry point and shared helpers

**New:** `core/resources/js/app.tsx`

- `createInertiaApp`: finds the page component in `resources/js/Pages/`.
  Each page is code-split, so it is downloaded only when someone visits it.
- **CSRF.** Laravel's usual Inertia set-up relies on the encrypted
  `XSRF-TOKEN` cookie, but this app's `web` group has no `EncryptCookies`
  middleware, so that route does not work here. Instead, every Inertia
  request sends an `X-CSRF-TOKEN` header taken from the `csrfToken` shared
  prop. This is verified in the browser test.
- **Flash toasts.** After every Inertia visit, `notify` messages are shown
  with the **same popup** the Blade pages use (`window.notify` from
  `partials/notify.blade.php`). The first full page load is already handled
  by the Blade partial, so messages never show twice.
- **Page titles** use the same format as Blade: `Site Name - Page Title`.
- **Progress bar:** the green bar at the top of the page while a request is
  running.

**New:** `core/resources/js/types/index.d.ts`. TypeScript types for the
shared props, registered with Inertia (`InertiaConfig.sharedPageProps`), so
`usePage().props.auth.user` is fully typed. `PageProps<{...}>` adds the
props of a single page.

**New:** `core/resources/js/lib/i18n.ts`. The `useTrans()` hook is the React
version of `__()`/`@lang()`:

```tsx
const t = useTrans();
t('Change Password');                 // translated, or the English key as fallback
t('Welcome :name', { name: 'Ada' });  // same :placeholder syntax as Laravel
```

Wrap **every** user-facing string in `t()`. That is what makes the platform
translatable.

**New:** `core/resources/js/lib/notify.ts`. `showNotifications()` passes
messages to the legacy toast.

### Step 6: Pilot page, Change Password

**New:** `core/resources/js/Components/PasswordInput.tsx`. A reusable
password field with a show/hide toggle, an inline error and ARIA
attributes, styled with the existing `sl-*` classes.

**New:** `core/resources/js/Pages/User/ChangePassword.tsx`. Replaces
`templates/basic/user/password.blade.php` plus its jQuery:

- `useForm()` handles the field values, `processing` and `errors`.
- Strength meter and "passwords match" hint. These were jQuery before; now
  they are computed from React state.
- **New:** when the admin turns on "secure password", a live checklist
  shows the server's rules (mixed case, number, symbol). This replaces the
  jQuery `secure_password.js` popup.
- The button is disabled while the request runs, so a double click cannot
  submit twice.
- Password fields are cleared after every request.
- All text goes through `t()`.

**Changed:** `core/app/Http/Controllers/User/ProfileController.php`

```php
// before
return view('Template::user.password', compact('pageTitle'));
// after
return Inertia::render('User/ChangePassword', [
    'securePassword' => (bool) gs('secure_password'),
    'submitUrl'      => route('user.change.password'),
])->withViewData(compact('pageTitle'));
```

`submitPassword()` is **unchanged**. Its `$request->validate()` and
`back()->withNotify()` work with Inertia as they are.

`withViewData` passes `$pageTitle` to the Blade layout for the first load.

**Not migrated yet:** the duplicate `changePassword1` route and view
(`password1.blade.php`, using `master1`).

**Rollback:** put back the one `return view(...)` line in
`changePassword()`. The Blade file is still there.

### Step 7: Deployment (Railway / Nixpacks) and Git

**Changed:** `nixpacks.toml`

- Adds `nodejs_22`.
- Install phase runs `npm ci`.
- New build phase runs `npm run build`, which writes `<repo>/build`.

**Changed:** `.gitignore`. `/build` and `/core/public/hot` are generated
files and are not committed.

> ⚠️ **Hosting without Node (for example cPanel shared hosting):** run
> `cd core && npm ci && npm run build` on your own machine, then upload the
> `build/` folder next to `index.php`. Without it, migrated pages fail with
> "Vite manifest not found".

### Step 8: Verification performed

1. **TypeScript:** `npm run build` (strict type-check, then Vite build)
   passes.
2. **PHP wiring:** a script booted the real app against a throwaway SQLite
   database and confirmed:
   - `@vite` emits `/build/assets/...` URLs.
   - An `X-Inertia` request returns JSON with the component, the page props
     and all shared props, including a flashed `notify` message.
   - A stale asset version returns 409.
3. **Browser (headless Chromium) on the real build:** 14/14 checks passed:
   - The heading and `<title>` are translated from the shared translations.
   - The strength meter shows the translated label.
   - The secure-password checklist works, as do the match/mismatch hints
     and the show/hide toggle.
   - The POST carries `X-CSRF-TOKEN` and `X-Inertia` headers and the three
     fields.
   - The server's validation error appears inline and the fields are
     cleared.
   - A success flash shows through the legacy toast, and the inline error
     disappears.
4. **Not tested here:** a full end-to-end run against the real MySQL
   database. No database dump with the core tables is available in this
   environment. **Test `/user/change-password` on staging before going to
   production.**

---

## Part 4: Working on the front end

### Run locally

```bash
cd core
composer install
npm install
npm run dev          # keep running: hot reload for .tsx files
# in another terminal, from the repository root:
php -S localhost:8000 server.php
```

Open `http://localhost:8000/user/change-password`. Edits to `.tsx` files
appear instantly.

### Recipe: migrating one Blade page to React

1. **Pick the page.** Find its route in `routes/user.php` and its
   controller method.
2. **Create `resources/js/Pages/<Area>/<Name>.tsx`.** Copy the HTML from
   the Blade file and convert it:
   - `class=` → `className=`, `for=` → `htmlFor=`, and close every tag
     (`<br />`, `<input />`).
   - `@lang('Text')` or `{{ __('Text') }}` → `{t('Text')}`.
   - `@if` → `{cond && (...)}`; `@foreach` → `{items.map((x) => (...))}`,
     with a `key`.
   - `route('x')`: pass the URL from the controller as a prop. Ziggy comes
     in phase 8.
   - jQuery → React state (`useState`) or `useForm`.
   - `<form method="post">` → `useForm` + `form.post(url)`. No `@csrf`
     needed.
   - Links to **other React pages** → `<Link href>`. Links to **Blade
     pages** must stay plain `<a href>`: a Blade page is not an Inertia
     response.
3. **Type the props:** `type Props = PageProps<{ ... }>`.
4. **Controller:** replace `view(...)` with
   `Inertia::render('<Area>/<Name>', [...props])->withViewData(compact('pageTitle'))`.
   - Only pass what the page shows. Map models to arrays or API resources,
     never whole `User` models: everything in props is visible in the page
     source.
   - Paginated lists: pass `$paginator` and build a shared `Pagination`
     component (phase 3).
   - External gateway redirects: `return Inertia::location($url);`
5. **Keep the old Blade file.** Run `npm run build`, test, add an entry to
   this notes file, and commit.

### Known limitations of the current hybrid set-up

- **The sidebar is still Blade.** Moving between two React pages does not
  update the sidebar's "active" highlight until a full reload. This goes
  away in phase 5.
- **Every Inertia response includes the whole translation file.** That is
  fine while it is small. If it grows large, cache it per asset version or
  share only the keys a page needs.
- **Session expiry (HTTP 419)** shows Laravel's error page inside an
  Inertia modal. Phase 3 adds a handler that reloads the page with a "session
  expired" toast.
- **Hard-coded strings in Blade**, about 210 of them (see the earlier audit),
  are fixed as each page is migrated, because every React string goes
  through `t()`.
