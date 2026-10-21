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
| **2. Simple user pages** | Profile settings, two-factor, notifications, transactions, deposit/withdraw history, PV/BV logs, referrals (tables, filters, pagination) | ✅ Done: 8 pages (Steps 9–22); BV log skipped because its page is broken (Step 18) |
| **3. Deposit and withdraw flows** | Deposit methods, manual deposit proof, withdraw request, withdraw preview; `DynamicForm` for the admin-built forms; removal of the duplicate `*1` flows. Automatic gateway pages (Paystack, Stripe…) stay Blade on purpose. | ✅ Done (Steps 24–31) |
| **4. Other user flows** | 4a KYC + support tickets ✅ · 4b investments/projects · 4c network trees · 4d shop/cart/orders · 4e stockist area | 🟡 In progress: 4a, 4b and 4c done (Steps 32–52) |
| *(ongoing)* **Shared component library** | `Pagination`, `EmptyState`, `TextField`, `PasswordInput`, `Modal` and `DynamicForm` are done, plus the `useCopy`, `visitWithQuery`, `useTrans` and `money` helpers. Still to do: `DataTable`, `StatCard`, `Badge`; move the inline `sl-*`/`bk-*` CSS from `layouts/master.blade.php` into a stylesheet imported by Vite. | Grows with each phase |
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

## Part 3b: Phase 2 change log (simple user pages)

### Step 9: Shared building blocks

**New:** `core/resources/js/types/index.d.ts`. Adds the `Paginated<T>` and
`PaginationLink` types: the exact shape of a Laravel `LengthAwarePaginator`
once Inertia sends it to the browser (`data`, `current_page`, `last_page`,
`total`, `links`, and so on).

**New:** `core/resources/js/Components/Pagination.tsx`

- Renders any Laravel paginator with the same Bootstrap markup
  (`.pagination`, `.page-item`, `.page-link`) as Blade's
  `{{ paginateLinks() }}`, so it looks the same.
- Page links are Inertia `<Link preserveScroll>`. Changing page fetches only
  JSON and keeps the scroll position.
- Previous/next use icons instead of the label text, because language files
  may put HTML entities in those labels.
- Hidden when there is only one page.

**New:** `core/resources/js/Components/EmptyState.tsx`. The "nothing here yet"
block. `prefix="txn"` produces the existing `.txn-empty` and
`.txn-empty-icon` classes.

**New:** `core/resources/js/Components/TextField.tsx`. A labelled input with
an optional icon and an inline validation error, using the profile page's
`prof-field` styling and ARIA attributes.

**Pattern used in every controller below:**

- Format money and dates **on the server**, with the existing
  `showAmount()`, `showDateTime()` and `diffForHumans()`, so currency symbols
  and language rules stay identical to Blade.
- Send **only the fields the page displays**, never whole Eloquent models.
  Props are visible in the page source.
- Paginated lists use `->through(fn ($row) => [...])`, which keeps the
  paginator and replaces each row with a small array, and `->withQueryString()`,
  so page links keep the current filters.

### Step 10: Transactions (`/user/transactions`)

**New:** `Pages/User/Transactions.tsx`

- Filter bar: reference search, type (credit/debit) and remark.
  - Submitting does `router.get()` with only the filters that are set, for
    example `?trx_type=%2B`. The page refreshes without a full reload and
    starts again at page 1.
- Rows show green for credit and red for debit, the fee (only if there is
  one), the balance after the transaction, and the relative and full dates.
- Empty state and pagination.

**Changed:** `UserController@transactions` uses `Inertia::render('User/Transactions', ...)`
with:

| Prop | Contents |
|---|---|
| `transactions` | Paginated and pre-formatted |
| `remarks` | `{value, label}` for the dropdown |
| `filters` | The current query values, so the form shows what is filtered |

**Not changed:** `Template::user.transactions` is still used by
`StockistController` and `EpinController` (recharge logs). Those pages stay
on Blade for now.

### Step 11: Notifications (`/user/notifications`)

**New:** `Pages/User/Notifications.tsx`

- Unread dot and highlight.
- Admin "Personal Notice" badge with the sender's name.
- Email/SMS/Push type badge.
- Total count, empty state and pagination.

**Changed:** `UserController@notifications`

- Rows are mapped **before** the "mark all as read" update, so unread items
  are still highlighted on this visit. This matches the Blade behaviour, and
  the test confirms it.
- **Improvement:** the sender is now eager-loaded (`->with('notifyingAdmin')`).
  The Blade page ran one extra database query per notification.
- `strip_tags()` is still applied on the server. React also escapes all text,
  so a notification can never inject HTML.

### Step 12: Two-factor security (`/user/twofactor`)

**New:** `Pages/User/TwoFactor.tsx`

- While 2FA is **off**, the page shows:
  - the QR code and setup key, with a copy button. The copy button uses the
    Clipboard API on HTTPS and falls back to `execCommand('copy')` on HTTP,
    like the old jQuery code did.
  - the help text with a link to the Google Authenticator download.
  - the "Enable" form.
- While 2FA is **on**, only the "Disable" form is shown.
- The code field is cleared after each attempt, and the button is disabled
  while sending.

**Changed:** `UserController@show2faForm`

- Uses `Inertia::render('User/TwoFactor', ...)`.
- The **secret and QR code are only sent while 2FA is off**. Once enabled,
  nothing about the key reaches the browser.
- The form posts to `user.twofactor.enable` / `user.twofactor.disable`. The
  Blade view posted to the identical `...enable1` / `...disable1` copies.

**Visual change:** the old view used the `master2` layout. The React page
uses the normal dashboard layout (`master`), so the page now has the same
sidebar as every other account page.

### Step 13: Profile settings (`/user/profile-setting`)

**New:** `Pages/User/ProfileSetting.tsx`

- Personal info, bank details and address sections, plus the bank-details
  history table.
- Profile photo: choosing a file shows an instant preview. The temporary
  preview URL is released when the photo changes.
- With a photo attached, Inertia sends the form as `multipart/form-data`
  automatically. `ProfileController@submitProfile` is **unchanged**.
- Validation errors show under each field, and the typed values are kept.
- Email stays read-only but is still sent, because `submitProfile` validates
  it. Country stays disabled.

**Changed:** `ProfileController@profile` sends:

- `profile`: the 15 fields the form shows, plus the photo URL.
- `bankHistories`: the bank-details history.
- `submitUrl`.

### Step 14: The admin Language manager now finds React text

**Problem found:** the admin "fetch keywords" button
(`LanguageController@getKeys`) only scanned `.blade.php` files for
`@lang('...')`, so translators could never see text from the React pages.

**Changed:** `core/app/Http/Controllers/Admin/LanguageController.php`. A new
`getReactLangKeys()` method also scans `resources/js/**/*.ts(x)` for
`t('...')` / `t("...")`. It is verified to find all 106 keys, including ones
with apostrophes and `:placeholders`.

**Rule from now on:** always write the English text **directly inside**
`t('...')`.

```tsx
const labels = [t('Weak'), t('Strong')];   // ✅ the scanner finds these
const LABELS = ['Weak', 'Strong']; t(LABELS[i]);   // ❌ invisible to the scanner
```

`ChangePassword.tsx` and `Notifications.tsx` were changed to follow this
rule. Text that comes from the database (transaction details, remarks,
notification subjects) is translated the same way Blade does it, with
`__()` / `t(value)`. It only translates if an admin adds that exact text to
the language file.

### Step 15: Verification for Phase 2

1. `npm run build` (strict TypeScript) passes. Each page is its own small
   file of 2–7 kB.
2. **PHP:** the **real controller methods** were run against a throwaway
   SQLite database with sample users, transactions, notifications and bank
   history. Confirmed:
   - Only the logged-in user's transactions are returned; another user's row
     is excluded.
   - The `trx_type=+` filter works, and the page links keep `?trx_type=%2B`.
   - Amounts are formatted as `₦1,200.50`.
   - Notification HTML is stripped; the unread flag is correct on the page,
     and everything is marked read in the database afterwards.
   - The 2FA secret is only present while 2FA is disabled.
   - Profile and bank history map correctly.
3. **Browser (headless Chromium, real build): 30/30 checks passed**, across:
   - Transactions: rows, credit/debit styling, amounts and fees, the
     pagination click making an XHR request (no full reload) and updating
     the URL, filter query, empty state.
   - Notifications: unread dot, admin badge and sender.
   - Two-factor:
     - QR code and key.
     - The copy button over both HTTP (fallback) and HTTPS (Clipboard API).
     - The enable POST with key, code and CSRF header; the toast; the
       switch to the "disable" view.
     - The disable POST.
   - Profile:
     - Fields pre-filled.
     - Initials avatar, and the photo preview.
     - The multipart POST carries every field plus the file.
     - Inline validation error, with the typed value kept.
4. The Change Password pilot tests still pass (14/14).
5. **Still to do on staging with the real MySQL database:** open each page,
   submit each form once, upload a profile photo, enable and disable 2FA with
   a real authenticator app.

### Rollback (any Phase 2 page)

In the controller method, replace the `return Inertia::render(...)` with the
`Template::...` view named in the comment just above it. The old Blade files
have not been deleted.

### Step 16: Duplicate `*1` pages removed

**Decision (owner):** "if it is a duplicate page, remove it."

**What these pages were.** Users with `section = 2` (sent to `user.land`
after login) had a second copy of several account pages: the `*1` routes,
using the `layouts.master2` layout.

Before removing anything, I checked that they really are duplicates:

- **Same controller logic.** The only differences:
  - `myRefLog1` also filtered on `profile_complete = 1`.
  - `submitProfile1` did not ask for a mobile number.
- **Same menu.** `master2` includes the same sidebar
  (`partials.dashboard`) as `master`; it only adds flashcards.
- **The second menu is unreachable.** `partials/dashboard2.blade.php`,
  which links to the `*1` pages, is only used by `layouts.master1`, which
  only `user/welcome.blade.php` uses, and no route renders that page.

**Removed:**

| Removed duplicate | Old URL now redirects to |
|---|---|
| `show2faForm1`, `create2fa1`, `disable2fa1` + `twofactor1.blade.php` | `/user/twofactor` |
| `transactions1` + `transactions1.blade.php` | `/user/transactions` |
| `profile1`, `submitProfile1` + `profile_setting1.blade.php` | `/user/profile-setting` |
| `changePassword1`, `submitPassword1` + `password1.blade.php` | `/user/change-password` |
| `depositHistory1` + `deposit_history1.blade.php` | `/user/deposit/history` |
| `withdrawLog1` + `withdraw/log1.blade.php` | `/user/withdraw/history` |
| `myRefLog1` + `myRef1.blade.php` | `/user/referrals` |

How the redirects work:

- **The old GET URLs keep their route names** and redirect, so bookmarks
  and the old menu still work. The query string is kept, so
  `?search=TRX9` survives.
- **`deposit/history1` *must* keep working.** The old deposit flow saved it
  as the `success_url` / `failed_url` on deposits already in the database.
  `PaymentController` now sends users to the main page directly.
- **The duplicate POST routes are deleted**, because only the deleted forms
  used them.

**Verified:**

- All 7 old URLs return 302 to the right page, with the query string kept.
- The 3 old POST URLs return 404/405.
- `route:list` loads.

**Not removed yet.** These are also `*1` duplicates, but they are payment or
shop *flows*, not simple pages. They will be checked and removed in the phase
that migrates those flows:

- the deposit flow (`insert1`, `confirm1`, `manual1`)
- the withdraw flow (`withdraw1/now`, `preview1`)
- the shop pages (`orders1`, `products1`, `product1`, `purchase1`)
- the registration pages (`user-data1`, `register1`)

**Small behaviour change:** `section = 2` users now see the main profile
form, which requires a mobile number (9–11 digits).

### Step 17: 🔴 Security fix: PV/BV log `?type=` could wipe tables

**Found while migrating the PV log.** `PlanController@pvData` ran
`PvLog::$scope()`, with `$scope` taken straight from `?type=` in the URL.
PHP then calls *whatever static method is named*.

**Proven on a throwaway SQLite database:** a logged-in user visiting
`/user/pv-log?type=truncate` **emptied the entire `pv_logs` table for every
user** (3 rows → 0). PV history is what binary commissions are calculated
from.

`Admin\ReportController@bvData` had the same flaw for `BvLog`. It is
admin-only, but it is a plain GET link, so an attacker could trigger it by
getting a logged-in admin to load a page containing
`<img src=".../admin/report/bv-log?type=truncate">`.

**Fix (its own commit):** only the real scopes are accepted:

- PV: `leftPV`, `rightPV`, `cutPV`, `paidPV`
- BV: `leftBV`, `rightBV`, `cutBV`, `paidBV`

Anything else is treated as "show all".

**Verified:** the same request now leaves the table intact (4 rows before
and after) and shows the normal "All" view. The other `Model::$scope()`
calls (admin users, deposits, withdrawals) only ever receive hard-coded
strings, so they are safe.

> **Action for you:** deploy this fix as soon as possible, even before the
> rest of the migration. Also check the database backups: if `pv_logs` or
> `bv_logs` were ever unexpectedly emptied, this is a likely cause.

### Step 18: BV log is not migrated (the page is already broken)

`routes/user.php` points `user/bv-log` (`user.bv.log`) to
`PlanController@bvlog`, but **that method does not exist**, so the page
currently fails with a server error. Only the unused `dashboard_old`
sidebar links to it.

I did not invent a page for it. Decide whether users should have a BV log at
all; if not, delete the route.

### Step 19: Shared pieces for the history pages

**New PHP:** `core/app/Support/PaymentPresenter.php`

- `depositStatus()` / `withdrawStatus()`: return the status as data
  (label, colour, "when") instead of the HTML string that
  `Deposit::statusBadge` built.
- `details()`: the form a user filled in for a manual payment. Files become
  the same encrypted download link as before.

**New React:**

- `Components/Modal.tsx`. A Bootstrap-styled dialog driven by React state,
  without jQuery or bootstrap.js:
  - Escape and backdrop click close it. There is a `staticBackdrop` option
    for notices that must be acknowledged.
  - It locks page scrolling while open.
  - Focus moves into the dialog and returns to the button that opened it.
  - It sets `role="dialog"`, `aria-modal` and `aria-labelledby`.
- `lib/clipboard.ts` → `useCopy()`. Copies with the Clipboard API, falls back
  to `execCommand` on HTTP, and shows a tick briefly afterwards. The 2FA page
  now uses it too.
- `lib/search.ts` → `visitWithQuery()`. A search or filter form reloads the
  page data (no full reload), drops empty values and starts at page 1. The
  Transactions page now uses it too.
- A `PaymentDetail` type in `types/index.d.ts`.

**XSS fixed in passing.** The old jQuery pop-ups on the deposit and
withdraw pages built their HTML from the user's submitted form values and
the admin's feedback text without escaping. React escapes everything; this
was tested with `<img onerror>` and `<script>` payloads.

### Step 20: Deposit history (`/user/deposit/history`)

**New:** `Pages/User/DepositHistory.tsx`

- Header with a "Deposit Now" link. The deposit page is still Blade, so this
  is a plain `<a>`.
- Search box.
- Rows: gateway, transaction number, date, amount with charge, conversion,
  status badge.
- Manual deposits get a details pop-up (submitted fields, attachment link,
  admin feedback). Automatic ones get a tick.
- Empty state and pagination.

**Changed:** `UserController@depositHistory` sends pre-formatted rows,
`search` and `depositUrl`.

### Step 21: Withdraw history (`/user/withdraw/history`)

**New:** `Pages/User/WithdrawHistory.tsx`

- Four totals: withdrawn, pending, rejected, number of requests. These were
  database queries inside the old view; they are now in the controller.
- Search, rows, details pop-up and pagination.
- If an admin suspended withdrawals, the **"Withdraw Suspended"** notice
  opens on load. It is static: only "I Understand" or × closes it. The reason
  is shown as text.

**Changed:** `WithdrawController@withdrawLog` sends:

- `withdraws`, `stats`, `search` and `withdrawUrl`
- `blocked` and `blockReason` (the reason only when blocked)

### Step 22: Referrals and PV log

**New:** `Pages/User/Referrals.tsx`

- Stats, referral code, and the referral link with a copy button (shows a
  toast).
- WhatsApp, Telegram, Facebook, X and Email share links, all URL-encoded and
  opened with `rel="noopener"`.
- Member cards with coloured initials, and pagination.
- **Privacy:** `PlanController@myRefLog` now sends only each referral's name,
  username and join date. The Blade view had every column of the referred
  users' records available to it.

**New:** `Pages/User/PvLog.tsx` and `Pages/User/PvLog.css`

- **CSS moved as-is.** The PV page's 340 lines of CSS lived only in
  `pvLog.blade.php`. They are now a stylesheet the page imports, which Vite
  builds into its own file and loads only on this page.
- Stats and tabs (All / Left / Right). The "Cut" card and tab stay hidden,
  as they were commented out in Blade.
  - Tabs are Inertia links (`?type=`), so switching only fetches data.
- Feed rows with leg badges, and pagination that keeps `?type=`.

### Step 23: Verification for Steps 16–22

1. `npm run build` passes.
2. **PHP:** the real controllers were run against fixtures with two users'
   data:
   - Only the user's own deposits and withdrawals are returned; initiated
     ones are hidden, as before.
   - Totals are correct (₦10,000 / ₦3,000 / ₦500 / 3 requests).
   - The search `DEP2` works.
   - Attachments become encrypted download links.
   - Referrals contain no email address.
   - PV `?type=leftPV` filters correctly, and `?type=truncate` is harmless.
3. **Browser: 32/32 new checks**, fed the *real controller output*,
   including XSS payloads:
   - **Deposit pop-up:**
     - It opens with focus inside and locks page scrolling.
     - The payload is shown as text.
     - It shows the attachment link and the admin feedback.
     - Escape and backdrop click close it, and focus returns to the button.
   - **Search:** sends an XHR request (no full reload).
   - **Withdraw:**
     - The suspended notice opens on load and stays open until
       acknowledged.
     - Totals, status badges, pagination and the details pop-up.
   - **Referrals:**
     - Member numbers and avatar colours.
     - The share links are URL-encoded.
     - Copy shows "Copied!" and a toast.
   - **PV log:**
     - The page CSS chunk loads.
     - The stat values, and the row kinds and signs.
     - The tab click fetches `?type=leftPV` and updates the title.
   - **No `alert()` ever ran.**
4. The earlier suites still pass (30/30 Phase 2, 14/14 pilot).
5. The admin keyword scanner now finds 195 React text strings.

### Phase 2 summary

| Page | URL | React page |
|---|---|---|
| Change password (pilot) | `/user/change-password` | `User/ChangePassword` |
| Transactions | `/user/transactions` | `User/Transactions` |
| Notifications | `/user/notifications` | `User/Notifications` |
| Two-factor | `/user/twofactor` | `User/TwoFactor` |
| Profile | `/user/profile-setting` | `User/ProfileSetting` |
| Deposit history | `/user/deposit/history` | `User/DepositHistory` |
| Withdraw history | `/user/withdraw/history` | `User/WithdrawHistory` |
| Referrals | `/user/referrals` | `User/Referrals` |
| PV log | `/user/pv-log` | `User/PvLog` |

**Still to test on staging (real MySQL):** open each page, then:

- open the deposit and withdraw detail pop-ups;
- check the "suspended" notice with a blocked test user;
- copy the referral link;
- switch the PV tabs.

### Known issue (not caused by this work)

`php artisan route:cache` and `config:cache` failed on the original code too.
**This was fixed in Step 24, with your approval.**

---

## Part 3c: Phase 3 change log (deposit and withdraw flows)

### Step 24: Config and route caching fixed (owner approved)

Production should run `php artisan config:cache` and `route:cache` for speed.
Both failed, for two separate reasons that were already there.

1. **`config/app.php`** used `require_once('timezone.php')`. Laravel loads
   the config file more than once, so the second time `$timezone` was
   undefined.
   - **Fix:** `require __DIR__ . '/timezone.php';`.
   - The admin General Settings page still writes `timezone.php` the same
     way.
2. **`routes/admin.php`** gave the same name to two routes in four places.
   Without caching, Laravel silently keeps the later one; caching refuses.
   - `admin.login`, `admin.users.notification.single` and
     `admin.setting.logo.icon`: a GET and a POST on the *same* URL. The
     name now stays on the GET only.
   - `admin.stockist.changeUserStockistStatus`: the name was on both a GET
     without an id and the POST `status/{id}`. Laravel already resolved it
     to the POST, which is the one the stockist form uses, so the name now
     stays on the POST.
   - In every case `route()` produces exactly the same URL as before.

**Verified:** `config:cache` and `route:cache` both succeed, and all route
names resolve to the same URLs as before (checked with `tinker`).

### Step 25: Duplicate `*1` deposit/withdraw flows removed

**Decision (owner):** "if it is a duplicate page, remove it."

The duplicate methods were compared line by line with the main ones. **The
only difference was which `*1` route they redirected to next.** Nothing
outside the duplicates linked to them.

**Removed:**

- `PaymentController`: `depositInsert1`, `depositConfirm1`,
  `manualDepositConfirm1` and `manualDepositUpdate1`.
- `WithdrawController`: `withdrawMoney1`, `withdrawStore1`,
  `withdrawPreview1` and `withdrawSubmit1`.
- Their views: `payment/manual1`, `withdraw/methods1` and
  `withdraw/preview1`.
- The POST routes `deposit/insert1`, `deposit/manual1`, `withdraw1` and
  `withdraw1/preview1`.

**Old GET URLs redirect** to the main flow:

| Old URL | Now goes to |
|---|---|
| `deposit/confirm1` | `deposit/confirm` |
| `deposit/manual1` | `deposit/manual` |
| `withdraw1/now` | `withdraw` |
| `withdraw1/preview1` | `withdraw/preview` |

The deposit in progress is tracked in the session, so a user halfway through
the old flow simply continues in the main one.

### Step 26: New shared pieces

**PHP:** `app/Support/FormPresenter.php`

- `fields($formId)`: the form an admin built (gateway or withdraw method) as
  a plain list for React. Validation is **unchanged** and still on the
  server (`FormProcessor::valueValidation`).
- `safeHtml($html)`: admin-written instructions passed through
  **HTMLPurifier**, which was already installed. Formatting is kept;
  `<script>` and `onerror=` are removed. The Blade pages printed this HTML
  completely raw.

**React:**

- `Components/DynamicForm.tsx`. The React version of `<x-viser-form>`.
  - It supports every field type: text, url, email, date, time, datetime,
    number, textarea, select, checkbox, radio and file.
  - Widths, required marks, instruction tooltips and accepted file types
    match the Blade component.
  - Server errors show under the right field, including errors on checkbox
    arrays (`channels.0`).
  - **KYC can reuse it later.**
- `lib/money.ts`. `depositQuote()` and `withdrawQuote()` are the fee
  calculations from the old jQuery code, written once, with comments
  pointing at the PHP that does the real calculation.
- `lib/clipboard.ts`. The fallback now copies through a temporary text box.
  The bank-details copy buttons have no input to select, so on plain HTTP
  the old fallback would have shown a tick **without copying**. The 2FA and
  Referrals pages were simplified to match.

### Step 27: Deposit page (`/user/deposit`)

**New:** `Pages/User/Deposit.tsx`

- Gateway list. The selection is a real radio button, so it works with the
  keyboard (arrow keys) and with the existing `:has(input:checked)` styling.
- Amount box, quick amounts (₦500 … ₦50,000), and live limit, charge and
  total, plus conversion rows for foreign currencies and the crypto notice.
- "Confirm Deposit" stays disabled outside the gateway's limits. The server
  checks the limits again.
- The "Company Bank Details" pop-up has copy buttons. Those details were
  hard-coded in the view; they are now in one place in
  `PaymentController@deposit`.

**Changed:** `PaymentController@deposit`

- **It sends only the fields the page shows.** The Blade page dumped each
  whole gateway record as JSON into the HTML. The secret keys were hidden by
  the model, so nothing leaked, but now there is nothing to leak.

**Changed:** `PaymentController@depositInsert`

- On success it returns `Inertia::location(route('user.deposit.confirm'))`.
- The confirm step for **automatic gateways stays Blade**: each one loads
  its provider's own JavaScript or redirects off-site. `Inertia::location`
  tells the browser to load that step as a normal page.
- Manual gateways continue to the React page below.

**Small fix:** the "Show All Payment Options" button appeared with exactly 5
gateways, when there was nothing more to show. It now appears only when
there are more than 5.

### Step 28: Manual deposit proof (`/user/deposit/manual`)

**New:** `Pages/User/DepositManual.tsx`

- Amount banner, and the steps (Amount ✓, Gateway ✓, Confirm), with
  `aria-current` on the current step.
- The admin's payment instructions, sanitised.
- The admin-built proof form (`DynamicForm`). An upload goes as multipart
  automatically.

**Changed:** `PaymentController@manualDepositConfirm` sends:

- `amount` and `payAmount`
- `instructions` (sanitised) and `fields`
- the URLs

`manualDepositUpdate` (saving the proof) is **unchanged**.

### Step 29: Withdraw page (`/user/withdraw`)

**New:** `Pages/User/Withdraw.tsx` and `Pages/User/Withdraw.css`

- Balance, payout bank account (with an "Edit" link to the profile) and
  method list.
- Amount box with live fee, "You Receive" and conversion. "Confirm
  Withdrawal" is disabled outside the method's limits.
- "Withdrawal Suspended" notice for blocked users.
  - Its 110 lines of CSS lived only in `withdraw/methods.blade.php` and were
    moved to `Withdraw.css`.
  - **One typo fixed:** a stray `xx` made browsers ignore
    `align-items: center` on the bank "Edit" link.
  - The old view also left a `<div>` unclosed in the blocked case.

**Changed:** `WithdrawController@withdrawMoney` sends:

- `balance`, `bank`, `methods` (only display fields)
- `blocked` and `blockReason`
- the URLs

`withdrawStore` is **unchanged**; it still redirects to the preview, which
is now React.

### Step 30: Withdraw preview (`/user/withdraw/preview`)

**New:** `Pages/User/WithdrawPreview.tsx`

- Summary (requested amount, charge, "you receive", amount in the method's
  currency) and the steps.
- The method's description, sanitised.
- The admin-built details form (`DynamicForm`).
- A Google Authenticator code field when the user has 2FA on. The code is
  cleared after every attempt; the other fields are kept.

**Changed:** `WithdrawController@withdrawPreview`. `submitWithdrawal`, which
locks the row, debits through `WalletService` and blocks double submits, is
**unchanged**.

### Step 31: Verification for Phase 3

1. `npm run build` passes with no warnings (after the CSS typo fix).
2. **The real controllers were run end to end** against a throwaway database:

   | Case | Result |
   |---|---|
   | Deposit below minimum / disabled gateway / no amount | rejected, **no deposit row created** |
   | Paystack ₦10,000 (1.5% + ₦100) | saved: charge ₦250, total ₦10,250; browser sent to the gateway page (409 + location) |
   | Gateway page props | **no secret key** anywhere in the props |
   | Manual deposit: missing fields / bank not in the admin's list | validation errors, nothing saved |
   | Manual deposit: valid | status "pending review", details saved, admin notified, back to history with a toast |
   | Withdraw above balance / below minimum / disabled method | rejected |
   | Withdraw ₦5,000 (1% + ₦50) | charge ₦100; **balance untouched until submit** |
   | Submit | balance ₦20,000 → **₦15,000**, one ledger entry, status pending |
   | **Double submit** | rejected, **balance still ₦15,000** |
   | 2FA user, wrong code | error, balance and status unchanged |
   | 2FA user, correct code | balance ₦15,000 → ₦13,000 |
   | Admin HTML containing `<script>` / `onerror` | stripped; formatting kept |

3. **Browser: 43/43 checks** on the real build, fed the real controller
   output:
   - **Deposit page:** fee and total calculation, limits, quick amounts,
     keyboard selection, the POST body, the full-page hand-off to the
     gateway, the bank pop-up and its copy button.
   - **Manual proof:** sanitised instructions, field errors, success toast.
   - **Every `DynamicForm` field type:** multipart upload, checkbox arrays,
     radio, select, the `channels.0` error.
   - **Withdraw page:** fees, limits, the POST, the switch to the preview,
     the blocked notice with its CSS.
   - **Preview:** 2FA field, code cleared after a wrong attempt.
4. All earlier suites still pass: **119 browser checks in total, 0
   failures.**
5. The admin keyword scanner now finds **280** React text strings.

### Still to test on staging (real MySQL, real gateways)

- A **small real Paystack deposit** from start to finish (the confirm page is
  unchanged Blade, but test the hand-off).
- A **manual bank-transfer deposit** with a real receipt upload, then
  approve it in the admin panel.
- A **withdrawal** with and without 2FA, then approve it in the admin panel.
- A user with **withdrawals suspended**.

### Rollback (any Phase 3 page)

As before: restore the `return view(...)` named in the comment above each
`Inertia::render`.

For `depositInsert`, restore `return to_route('user.deposit.confirm');`.

---

## Part 3d: Phase 4 change log (other user flows)

Phase 4 covers about 50 pages, so it is done in five batches, each tested and
committed on its own:

| Batch | Pages |
|---|---|
| 4a | KYC form, KYC status, guarantor requests; support ticket list, new ticket, ticket view |
| 4b | Investments and projects |
| 4c | Network trees |
| 4d | Shop, cart and orders |
| 4e | Stockist area |

### Step 32: Shared pieces for 4a

- `Components/ConfirmDialog.tsx`. The React `<x-confirmation-modal>` ("Are
  you sure?"), with the same markup, built on `Modal`.
- `Components/AttachmentPicker.tsx`. Up to 5 ticket attachments, each
  removable, with the same limits text. `chosenFiles()` leaves empty pickers
  out of the upload.
- `Components/Lightbox.tsx`. A full-screen image viewer for KYC documents
  (click outside, × or Escape closes it).
- `lib/initials.ts`. "Ada Obi" → "AO", used by the avatars.

### Step 33: KYC form (`/user/kyc-form`)

**New:** `Pages/User/KycForm.tsx`

- Rejection notice.
- The admin's KYC form, rendered with **`DynamicForm` from Phase 3**.
- NIN, the ID card type pills, and the ID image upload with drag-and-drop
  and a preview.
- Guarantor search:
  - It uses the same JSON endpoint (`user.kyc.user.search`), the same 280 ms
    debounce, and the server's rate limit.
  - It is a `combobox`, and results can be chosen with the keyboard.
  - Results are escaped. The old version built them with string HTML, and the
    initials were not escaped.
- **UX fix:** the status of the request already sent ("Guarantor Declined",
  "Awaiting Response") now shows under the pre-selected guarantor. **Blade
  hid it as soon as the page loaded, so users never saw that their guarantor
  had declined.**

**Changed:** `UserController@kycForm` sends:

- the fields and the user's NIN and card type
- whether an ID image is on file
- the nominated guarantor (name and username only) and the request status
- the URLs

**Small hardening:** `kycSubmit` now trims and upper-cases the NIN **before**
checking that it is unique, the same way it is saved. Before, ` taken123 `
passed the check on a case-sensitive column and was then saved as a duplicate
`TAKEN123`. The rest of `kycSubmit` is unchanged.

### Step 34: KYC status (`/user/kyc-data`)

**New:** `Pages/User/KycInfo.tsx`

- Hero for each state (verified, pending, rejected, not submitted), plus the
  rejection reason.
- Submitted details.
- File and ID-card thumbnails, which open in the lightbox, with download
  links (the same encrypted attachment URLs).
- Guarantor status.
- "Resubmit" button, except while pending or verified.

**Changed:** `UserController@kycData`. The state and the rows are now worked
out in the controller (they were a `@php` block in the view).

### Step 35: Guarantor requests (`/user/guarantor-requests`)

**New:** `Pages/User/GuarantorRequests.tsx`. Cards with Accept / Decline.
Both buttons are disabled while a request is being sent.

**Changed:** `UserController@guarantorRequests` sends only the requester's
name and username, never their KYC details. Accept and decline are
**unchanged**; they still only work on pending requests addressed to you.

### Step 36: Support tickets (`/ticket`, `/ticket/new`, `/ticket/view/{n}`)

**New:**

- `Pages/User/Tickets.tsx`: the list, with status and priority badges.
- `Pages/User/TicketCreate.tsx`: subject, priority, message and
  attachments.
- `Pages/User/TicketView.tsx`:
  - The conversation. Staff replies are highlighted, and line breaks are
    kept but shown as text.
  - Attachment links.
  - The reply form.
  - The "Close ticket" button, behind a confirmation.

**Changed:** `app/Http/Controllers/TicketController.php`

- It overrides only the three page methods.
- The `SupportTicketManager` trait is **not changed**. The admin panel uses
  it too and keeps its Blade pages.
- Opening, replying, closing and downloading still go through the trait.

**Stricter ticket access:** the trait also let any logged-in user open a
"guest" ticket (`user_id = 0`) if they knew its 6-digit number. The React
page shows **only your own tickets**.

### Step 37: Verification for 4a

1. `npm run build` passes.
2. **The real controllers were run** against a throwaway database (uploads
   went to a scratch folder):

   | Case | Result |
   |---|---|
   | KYC with nothing filled in | 5 field errors |
   | NIN already used by someone else (also tried as ` taken123 `) / yourself as guarantor / wrong card type / `.exe` as the ID image | rejected, KYC status unchanged |
   | Valid KYC | status pending, NIN saved upper-case, image saved, old rejection cleared, the guarantor request moved to the new guarantor |
   | KYC form or submit while pending | redirected with "under review" |
   | Accept **someone else's** guarantor request | 404 |
   | Change your answer after accepting | 404 |
   | Guarantor page props | no email, phone or KYC data |
   | Open / reply / close **someone else's** ticket | 404 / 404 / 403 |
   | New ticket: missing fields / `.exe` attachment | rejected |
   | New ticket with 2 attachments; reply; close own ticket | all work |

3. **Browser: 33/33 checks**, fed the real controller output:
   - **KYC form:**
     - Guarantor pre-selected, with the "declined" status visible.
     - Search is debounced and shows "No users found" when empty.
     - An XSS name in the results is shown as text.
     - Clicking outside closes the result list.
     - Card type pills and the upload preview.
     - The multipart POST carries all fields, and the NIN error appears.
   - **KYC status:** the pending state, the lightbox opening and closing with
     Escape.
   - **Guarantor requests:** Accept sends the POST.
   - **Tickets:**
     - Attachments are limited to 5, and only chosen files are uploaded.
     - A staff message containing `<script>` is shown as text.
     - Line breaks are kept.
     - Closing asks first: "No" sends nothing, "Yes" closes.
4. All earlier suites still pass: **152 browser checks, 0 failures.** The
   keyword scanner finds **380** React strings.

**To test on staging:**

- Submit KYC with a real ID photo, then approve and reject it in admin.
- Nominate a guarantor and accept it from that user's account.
- Open a ticket with an attachment, reply to it from admin, then close it.

### Step 38: 🔴 Fix: successful share purchases were reported as failed (own commit)

**Found while reading `PlanController@sharesStore`** for the Buy Shares pop-up.

**What happened:**

- After the purchase was committed, the AJAX branch built its reply
  **inside the `try` block**, using `route('plan.details')`.
- That route name does not exist (it is `user.plan.details`), so building
  the reply threw.
- The `catch` then answered **500 "Error completing purchase."**, even though
  the money had already been debited, the investment created and the plan
  units taken.
- The `rollBack()` in the catch did nothing, because the transaction was
  already committed.

**Who was affected:** the standalone `/user/shares` page, which posts with
`fetch` + `X-Requested-With`. Nothing in the menus links to it, but it can be
reached by its address. The portfolio's Buy Shares pop-up posted a normal
form and was not affected. Inertia requests also send `X-Requested-With`, so
the React pop-up would have hit the same bug.

**Proven** on a test database: ₦20,000 → ₦15,000, 1 investment created, and
the reply was the 500 error.

**Fix:**

- The transaction is committed first; the reply is built afterwards with the
  correct route name.
- The reply also no longer calls `->sum(fn)` on a query builder (another
  error in the same code).
- Inertia requests get the normal redirect. Only the old page gets JSON.

**Verified:** the old page's request now gets 200 "Purchased 5 units…".
Every rejection (closed plan, < 5 units, not enough balance) charges nothing.

> **Action:** check the database for `shares_purchase` transactions from
> users who then bought again within minutes. They may have been told their
> first purchase failed.

### Step 39: 🔴 Fix: anyone could download another user's investment receipt (own commit)

**What was wrong:** `PlanController@generatePdf` looked the investment up by
the posted `rivest_id` only. The ids are sequential, so any logged-in user
could download **another user's payment receipt or certificate**. Those show
the owner's name, address, phone and email.

**Proven:** logged in as user 7, the PDF of user 20's investment was
downloaded, and it contained user 20's address.

**Fix:** the lookup is limited to `user_id = auth()->id()`. Another user's id
now gets "Cannot download this receipt".

### Step 40: Scoped page CSS, and a helper script

**The problem.** With Inertia, a page's imported CSS **stays loaded after you
navigate away**. The investment pages' CSS restyles generic classes
(`.card`, `.card-body`, `.badge-light` …), so after visiting the portfolio,
those rules would have applied to every later page.

**New:** `core/scripts/extract-page-css.mjs`

- It moves a Blade view's `<style>` block into `Pages/User/<Name>.css`.
- Every rule is wrapped in `.page-<name> { … }` (native CSS nesting). The
  Vite build flattens it to `.page-<name> .card` for older browsers.
- `@keyframes` and `@font-face` stay at the top level.
- It refuses CSS that contains Blade syntax, and warns about `html`, `body`
  and `:root` rules, which cannot be scoped.

Usage:

```bash
node scripts/extract-page-css.mjs resources/views/templates/basic/user/awards.blade.php \
     resources/js/Pages/User/Awards.css page-awards
```

The page component then wraps its content in `<div className="page-awards">`.

**Verified:**

- All 281 built selectors of the four new stylesheets start with `.page-…`.
- In the browser, a `.card-body` inside the portfolio gets 24px; one outside
  it keeps the layout's normal 0px.

The two `body { background }` rules were dropped; the dashboard layout
overrides them with `!important`. (PvLog.css and Withdraw.css were checked:
all their selectors already use page-specific prefixes.)

### Step 41: Investment portfolio and Buy Shares (`/user/investment/portfolio`)

**New:** `Pages/User/InvestmentPortfolio.tsx` (+ scoped CSS)

- Key figures, the investment cards, the dividend summary per plan, and the
  dividend history with pagination.
- **Buy Shares pop-up:** plan, units (minimum 5, raised when leaving the
  field), live total (`₦…`), balance.
  - It posts to `user.shares.purchase`.
  - On success it closes, and the page reloads its data with the toast.
- **"Start Investing"** (shown when there are no investments) linked to
  `/user/plan`, which fails (see Step 45). It now opens the Buy Shares pop-up.

**Changed:** `PlanController@investmentPortfolio`

- It sends display-ready data.
- **Removed 8 wasted queries:** a weekly-growth figure and a 7-day series
  were computed on every visit but never shown.

### Step 42: Investment details (`/user/investment/{id}/details`)

**New:** `Pages/User/InvestmentDetails.tsx` (+ scoped CSS)

- Details, the dividends for this investment, performance (ROI bar) and
  recent activity.
- **Receipt / certificate:** plain HTML form posts with the CSRF token,
  because the reply is a PDF download, not a page.

**Changed:** `PlanController@investmentDetails`

- **The ROI is guarded against a zero amount.** The Blade view divided by
  `five` and crashed when it was 0.
- "And N more dividends…" now counts correctly. Blade compared the page size
  with itself, so it could never show.

### Step 43: Awards, Repurchase award, ACB

**New:**

- `Pages/User/Awards.tsx` (+ scoped CSS): award cards (locked, earned,
  paid), PV progress per leg, prerequisite chip, dates.
  - **The prerequisite is now eager-loaded:** one query instead of one per
    award.
- `Pages/User/RepurchaseAward.tsx`: PV, wallet with a transfer link,
  progress per tier, "How it works". Its CSS is in the global `main.css`.
- `Pages/User/Acb.tsx` (+ scoped CSS): balance, rates and payment history.
  - **Display fix:** Blade wrote `@{{ $memberUser }}`, which Blade outputs
    literally, so every row said **"from {{ $memberUser }}"**. It now shows
    the member's username.

**Changed:** `myAwards`, `repurchaseAward` and `AcbController@index`. ACB is
still only for ACB members (403 otherwise).

### Step 44: My project (`/user/my-project`)

**New:**

- `Pages/User/MyProject.tsx`:
  - Project cards with the same badge rules as Blade, including a same-tier
    project, which gets no badge.
  - The upgrade pop-up shows the price breakdown, PV gain and sponsor bonus
    estimate (as before), plus a VISA shortfall warning.
- `Components/PageTitleBar.tsx`: the `layouts.breadcrumb` title with the
  Cart button. It will be reused by the shop pages.

**Unchanged:** `ProjectController@upgrade`. All its guards were tested:
insufficient VISA, upgrades switched off, already on that project, same
tier, unknown project. VISA stays untouched in every case.

### Step 45: Pages left on Blade, and broken links found

| Page | Status |
|---|---|
| `/user/plan` (`user.plan.index`) | **Already broken**: the route points to `PlanController@planIndex`, which does not exist. The menu does not link to it; the old portfolio button and the land page did. `plan.blade.php` also uses the missing route `user.plan.reserve`. |
| `/user/shares` (`user.shares`) | Not linked anywhere; buying works through the portfolio pop-up. Its purchase bug is fixed (Step 38). **Decision for you:** remove it as a duplicate of the Buy Shares pop-up, or keep it. |
| `reservation` | Its route is commented out, so it cannot be reached. |

**Route-name scan.** A script checked every `route('…')`, `to_route('…')`
and `urlPath('…')` in `app/` and the views against the real routes. Leaving
out the old `_old` / `dashboard2` files and false positives (`'ipn.' .
$alias`), these names **do not exist**:

- `LoginController:146` → `user.land`. After login, `section = 2` users are
  sent to a route that does not exist.
- `LoanController:242,255` → `user.loans.index`
- `SavingsController:232,242,466` → `user.savings.index`
- `TransferController:98,147` → `user.transfer.index`
- `StockistController:189` → `stockist.dashboard`; `:692` → `stockist.setup`
  (probably meant `user.stockist.…`)
- `Admin/AdminUserController:185,234,247` → `admin.users.index`
- `LeadershipBonusService:324` → `user.leadership`
- `StockistMiddleware:15` → `login`
- `bonus/transfer.blade.php:290` → `user.bonus.purchase-status`
- `blog.blade.php`, `blog_details.blade.php`, `sections/blog.blade.php` →
  `blog.details` (the public blog pages)
- `components/email/button.blade.php` → `order.tracking`
- `admin/frontend/section.blade.php:358` → `admin.frontend.import`

Each of these fails (an error page) when that line runs. The stockist,
transfer and bonus ones will be handled when those pages are migrated
(4d/4e); the rest need a decision about where they should go.

### Step 46: Fix in the shared Modal: typing inside a pop-up lost focus

**Found by the Buy Shares test.** `Modal`'s effect depended on `onClose`.
Parents pass a new function on every render, so **each keystroke in an
input inside a pop-up re-ran the effect and moved focus back to the
dialog**: you could type one character only. The earlier pop-ups had no
text inputs, so it did not show until now.

**Fix:** the latest `onClose` is kept in a ref, and the effect runs only when
the pop-up opens or closes. The same fix was applied to `Lightbox`.

The test now types "3", then "30", and checks the field keeps focus.

### Verification for 4b

- **Real controllers against a test database:**
  - Portfolio totals and per-plan dividends; closed plans are absent from
    the pop-up; no other user's data.
  - Details: ROI 5%, and 0 for the zero-amount case (no crash). Another
    user's investment is not found.
  - Awards: paid, locked at 42%, inactive hidden.
  - Repurchase tiers; ACB rows, and 403 for non-members.
  - Project cards, and all 5 upgrade guards (VISA untouched).
- **Browser: 35/35** on the real build, including the CSS-leak check, the
  pop-up maths and typing, the receipt download as a real file, and the
  upgrade breakdown.
- **All suites: 187 browser checks, 0 failures.**

**To test on staging:**

- Buy shares from the pop-up.
- Download a receipt and a certificate.
- Upgrade a project with enough VISA, then check the sponsor's bonus,
  cash-back and PV. **This is the one money path not run end to end here**,
  because it calls about ten commission helpers.

### Step 47: Duplicate Shares page removed (owner: "remove the duplicates")

`/user/shares` was a second place to buy shares, next to the Buy Shares
pop-up on the investment portfolio. Nothing linked to it.

**Removed:**

- `PlanController@shares`, `shares.blade.php`.
- The JSON reply in `sharesStore`, which only that page used. Failures now
  always redirect back to the portfolio with an error toast.

**Kept:** the purchase route itself (`POST /user/shares`,
`user.shares.purchase`), used by the pop-up.

**Old URL:** `GET /user/shares` redirects to the investment portfolio.

**Re-tested:** valid purchase, closed plan, fewer than 5 units, not enough
balance. The balance always matches.

`/user/plan` is **not** a duplicate (it is a broken page), so it is still
waiting for your decision (Step 45).

### Step 48: Duplicate downline pages removed

`/user/downline` + `POST /user/downline1` (search for a member and show their
tree) did the same as the tree page's "Search username" (`otherTree`), with
the same "is this user in your tree?" check. No menu linked to them.

**Removed:**

- `UserController@downline` and `@downline1`.
- `downline.blade.php` and `downline1.blade.php`.
- `myStages2.blade.php` (no controller rendered it).

**Old URL:** `GET /user/downline` redirects to My Tree.

### Step 49: TreePresenter and the GenealogyTree component

**New PHP:** `app/Support/TreePresenter.php`

- Turns the 15-slot tree (keys `a`–`o`) into data: username, stage, sponsor,
  pairing PV, ranking PV, and a link to their tree.
- **3 batched queries** instead of the old `showSingleUserinTree_new()`
  helper's 3 queries per node. A whole tree page is now about 21 queries,
  roughly 45 fewer.
- Empty positions under an existing member become "slot" links to the
  distributor form, with the parent and side pre-filled (as before). These
  appear on My Tree only, not on the stage trees.
- The helper built its HTML with values that were not escaped; React
  escapes everything.

**New React:**

- `Components/GenealogyTree.tsx`:
  - The 4 levels and the connector lines, with the same markup and CSS as
    the Blade page.
  - The member pop-up: stage, sponsor, pairing PV with bars, ranking PV,
    and "View their tree" (no full reload).
  - The phone auto-scale, so the tree shrinks to fit the screen.
  - Members are real buttons, so they work with the keyboard;
    `GenealogyTree.css` keeps their old look and green hover.
  - **Small fix:** the pop-up avatar's letter was always "U", because it was
    taken from the text " Username: …". The avatar image is shown instead.
- `Components/TreeSearch.tsx`: the "Search username" box.

### Step 50: My Tree, My Stages, a member's tree, Binary List

**New:**

- `Pages/User/MyTree.tsx`: My Tree, and the tree of a downline member.
- `Pages/User/MyStages.tsx`: stage trees, with the stage 1–5 tabs.
- `Pages/User/BinaryList.tsx`: the table on desktop and cards on phones;
  search, "not in your tree", empty state, and pagination.

**Changed:** `PlanController`

- `myTree`, `myTreeStages`, `myTreeStage` and `otherTree` now share
  `renderTree()` / `renderStageTree()`.
- `binaryList` sends display-ready rows.
- **Unchanged:** the "is this member in your downline?" check, and the
  validation on tree search.

**Crash fix:** `myTreeStages` read `->stage_id` from
`returnCurrentMatrixStage()`, which returns `false` for a user without an
active matrix row. **"My Stages" in the sidebar failed for those users.**
It now shows stage 1.

**Not changed, but worth knowing:**

- `binaryList` loads **every user's** stage-1 matrix row into memory on each
  visit, to walk the tree. That is fine now, but it will slow down as the
  network grows. Worth replacing with a stored path or nested-set column
  later.
- `/user/binary-summery` points to a method that does not exist (another
  broken route, like `/user/plan`).

### Step 51: Verification for 4c

- **The real controllers were run on a small test network** (7 → 20, 21;
  20 → 22, plus one outsider):

  | Case | Result |
  |---|---|
  | Tree nodes | correct, with stage, sponsor and PV |
  | Empty slots | correct slot links |
  | Stage pages | no slot links |
  | Another member's tree | yours: shown; the outsider: "User is not in your tree" |
  | Search for an unknown username | validation error |
  | Binary list | levels, legs, plans and slots correct; search and "not in tree" work |
  | "My Stages" with no active matrix | loads instead of crashing |

- **Old URLs:** `/user/downline` → My Tree, `/user/shares` → portfolio;
  `POST /user/downline1` no longer exists. `route:cache` still works.
- **Browser: 20/20 checks** on the real build, with the dashboard layout's
  CSS loaded:
  - The 1/2/4/8 layout and the connectors.
  - Members as buttons that keep the old look and hover.
  - Enter opens the pop-up, with the PV bars.
  - "View their tree" and search without a full reload.
  - The tree **scales to fit a 375px phone** (scale 0.61).
  - Stage tabs, and the binary list on desktop and phone.
- **All suites: 207 browser checks, 0 failures.** The keyword scanner finds
  585 React strings.

### Step 52: Phase 4 status

| Batch | Status |
|---|---|
| 4a KYC and tickets | ✅ |
| 4b investments, awards, ACB, projects | ✅ |
| 4c network trees | ✅ |
| 4d shop, cart and orders | next |
| 4e stockist area | after 4d |

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
