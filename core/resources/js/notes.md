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
2. **React pages look exactly like the Blade ones.** Until Phase 5 the
   Inertia root view extended the Blade dashboard layout. Since Step 78 the
   sidebar and header are a React layout with the same HTML and CSS, and
   the root view is a bare HTML page. Users see no visual change while pages
   move to React underneath.
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
| **4. Other user flows** | 4a KYC + support tickets ✅ · 4b investments/projects ✅ · 4c network trees ✅ · 4d shop/cart/orders ✅ · 4e stockist area ✅ | ✅ Done (Steps 32–74); a few member pages remain, see Step 74 |
| *(ongoing)* **Shared component library** | `Pagination`, `EmptyState`, `TextField`, `PasswordInput`, `Modal` and `DynamicForm` are done, plus the `useCopy`, `visitWithQuery`, `useTrans` and `money` helpers. Still to do: `DataTable`, `StatCard`, `Badge`; move the inline `sl-*`/`bk-*` CSS from `layouts/master.blade.php` into a stylesheet imported by Vite. | Grows with each phase |
| **5. Layout to React** | 5a sidebar/header as the persistent `Layouts/DashboardLayout.tsx`, bare root view ✅ (Steps 78–79) · 5b dashboard ✅ (Steps 80–81) · 5c remaining member pages ✅ (Steps 82–85) · 5d auth pages ✅ (Steps 86–87) | ✅ Done |
| **6. Public pages** | Login, register, password reset and authorization moved in 5d. 6a public layout + Terms page ✅ (Step 89) · 6b public shop ✅ (Steps 90–92). Home, blog, FAQ, contact and policy pages stay as they are: the site owner switched them off (they redirect to login). Link previews use server-side meta tags, no SSR (Step 92) | ✅ Done |
| **7. Admin panel** | 185 views. 7a frame: root view `inertia.admin`, `Layouts/AdminLayout.tsx` (sidebar, top bar, search), Profile and Notifications ✅ (Steps 93–94) · 7b reports and logs ✅ (Steps 95–96) · 7c users ✅ (Steps 97–99) · 7d shop, stockists, affiliate ✅ (Steps 100–102) · 7e-1 deposits and withdrawals ✅ (Step 103) · 7e-2 pins and Stage Twice ✅ (Step 104) · 7e-3 savings and loans ✅ (Step 105) · 7f-1 site settings ✅ (Step 107) · 7f-2 notifications and languages ✅ (Step 108) · 7f-3 gateways, withdraw methods, KYC form ✅ (Step 109) · 7f-4a extensions, cron, system ✅ (Step 110) · 7f-4b frontend content, pages, SEO ✅ (Step 111) · 7f-5a admins, roles, permissions ✅ (Step 112) · 7f-5b tickets, notices, flashcards ✅ (Step 113). Next: 7f-6 MLM pages and dashboard · 7g admin login pages | In progress |
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
| `/user/plan` (`user.plan.index`) | ✅ **Fixed in Step 60** (now redirects to the portfolio). Was: the route pointed to `PlanController@planIndex`, which does not exist. The menu does not link to it; the old portfolio button and the land page did. `plan.blade.php` also uses the missing route `user.plan.reserve`. |
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

### Step 53: Two money bugs in the cart and checkout (own commit, first)

Found while mapping the shop, and fixed **before** touching the pages, so
the fix can be reviewed on its own.

1. **Anyone could change or empty anyone's cart.** The cart routes take a
   cart-item id from the URL (`PUT /user/cart/update/201`,
   `DELETE /user/cart/remove/201`) and the controller never checked whose
   cart that item was in. `CartController::authorizeItem()` now answers
   **404** unless the item is in the signed-in member's cart.
2. **A double click on "Pay Now" could charge twice for one cart.** The
   checkout read the cart *before* locking the user row. The second request
   waited for the lock, then paid again with the cart it had already read,
   although the first request had emptied it. The cart is now read and
   locked **inside** the transaction, after the user lock, and the total is
   recomputed there.

Also:

- "Add to cart" now refuses inactive products, and a state price that
  belongs to a different product.
- Errors are written to the log (`report($e)`) instead of showing the
  exception text to the member.

**Checked:** on the test data, user 7 got a 404 for user 20's cart item.
Two pays in a row gave **one order and one ₦9,000 deduction**
(₦30,000 → ₦21,000).

### Step 54: Duplicate shop pages removed

Same rule as before: **the duplicate goes, its old URL redirects to the
page that stays.**

| Old URL | Now goes to |
|---|---|
| `/products`, `/products/{cat}` (SiteController) | `/user/products`, `/user/products/{cat}` |
| `/product/{id}/{slug}` (SiteController) | `/user/product/{id}/{slug}` |
| `/products1…`, `/product1…` (both controllers) | the same two pages |
| `/user/orders1` | `/user/orders` |
| `/user/pp/products` (ProductController) | `/user/products` |

The two product pages in `SiteController` and `UserController` were
copies of each other. `SiteController` was also behind `auth`, so it was
never really a public page. The sidebar, dashboard and home-page product
section now link straight to `user.products` / `user.product.details`;
the redirects are only there for old bookmarks.

**Removed:**

- `ProductController` (a third product list, with its own search, never
  linked from any menu).
- `UserController::products1 / productDetails1 / orders1 / purchase1`, and
  the unrouted `UserController::orders`.
- **9 Blade views:**
  - `products1`, `product_detail1`
  - `user/orders1`, `user/orders/checkout1`
  - `user/cart/index1`, `user/cart/index2`
  - `user/products/index`, `user/products/products_grid`
- **POST routes** that nothing uses any more: `purchase1`,
  `pp/products/search`, `pp/products/{p}/price/{s}`, `products/search`.
  The last one was the state-price lookup; Step 55 explains why it is no
  longer needed.

**Kept:**

- `user/orders.blade.php`: `StockistController` still uses it (4e).
- `POST /user/purchase` (direct buy from the Money Box). It is a carefully
  written endpoint, but no page calls it any more: its only button was
  commented out in the old product page. **Decide later whether to delete
  it.**

### Step 55: The six shop pages in React

| Page | URL | React file |
|---|---|---|
| Products | `/user/products/{cat?}` | `Pages/User/Shop/Products.tsx` |
| Product detail | `/user/product/{id}/{slug}` | `Pages/User/Shop/ProductDetail.tsx` |
| Cart | `/user/cart` | `Pages/User/Shop/Cart.tsx` |
| Checkout | `/user/checkout` | `Pages/User/Shop/Checkout.tsx` |
| My orders | `/user/orders` | `Pages/User/Shop/Orders.tsx` |
| Order details | `/user/orders/{id}` | `Pages/User/Shop/OrderShow.tsx` |

**Shared pieces (`Components/Shop/`):**

- `ProductPrice`: the React twin of `<x-product.price>`. It shows "₦4,000 NGN"
  or "₦4,000–₦4,200 NGN".
- `Sku`: the twin of `<x-product.sku>`.
- `ProductCard`: one product tile, used on the list and for related products.
- `CopyNotice`: the "code copied" dialog.
- `types.ts`.

**On the server:** `App\Support\ShopPresenter` formats prices, exactly like
the Blade component (currency from General Settings, ".00" dropped on whole
amounts). It also builds product tiles and works out an order's status.

**How the JSON endpoints became Inertia requests (new idea).** The old
pages used jQuery `$.ajax` and got JSON back (`{success:false,
message:…}`). Now the browser sends a normal Inertia request and the
server answers like a form post:

| Action | Server answers | React does |
|---|---|---|
| Add to cart, success | `back()` | `onSuccess` → "Product added" dialog and updates the cart badge |
| Add to cart, refused (wrong state, product not available) | `back()->withErrors(['cart' => '…'])` | `onError` → the warning dialog with that text |
| Change quantity / remove | `back()` (+ a toast on remove) | the page reloads its props: totals are always the server's |
| Pay | redirect to the new order + success toast, or back + error toast | — |

Because the server always sends fresh props, the old
`location.reload()` after every change is gone.

**Smaller changes on each page:**

- **Product detail:**
  - All state prices come with the page, so choosing a state switches the
    price **instantly, with no server call**.
  - The owl-carousel became a main photo with thumbnails; clicking the photo
    opens it full screen.
  - The admin-written description goes through HTMLPurifier first. The old
    page printed it raw.
  - Choosing **no state** now says "Please select your state first." Before,
    it failed silently.
  - The broken Instagram "share" link was dropped (Instagram has no share
    URL).
- **Product header badge:** the cart count lives in the Blade header,
  outside React, so the page updates `#cart-count` itself after adding.
- **Cart:**
  - Quantity is capped at 1–99 on both sides.
  - Typed quantities are sent when you leave the box or press Enter, not on
    every keystroke.
  - Removing uses the React `ConfirmDialog` instead of the browser's
    `confirm()`.
- **Checkout:** the button is disabled and the "Processing Payment" dialog is
  shown while the request runs. Together with the server lock from Step 53,
  one click gives one order.
- **My orders:**
  - Status, sort and search are now **done by the server** and kept in the
    URL (`?status=pending&sort=oldest&search=INV`). The old page only
    filtered and sorted the 10 orders on screen.
  - The search waits until you stop typing.
  - The counters (total/pending/redeemed/cancelled) now count **all** your
    orders, not only the current page.

### Step 56: Wrong data the old order pages showed

1. **Order status words.** What the code really stores:
   - 0 = pending
   - 1 = redeemed (set by the stockist at redemption)
   - 2 = cancelled (set by the admin)

   The old pages used different numbers:
   - The list called **3** "Cancelled", so cancelled orders were counted
     nowhere.
   - The detail page called **1** "Paid", so redeemed orders showed as
     "Paid".

   `ShopPresenter::orderStatus()` is now the one place that decides, and
   both pages use it. An order whose invoice has a redemption date counts as
   redeemed too.
2. **Total ₦0 on the order page.** Cart orders keep their total on the
   invoice; `orders.total_amount` stays empty. The "Total" row read the
   empty column. It now uses the invoice total, the same number shown under
   "Subtotal".
3. **Made-up time in the timeline.** "Payment Confirmed" was shown as
   *order time + 5 minutes*. Wallet payment happens in the same request as
   the order, so it now shows the real (same) time.
4. **The cart total could disagree with its lines.** The cart and checkout
   pages showed the stored `carts.total_amount`. They now add up the lines
   they show, with the same sum as `Cart::updateTotal()`. Payment always
   recomputed it, so nobody was charged a wrong amount.

### Step 57: Verification for 4d

- **The real controllers were run on test data:**
  - 3 products, 2 states, 1 hidden product.
  - Two members with carts.
  - Older orders: one redeemed, one cancelled, one pending, plus another
    member's order.

  | Case | Result |
  |---|---|
  | Product list | hidden product not listed; price range + "depends on your state" only where state prices differ; category filter |
  | Product detail | 2 photos; `<script>` removed from the description; specs and tags; related products; unknown/hidden product → 404 |
  | Add to cart | no state → "Please select your state first."; Abuja price in a Lagos cart → "single state"; another product's state price → "not available"; hidden product → "not available"; valid → added |
  | Quantity / remove | 3 and 0 (rejected); another member's item → 404; remove → toast |
  | Checkout | short wallet → disabled + shortfall; pay → redirect to the new order; second pay → "Your cart is empty", wallet charged once (30,000 → 12,000) |
  | Empty cart at checkout | sent back to the cart |
  | My orders | counts 4/2/1/1; each filter, both sorts and search return the right orders; a bad `?status=` falls back to "all"; the other member's order is never listed |
  | Order details | your order shown; the other member's order → 403 |

- **Old URLs:** all 9 redirects checked. `purchase1` and
  `pp/products/search` no longer exist. `route:cache` still works. The
  route-name scan still shows the same 60 old broken names, and no new ones.
- **Browser: 46/46 checks** on the real build. They cover:
  - the price range and instant state price
  - the add-to-cart request body and the cart badge update
  - the server-refusal dialog
  - + / typed quantity capped at 99 / confirm-then-delete
  - the processing dialog and **one POST on a double click**
  - copy codes
  - status filter in the URL
  - search sent once after typing stops
  - **no sideways scroll at 375px** on all six pages
- **All suites: 253 browser checks, 0 failures.** The keyword scanner finds
  710 React strings.

### Step 58: Phase 4 status

| Batch | Status |
|---|---|
| 4a KYC and tickets | ✅ |
| 4b investments, awards, ACB, projects | ✅ |
| 4c network trees | ✅ |
| 4d shop, cart and orders | ✅ |
| 4e stockist area | next |

**Noticed for 4e (not changed yet):** `StockistController` line ~980 sets
an order's status to the text `'redeemed'`, but everywhere else the status
is a number (1 = redeemed). It will be fixed with the stockist pages.


### Step 59: "Buy directly from the Money Box" removed

`POST /user/purchase` (`UserController::purchase`) let a member buy a
product with their **Money Box** balance. Checking it against how the shop
works now:

| | Cart checkout (Step 55) | Money Box direct buy |
|---|---|---|
| Wallet | Re-purchase wallet (the cart page says "Products are purchased using your Re-purchase wallet only") | Money Box |
| Price | the price for your state | the product's base price, **ignoring state prices** |
| Invoice code to collect the goods at a stockist | yes | **no**: the order has no invoice, so it can never be redeemed |
| Used by a page | yes | **no**: its only button was commented out |

So it was not needed, and it would have taken money for goods the member
could not collect. **Removed:** the route and the method (and the two
imports only it used). The `ORDER_PLACED` email template stays, because
`StockistController` still sends it.

### Step 60: `/user/plan` fixed (it was a duplicate)

The route pointed to `PlanController@planIndex`. That method **never
existed**: it is not in the git history either. The views it would have
shown (`plan.blade.php`, `plan_new1.blade.php`, `investment.blade.php`)
had four tabs:

| Old tab | Where it lives now |
|---|---|
| Current offering / Subscription (buy units) | **Buy Shares** pop-up on the investment portfolio |
| My subscriptions (+ PDF receipt + certificate) | Portfolio list → investment details, which has both PDF buttons |
| Documents | two "Download" buttons that did nothing |

The subscribe form also posted to `user.plan.reserve`, a route that is
commented out. So the page was a broken copy of the portfolio. Following
the duplicate rule:

- `/user/plan` now **redirects to `/user/investment/portfolio`**. The
  route name stays, so old links keep working.
- Links to it (land page, home-page plan section, old menus) now point
  straight at the portfolio.
- The 3 views were deleted.

### Step 61: `/user/binary-summery` rebuilt as a React page

The route pointed to `PlanController@binarySummery`, which never existed
either. The left-over Blade view showed six numbers from the
`user_extras` table: paid/free members and BV per leg. **Those numbers are
from the script's old binary plan and are no longer kept up to date:**

- the "paid" counts are never updated (`updatePaidCount()` is never
  called);
- BV is only added by the admin supply screen.

Showing them would mislead members. The page now shows the numbers this
site really uses, from the **stage-1 tree**, where PV is credited
(`upLinePvOnUpgrade()`):

| Per leg | Source |
|---|---|
| Members in the leg, deepest level | the same breadth-first walk the Binary List uses |
| Direct member under you | `matrices.left` / `matrices.right` |
| Pairing PV | `pv_left_pairing` / `pv_right_pairing`: waiting to be matched for the matching bonus |
| Ranking PV | `pv_left` / `pv_right`: everything the leg has earned |
| Empty first position | "Register into this slot" link to the distributor form |

**Files:**

- `Pages/User/BinarySummary.tsx` + `BinarySummary.css`, scoped under
  `.page-binary-summary`. It reuses the Binary List's hero and empty-state
  styles from `main.css`.
- `PlanController::binarySummery()`.
- `binaryList()` and the summary now share one private method,
  `stageOneDownline()`, instead of two copies of the tree walk.
- The URL keeps its old spelling (`summery`), so existing links work. The
  page title says "Summary".
- New sidebar entry "Binary Summary" under Binary List.
- The old `binarySummery.blade.php` was deleted.

### Step 62: The dead links from Step 45, one by one

The route-name scan now finds **56** references to names that do not exist
(was 60; the 4 in the deleted plan views are gone). They fall into three
groups.

**A. Lines that really run, and what each should point to:**

| Where | Missing name | When it runs, and what happens | Should point to |
|---|---|---|---|
| `LoginController:146` | `user.land` | A member whose `section` is 2 logs in → **error page right after login**. Nothing sets `section = 2` any more (registration and Add Distributor set 1), so this only hits old accounts. | `user.home`: treat them like everyone else (the "land" page has no route and its view is not used). |
| `LeadershipBonusService:324` | `user.leadership` | Building the "you qualified for the Leadership bonus" notice. The error is caught and logged, so **that notice is silently never sent**. | There is no member leadership page. Best fit today: `user.pv.log` (leadership is earned from leg PV). Or build a small "Leadership" page later and link that. |
| `Admin/AdminUserController:234,247` (also 185 in an unrouted `update1`) | `admin.users.index` | An admin tries to delete or deactivate **their own** admin account → error page instead of "You cannot…". | `admin.admins.index`: the Admin Users list this same controller uses elsewhere. |
| `StockistController:189` | `stockist.dashboard` | Right after a stockist profile is created. The profile **is saved**, but the member sees an error page. | `user.stockist.dashboard` (the stockist routes sit in the `user.` group). Fix in 4e. |
| `StockistController:692` | `stockist.setup` | Opening redemption history without a stockist profile. | `user.stockist.setup`. Fix in 4e. |
| `StockistMiddleware:15` | `login` | A logged-out visitor on a stockist URL. Rare: the outer `auth` middleware usually catches them first. | `user.login`. Fix in 4e. |

**B. Lines in code nothing can reach** (no route or view uses them, so the
link never runs). The fix is to **delete the dead code**, not to repoint it:

| File | Missing name(s) | Why it is dead |
|---|---|---|
| `User/LoanController`, `User/SavingsController` | `user.loans.index`, `user.savings.index` | No member routes. Loans and savings exist only in the admin panel. |
| `User/TransferController` | `user.transfer.index` | Old bonus transfer; replaced by `BonusTransferController` (live). |
| `Gateway/Paystack/ProcessController_new.php` | `user.land` | A backup copy; the routes use `ProcessController`. |
| `User/TestController:170` | `dashboard` | In `test1()`, which has no route. |
| `partials/dashboard1`, `partials/dashboard_new` | `user.savings.index`, `user.loans.index`, `user.welcome`, `user.land`, `user.orders` | Old menus; the layout uses `partials/dashboard`. |
| `user/land.blade.php`, `user/welcome.blade.php` | `user.deposit.index1`, `user.land` | No controller renders them. |
| `user/auth/signup.blade.php` | `user.register2` | Registration uses another view; `register2` is commented out. |
| `user/bonus/transfer.blade.php` | `user.bonus.purchase-status` | The live page is `user/bonus_transfer.blade.php`. |
| `blog.blade.php`, `blog_details.blade.php`, `sections/blog.blade.php` | `blog.details` | The public site (home, blog, pages) is switched off: `SiteController` sends every visitor to the login page. |
| `components/email/button.blade.php` | `order.tracking` | Not a real component: a pasted example whose "usage" line is inside the component itself. |
| `admin/frontend/section.blade.php:358` | `admin.frontend.import` | Inside `@if (!empty($templates))`, and `$templates` is never passed, so the block never shows. Delete the block. |

**C. The rest (about 30):** inside `_old` files and `dashboard2.blade.php`,
which are not used. They go away when those files are deleted.

**Also found, worth fixing:** `/user/test` (`TestController@anotherTest`) is
a **developer test route that any logged-in member can open**. It runs the
matrix reconnection code for whoever visits, and crashes for members without
an active matrix. It should be removed.

**Applied in Step 64** (you said yes).

### Step 63: Verification for Steps 59–61

- **Server:**
  - `GET /user/plan` → 302 to the portfolio.
  - `POST /user/purchase` no longer exists.
  - Binary summary on the test network (7 → bola, chi; bola → dan): left 2
    members, deepest 2, PV 80/300; right 1, deepest 1, PV 40/120; total 3.
    These match the Binary List.
  - A member with no downline sees "Register into this slot" links; one
    with no tree position sees the empty state.
- **Browser: 7/7 new checks** (numbers, direct members, link to the Binary
  List without a reload, empty state, legs side by side on desktop and
  stacked at 375px with no sideways scroll).
- **All suites: 260 browser checks, 0 failures.** `route:cache` works.

---


### Step 64: All dead links fixed, dead code deleted

**A. Repointed (lines that really ran):**

| Where | Was | Now |
|---|---|---|
| `LoginController` | `user.land` for `section = 2` members | everyone goes to their intended page or the dashboard |
| `LeadershipBonusService` | `user.leadership` (notice never sent) | `user.pv.log`, so the "you qualified" notice is sent again |
| `Admin/AdminUserController` ×3 | `admin.users.index` | `admin.admins.index` |
| `StockistController` | `stockist.dashboard`, `stockist.setup` | `user.stockist.dashboard`, `user.stockist.setup` |
| `StockistMiddleware` | `login` | `user.login` |

**B + C. Deleted (nothing could reach them):**

- **Controllers:**
  - `User/LoanController`, `User/SavingsController`, `User/TransferController`
  - `User/TestController`, together with the `/user/test` route
  - `Gateway/Paystack/ProcessController_new` and `_old`
  - 3 `Admin/*_old` controllers, which also re-declared the live class names
- **Methods:**
  - `SiteController::blog()` and `blogDetails()` (no routes; the public site
    is off)
  - `PlanController::reservation()` (its route was commented out)
- **Views:**
  - every `_old` view
  - `partials/dashboard1`, `dashboard2`, `dashboard_new`
  - `layouts/master1`, `master2` (only dead pages used them)
  - `user/land`, `welcome`, `reservation`, `auth/signup`, `bonus/transfer`
  - the legacy `investment-portfolio` / `investment-details` views, which
    React replaced in 4b
  - `blog`, `blog_details`, `sections/blog`
  - `components/email/button`
- **Admin:** the never-shown "Import" block in the frontend section page.

**If the public site is switched back on later:** the blog pages will need
to be rebuilt, because their views and methods are gone.

**Result:** the route-name scan finds **no real missing names**. The 21
left are all `'ipn.' . $alias` in the payment gateways, which the scanner
cannot resolve. They are fine. `route:cache` works, and all suites pass
(260 browser checks).


### Step 65: Phase 4e plan, and dead stockist code removed

The stockist area is large, so 4e is done in four parts, each committed on
its own:

| Part | Pages |
|---|---|
| 4e-1 | Map the area, remove dead code, fix bugs (this step and the next) |
| 4e-2 | Stockist dashboard (redeem an invoice), history, redemption report, stockist profile, Find Stockist (member page) |
| 4e-3 | Inventory: dashboard, catalog + cart, checkout, orders, order details |
| 4e-4 | POS, stock transfers, welcome-pack redemption, affiliate pickup |

**Removed: unreachable or broken.** Nothing in the site linked to any of
these:

| What | Why it was dead |
|---|---|
| `GET /user/redeem` (registered **twice**) | its view `user.stockist.redeem` does not exist (error page). Now redirects to the stockist dashboard, where redemption really happens. |
| `POST /user/redeem` (`redeemProduct`) | compared the numeric order status with the text `'paid'`, so it always refused. It also rendered a missing `redeem-success` view and would have stored the text `'redeemed'` as a status (the bug noted in Step 58). |
| `/user/stockist/setup` (`setup`, `storeSetup`) | behind the "stockists only" middleware, so non-stockists could never open it, and stockists were sent to the dashboard. It also wrote columns the `stockists` table does not have. The admin creates stockists. The URL now redirects to the dashboard. |
| `POST /user/check-code`, `POST /user/purchaseDone`, `POST /user/restock`, `GET /user/sales`, `GET /user/sorders` | the old "order code" flow built on the Money Box direct buy removed in Step 59; replaced by invoice redemption and the inventory catalog. `purchaseDone` paid commissions and was reachable by POST from any stockist account. |
| `StockistController`: `verifyInvoice1`, `calculateRemainingItems`, `processPurchaseCommision`, `transactions`, `downloadAttachment`, `purchase`, `orders` | no route pointed to them (same names are routed to `UserController`). |
| Views | `user/orders`, `sales_orders`, `sorders`, `stockist/setup`, `stockist/find1` (an older copy of Find Stockist) |

Unused imports in both controllers were removed too.

### Step 66: Money bugs fixed in redemption and order cancelling

**Stockist redemption (`processRedemption`):**

1. **Every refusal became an error page.** After the `catch`, the code ran
   `throw $e;` before its `return`. "You don't have this product in your
   store" or "quantity too high" therefore showed a server error instead of
   the message. Refusals are now `DomainException`s that come back as a
   toast; unexpected errors are logged and show a generic message.
2. **No lock on the invoice.** Two stockists (or a double click) could both
   hand over the same remaining items and both be paid. The invoice row and
   the stockist's store row are now locked inside the transaction.
3. **Cancelled orders could still be collected.** An admin cancel refunds the
   member, but the invoice stayed redeemable, so the member could get the
   money **and** the goods. Cancelled orders are now refused, both when
   verifying and when processing.
4. The stockist wallet is credited with an atomic SQL increment
   (`addToWallet`) instead of read-add-save.
5. A fully collected order is marked with the new constant
   `Status::ORDER_COLLECTED` (= 1, the value it always stored), instead of a
   bare `1`.

**Admin order cancel (`Admin\OrderController::status`):**

1. **It refunded ₦0.** It refunded `orders.total_amount`, which is empty for
   cart orders (Step 56). It now refunds the invoice total.
2. **It refused nothing.** An order that had already been (partly) collected
   from a stockist can no longer be cancelled.
3. **It invented stock.** It added the items back to product stock, but
   checkout never takes stock out. That line is gone.
4. **Two clicks could refund twice.** The order row is now locked, and only a
   pending order can change.

**Checked on test data:**

- Over-quantity, no-stock, other-state, cancelled and all-zero redemptions
  are all refused, and stock, wallet and ledger stay unchanged.
- 2 of 3 then 1 of 1: stock 10 → 8 → 7, wallet +₦8,000 then +₦4,000, order
  marked collected, invoice fully redeemed, 2 history rows, 2 stockist
  transactions.
- Admin cancel:
  - a partly collected order is refused;
  - a pending ₦10,000 order refunds ₦10,000, with no stock change;
  - a second click finds nothing to cancel.

### Step 67: Stockist pages in React (4e-2)

| Page | URL | React file |
|---|---|---|
| Stockist dashboard | `/user/stockist/dashboard` | `Pages/User/Stockist/Dashboard.tsx` |
| Redemption history | `/user/stockist/history` | `Pages/User/Stockist/History.tsx` |
| Redemption report | `/user/stockist/redemptions` | `Pages/User/Stockist/Redemptions.tsx` |
| Stockist profile | `/user/stockist/profile` | `Pages/User/Stockist/Profile.tsx` |
| Find Stockist (members) | `/user/find-stockist` | `Pages/User/Stockist/Find.tsx` (+ `Find.css`, scoped) |

New shared component: `Components/Stockist/SlField.tsx`. It is a labelled
input or textarea in the stockist "sl-" style, with its validation error.
The `sl-*` styles already live in the global layout, so no CSS moved.

**Dashboard: redeeming an invoice without JSON.** Before, "Verify" posted
to a JSON endpoint that returned the whole invoice, order and redemption
records to the browser. Now:

1. **Verify** loads `?invoice=CODE`. The server checks the invoice (exists,
   not cancelled, not fully redeemed, your state) and sends only what the
   table needs (product, ordered / redeemed / remaining), or the reason it
   was refused, which the page shows in a dialog.
2. **Process** posts the quantities. The server sends you back to the same
   `?invoice=` with a toast, so the table shows the new remaining amounts.
3. The quantity boxes start at "everything remaining". A box cannot go above
   what is left: the browser refuses it, and the server caps it too.

`POST /stockist/verify-invoice` is gone.

**History:**

- Date range, custom dates, sort and search now run on the server, and the
  four counters cover **all** redemptions. The old page filtered and counted
  only the 10 on screen.
- **Export** downloads a CSV of the whole filtered list from the server. The
  old export only covered the visible cards, and its "Redemption ID" column
  was always empty.

**Profile:**

- One page with five tabs, each form saved through Inertia.
- **Closed days can now be saved.** Before, every day needed both times, and
  the times of a "closed" day were still sent.
- Saving opening hours **before a location exists** used to say "updated
  successfully" and save nothing. It now says to save the location first.
- The preview used to build HTML from the stockist's own text. It is now
  plain text.
- `GET /stockist/profile/data` is gone; the data comes with the page.

**Find Stockist (members):**

1. **Stored XSS fixed.** The results and the pop-up were built as HTML
   strings from stockist-written text (business name, services, address).
   A stockist could run script in every member's browser. React shows that
   text as text.
2. The search is `GET ?city=&state=`, so a search can be bookmarked or shared.
3. Inactive stockists are no longer listed.
4. **The "Map View" is gone.** It placed markers at made-up positions, not
   at the stockists' real locations. Each stockist now has a real
   **Get Directions** link to Google Maps.

`POST /search-stockists` is gone.

### Step 68: Verification for 4e-1 and 4e-2

- **Server:** the redemption and cancel cases in Step 66, plus:
  - the stockist middleware lets a stockist in and sends a member to the
    dashboard;
  - history filters, sort, search, custom range and the CSV;
  - report totals per channel (cash, invoice, welcome pack, affiliate);
  - profile validation (bad email, open day without a time, empty service
    name);
  - Find Stockist: active only, no wallet data in the page;
  - every removed URL is redirected or gone.
- **Browser: 38/38 checks:**
  - verify is sent as a GET, and process posts the right quantities and
    notes;
  - quantities over what remains are refused;
  - the refusal dialog;
  - history filters and the export link;
  - report tabs;
  - profile tabs, saving, field errors, the closed-day switch, adding and
    removing services, and the text-only preview;
  - Find Stockist: injected HTML shown as text, the directions link, quick
    locations;
  - no sideways scroll at 375px on all 7 views.
- **All suites: 298 browser checks, 0 failures.** `route:cache` works.

### Step 69: Phase 4e status

| Part | Status |
|---|---|
| 4e-1 map, dead code, money bugs | ✅ |
| 4e-2 dashboard, history, report, profile, Find Stockist | ✅ |
| 4e-3 inventory | ✅ (Steps 70–71) |
| 4e-4 POS, transfers, welcome pack, affiliate pickup | ✅ (Steps 72–74) |


### Step 70: Inventory pages in React (4e-3)

| Page | URL | React file |
|---|---|---|
| Inventory | `/user/stockist/inventory` | `Pages/User/Stockist/Inventory/Dashboard.tsx` |
| Company catalog (Mega only) | `/user/stockist/inventory/catalog` | `…/Inventory/Catalog.tsx` |
| Cart + checkout (Mega only) | `/user/stockist/inventory/checkout` | `…/Inventory/Checkout.tsx` |
| Company orders | `/user/stockist/inventory/orders` | `…/Inventory/Orders.tsx` |
| Order details | `/user/stockist/inventory/orders/{id}` | `…/Inventory/OrderDetails.tsx` |

New shared component: `Components/Stockist/OrderStatus.tsx`, the status
badge for company orders. `StockistInventoryController` was rewritten. The
session cart now holds only `product_id => quantity`; older sessions that
stored whole rows still work.

**Bugs fixed:**

| Was | Now |
|---|---|
| Every item showed **"In Stock", even at 0**, and the "Min / Max" column was blank. The view read fields the store rows don't have. | Status comes from the quantity: 0 = out, under 20 = low (the threshold `Stockist::low_stock_items` already used). The empty column is gone. |
| The catalog loaded 12 products and then hid inactive or sold-out ones, so pages could be short or empty. | Only orderable products are loaded, one row per product even if the admin entered a state's price twice. |
| A product's maximum and the stock were checked per click, so adding the same product twice could pass both. | The quantity **already in the cart + the new one** is checked. The page shows "N in cart" and stops the + button there. |
| A price missing for your state crashed "add to cart". | "This product is not available for ordering." |
| **The order charged the price saved when the item was added.** Add when cheap, pay that price later. | Prices are read fresh on the checkout page and when placing the order. |
| No row locks: two stockists could both take the last units. | Product rows are locked while the order is placed. |
| **Double-clicking "Place Order" placed the same cart twice** (the button never disabled). | The button disables, a progress dialog shows, and the server holds a per-stockist lock: a second request at the same moment is refused, and a later one finds the cart empty. |
| "Top Up Wallet" linked to `#`; "View Reports" did nothing. | The shortfall message explains the wallet is credited by redeeming invoices; "View Reports" opens the Redemption Report. |
| The order counters counted only the current page. | They count all orders. |
| The inventory-levels editor (min/max) posted to a validation rule naming a table that doesn't exist (`stockist_inventory`), so it always failed. Its button was already hidden. | Removed (route and method), as was the unused `GET inventory/cart` JSON. |

The old order page showed a "Tax (5%)" row that was always ₦0 (tax is
switched off in the code). It is not shown any more.

**Admin side, `Admin\StockistOrderController::cancelOrder`:**

- Cancelling a company order refunded the wallet but **never returned the
  stock** that placing the order took from the company. It does now.
- The order row is locked, so a second click cannot refund twice.

Stock only reaches the stockist on delivery, and only pending or approved
orders can be cancelled, so returning it to the company is right.

### Step 71: Verification for 4e-3

- **Server, on test data.** A Mega stockist with 10 rice, 1 oil and 0 beans.
  Rice: min 2, max 10. Oil: no product maximum, so stock caps it.
  - **Inventory:** 3 products, 2 low, 1 out.
  - **Add to cart:**
    - under the minimum → refused;
    - 6 + 6 rice (over the max of 10) → refused;
    - oil 30 with no product max → accepted;
    - hidden product → refused.
  - **Update:** 11 → refused; 4 → accepted; removing beans works.
  - **Fresh price:** the rice price rose to ₦4,500 after it was added, and
    checkout shows and charges the new price.
  - **Placing the order:**
    - short wallet → refused, cart kept;
    - oil stock fell to 20 → refused;
    - lock held by another request → refused;
    - success → order ₦93,000, wallet 100,000 → 7,000, rice 100 → 96,
      oil 50 → 20, cart emptied;
    - a second click → "Your cart is empty".
  - **Order list:** counters over all orders; another stockist's order is
    never shown and gives 403.
  - **Admin cancel:** refund +₦93,000 and stock back to 100 / 50; a second
    cancel and a delivered order are both refused.
  - **A Mini stockist** gets "Buy from Mega" links and is kept out of the
    company catalog.
- **Browser: 28/28 checks:**
  - computed statuses and the low / out alerts;
  - the Mega vs Mini buttons;
  - the catalog's "room left" limit;
  - add, and the refusal dialog;
  - checkout + / remove-with-confirm;
  - **one POST on a double click**;
  - the short-wallet and empty states;
  - order counters and badges, and the 4-step timeline;
  - no sideways scroll at 375px on all 8 views.


### Step 72: POS, welcome packs, affiliate pickup and stock transfers in React (4e-4)

| Page | URL | React file |
|---|---|---|
| Point of sale | `/user/stockist/pos` | `Pages/User/Stockist/Pos.tsx` |
| Welcome-pack redemption | `/user/stockist/welcome-pack` | `Pages/User/Stockist/WelcomePack.tsx` |
| Affiliate (/shop) pickup | `/user/stockist/affiliate` | `Pages/User/Stockist/AffiliateRedeem.tsx` |
| Stock requests / my orders | `/user/stockist/transfers` | `Pages/User/Stockist/Transfers/Index.tsx` |
| Buy from a Mega | `/user/stockist/transfers/new` | `…/Transfers/Create.tsx` |
| One request | `/user/stockist/transfers/{id}` | `…/Transfers/Show.tsx` |

**The same "verify with ?code=" pattern on every page.** The POS, the
welcome-pack page and the affiliate page each had a JSON "verify" endpoint.
Each page now reloads itself with `?code=…` (an Inertia partial reload).
The server checks the code and sends either the details or the reason it
was refused.

- On the POS, `preserveState` keeps the order being built in the browser.
- The actions (sell, redeem, confirm pickup) post normally. On success the
  server redirects back; on failure it returns errors that the page shows.
- **The POS receipt** is flashed in the session (`pos_receipt`) and shown
  once after the redirect.

`POST stockist/welcome-pack/verify` and `POST stockist/affiliate/verify`
are gone.

**Bugs fixed:**

| Was | Now |
|---|---|
| POS: an unexpected error ran `throw $e;` before its return, so the member saw an error page. (Same bug as Step 66.) | Logged, plus a clear message. |
| POS: a product with no state price showed the base price, then the sale was refused. | "No price for your state", and the + button is disabled. |
| POS: a welcome code typed or pasted with a dash (`WPPOS-00001`) was cut to 10 characters **before** the dash was removed, so verify always failed. | Letters and digits are kept first, then cut to 10. The welcome-pack page's phone input had the same problem and is fixed too. |
| POS: nothing told you why "Charge Package" stayed disabled. | It says how much to add or remove: the package must match the order exactly. The server rule is unchanged. |
| Welcome-pack page: the "redemption ledger" row was written **outside** the package redemption's transaction. | Both commit together. |
| Affiliate pickup: the stockist's stock rows were checked and then reduced without a lock, so a POS sale at the same moment could push stock below zero. | Rows are locked in `AffiliateOrderService::confirmPickup`. |
| Transfers: confirm and cancel used the browser's `confirm()`. | The site's `ConfirmDialog`. A decline needs a reason, and field errors show under it. |

The stock-transfer controller and service were already well built (locks,
held payments, refunds). Only their pages moved.

**Two things for you to decide** (both decided in Steps 76–77):

1. **There are two ways to redeem a welcome pack.**
   - The **POS** hands over goods worth exactly the package, **takes the
     stock**, and pays the stockist the package value (plus rebate).
   - The **welcome-pack page** pays the stockist the package value
     **without any stock movement** ("handed over in person").

   If the gift should always be goods, the welcome-pack page should be
   removed or turned into a link to the POS. I have not changed this: it is
   a business decision.
2. **`ext-bcmath` is not declared in `composer.json`,** but the transfer
   service uses `bcadd` / `bcmul` / `bcsub` / `bccomp`. A server without that
   PHP extension would fail only when a stockist sends or confirms a
   transfer. Ask the host to confirm it is installed, and add
   `"ext-bcmath": "*"` to `composer.json` so a missing extension is caught at
   install time. This test container lacks it, so the test harness defines
   simple stand-ins for those four functions.

### Step 73: Verification for 4e-4

- **Server, on test data.** Ada is a Mega stockist and Chi a Mini, both in
  Lagos.
  - **POS:**
    - cash sale over stock → refused;
    - a ₦4,000 order on an ₦8,000 package → refused;
    - cash sale of 3 → stock 10 → 7, with the receipt;
    - welcome sale of exactly ₦8,000 → package redeemed, wallet +₦8,000;
    - same code again → refused, nothing changes;
    - a used, unknown or lower-case code is handled.
  - **Welcome-pack page:** verify shows the package; redeem → +₦5,000 and a
    ledger row; a second redeem is refused.
  - **Affiliate pickup:**
    - the right order is shown;
    - another state, an already-collected order and an unknown code are
      refused;
    - over-stock is refused with the order left unchanged;
    - confirm → fulfilled, stock −2;
    - a second confirm is refused.
  - **Transfers:**
    - asking for 11 when the Mega has 10 → refused;
    - request 2 → ₦8,000 held from the Mini;
    - the Mega sees "you have 10";
    - confirm → stock 10 → 8 and 1 → 3, Mega wallet +₦8,000;
    - a second confirm is refused;
    - request + cancel refunds the Mini;
    - a member who is not part of the transfer gets "not found".
- **Browser: 42/42 checks.**
  - **POS:**
    - search, the stock count going down;
    - the cash sale body and the receipt;
    - a refusal keeps the cart;
    - code cleaning and verify by partial reload with the cart kept;
    - the package-vs-order message and the welcome sale body.
  - **Welcome-pack page:** pasting into the 10 boxes, backspace, verify,
    redeem, and the refusal dialog.
  - **Affiliate page:** verify, confirm, and a refusal.
  - **Transfers:**
    - quantities capped by stock, the request body;
    - the Mega's confirm through the dialog, then the completed view;
    - decline with a reason;
    - the Mini's cancel button.
  - No sideways scroll at 375px on all 11 views.
- **All suites: 368 browser checks, 0 failures.** `route:cache` works. The
  route-name scan finds nothing real (only the 21 `ipn.` false positives).
  The keyword scanner finds 1,116 React strings.

### Step 74: Phase 4 done, and what is still Blade

Every stockist page is now React: **19 pages** across Steps 65–73. The
`user/stockist` view folder is gone.

**Member pages still on Blade:**

| Page | Controller |
|---|---|
| Dashboard | `UserController@home` |
| Bonus Transfer | `BonusTransferController` |
| Balance transfer | `UserController@indexTransfer` |
| E-pin recharge + log | `EpinController` |
| Add Distributor | `DistributorController` |
| Affiliate dashboard / orders / ads | `AffiliateController` |
| Stock page | `UserController` (`user.stock`) |
| Login, register, password reset, email/SMS authorization, "complete your profile" | `Auth/*`, `AuthorizationController`, `UserController@userData` |

**Suggested next step:** move these with the layout itself (Phase 5,
sidebar + header in React). The dashboard and the auth pages are the
natural place to do that, and that is when navigation becomes fully
single-page.


### Step 75: The dashboard wrote a new "announcement" on every visit

**What happened.** `UserController@home` began with
`$user->flashcardPreferences()->create([... 'Announcement: New Feature' ...])`.
Every time any member opened the dashboard, one more row was inserted
into `user_flashcard_preferences`: one row per member per page view, for
as long as the site has run.

**Why nobody saw it.** The site has two flashcard systems:

| System | Tables | Used? |
|---|---|---|
| Admin-created flashcards (the pop-ups members see) | `flashcards` + `flashcard_views` | yes: `partials/flashcards.blade.php` → `User::pendingFlashcards()` |
| Old per-user "preferences" | `user_flashcard_preferences` | **no**: nothing ever reads it |

The rows were written to a table no page reads, so the only effect was a
table growing without limit. It also made every dashboard load do a write
it didn't need.

**Fix:**

1. `home()` is now read-only. Two other queries whose results were never
   used went too: it loaded the user a second time and looked up the active
   matrix, then passed neither to the view.
2. The same pattern elsewhere, removed:
   - the `first.login` middleware (`CheckFirstLogin`), which wrote a
     "Welcome to Our Platform!" row into the same dead table once per new
     session. It wasn't attached to any route, so it was dead code waiting
     to be switched on.
   - the `flashcards:create-sample` command, which filled the same table
     with demo rows.
   - the `UserFlashcardPreference` model and `User::flashcardPreferences()`
     / `activeFlashcards()`.
3. **Clean-up migration**
   `2026_10_22_000001_remove_duplicate_dashboard_announcements.php`:
   - deletes only rows with the exact signature the bug wrote (that title,
     that text, that image path);
   - works in batches of 5,000 to keep locks short;
   - is safe to run twice.

   The table itself is kept: deleting rows is reversible in spirit (nothing
   used them), dropping a table is not. It can be dropped later once you're
   happy.

**Deploy step:** run `php artisan migrate` once to clear the old rows.

**Checked:**

- `home()` now runs all 15 of its queries with **0 writes**.
- The migration on a table with 12,003 bug rows plus 2 others removed the
  12,003 in 0.08 s and left the 2.
- A second run changes nothing.

### Step 76: Decision: welcome packs are redeemed in the POS only

There were two ways to redeem a welcome package (Step 72):

| Path | Stock | Stockist is paid |
|---|---|---|
| POS (Welcome Code tab) | goods worth **exactly** the package are taken from stock | package value + stockist rebate |
| Welcome-pack page "Confirm & Redeem" | **nothing** | package value |

**Decision: keep only the POS path.** As a rule, money should never move
without the goods it pays for:

- **Fraud risk.** With the page path, a stockist could enter a member's
  code and collect the package value without handing anything over. Nothing
  in the system could show otherwise.
- **Inventory never reconciled.** Goods given out "in person" never left
  the stockist's stock, so stock reports overstated what stockists hold.
- **Reporting.** POS redemptions write the items to the redemption ledger
  (what was given); the page wrote `items: [], quantity: 0`.
- **The member is better served.** The POS forces a real hand-over of
  goods to the value of the gift.

**What changed:**

- `POST /user/stockist/welcome-pack/redeem` and its controller method are
  removed.
- The welcome-pack page still verifies a code, shows who it belongs to and
  its value, and lists the stockist's redemption history (POS redemptions
  included). Its button is now **"Hand over in POS"**. It opens
  `/user/stockist/pos?code=…`, where the code is already verified and the
  Welcome Code tab is open. The stockist adds goods to exactly the package
  value and charges it.
- `WelcomePackageService::redeem()` is unchanged (the POS uses it; it
  locks the package, so a code can only be used once).

### Step 77: Decision: declare `ext-bcmath`

Stock transfers do their money maths with `bcadd`/`bcmul`/`bcsub`/`bccomp`.
Without the PHP `bcmath` extension, a stockist sending or confirming a
transfer would hit a server error.

**Finding:** `btcpayserver/btcpayserver-greenfield-php`, already one of the
site's packages, **also requires `ext-bcmath`**. So any server where
`composer install` succeeded already has it, and production is fine.

**Change:**

- `"ext-bcmath": "*"` added to `composer.json` → `require`, so the
  requirement is written down where it is used.
- `composer.lock` gets the matching `content-hash` and platform entry.
  This is a 3-line change; package versions are untouched.

On a server without bcmath, `composer install` now stops with "requires PHP
extension ext-bcmath", instead of the site failing later on the first
transfer. This test container lacks bcmath, which is why the test harness
defines simple stand-ins for those four functions.

**Verification for Steps 75–77:**

- The server checks above, plus:
  - the welcome-pack redeem route is gone;
  - the page's link opens the POS with the code;
  - a POS sale that doesn't match the package value is refused, and the
    package and wallet are unchanged.
- **Browser:** the welcome-pack page has no cash-redeem button; "Hand over
  in POS" opens the POS without a full reload, with the code verified and
  the Welcome tab active; a used code shows "Cannot Redeem".
- **All suites: 369 browser checks, 0 failures.** `route:cache` works. The
  route-name scan is clean.

## Part 3e: Phase 5 change log (layout to React)

### Step 78: The sidebar and header are React (5a)

**Before:** every React page was drawn *inside* the Blade dashboard. The
root view `inertia/user.blade.php` extended `layouts.master`, which included
`partials/dashboard.blade.php` (or the stockist or affiliate copy). Moving
between React pages swapped only the page body, so the sidebar's "active"
highlight, its badges and the cart count stayed as they were at the last
full page load.

**Now:**

```
first load:  inertia/user.blade.php  (bare HTML: stylesheets + @inertia + toasts)
               └─ React app
                    └─ Layouts/DashboardLayout.tsx   sidebar, top bar, footer
                         └─ Pages/User/...           the page
every visit: JSON { component, props: { layout: {...}, ...page props } }
```

1. **`app/Support/DashboardLayout.php` (new)** builds everything the
   sidebar and top bar show:
   - the user's name, initials and ID card label (`WCS1042`, or `@ username`);
   - the cart and unread-notification counts;
   - the greeting and date;
   - the footer's social links;
   - the menu itself.

   The menu is a list of labels, link lists and collapsible groups. Each
   link has its URL (from `route()`), an `active` flag (from
   `request()->routeIs()`, like `menuActive()`) and an optional badge (KYC
   status, pending awards, guarantor requests, project, ACB, POS, pending
   stock requests). It picks one of three menus, with the same rules as
   `layouts/master.blade.php`:
   - **member**;
   - **stockist** (`user.stockist.*`);
   - **affiliate** (`user.affiliate.*`).

   It moves the PHP that sat at the top of the three Blade partials into one
   class.
2. **`HandleInertiaRequests` shares it as the `layout` prop.** It is a
   closure, so a partial reload (`only: [...]`, used by the filters and the
   "verify a code" boxes) skips its ~8 queries. Inertia keeps the previous
   value.
3. **`Layouts/DashboardLayout.tsx` (new)** draws the same HTML and class
   names as the Blade partial, so the existing CSS applies unchanged. The
   old inline jQuery is now React state:
   - mobile sidebar open/close, with the overlay and body scroll lock;
   - the avatar menu, closed by an outside click or Escape;
   - accordion groups, one open at a time.

   On every visit it closes the mobile sidebar and avatar menu and opens
   the group that holds the current page.
4. **`app.tsx` makes it the default, persistent layout.** Every page gets
   `layout = page => <DashboardLayout>{page}</DashboardLayout>` unless it
   sets its own; the auth pages will. Because it is the same component on
   every page, React keeps it mounted: clicking a menu link replaces only
   the page body.
5. **The root view is a bare HTML page.** It loads:
   - Bootstrap CSS and the icon fonts;
   - `main.css` and `custom.css`;
   - the dashboard CSS and `app.tsx` through Vite;
   - `color.php`;
   - the notify partial (`window.notify` plus toasts flashed before a
     full load);
   - push notifications if enabled (with jQuery, which only that script
     needs).

   Left out: select2, owl, magnific-popup, isotope, main.js, and the jQuery
   block that patched form labels and table cells on page load. That block
   ran before React had drawn anything, so it never touched React pages.
6. **The dashboard CSS is one file: `resources/css/dashboard.css`.**
   - **Where it came from:**
     - the 4,400-line inline `<style>` in `layouts/master.blade.php`;
     - the same 180 lines copied into each of the three sidebar partials.
   - **Where it loads:**
     - Vite builds it as its own entry (`vite.config.ts`, about 32 kB
       gzipped);
     - the Blade master layout loads it with `@vite`;
     - so does the React root view.
   - **What else changed:**
     - The partials' identical sidebar script is now one include,
       `partials/dashboard_script.blade.php`.
     - The inline `style=""` badges became `.bank-nav-pill-*` classes.

**Links to pages that are still Blade.** Inertia cannot show a Blade page
inside the React layout. There are two safeguards:

- **`DashboardLayout::BLADE_ROUTES`** lists the menu links that must do a
  full page load:
  - Dashboard;
  - Affiliate (dashboard, orders, ads);
  - Add Distributor;
  - Bonus Transfer;
  - Sign Out.

  The layout renders these as a plain `<a>` and the rest as `<Link>`.
  **Remove a route from this list when its page moves to React.**
- **`router.on('invalid')` in `app.tsx` is the safety net for any other
  link.** When a GET visit gets HTML back, the browser loads that URL
  normally, instead of Inertia showing the HTML in a pop-up. Form posts
  keep the pop-up, because there it is the only place a server error
  shows.

**Fixes made on the way:**

- **Find Stockist** is a member Shop page, but its route starts with
  `user.stockist.`, so the Blade master showed the *stockist* sidebar on it,
  even to non-stockists. The React layout shows the member menu there.
- **The "Find Stockist" link was never highlighted.** It checked
  `menuActive('user.stockist.fin')`, a typo.
- **"Deposit History" was highlighted together with "Deposit"** on the
  deposit page. Now only "Deposit" is.
- **The avatar menu said "Change.password"**, with a typo and the same icon
  as Profile. It now reads "Change Password", with a key icon.
- **The Tawk.to live chat never loaded.** `layouts/app.blade.php` checked
  `routeIs('user.dashboard')`, but the dashboard route is `user.home`. The
  check is fixed for the Blade dashboard. The React dashboard (5b) loads the
  chat itself.
- **Deleted:** `layouts/master_stockist.blade.php`,
  `partials/master_stockist.blade.php` and
  `partials/dashboard_stockist.blade.php`. No view or controller used them;
  they were an older copy of the stockist sidebar.

**Kept on purpose:** `layouts/master.blade.php` and the three
`partials/dashboard*.blade.php` files. They are still needed by:

- the automatic payment-gateway pages (Stripe, Paystack…);
- the pages 5b/5c still have to move;
- the one-line rollback for each migrated page (Part 2, rule 4).

When you change the menu, change `DashboardLayout.php` **and** the partial
until those pages are gone.

### Step 79: Verification for 5a

**Server.** Real routes run against the SQLite fixtures, with
`request()->routeIs()` working (`p5a_check.php`).

- **Members:**
  - **Demo user (section 1, Mega stockist), on Transactions, Orders and Find
    Stockist:**
    - the member menu, with User Tree, Achievements and Stockist Dashboard;
    - the correct group open and the correct link highlighted;
    - the project pill, a "Pending" KYC badge, cart 3, and ID label
      `WCS1042`.
  - **A section-2 member with no stockist:**
    - no tree, achievements or stockist link;
    - KYC "!" and guarantor badge 1;
    - label `@ bola`.
- **Stockists:**
  - **POS and Inventory orders:** the stockist menu, with "Back to Main Menu",
    POS badge, My Orders and Stock Requests (Mega).
  - **A Mini on "Buy from Mega":** that link and My Orders, and no Stock
    Requests.
- **Requests and queries:**
  - A partial reload returns only the requested prop and runs 3 queries.
  - A full visit runs 13 queries, against 18 for Orders.
- **Pages:**
  - **The first page load** has `data-page`, the dashboard CSS, `notify()`
    and `<title inertia>`, and no Blade sidebar, jQuery or select2.
  - **Blade pages still on the master layout** (Bonus Transfer, Affiliate
    orders) still render their Blade sidebar (the affiliate one on
    `/user/affiliate/*`), with the built CSS and the shared script, and no
    inline CSS.

**Browser (`phase5a.mjs`, 29 checks).** These run on the real build with the
same stylesheet tags as the root view.

- **Layout:**
  - sidebar, top bar, body and footer;
  - profile, greeting and badges;
  - the project colour;
  - the footer social link.
- **Accordion:** opening one group closes the other; clicking again closes
  it.
- **Moving between pages:**
  - **Clicking Orders** sends one Inertia request with no full page load.
    The sidebar is the **same DOM node** afterwards, the highlight moves and
    the URL updates.
  - **Dashboard** (Blade) is a normal page load.
  - **A link whose server answers with HTML** is loaded by the browser, with
    no pop-up.
- **Avatar menu:** opens; closes on an outside click and on Escape.
- **Section-2 and stockist menus.**
- **Mobile (375 px):**
  - the sidebar starts off screen;
  - the toggle slides it in, with the overlay and scroll lock;
  - an overlay click closes it;
  - following a link closes it.
- **Side by side:** a screenshot of the React layout next to the Blade one
  looks the same.

**All earlier suites still pass** (they now read the CSS from
`resources/css/dashboard.css`): **398 browser checks, 0 failures**. That is
355 + 14 (pilot) + 29 (this step).

### Step 80: The member dashboard is React (5b)

`UserController@home` now returns `Inertia::render('User/Dashboard')`, and the
page is `Pages/User/Dashboard.tsx`. Every value arrives display-ready: amounts
go through `showAmount()`, dates are formatted, the card's last 4 digits and
the PV bar widths are computed. It reuses the dashboard classes in
`resources/css/dashboard.css` (`bk-*`, `nc-*`, `fc-*`), so the page looks the
same. Visiting it still writes nothing (Step 75).

**What the page shows:**

- the balance card, where the eye hides the balance;
- quick actions, where stockists get Genealogy instead of Withdraw;
- the two site notices from General Settings;
- the four stat cards and the seven MLM/product earnings;
- strips: the admin notice, welcome packages (copy code), ACB and autoship;
- binary PV bars;
- recent transactions;
- the portfolio, account status and quick links;
- the admin flashcards overlay.

**Removed / replaced:**

- `user/dashboard.blade.php` and `partials/flashcards.blade.php` are deleted.
  The controller changed shape, so a one-line rollback was not possible
  anyway.
- The unused `totalBvCut` and `total_ref_debit` queries are dropped. The
  page never showed them.
- The route `GET /user/flashcards` (`user.flashcards.index`) is removed. It
  pointed at `FlashcardController::index()`, which does not exist, so the
  URL was a server error.
- `user.home` is no longer in `DashboardLayout::BLADE_ROUTES`. The logo,
  "Back to Main Menu" and the Dashboard link are now single-page links.

**Fixed on the way:**

- **The KYC rejection reason could never be read.** The old page had a
  Bootstrap modal for it, but nothing opened it. An unverified member with
  a rejection reason now gets a "Why?" link next to the KYC badge. It opens
  the reason and a "Submit KYC again" button.
- **Only one site notice could be dismissed.** Both notices used
  `id="ncWrap"`, so the × closed only the first one. Each has its own ×
  now.
- **"Vise wallet"** now reads "Visa wallet" (the column is `users.visa`).
- **Tawk.to live chat.** It loads once, from the dashboard page itself
  (`useTawkChat`). Leaving the dashboard hides the widget and coming back
  shows it again, without loading the script twice. The never-matching
  check in `layouts/app.blade.php` (Step 78) is gone.

**Flashcards.**

- **"Got it"** records that card as seen. It sends
  `POST /user/flashcard/{id}/dismiss` with the CSRF header, the same endpoint
  as before, and moves to the next card.
- **The ×** records every remaining card. This is the old behaviour.
- The overlay is a component, so it no longer depends on a global
  `fcDismissCurrent()`.

### Step 81: Verification for 5b

**Server (`p5b_check.php`).**

- **Demo user (Mega stockist, KYC pending)** gets the correct values:
  - balance ₦20,000.00 and card digits `1042`;
  - referral earnings ₦300.00 / 2 referrals and dividends ₦330.00 / 7 units;
  - re-purchase wallet ₦10,000.00;
  - one welcome package;
  - PV 80/40 → 100%/50%;
  - 6 recent transactions;
  - 2 pending withdrawals;
  - flashcards 1 and 2 (the inactive card 3 is left out);
  - the sidebar's Dashboard link is active and is not a full reload.
- **A member with everything switched on:**
  - KYC rejected → the reason is sent;
  - ACB ₦250.00;
  - autoship ₦1,500.00 / 10 PV;
  - 2 unread admin notices, with the latest subject;
  - both site notices;
  - one flashcard left, because they had already seen card 1.
- **No writes:** a visit writes 0 rows (`flashcard_views`,
  `notification_logs`).
- **Dismissing a card:**
  - it records once, and a second dismiss does not add a row;
  - dismissing an inactive card is a 404;
  - after a dismiss, that card is gone from the next visit.
- **`GET /user/flashcards`** is gone.

**Browser (`phase5b.mjs`, 31 checks).**

- **Flashcards:**
  - **"Got it, Next"** sends a POST with the CSRF header and moves to the
    next card.
  - **"Got it, Close"** and **×** each record the remaining cards and remove
    the overlay.
- **Balance card:** the eye hides and shows the balance.
- **Quick actions** by stockist status.
- **Values on the page:**
  - stats, earnings and the project chip;
  - PV bar widths;
  - welcome code copy (with a tick);
  - debit row `-₦2,000.00`;
  - account status.
- **Strips:**
  - the admin notice, ACB and autoship strips;
  - autoship is a plain link, because Bonus Transfer is still Blade.
- **Site notices:** dismissing one leaves the other.
- **Empty state** for no transactions.
- **KYC "Why?"** opens and closes the modal.
- **Navigation:**
  - History quick action → Inertia visit, and the chat is hidden;
  - sidebar → back to the dashboard with no full load, the chat is shown
    again, and there is still only one Tawk script.

**All suites: 429 browser checks, 0 failures** (398 + 31). `route:cache`
and `view:cache` both work.

### Step 82: E-pin, affiliate dashboard and orders in React; balance transfer removed (5c-1)

**E-pin (`/user/e-pin/recharge`, `Pages/User/Epin.tsx`).** The controller
sends display-ready props: VISA balance, price, stats, the pins (paginated,
with the `search` / `status` filters) and the URLs. "Generate New Pin" asks
for confirmation first and shows the total, then posts.

Fixed:

- **Two quick clicks could spend the same VISA money twice.** The balance
  check ran on the copy of the user loaded at the start of the request.
  The user row is now locked (`lockForUpdate`) inside the transaction, and
  the balance is checked again there.
- **Pin codes were predictable.** `rand()` is not a secure generator, and a
  pin is money: anyone holding the code can activate an account with it.
  Codes now come from `random_int()` and are checked for uniqueness.
- **The server decides the amount.** It used to take `amount` from a hidden
  form field, so any amount could be posted. It is now always
  `EpinController::PIN_AMOUNT` (9,000), the only amount the page ever
  offered. The admin's e-pin charge is shown on the page.
- **The stats counted only the current page.** The Unused/Used numbers
  came from the visible 30 pins; they now count all of the member's pins.
- **Errors are hidden from the member.** A failure is now logged with
  `report($e)` and the member sees a generic message. The old code
  swallowed the error without a trace.
- **Removed:**
  - `eRecharge()` had no route (dead code).
  - The "recharge log" page was a copy of Transactions. Its URL now
    redirects to `/user/transactions?remark=epin`.

**Affiliate dashboard and My Sales (`Pages/User/Affiliate/{Dashboard,Orders}.tsx`).**

- The order status is sent as a label plus a badge class, the same
  colours as `AffiliateOrder::status_badge` but without HTML in the props.
- The item count comes from `withSum`, with no more items loaded per row.
- Copying the link uses the shared `useCopy` (it falls back to `execCommand`
  on plain HTTP).
- Ad Center and Bonus Transfer are still Blade pages, so they are plain
  links.

**Balance transfer (`/user/transfer`) removed.** The form posted to a URL that
only accepts GET (so every submit failed), and no code anywhere moved the
money. Bonus Transfer is the real transfer page, so `/user/transfer` now
redirects there. Also deleted:

- `UserController::indexTransfer()`;
- `searchUser()` and its `POST /user/search-user` route. It told anyone
  logged in whether a username or email exists, and only this page used
  it;
- the view.

The admin's "balance transfer charge" settings are now unused. They are
left in General Settings, untouched.

**Stock page removed.** `UserController::stockists()` rendered `user.stock`,
a view that does not exist, and no route pointed at it. It is deleted.

**Menu.** `user.affiliate.dashboard` and `user.affiliate.orders` left
`DashboardLayout::BLADE_ROUTES`. The dashboard's E-Pin quick link is a
single-page link now.

**Deleted views:** `balanceTransfer`, `epin_recharge`, `affiliate/dashboard`,
`affiliate/orders`.

**Verification:**

- **Server (`p5c_check.php`):**
  - **Fixed price:** the e-pin price is 9,000 NGN plus a 2% charge
    (₦180.00, ₦9,180.00 in total).
  - **Stats and filter:** the stats count all pins, and the `status=0`
    filter leaves them unchanged.
  - **Tampered amount:** a POST with `amount=1` still makes a 9,000 pin
    (`dddddddd-dddddddd-dddddddd-dddddddd`). VISA goes 30,000 → 20,820, and
    the ledger shows `-9000`, charge 180.
  - **Insufficient balance:** three pins later, with ₦2,460 left, the next
    one is refused and VISA is unchanged. The page then shows `enough=false`.
  - **Redirects:** the log URL goes to Transactions `?remark=epin`;
    `/user/transfer` goes to Bonus Transfer; `POST /user/search-user` is gone.
  - **Affiliate pages:** the dashboard shows 3 clicks, 4 orders, 2
    fulfilled and ₦1,250.00 bonus. Badges and item counts are correct, and
    the affiliate code does not change between visits.
- **Browser (`phase5c1.mjs`, 14 checks):**
  - **E-pin:** the stats and the price/charge note; who used a pin; copy
    only on unused pins; confirm → POST with no amount; the filter as an
    Inertia GET; copy plus toast.
  - **Affiliate:** the sidebar variant and highlight; stats; badges; Ad
    Center as a plain link; copy link; My Sales as an Inertia visit with
    item counts, and the highlight moves.

### Step 83: Bonus Transfer in React (5c-2)

**What changed.** `BonusTransferController@index` renders
`User/BonusTransfer`. The 580 lines of page CSS moved to
`Pages/User/BonusTransfer.css`, scoped under `.page-bonus-transfer` with
`scripts/extract-page-css.mjs`. The Blade page's `:root` variables now sit on
the scope itself.

The controller sends display-ready props:

- the hero totals;
- one row per bonus with money in it, plus the ACB row for ACB members,
  each with `locked` set when it cannot be moved yet;
- the "Transfer All" summary, with the skipped rows marked;
- the autoship status (PV bought this month, the target, the latest order);
- welcome packages;
- the paginated history.

The three dialogs are React: transfer one bonus, Transfer All, and "why
autoship is locked". A wrong password comes back as an Inertia validation
error. The dialog stays open, shows it, and keeps the amount. The old
`reopen_all_modal` session flag and the hidden `#btAutoReopen` data div are
gone.

**Bugs fixed:**

- **The page crashed for anyone with an autoship balance.** The view used
  `$autoshipMinPv` and `$autoshipPv`, but the controller never passed them
  ("Undefined variable").
- **The autoship rule was not the one stated.** Autoship is 20% of the
  matching bonus, held back until the member buys `AutoshipService::MIN_PV`
  (10) PV of products in the month. That is what `MatchingBonusService`
  and the page text say. The transfer only checked for *any* order this
  month, and its error message named the Matching Bonus. The transfer, the
  total, the summary and "Transfer All" now all use
  `AutoshipService::monthlyPv()`.
- **Non-ACB members never saw their welcome packages here.** The Blade
  loop only printed them just before the ACB row, which only ACB members
  have. They are now listed after the bonus rows for everyone.
- **The footnote** said the Matching Bonus needs a purchase. It now
  describes the autoship rule.

**Removed:** `GET /user/bonus-transfer/check-purchase`. The page used to fetch
the autoship status when the dialog opened; it now arrives with the page.

**Menu and links.** `user.bonus.transfer.index` left
`DashboardLayout::BLADE_ROUTES`. The dashboard's autoship strip and the
affiliate dashboard's Bonus Transfer link are single-page links now.

**Unchanged:** the money movement itself (`WalletService::transferBonus`,
which locks the user row) and the per-source history rows.

**Verification:**

- **Server (`p5c2_check.php`).** The demo user has 8 PV this month and a
  10 PV target:
  - **Page:** the total is ₦5,050.50, without the locked autoship. Autoship
    is `LOCKED`, and the summary marks it skipped. The welcome package is
    listed, and the sidebar link is no longer a reload.
  - **Wrong password** → a validation error, and nothing moves.
  - **500 from Direct** → direct 1,500 → 1,000 and Money Box
    20,000 → 20,500.
  - **Refused:**
    - 5,000 (more than the balance) → "Insufficient Direct Bonus balance";
    - autoship while locked → "Buy 10 PV…", and the balance is unchanged;
    - the ACB bonus for a non-member.
  - **Transfer All:**
    - with a wrong password → error;
    - otherwise it moves everything except autoship (Money Box ₦25,050.50),
      with one history row per source.
  - **Unlocked:** after another purchase (12 PV), autoship unlocks and
    100 of it transfers.
  - **ACB:** an ACB member sees the ₦0.00 ACB row.
  - **`check-purchase`** is gone.
- **Browser (`phase5c2.mjs`, 17 checks):**
  - **Page:** the hero; the locked row text; the welcome card; the empty
    history; the sidebar highlight.
  - **Autoship dialog:** shows the PV and the invoice without a request,
    links to the shop, and Escape closes it.
  - **Transfer dialog:**
    - it is pre-filled on the first render;
    - more than the balance → warning and disabled button;
    - it posts `{bonus_field, amount, password}`;
    - a wrong password keeps the dialog open, with the error, the amount
      kept and the password cleared;
    - success closes it.
  - **Transfer All:** shows Skipped plus the PV notice, posts only
    `{password}`, and closes on an outside click.
  - **Unlocked fixture:** autoship has a Transfer button, and the history is
    numbered.

### Step 84: Add Distributor in React (5c-3)

**What changed.** `DistributorController@showForm` renders `User/Distributor`,
the same four-step wizard (Referral & Project → Credentials → Personal →
Review), in React. The page CSS (`dist-*`) was already global. Props:

- the sponsor's username and VISA balance;
- the active projects, each with an `affordable` flag;
- the country list, Nigeria first, with the visitor's country preselected
  as before;
- the `?parent=&position=` prefill from the binary list and binary summary;
- the URLs.

The links from those two pages and the menu item are single-page links now.

- **Live checks** use one new endpoint, `GET /user/add-distributor/check`.
  It answers: username taken? email / mobile already used (a warning only,
  as before)? does the parent exist, and **which of its two slots are
  free**? The page disables a taken slot. It replaces
  `POST /check-visa` (the page knows the balance and every project's price)
  and the shared parent check, which only said "found".
- **State / city** still come from countriesnow.space in the browser. If
  that service is down, the two fields become text boxes. The old page was
  stuck there, because both fields are required.
- **A server validation error** jumps the wizard to the first step that has
  an error and shows it under the field. The passwords are cleared after
  every submit.

**Bugs fixed in `store()`:**

- **Errors were shown to the member, stack trace and all.** The catch
  block put `'Error: ' . $e` in the toast. Now it calls `report($e)` and
  shows "Something went wrong. Please try again."
- **The typed password was saved in the session.** The "tree crossing"
  refusal used `withInput($request->all())`, which flashed `password` and
  `password_confirmation`. Inertia keeps the form in the browser, so
  nothing is flashed now.
- **The VISA wallet could be spent twice.** The balance check ran before
  the transaction, on the copy of the user loaded at the start of the
  request. The paying account is now locked inside the transaction and
  checked there.
- **Two registrations could take the same slot.** The parent's matrix row
  (or the slot found by the automatic search) is now locked and checked
  again before the new member is placed.
- **Automatic placement could never run.** `position` was always
  `required`, so leaving "Specify Placement Position" off always failed
  validation. It is now required only together with `parent`. Without a
  parent, the first open slot under the sponsor is used, as the code
  always intended.
- **The early `return back()` calls inside `DB::beginTransaction()`** left
  the transaction open. Refusals now throw a `DomainException` inside
  `DB::transaction()`, so it always rolls back.

**Unchanged:** the bonuses and their order: VISA debit, direct and indirect
bonus, PV, cash back, the key-in bonus (2% of PV × ₦600), the global pool
and the admin notification. The validation rules are also the same,
except `position`.

**Removed:** the never-checked captcha widget on the review step. `store()`
never called `verifyCaptcha()`, so it did nothing. It is not needed for a
logged-in member spending their own wallet.

**Deleted:** `user/distributor.blade.php`.

**Verification:**

- **Server (`p5c3_check.php`):**
  - **Page props:** the projects carry the right `affordable` flags; 242
    countries, Nigeria first; the prefill is applied, and a bad
    `?position=middle` is ignored.
  - **Live checks:** taken / free username, parent bola (left taken,
    right free), an unknown parent, a used email.
  - **Validation:** a parent without a position is refused, and so is an
    invalid position or a taken username.
  - **Refusals:** a parent outside the sponsor's tree; VISA ₦100 against a
    ₦10,000 project (nothing created).
  - **Registered under bola/right:** a new user, VISA 30,000 → 20,000,
    key-in bonus 120 (10 PV × 2% × 600), and bola's right slot set. The
    same slot again is refused.
  - **Automatic placement** (no parent) registers into chi's left slot,
    the first open one under the sponsor.
- **Browser (`phase5c3.mjs`, 26 checks):**
  - **Step 1:** the prefill and the parent check (taken slot disabled);
    refusals for no project and for an unaffordable one.
  - **Step 2:** the live username check; the email warning; password
    match and the eye button.
  - **Step 3:** the state → city lookups; digits-only mobile; the required
    address.
  - **Review:** the right values; "Edit Credentials" goes back.
  - **Submit:** posts all the fields; a server error jumps to step 2.
  - **Placement off:** sends an empty parent/position, and success resets
    the wizard.
  - **Lookup down:** state and city become text boxes.
  - **Sidebar:** the highlight is correct.

### Step 85: Ad Center in React; every member page is React (5c-4)

**Ad Center (`/user/affiliate/ads`, `Pages/User/Affiliate/Ads.tsx`).**

The controller sends one display-ready row per ad: title, caption, kind,
the member's tracked link, media / poster / thumbnail URLs, MIME type, a
download file name, the product it opens, and this member's visits and
paid orders from it. It also sends the four stats and the channel icons
from `AdShare::CHANNEL_ICONS`, so the server and the page use one list.

The 300 lines of inline script are now React. The behaviour is the same:

- **Share picture + link** uses the share sheet with the file. Downloaded
  files are cached, so the second tap shares at once (iPhones refuse a share
  that starts after a download). Where files can't be shared, the button
  saves the file and copies the caption instead.
- **Send the link:**
  - **WhatsApp** opens `wa.me` with the caption and link.
  - **Facebook** opens the sharer and copies the caption, because Facebook
    ignores pre-filled text.
  - **X** cuts the caption to 230 characters.
  - **Telegram** opens `t.me/share`.
- **Upload the picture / video.** Instagram, TikTok and YouTube (YouTube for
  video only) use the share sheet on phones. On computers they download the
  file and show a short guide.
- **Copy link** and **Download** work as before.
- **Logging:** every action is logged in the background to
  `POST /ads/{id}/share`, with the CSRF header.
- **The editable caption** stays per ad.
- **The full-size viewer** is a native `<dialog>`:
  - tap to zoom, never smaller than the real size;
  - drag to pan;
  - ← / → and the arrows move between ads;
  - Esc or an outside click closes it;
  - the page does not scroll behind it.

The `adc-*` CSS was already in `custom.css`.

**Every member page is React now.** `DashboardLayout::BLADE_ROUTES` holds only
`user.logout`: signing out ends the session and lands on the Blade login
page. The affiliate dashboard's Ad Center button is a single-page link.

**Removed:**

- **`partials/dashboard_affiliate.blade.php` and
  `dashboard_stockist_main.blade.php`.** No Blade page under
  `user.affiliate.*` or `user.stockist.*` is left. `layouts/master.blade.php`
  now always includes the member sidebar. It is only used by the payment
  gateway pages and by the old copies of migrated pages kept for rollback.
- **`user/affiliate/ads.blade.php`.** The `user/affiliate` view folder is now
  gone.

**Verification:**

- **Server (`p5c_check.php`):**
  - **Stats:** 2 active ads, 1 ad shared (distinct), 2 visits and 1 paid
    order from ad links.
  - **Per-ad rows:** visits/orders are correct, the link carries the
    member's code, and the file names come from the title.
  - **Sidebar:** Ad Center is highlighted.
  - **Share log:** a known channel → 200; an unknown channel → 422; an
    inactive or missing ad → 404.
- **Browser (`phase5c4.mjs`, 20 checks):**
  - **Page:** the highlight, the stats and the per-ad pills; YouTube only
    on the video.
  - **Main button:** reads "Save picture" where files can't be shared.
  - **Sharing:**
    - Copy link → clipboard, toast, and a logged `copy` with CSRF;
    - WhatsApp → the edited caption plus link;
    - Facebook → the sharer, plus the caption copied;
    - X → cut to 230 characters with "…";
    - Telegram;
    - Instagram on a computer → the guide plus "Open Instagram", which
      closes;
    - save + copy → a logged `download`.
  - **Viewer:** "1 / 2"; zoom; next (the video), with the zoom reset; wrap
    round; Esc unlocks scrolling.
  - **Links:** the dashboard ⇄ Ad Center links are both Inertia visits.

**Phase 5c is done.** All suites: **506 browser checks, 0 failures**. That is
429 + 14 (5c-1) + 17 (5c-2) + 26 (5c-3) + 20 (5c-4).

- **`phase5a` changed:** its "full page load" check now uses Sign Out,
  the only plain menu link left.
- **Also passing:** `route:cache` and `view:cache` work, and the route-name
  scan is clean (only the 21 known `ipn.` false positives).

**What is still Blade in the user area:** the automatic payment gateway pages
(Stripe, Paystack…), on purpose (Phase 3), and the auth pages (5d).

### Step 86: Login, password reset and verification in React; security fixes (5d-1)

**New layout for guest pages: `Layouts/AuthLayout.tsx`.** It is the green
gradient with one centred card, the design of the old login page
(`layouts/app1.blade.php`). Its CSS moved to `Layouts/AuthLayout.css`,
scoped under `.auth-shell`.

- **Opting in:** a page sets `Page.layout = withAuthLayout`, which replaces
  the default dashboard layout from `app.tsx`.
- **No dashboard data:** `HandleInertiaRequests` sends no `layout` prop on
  these routes (`GUEST_PAGES`). An unverified member has no dashboard yet.
- **Fonts:** Inter and Courier Prime load with plain `<link>` tags in the
  root view. A CSS `@import` that the browser cannot load (blocked Google
  Fonts) makes Chromium reject the *whole* stylesheet. Vite then throws
  "Unable to preload CSS", and the page never draws.

**Pages:**

| Page | Replaces |
|---|---|
| `Auth/Login` | `user/auth/login.blade.php` (+ `layouts/app1`) |
| `Auth/ForgotPassword` | `passwords/email.blade.php` |
| `Auth/VerifyCode` | `passwords/code_verify`, `authorization/email`, `sms`, `2fa` (four near-copies) |
| `Auth/ResetPassword` | `passwords/reset.blade.php` |
| `Auth/Banned` | `authorization/ban.blade.php` (full screen, its CSS scoped to `.page-banned`) |

**Shared pieces:**

- **`Components/VerificationCode`:** the six-box code input. It takes
  digits only and submits by itself on the sixth digit, as before. Its CSS
  is a copy of `assets/global/css/verification-code.css` without the global
  `.form-control:focus` rule. The input is also clipped inside its own box:
  it is wider than the box on purpose, and focusing it used to scroll the
  whole card sideways.
- **`Components/Captcha`** with **`App\Support\CaptchaPresenter`:** the
  Google reCAPTCHA 2 site key and a freshly drawn custom captcha (digits,
  tilt and the signed answer). These are data now, not the HTML that
  `App\Lib\Captcha` prints. It sends the same fields
  (`g-recaptcha-response`, `captcha`, `captcha_secret`), so
  `verifyCaptcha()` is unchanged.

**Full page load after login and verification.** The next stop can be a
Blade page: the registration payment page `user.data`, or the page the
member first asked for. So `LoginController::authenticated()` and
`AuthorizationController::leave()` answer with `Inertia::location()`, a 409
plus `X-Inertia-Location`. The browser then loads the next page normally.

**Fixed:**

- 🔴 **Password reset could take over any account with a pending reset.**
  `ResetPasswordController::reset()` looked the reset up by the 6-digit code
  alone and ignored the email. Codes never expired, and nothing limited
  guesses. Now:
  - a code works only for the email this browser started the reset with;
  - it expires after 60 minutes (`ForgotPasswordController::CODE_MINUTES`);
  - 5 wrong guesses cancel it;
  - it is deleted once used;
  - the routes are throttled.

  The hidden `email` field is gone.
- 🔴 **The same flaw in the admin password reset**, with worse
  consequences: anyone could request a reset for the admin email and
  then guess. `Admin\Auth\ForgotPasswordController::validReset()` now:
  - requires the session's email, an enabled status and an age under 60
    minutes;
  - disables older codes when a new one is sent, and all of them after a
    change;
  - is backed by throttled routes.

  The admin pages themselves stay Blade (Phase 7).
- **Unlimited guesses on 6-digit codes.** Email / SMS verification and 2FA
  at login are now `throttle:10,1`, and resending is `throttle:6,1`.
- **Login with a captcha switched on was impossible.** `login()` always
  called `verifyCaptcha()`, but the login page never showed a captcha.
  Login and Forgot Password now render it.
- **Logout wrote the whole session to the log.** It logged
  `session()->all()`, CSRF token included, before and after every logout.
  Removed.
- **No User-Agent header → server error.** `ClientInfo::osBrowser()` read
  `$_SERVER['HTTP_USER_AGENT']` directly. Requests without that header
  (some bots, scripts, health checks) crashed the login. It now falls back
  to "".
- **Redirect loop for section-2 members.** `RegistrationStep` and
  `userData()` sent them to `user.data1`, which ran the same method and
  redirected to itself. `user-data1` now just redirects to `user-data`.
  It is one of the `*1` duplicates Step 16 left for this phase.
- **Removed:**
  - **`POST /user/user-data-submit` and `userDataSubmit()`:** no page posted
    there, and the method could not run; `$user_parent_matrix` was only set
    when a parent was given.
  - **`login1.blade.php`:** unused.
  - **The template's `partials/verification_code`.**

**Still Blade on purpose:** `user/user_data.blade.php`, the registration
**payment** page (Paystack inline checkout, or an activation pin). Like the
other gateway pages (Phase 3), it posts to the IPN routes.

**Verification:**

- **Server (`p5d_check.php`):**
  - **Login:**
    - the page has no dashboard layout, and the captcha config is off;
    - a wrong password → a field error;
    - a right password (by email) → 409 to the dashboard, with the login
      log written.
  - **Verification pages:**
    - email verification shows the masked address and resend in 90 s;
    - a wrong code → error; the right code → 409 and `ev=1`;
    - the 2FA page has no resend link;
    - the ban page shows the reason.
  - **Password reset:**
    - **Requesting a code:** an unknown account is refused; a known one
      gets a code and goes to the code page.
    - **Wrong codes:** another account's valid code fails from this
      session, and the 5th wrong code cancels the reset.
    - **Expiry:** a 61-minute-old code fails.
    - **Right code** → the reset form.
    - **Reset:** "reset with Ada's code" leaves Ada's password unchanged;
      a mismatched confirmation is refused; a proper reset works and
      deletes the code; reusing the code fails.
  - **Admin:**
    - only the newest code is enabled, and an older one fails;
    - the code without the reset session fails, and the password is
      unchanged;
    - the proper flow changes it and disables every code.
  - **Throttles** are on all five POST routes; `/user/user-data1`
    redirects; `user-data-submit` is gone.
- **Browser (`phase5d1.mjs`, 21 checks):**
  - **Login:**
    - the card renders with no sidebar; no captcha or sign-up link while
      they are off;
    - the eye button works;
    - a wrong password → error, password cleared;
    - success → a full load of the dashboard via `X-Inertia-Location`;
    - with a custom captcha it shows 6 tilted digits and sends
      `captcha` + `captcha_secret`.
  - **Forgot → code page** as an Inertia visit.
  - **Code box:** digits only; auto-submits at 6 digits; an error clears
    the box.
  - **Reset form:** the secure-password rules tick as you type; a
    mismatched confirmation disables Submit; it posts
    `{token, password, password_confirmation}`.
  - **Email verification:** a countdown, then "Try again" (an Inertia GET
    to resend); the right code → a full load of the payment page.
  - **2FA** has no resend link.
  - **Banned:** full-screen card with no dashboard.

### Step 87: Register in React; Phase 5 done (5d-2)

**What changed.**

- **`RegisterController@showRegistrationForm`** renders `Auth/Register`, the
  same two-panel, four-step page. Its CSS moved to `Pages/Auth/Register.css`,
  scoped under `.page-register`.
- **The country → state → city lookup** moved to `lib/geo.ts`
  (`useGeoLookup`). Register and Add Distributor share it. When the free
  lookup service is down, both pages offer text boxes.
- **Live checks** use one throttled guest endpoint,
  `GET /user/register/check` (`throttle:30,1`). It answers:
  - is the username taken;
  - is the email or mobile already used? A used email opens the "Account
    Already Exists" dialog, as before;
  - is the referral an active member;
  - does the parent exist, and which slots are free? A taken slot is
    disabled.

  It replaces four endpoints, three of which sent back HTML:
  - `POST /check/referral`;
  - `POST /check/matrixParent`;
  - `POST /get/user/position`;
  - `POST /user/check-user`.

  The matching `SiteController` methods are deleted. Nothing else used
  them; the admin's `checkUser` class is only a CSS class.
- **After signing up**, the member is logged in (with a new session id) and
  sent on with `Inertia::location` to the Blade payment page `user.data`.

**`register()` cleaned up:**

- **Form checks are validation rules now:**
  - the username, referral and parent formats;
  - `position` required with a parent;
  - `agree` → `accepted`.

  They used to be hand-written checks after validation, answered with
  toasts. The rules themselves are the same.
- **Passwords are no longer flashed to the session.** Every refusal used
  `withInput($request->all())`.
- **Refusals throw `DomainException` inside one `DB::transaction()`:**
  unknown referral, tree crossing, taken slot, unknown parent. The old
  early `return back()` calls inside `DB::beginTransaction()` left the
  transaction open. Unexpected errors are reported with `report($e)`.
- **Registration switched off** now says so. It used to answer with a bare
  redirect and no message.
- **Deleted:**
  - `create()`, which could not run: it used `$request`, `$user` and
    `$user_parent_matrix`, none of which exist there;
  - `registered()`, which was never called because `register()` is
    overridden. Its intent was a `UserExtra` row and `updateFreeCount()`
    after sign-up; that has never happened in production. ⚠️ **Owner:**
    decide whether sign-up should do it. (Decided in Step 88.)
  - `checkUser()`.
- **Duplicate page:** `GET /user/signup` (`user.register1`) redirects to
  `/user/register`, keeping `?ref=`. This is the last `*1` duplicate from
  Step 16.
- **Deleted views:** `register.blade.php`, plus `registration_online` and
  `registration_try`, which nothing referenced. The whole `user/auth` view
  folder is gone.

**Verification:**

- **Server (`p5d2_check.php`):**
  - **Page:**
    - `?ref=demo` → referrer fixed; an unknown or inactive referral →
      back to home with an error;
    - 242 countries, Nigeria first;
    - `signup?ref=demo` → `register?ref=demo`.
  - **Check endpoint:** answers each field; the four old endpoints are
    gone.
  - **Refused:**
    - validation errors for a username with a space or too short, a
      parent without a position, a parent with a dash, and terms not
      ticked;
    - toasts for an unknown referral, tree crossing, a taken slot, and
      registration switched off.

    Nothing is created, and no password is in the session.
  - **Valid sign-up** → 409 to the dashboard. `ngozi_1` is created under
    demo, with bola/right recorded and a pending rmatrix (bola, right),
    and logged in.
  - **No referral** → sponsor #1 and no parent.
- **Browser (`phase5d2.mjs`, 19 checks):**
  - **Step 1:** a `?ref` link fixes the sponsor; a typed referral is
    looked up, and an unknown one blocks Continue; the parent check marks
    the left slot taken; a parent without a position is refused.
  - **Step 2:** the username check; the "Account Already Exists" dialog;
    the strength meter (Weak → Strong).
  - **Step 3:** the state → city lookups; a digits-only mobile.
  - **Review:** the right values; Submit stays disabled until the terms are
    ticked.
  - **Submit:** posts every field with `agree=1`; a server error jumps to
    step 2 with the passwords cleared; success → a full load of the payment
    page.
  - **Registration off:** the notice shows and the form is disabled.

**All suites: 546 browser checks, 0 failures.** That is 506 + 21 (5d-1) +
19 (5d-2). In `phase5c4` the Escape check now waits for the dialog's close
event: it fires a moment after `open` turns false, which made the check
fail now and then. `route:cache` and `view:cache` work, and the route-name
scan is clean.

**Phase 5 is complete.** Every user-area page is React, apart from the
automatic payment gateway pages and the registration payment page
(`user_data`), which stay on Blade on purpose. Signed-in navigation is fully
single-page.

### Step 88: Decision: every member gets a `user_extras` row; `updateFreeCount()` stays off

**Owner's decision (Step 87 follow-up):** "yes add it", meaning the
`registered()` step the old sign-up never ran.

**What that step did:**

- create the member's `user_extras` row (BV and left/right counters);
- call `updateFreeCount()`.

**Done: the `user_extras` row.** `User::booted()` now creates it whenever a
user is created. That covers every path: sign-up, Add Distributor, and an
admin creating a member. Migration `2026_10_26_000001_backfill_user_extras`
adds the missing rows for existing members, in chunks of 1,000. It is safe
to run twice and keeps existing rows untouched.

This matters beyond the counters. `admin/users/detail.blade.php` reads
`$user->userExtra->bv_left` with no null check, so the admin user page
failed for every member without a row: everyone who signed up through this
site.

**Not done, on purpose: `updateFreeCount()` on sign-up.** It walks the
*original script's* tree (`users.pos_id`), not this app's tree
(`matrices`). Two problems:

- **Wrong member credited:** sign-up and Add Distributor set `pos_id = 1` for
  every new member. Each sign-up would therefore add to member #1's "free"
  count, whatever the real placement.
- **Endless loop:** if member #1's own `pos_id` is 1, the loop never ends.
  The sign-up request hangs, and keeps writing, until it times out.

A new member also has no place in the tree yet: placement happens when they
pay (`user.data` → Paystack). If free/paid leg counts are wanted, they should
be computed from `matrices` at payment. That is a separate change for the
owner to request.

**Hardening.** `updateBV()` (used by Admin → Supply), `updateFreeCount()` and
`updatePaidCount()` share the same `pos_id` walk. Now they:

- **stop on a repeat:** they remember the members they visited, so a cycle
  cannot loop forever;
- **create a missing row:** a missing `user_extras` row is created
  (`firstOrCreate`) instead of crashing on `null->bv_left`.

**Verification:**

- **Backfill:** 1 → 5 rows, one per member; demo's existing `bv_left` is
  kept; a second run changes nothing.
- **Sign-up:** a new member gets a row.
- **Walkers:** `updateFreeCount()` on a `pos_id` cycle returns in 0.007 s
  (it used to never return); `updatePaidCount()` with an upline that does
  not exist returns cleanly.

## Part 3f: Phase 6 change log (public pages)

### Step 89: Public layout and the Terms page in React (6a)

**What "public pages" means in this app.** The survey for Phase 6 found
that the marketing site is switched off:

- `SiteController@index`, `pages`, `faq`, `contact` and `policyPages` all
  start with `return to_route('user.login');`.
- So the home, blog, FAQ, contact and policy views are never shown.

The public pages people actually see are:

- the **affiliate shop** (`/shop/...`), where guests buy through affiliate links;
- the **Terms of Use** page (`/agreement`), linked from sign-up and the shop footer;
- the **maintenance** page.

**New: `Layouts/PublicLayout.tsx`.** It holds the shop header (logo, search,
account menu, cart badge, category bar) and the footer. It replaces
`layouts/shop.blade.php`, `partials/shop_header.blade.php` and
`partials/shop_footer.blade.php`.

- **Same markup and classes,** so the existing `shophd-*` / `shopft-*` styles
  in `custom.css` apply unchanged.
- **Opting in:** pages use `Page.layout = withPublicLayout`, the same way the
  auth pages use `withAuthLayout`.
- **Its data:** a new shared prop, `publicLayout`, built by
  `App\Support\PublicLayout::for()`. `HandleInertiaRequests` fills it only
  on `PublicLayout::ROUTES` (`shop.*`, `agreement`). On those routes the
  dashboard `layout` prop is `null`, so a guest never reaches
  `DashboardLayout::for()`.
- **What Bootstrap's JavaScript used to do:**
  - the account menu opens with React state and closes on an outside click
    or Escape;
  - the "stuck" shadow under the header is a scroll listener;
  - neither needs jQuery now.
- **Sign Out stays a plain `<a>`:** it ends the session, as in the dashboard.

**Terms page: `Pages/Public/Agreement.tsx`.**

- The text is the same as before. It is now a list of sections in the
  file, and the contents box is built from that list, so it can no longer
  disagree with the headings.
- **Fixed:** the contents list called section 8 "Dispute resolution" (a
  copy of 7) and section 3 "Purpose of the Platforme".
- **Page CSS:** the old view's CSS was pushed to a stack the layout never
  printed (`css-styles`), so it never applied. The page now has
  `Agreement.css`. Contents links stop below the sticky header
  (`scroll-margin-top`).

**Duplicate removed: Cookie Policy.**

- `cookie.blade.php` and `agreement.blade.php` were the same file apart
  from one typo. Both showed the Terms of Use, under the same title.
- `/cookie-policy` now answers `301 → /agreement`, the route name
  `cookie.policy` still works, and the footer's second link to the same text
  ("Cookie Policy") is gone.
- Deleted: `cookie.blade.php`, `agreement.blade.php`.

### Step 90: The public shop in React (6b)

**Pages:** `Pages/Shop/{Index, Product, Cart, Checkout, Pay, OrderSuccess}.tsx`.

**Shared pieces:** `Components/Shop/ShopCard.tsx` (the product tile) and
`Components/Shop/useAddToCart.ts`.

**Server:**

- **Presenter:** `App\Support\PublicShopPresenter` builds what the pages
  show. The member shop's `ShopPresenter` prices by state and links to
  member pages. The public shop uses one flat price (`Product::shop_price`)
  and the tokenised image URL (`shop.image`), which hides file names.
- **Orders list:** `AffiliateController::presentOrder()` now uses the same
  `PublicShopPresenter::orderStatus()` instead of its own copy of the
  colour table.

**How the cart works now.**

- The old pages called the cart endpoints with `fetch`, read JSON back, then
  ran `window.location.reload()` (cart page) or edited the header badge by id
  (`cart_script.blade.php`).
- Now `ShopCartController` `add` / `update` / `remove` answer
  `back()->withNotify(...)`. Inertia follows the redirect and gets fresh
  props: the cart lines, the totals and the header's cart count. The toast
  comes from the normal `notify` prop.
- Nothing else called those JSON endpoints.

**The pay step has its own GET page.**

- **Before:** the payment page was the *answer to the checkout POST*.
  Refreshing it re-sent the form and created a second order, which held
  stock a second time.
- **Now:** the POST redirects to `GET /shop/order/{code}/pay`
  (`shop.order.pay`).
  - It is safe to reload or bookmark.
  - It sends a paid or cash order on to the order page.
  - It loads Paystack's inline script itself, once; if that fails it shows a
    toast and keeps "Pay Now" disabled.
- **After paying:** the browser does a normal page load of the callback,
  exactly as before. The server still verifies the payment with Paystack.

**Bugs fixed on the way:**

| Before | Now |
|---|---|
| Choosing "Pay Online" while Paystack had no public key: the order was saved, its stock held and the cart emptied; *then* the buyer was sent to an empty checkout. | Checked **before** the order is created: a toast, and the cart is kept. |
| A failed Paystack verification sent the buyer to `/shop/checkout`, which was empty (the cart had become the order), so they bounced to an empty cart. | Back to the order's pay page with the message, so they can try again. |
| The order page said "Your payment was received" for an online order that was never paid (pop-up closed). | "Payment Not Completed" with a Pay Now button; no "invoice emailed" line until it is true. |
| Checkout said "Final total is calculated for your selected state". The total never depends on the state (`ShopCartService::resolve()`). | "Prices are the same in every state. Your state tells us where you will collect your order." |
| "Add" could grow one cart line past 50 (the limit `update` enforced) by adding again and again. | `ShopCartService::MAX_QUANTITY` (50) caps `add` too; one constant is used by the rules and the pages. |
| The cart page allowed + past the stock; the server quietly showed the stock instead. | Each line carries its own `max` (stock, up to 50), and + stops there. |
| Errors and messages used `->with('error')`, which only the checkout view printed. | `withNotify`, the toasts every other page uses. Checkout errors show under their own field. |

**Kept from the old pages:**

- the honeypot field on checkout;
- the throttles on the cart and checkout routes;
- last-click affiliate tracking (`affiliate.track` still runs on every
  `/shop` request; React visits drop `?ref=`, so they never add clicks).

**Still Blade, on purpose:**

| View | Why |
|---|---|
| `shop/ad_preview.blade.php` | Read by link-preview bots (Facebook, WhatsApp…), which do not run JavaScript. People are redirected and never see it. |
| `shop/pdf/invoice.blade.php` | Rendered to a PDF by DomPDF (download and email attachment). |
| `maintenance.blade.php` | Shown while the site is down for maintenance; it should depend on as little as possible. |
| `home`, `pages`, `faq`, `contact`, `policy` and `layouts/frontend` | Switched off by the owner (they redirect to login). Left untouched so they can be switched back on. |

**Deleted:**

- `shop/{index,product,cart,checkout,pay,success}.blade.php`;
- `shop/partials/cart_script.blade.php`;
- `layouts/shop.blade.php`;
- `partials/shop_header.blade.php` and `partials/shop_footer.blade.php`.

### Step 91: Fix: no `position: sticky` worked on any React page

**The rule.** `dashboard.css` (copied from the old master layout) started
with:

```css
html, body { overflow: auto !important; ... }
```

**What it broke.** Setting `overflow` on `<html>` stops the browser from
handing the body's overflow to the window. The `<body>` then becomes a
scroll box of its own, and that box never scrolls. Sticky elements stick
inside the box that scrolls, so they never stuck:

- the dashboard top bar (`.bank-topbar`);
- the cart summary;
- the new shop header.

The `!important` also beat two scroll locks:

- **the mobile sidebar,** which sets `body.style.overflow = 'hidden'`, so the
  page scrolled behind the open menu;
- **the Ad Center viewer,** which adds `html.adc-noscroll`.

**Fix.** The `overflow` part of the rule is removed (padding and margin
stay). `main.css` still hides sideways scrolling on the body, which now
applies to the window as intended.

**Tests.** New checks for the sticky top bar and the sidebar scroll lock fail
with the old rule and pass with the new one.

### Step 92: SEO without SSR, and verification for Phase 6

**Why no server-side rendering.** The roadmap left the choice open: keep
public pages on Blade, or turn on Inertia SSR. Neither was needed:

- **The pages that matter for search** (home, blog, FAQ) are switched off.
- **The shop's real need is link previews:** an affiliate shares a product
  link on WhatsApp or Facebook. Previews only need the `<head>` of the
  first HTML response.
- **How it works:** `ShopController@show` passes `pageTitle`,
  `seoContents` (description, social title, social description) and
  `seoImage` (the product photo) to the root view with `withViewData()`.
  `partials/seo` prints them as `<title>`, `description`, `og:title`,
  `og:description` and `og:image`.
- **Pages that do not pass them** keep the site-wide SEO settings.
- **Shared ads** already have their own preview page (`ad_preview`).
- **The cost of SSR** would be a Node process next to PHP on the server, for
  no visible gain today.

**Server checks** (`p6_check.php`, SQLite fixture):

- **List:** filtered by category and by search. `publicLayout` shows the
  categories, the highlight and a cart count of 0; the dashboard `layout` is
  `null`.
- **Product page:** the first HTML has the product's `<title>`,
  `description`, `og:title`, `og:description` and `og:image` (the
  `/shop/img/` URL). An inactive product → 404.
- **Cart:**
  - add ×2 works; ×60 is refused (max 50);
  - an out-of-stock product → "currently unavailable";
  - update to 9 with stock 3 shows 3;
  - add ×50 more caps the line at 50;
  - remove works.
- **Checkout:**
  - missing fields and a filled honeypot are refused;
  - Paystack without a key: refused, the number of orders unchanged (4 → 4),
    the cart kept;
  - a Paystack order redirects to `/shop/order/{code}/pay`, whose props
    hold only the gateway's public key, never the secret key;
  - the order page shows "awaiting payment" with a pay link;
  - the pay page for a paid order redirects to the order page;
  - a cash order goes straight to the order page; with cash on pickup
    switched off it is refused.
- **Callback:** an unknown reference → shop with a toast; no secret key →
  the order page with a toast.
- **Old URLs and shared presenter:** `/cookie-policy` → 301 to
  `/agreement`; the affiliate orders list still gets its status badge from
  the shared presenter.
- `route:cache` and `view:cache` succeed. The route-name scan finds only the
  21 known `ipn.` references.

**Browser checks** (`e2e/phase6.mjs`, 55 checks):

- **Header:**
  - categories, with "All Products" highlighted only on the list;
  - the account menu opens, and closes on an outside click or Escape;
  - the "stuck" shadow appears on scroll.
- **Add to cart:** one POST with the CSRF header; the header badge updates
  with no page reload.
- **Search:** trims the text and keeps it in the box.
- **Product page:** the quantity stays between 1 and the stock.
- **Cart:**
  - +, typed + Enter and remove send the right requests;
  - + is disabled at the stock;
  - an emptied box sends nothing.
- **Checkout:** errors show under their fields; the honeypot is sent empty.
- **Pay page:** Paystack opens with the key, email, amount in kobo and
  reference; a finished payment does a full load of the callback; if
  Paystack can't load, a toast shows and Pay Now stays disabled.
- **Order page:** Copy Code works; Download Invoice is a plain link.
- **Terms page:** 8 contents entries; links land below the sticky header.
- **Members:** see "Hi, Ada"; Sign Out is a full page load.
- **Phone width:** no page scrolls sideways at 375 px.
- **Step 91:** the dashboard top bar sticks, and the open mobile sidebar
  locks the page.

**Regression:** all 21 browser suites pass, 601 checks in total.

## Part 3g: Phase 7 change log (admin panel)

### Step 93: The admin panel's frame in React (7a)

**The survey.**

- **185 Blade views** in `resources/views/admin`.
- **The frame** is `admin/layouts/master` + `app`, with:
  - the sidebar `partials/sidenav.blade.php`, built from
    `partials/sidenav.json`;
  - the top bar `partials/topnav.blade.php` (search, notifications, profile
    menu).
- **Access:** every admin route goes through `AdminAccessMiddleware`
  (deny by default, `config/admin_access.php`), and the sidebar hides
  links the admin may not open.

**New root view: `resources/views/inertia/admin.blade.php`.**

- It loads the same stylesheets as the Blade master layout (Bootstrap,
  Font Awesome, Line Awesome, `assets/admin/css/app.css`, Poppins) and the
  React app.
- No jQuery, Bootstrap JS, select2, nicEdit, cuModal or `admin/js/app.js`:
  React pages do those jobs themselves.
- **How it is chosen:** `HandleInertiaRequests::rootView()` picks it for
  `admin.*` routes; everything else keeps `inertia.user`.

**New shared prop: `adminLayout`.**

- It is built by `App\Support\AdminLayout::for()`, only on `admin.*` routes.
  There the member `layout` prop is `null`.
- **Sidebar:** from the same `sidenav.json`, with the same rules as the Blade
  sidebar:
  - only links `AdminAccessMiddleware::allows()`;
  - empty groups are dropped;
  - a section header moves to the next visible entry;
  - per-entry `roles`;
  - the same counters.
- **Counters:** `AdminLayout::counters()` is now the one copy. The Blade
  view composer uses it too. A second composer, for a view that does not
  exist (`admin.partials.sidenavs`), was deleted.
- **Top bar:** the 10 newest unread notifications and the unread count, the
  admin's name and photo, and the links.
- **`AdminLayout::REACT_ROUTES`** lists the admin pages that are React.
  Links to them are Inertia visits; every other admin link stays a full
  page load, because those pages are still Blade.

**New layout: `Layouts/AdminLayout.tsx`.**

- **Same markup and classes** as the Blade sidebar and top bar.
- **The jobs `admin/js/app.js` and Bootstrap did:**
  - groups open and close;
  - the group of the current page is open;
  - the mobile sidebar opens, and closes with ×, a tap outside or a visit;
  - the bell and profile menus close on an outside click or Escape;
  - the current link is scrolled into view.
- **Invalid HTML fixed:** the top-bar icons were an `<a>` inside a
  `<button>`; they are plain links now.
- **Chosen in `app.tsx`:** every page under `Pages/Admin/` gets this layout.
  It is loaded with `import()`, so its JS and CSS reach the admin panel
  only. A static import would have put the admin CSS into the bundle every
  member page loads.

**Search box.**

- **Before:** `admin/js/search.js` searched a list printed into every admin
  page. That list held **every admin route URL** plus every menu and
  settings entry, including pages the admin could not open (which then
  answered 403).
- **Now:**
  - the list comes from a new endpoint, `GET /admin/search-index`
    (`admin.search.index`, open to any logged-in admin);
  - it is fetched once, the first time the box is used;
  - it is filtered by the same `AdminAccessMiddleware::allows()` as the
    sidebar.
- **Keyboard:** ↑ / ↓ move, Enter opens, Escape closes.

**Duplicates removed from the menu** (`sidenav.json`; the Blade sidebar
reads the same file):

- **"Affiliate"** repeated four links of "Affiliate Program" (same routes).
- **"Ad Manager"** repeated "Ad Materials & Media" (`admin.ads.index`). Its
  search keywords moved to the remaining entry.

**Bug fixed: three groups never opened on their own pages.** "Shop",
"Affiliate Program" and "Savings & Loans" used `"a*|b*"` as their active
pattern. `routeIs()` does not understand `|`, so the group stayed closed
on its own pages. They are lists now. For example, on `/admin/ads` the
Blade sidebar now opens Affiliate Program and highlights Ad Materials &
Media.

### Step 94: First admin pages in React: Profile and Notifications (7a)

**Profile: `Pages/Admin/Profile.tsx`.**

- **Duplicate merged:** Profile and Password Setting were two pages with the
  same profile card and one form each. They are one page now.
- `/admin/password` (route name kept) redirects to it. The top bar's
  "Password" link opens `/admin/profile#password`.
- **Photo:** a new `Components/Admin/ImageUploader.tsx`, the React version of
  `<x-image-uploader>`, shows a preview of the picked file. The form is sent
  as multipart.

**Fixes:**

| Before | Now |
|---|---|
| Two admins could save the same email; password-reset codes are looked up by email. | `unique:admins,email` (ignoring the admin's own row). |
| New admin password: 5 characters minimum. | 8. It only applies to the next change. |
| Wrong current password: a toast, and the typed passwords stayed in the form. | An error under "Current Password"; all three boxes are emptied after every submit. |

**Notifications: `Pages/Admin/Notifications.tsx`.**

- **Actions:**
  - open one, delete one;
  - mark all as read;
  - delete all, which asks first in `ConfirmDialog` (the same dialog as the
    member pages).
- **After each action** the props come back, so the bell count updates
  without a page load.
- **Opening a notification** stays a full page load: it marks it read and
  goes to its page, which is usually still Blade.

**Shared admin components:**

- `Components/Admin/PageHeader.tsx`: the title row, with action buttons (the
  old `breadcrumb-plugins` stack); it also sets the tab title.
- `Components/Admin/ImageUploader.tsx`.

**Deleted:** `admin/profile.blade.php`, `admin/password.blade.php`,
`admin/notifications.blade.php`.

**Verification:**

- **Server checks** (`p7_check.php`):
  - **Super-admin:** the full sidebar (counters, alerts, headers), a
    100-entry search index (95 after the duplicates were removed), and the
    admin root view on a full load.
  - **Admin with only "manage account":** sidebar and search show
    Dashboard only.
  - **Profile:** the email check, the 8-character rule, the wrong current
    password, the password change and `/admin/password` → 302.
  - **Notifications:** delete one, read all (the bell count goes to 0) and
    delete all.
  - **Member pages:** still get `inertia.user` and no `adminLayout`.
  - **Blade sidebar,** rendered with the edited JSON: no duplicates, and
    Affiliate Program opens on `/admin/ads`.
- **Browser checks** (`e2e/phase7a.mjs`, 43 checks):
  - **Sidebar:** groups open and close; Blade pages get a full load.
  - **Bell:** count, menu, outside click; "View all" is an Inertia visit.
  - **Profile menu:** Password opens the profile page at `#password`;
    Logout is a full load.
  - **Search:** loaded once; found by keyword and title; ↓ + Enter; no
    results; Escape.
  - **Limited admin:** sees Dashboard only.
  - **Profile:** multipart upload with a preview; server errors in place;
    the password boxes are emptied.
  - **Notifications:** confirm and delete, mark all read, delete all, and
    "No" sends nothing.
  - **Phone width:** the sidebar opens and closes, and no page scrolls
    sideways at 375 px.
- **Regression:** all 22 browser suites pass, 644 checks in total.
  `route:cache` and `view:cache` succeed, and the route-name scan finds
  only the 21 known `ipn.` references.

### Step 95: Reports and logs in React (7b-1)

**Pages** (`Pages/Admin/Reports/`):

| Page | Routes |
|---|---|
| `Transactions.tsx` | `admin.report.transaction` and the three commission logs (`referral.commission`, `stageOut.commission`, `binary.commission`): one table, one remark each |
| `Logins.tsx` | `admin.report.login.history`, `admin.report.login.ipHistory` |
| `NotificationHistory.tsx` | `admin.report.notification.history` and `admin.users.notification.log` (one member) |
| `Investments.tsx` | `admin.report.invest` |
| `BvLog.tsx` | `admin.report.bvLog` (`?type=leftBV`, `rightBV`, `cutBV`, `paidBV`) |

**Shared admin components** (`Components/Admin/`), for the rest of Phase 7:

| Component | Replaces |
|---|---|
| `DataTable` | The card + `table--light style--two` + page links. Every cell gets `data-label`, which app.css uses to show rows as cards on phones; `admin/js/app.js` used to add it with jQuery. |
| `FilterCard` | The filter card, and on phones the "Filter" button that folds it (was jQuery `slideToggle`). It also shows the server's "Invalid date format" error. |
| `DateRangeField` | The jQuery date-range picker (moment.js + daterangepicker.js): two date inputs and the same quick ranges (Today … This Year). |
| `SearchForm` | `<x-search-form>` in the title row. It can keep other query values (the BV log keeps `?type=`). |
| `Cells` | `UserCell` (name + @username: filters the list, or opens the member page), `WhenCell` (date + "2 days ago"), `BadgeCell`. |
| `StatWidget` | `<x-widget style="2">`. |

`App\Support\AdminPresenter` formats the same pieces on the server: user,
date, badge, and the date filter turned back into the inputs' values.

**Date filter (`App\Lib\Searchable::dateFilter`).**

- **The problem:** it split the value on every `-`. An ISO range such as
  `2026-10-01 - 2026-10-26` became six pieces.
- **Now** it splits on ` - ` first. A single ISO day also works. The old
  formats still work: `October 01, 2026 - October 26, 2026` from the old
  picker, and `2026/10/19 - 2026/10/26` from the partner dashboard's links.
- `AdminPresenter::dateRange()` reads all of them, so a link from a Blade
  page fills the React date inputs.

**Duplicates removed:**

- **`reports/pvLog.blade.php`** was a copy of `bvLog.blade.php` with one
  word changed, and nothing used it. Deleted. The PV log is
  `admin/pv-log` (7b-2).
- **The member's "Notifications" page** (`admin.users.notification.log`)
  rendered the Notification History view for one member. It now shares the
  React page and one builder, `ReportController::notificationPage()`. The
  route stays because it needs a different permission ("view users" instead
  of "Report").

**Fixes:**

| Before | Now |
|---|---|
| **Security.** The email detail opened in a same-origin frame that printed the stored email HTML unescaped. Emails can contain text a member typed (name, ticket subject); a `<script>` in it would run with the admin's session. | The frame has `sandbox` (no scripts, no access to the admin page), and the detail response sends `Content-Security-Policy: script-src 'none'`. Checked: a script in the email does not run. |
| SMS and push messages were inserted into the dialog as HTML. | Shown as text, line breaks kept. |
| The commission logs showed a Date box that did nothing (their queries had no date filter). | The date filter applies. |
| Login History and Notification History had no filters, though their queries read `?search=` and `?date=` (a member's page linked with `?search=`). | A filter card with username and date. |
| BV log with an unknown `?type=` was titled "All Paid BV" over the full log. | Titled "BV Log" (the full log, as before). |
| Report lists lost their filters on page 2 (no `withQueryString()`). | Kept. |

**Deleted:** `reports/{transactions,logins,notification_history,investments,bvLog,pvLog}.blade.php`.
Still Blade: `reports/email_details.blade.php`, the raw email body that
the frame loads.

**Verification:**

- **Server checks** (`p7b_check.php`):
  - **Type filter:** Minus shows only debits.
  - **Search and dates:** search + ISO range; the old dashboard format and
    a single ISO day fill the inputs.
  - **Bad date:** gives the "Invalid date format" error.
  - **One member's transactions:** the page links keep the path.
  - **Commission logs:** no remark filter, but the date filter applies.
  - **Logins:** filtered by date and by IP.
  - **Notification History:** the SMS text is sent raw for the page to
    escape; emails get a detail URL. The member version is the same page
    with no filters.
  - **Email detail:** sends the CSP header.
  - **Investments:** totals.
  - **BV log:** `?type=leftBV`; a made-up `?type=truncate` leaves the table
    untouched.
  - **Sidebar:** the Report group is open and the React links are marked.
- **Browser checks** (`e2e/phase7b1.mjs`, 30 checks):
  - **Filters:** sent with an ISO range; "Last 7 Days" fills both inputs;
    the date error shows.
  - **Table:** `data-label` on every cell.
  - **Links:** username filter, IP link, page links (all Inertia); the
    member page opens with a full load.
  - **Notification details:** the email opens in a sandboxed frame and its
    script does not run; SMS shows as literal text.
  - **Widgets:** the investment totals.
  - **BV log:** search keeps `?type=`.
  - **Phones:** the Filter button folds the card; table cells show their
    labels; no sideways scroll on any of the six pages.
- **Regression:** 23 browser suites, 674 checks, all pass.

### Step 96: PV log, Financial Report and Admin Action Log in React (7b-2)

**Pages:**

- **`Pages/Admin/PvLog.tsx`** (`admin.pv-logs.index`). The page has its own
  design; its CSS moved to `PvLog.css`, scoped under `.page-pvlog` with
  `scripts/extract-page-css.mjs`.
  - **Filters:** search, position and type.
  - **Chips:** each applied filter shows as a chip; × removes only that
    filter.
  - **Totals:** over the whole log, as before.
  - **Fix:** made-up `?position=` / `?type=` values used to be applied as
    they were; only 1/2 and +/− are used now.
- **`Pages/Admin/Reports/Financial.tsx`** (`admin.report.financial`).
  - **Server:** `ReportController::financialGroup()` formats each panel the
    way `reports/partials/financial_boxes.blade.php` did. A panel with more
    than 4 figures hides the empty ones and names them below.
  - **Date range:** the same `DateRangeField`; an unreadable range shows its
    error.
  - **Print:** opens the browser's print dialog; app.css already hides the
    sidebar, the top bar and the buttons when printing.
  - **New component:** `Components/Admin/BoxWidget.tsx` is
    `<x-widget style="6">`.
- **`Pages/Admin/ActionLog.tsx`** (`admin.system.action.log`, super-admins
  only).
  - **Filters (new):** admin, method, page/description and date.
  - **Detail dialog:**
    - the full description, which the table cuts at 100 characters;
    - the request, method and URI;
    - the subject and the admin's role;
    - IP and browser;
    - the data sent (passwords are never stored:
      `AdminActionLogMiddleware` drops them).

**Fixes:**

| Before | Now |
|---|---|
| The Admin Action Log was not linked from anywhere (no menu entry, no settings card). | A card on System Setting, which the top-bar search also finds. Only super-admins see it, like the page. |
| The System Setting page showed every card to every admin, including pages that then answered 403 (gateways, extensions…). | `GeneralSettingController::systemSetting()` keeps only the cards `AdminAccessMiddleware::allows()`, the same rule as the sidebar and search. |
| A log with neither a description nor data showed the word "null". | "—". |

**Deleted:**

- `admin/pv-log/index.blade.php`;
- `reports/financial.blade.php` and `reports/partials/financial_boxes.blade.php`;
- `system/admin_action_log.blade.php`.

**Still Blade, on purpose:** Stage Twice (`reports/stage_twice*`). It is a
report, but it has a Refund button that moves money, so it moves with the
other money pages in 7e.

**Verification:**

- **Server checks** (`p7b2_check.php`):
  - **PV log:**
    - filters (made-up values ignored) and search;
    - a deleted member's row shows "— deleted —";
    - row numbers carry on across pages.
  - **Action log:**
    - filters by admin + method, by search and by one ISO day;
    - "—" instead of "null";
    - a support admin may not open it; it is in the super-admin search.
  - **System Setting cards:** super-admin 13 (with the new card); the
    support admin 0.
  - **Financial Report:**
    - all time and a 7-day range;
    - groups, cash-in with its empty figures named, standing;
    - a bad range → "Invalid date range.".
    - The fixture database lacked 25 of the tables and columns the
      report reads; the script added them as it hit them.
- **Browser checks** (`e2e/phase7b2.mjs`, 25 checks):
  - **Scoped CSS** applies on the PV log.
  - **Filters and chips:** × drops only its filter; the empty state shows.
  - **Action log:** no "null"; the detail dialog shows the full
    description and data.
  - **Financial Report:**
    - the headline boxes and section heads;
    - linked boxes and the named empty figures;
    - the standing panel;
    - the date range and its error;
    - print hides the frame; the Print button opens the dialog.
  - **Phone width:** no sideways scroll at 375 px.
- **Regression:** 24 browser suites, 699 checks, all pass. `route:cache`
  and `view:cache` succeed, and the route-name scan finds only the 21 known
  `ipn.` references.

### Step 97: Manage Users lists, stages, sponsor history and KYC review in React (7c-1)

**Plan for 7c (users).** `ManageUsersController` has 43 actions and the
member page (`users/detail.blade.php`) is 1,555 lines, so 7c is done in
three parts:

1. **7c-1:** the lists and the small member pages.
2. **7c-2:** the member page and its edit actions.
3. **7c-3:** sending notifications and the trees.

**Pages** (`Pages/Admin/Users/`):

| Page | Routes |
|---|---|
| `List.tsx` | The 12 segment lists (`admin.users.all`, `active`, `banned`, `email.verified`, `email.unverified`, `mobile.verified`, `mobile.unverified`, `kyc.unverified`, `kyc.pending`, `with.balance`, `paid`, `free`) and `admin.users.referral` ("Referred By …"). One builder: `ManageUsersController::listPage()`. |
| `Stages.tsx` | `admin.users.stages` (super-admins). One tab per stage, with counts. Its CSS moved to `Stages.css`, scoped under `.page-user-stages`. |
| `SponsorHistory.tsx` | `admin.users.sponsor.history` |
| `KycDetail.tsx` | `admin.users.kyc.details`, with Approve (confirm) and Reject (reason). The ID card opens full screen in the shared `Lightbox`. |

**Fixes:**

| Before | Now |
|---|---|
| The lists had two search boxes for the same `?search=` (one in the card, one in the title row). | One filter card: search + status. |
| The Sponsor cell printed a lone "@" for members with no sponsor (`'@'.@$user->refBy->username ?? 'N/A'` never reaches `?? 'N/A'`). | "N/A". |
| The sponsor was loaded with one query per row. | Loaded with the list (`with('refBy:id,username')`). |
| The lists lost their filters on page 2. | Kept (`withQueryString()`). |
| `?status=` with any other value than 1 was taken as "banned". | Only 1 or 0 filter; anything else shows both. |
| **KYC approve and reject worked on any member, pending or not.** A stale tab could approve a member whose documents had just been rejected, or reject one already verified. | Only a pending submission can be approved or rejected; otherwise "This KYC submission is no longer pending." |
| The rejection reason had no length limit. | 1,000 characters. |
| An unknown `?stage=` showed an empty page titled "Stage Members". | Falls back to the first stage. |

**Dead code removed:** `StockistController::changeUserStockistStatusddd()`. No
route points to it, and it called a method that does not exist.

**Deleted:**

- `users/list.blade.php`, `users/stages.blade.php`;
- `users/sponsor_history.blade.php` and its `partials/sponsor_cell.blade.php`;
- `users/kyc_detail.blade.php`.

**Not moved yet:** the member's pin pages (`admin.users.generate.pin`,
`used.pin`). They render the pin list, which moves with the pins in 7e.

**Verification:**

- **Server checks** (`p7c1_check.php`):
  - **Lists:** eight segments with their counts; status filter; search +
    status; a made-up status shows all; the KYC Data link only on KYC
    Pending.
  - **Referred By:** shows the right members.
  - **Stages:** counts per tab; stage + search; an unknown stage falls back.
  - **Sponsor history:** the current change is marked; a deleted previous
    sponsor shows "Deleted user".
  - **KYC page:** answers (text escaped, checkboxes joined, files with
    view/download, empty answers hidden), identity, guarantor.
  - **Reject:** without a reason → refused; with one → saved.
  - **Stale actions:** approving the rejected member → refused; approving
    when pending → done; rejecting the now verified member → refused.
- **Browser checks** (`e2e/phase7c1.mjs`, 32 checks):
  - **Lists:** one search box; "N/A" / "@demo" sponsors; the filters sent;
    Details is a full load to the Blade member page; KYC Data opens a new
    tab.
  - **Stages:** tabs and counts; the scoped CSS applies; search keeps the
    stage; the empty state.
  - **Sponsor history:** the history table.
  - **KYC:** answers shown as text; the ID card full screen; Reject needs
    a reason; Approve confirms first; a rejected submission shows its
    reason and no buttons.
  - **Phone width:** no sideways scroll on any of the six pages.
- **Regression:** 25 browser suites, 731 checks, all pass.

### Step 98: The member page in React, and its actions made safe (7c-2)

**Page: `Pages/Admin/Users/Detail.tsx`** (`admin.users.detail`). It replaces
`users/detail.blade.php` (1,555 lines). Its CSS moved to `Detail.css`, scoped
under `.page-user-detail`; the toolbar and Buy Shares styles were already in
`app.css`.

- **Tiles:** balances, bonuses and binary PV, using the new
  `Components/Admin/LinkWidget.tsx` (`<x-widget style="7">`). A tile opens
  React pages as Inertia visits and Blade pages with a normal load; the
  server says which (`AdminLayout::isReact()`).
- **Toolbar:**
  - wallet adjustments;
  - quick links;
  - ban/unban, block/unblock withdrawals, Buy Shares.
- **Cards:**
  - profile, sponsor change, username change;
  - the edit form;
  - the password.
- **Dialogs:** React `Modal`s instead of Bootstrap ones. Errors show inside
  the dialog; it closes on success.

**Who sees which button.** `ManageUsersController::detail()` sends a `can`
map built with `AdminAccessMiddleware::allows()`, the same rule the routes
enforce.

- **Before:** the wallet buttons showed for an "Owner" role, whose clicks
  then failed with 403.
- **Example:** an admin with "view users" + "edit users" sees the edit form
  and ban/block. They do not see the wallets, Buy Shares, Login as User or
  the sponsor/username/password cards, which are super-admin only.

**Fixes:**

| Before | Now |
|---|---|
| The sponsor change, username change and member password update put the whole exception, stack trace included, into the toast (`'Error: ' . $e`). | `report($e)` and a plain message; the password update needs no try/catch. |
| The edit form had the First Name and Last Name boxes twice. | Once. |
| The account number and mobile were `type="number"` boxes. | Text boxes (numeric keypad): numbers like `0123…` keep their leading zero. |
| `update()` looked up the country before validating it: a missing or unknown country gave a server error. | Validated first → "The selected country is invalid." The mobile-number clash shows under the Mobile box. |
| Turning the KYC switch off deleted the member's documents without a word. | Same result, but the page asks first. |
| **Sponsor change** trusted a "current sponsor" sent from a read-only box, refused with "Mission Impossible AD102" when they differed, and could never give a sponsor to a member without one. | The current sponsor is read from the member. A member with none can be given one (the log's previous sponsor is empty). Errors are plain sentences under the box. |
| Username change had no format rules (spaces and symbols allowed). | The sign-up rules: at least 6 characters, letters/numbers/underscore, unique. |
| **Wallet adjustments** (Money Box, VISA, Re-purchase) read and wrote the balance without a lock: two adjustments at the same moment overwrote each other. | Each runs in a transaction with the member's row locked. Rules unchanged: VISA and Re-purchase may not go below zero; the Money Box may (its check was switched off on purpose in the old code). |
| Ban, block withdrawals and Ambassador were blind toggles: a second click from a stale tab undid the first. | The page sends what it means (`state=ban/unban`, `block/unblock`, `on/off`); a repeat answers "already banned" etc. and changes nothing. |
| The Ambassador button used the browser's `confirm()`. | `ConfirmDialog`. |
| **Buy Shares** checked the balance outside any lock: a double click could allocate twice on one check. | Locked; checked and charged in one transaction. Quantity is capped at 100,000 units. |

**For the owner to decide: "Force allocate".**

- **What the dialog says:** "skip balance deduction".
- **What the code does:** it always deducts. The skip was commented out, so
  a forced allocation takes the member's Money Box below zero by the
  shortfall.
- **What changed:** the behaviour is unchanged. The dialog now says "the
  balance goes below zero by the shortfall".
- **If the old meaning is wanted** (allocate without charging), it is a
  one-line change in `buySharesForUser()`; say so.

**Duplicates:** the commented-out second copy of the edit form is gone with
the Blade file.

**Deleted:** `admin/users/detail.blade.php`. `UserCell` and the list's
Details button now open the member page as Inertia visits.

**Verification:**

- **Server checks** (`p7c2_check.php`):
  - **Page:** tiles (React/Blade links marked), PV, form values (account
    number with its leading zero), plans.
  - **`can`:** super-admin vs the editor role.
  - **Edit:** unknown or missing country → messages; mobile clash; save;
    KYC off clears the documents.
  - **Sponsor:** a member without one gets one; same / self / later-joiner /
    unknown → messages.
  - **Username:** bad format, too short, taken, ok.
  - **Password:** mismatch, ok.
  - **Wallets:** Money Box below zero; VISA and Re-purchase overdraws
    refused; zero refused.
  - **Toggles:** ban needs a reason; ban / repeat ban / unban / repeat;
    block / repeat; Ambassador on / repeat.
  - **Buy Shares:** refused without Force; with Force the balance goes from
    100 to −2,900 and the investment is recorded; 0 units refused.
- **Browser checks** (`e2e/phase7c2.mjs`, 37 checks):
  - **Page:** tiles and links; the form (one first/last name, text account
    number, switches, dial code follows the country); edit errors in place.
  - **KYC switch:** turning it off asks first.
  - **Sponsor, username, password:** sponsor and username errors show in
    place; the password boxes are emptied.
  - **Wallet dialog:** titles, required fields, errors stay, closes on
    success.
  - **Ban, block, Ambassador:** send their state.
  - **Buy Shares:** totals, shortfall, Force (and its new label), the POST,
    Force unticked when the balance is enough.
  - **Editor role:** sees no super-admin buttons.
  - **Phone width:** no sideways scroll.
- **Regression:** 26 browser suites, 768 checks, all pass. Two older checks
  now expect an Inertia visit to the member page instead of a full load.

### Step 99: Send Notification and the admin member trees in React (7c-3)

This finishes 7c (users).

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Users/NotifyOne.tsx` | `admin.users.notification.single` (Send Notification on the member page) | `users/notification_single.blade.php` |
| `Pages/Admin/Users/NotifyAll.tsx` | `admin.users.notification.all` (Manage Users → Send Notification) | `users/notification_all.blade.php` |
| `Pages/Admin/Users/Tree.tsx` | `admin.users.other.tree` (a member's binary tree) | `users/tree.blade.php` |

**New shared pieces (`Components/Admin/`):**

- `ChannelPicker.tsx`: the "Send via Email / SMS / Firebase" cards. Only
  the channels switched on in General Settings are offered; the server
  sends the list (`notificationChannels()`).
- `RichTextEditor.tsx`: a small formatting box (bold, italic, lists,
  links…) that replaces the jQuery nicEdit editor. It hands back HTML.
  Links must start with `http(s):` or `mailto:`.
- `NotificationFields.tsx`: channel, subject, image and message, used by
  both pages. Email gets the editor; SMS and push get a plain text box.
  SMS has no subject; only push takes an image. Switching channel keeps
  the text (HTML is turned into plain text and back) instead of wiping it,
  as the old page did.

**How bulk sending works now.** The message goes out in batches with a
pause (the "cooling period") between them.

1. The first POST carries the message and the settings. The server checks
   them, works out how many members will get it, sends the first batch and
   keeps the run in the session.
2. The page then shows the run: should be sent / sent / still to send /
   per batch, a countdown and a progress bar.
3. When the countdown ends, the page posts `continue=1` and the server
   sends the next batch from the stored run. This repeats until all are
   sent; then the form comes back.
4. **Stop Sending** posts `cancel=1` (after a confirm), which forgets the
   run.
5. Leaving the page mid-run asks first: closing the tab, or a sidebar link.
   Coming back later resumes the run.
6. If a batch comes back with no progress (an error), the page waits a full
   period again instead of retrying at once.

The **"Selected Users"** picker searches active members by username or
email (`admin.users.list`, JSON) and shows the chosen ones as removable
chips. It replaces select2. The hint under "Being Sent To" shows how many
active members are in the group (`admin.users.segment.count`).

**Fixes:**

| Before | Now |
|---|---|
| **Security:** the audience name from the form was called as a query method on `User` (`User::active()->$name()`). `being_sent_to=delete`, or opening `/admin/users/count-by-segment/delete`, would have **deleted every active member**. | The name must be one of the audiences in `User::notifyToUser()`. Anything else is refused (form) or answers `{"count": null}` (count URL). |
| The cooling-period box was pre-filled with the batch size. | Its own value (default 10 seconds, at most 3,600). |
| A push image was only attached to the first batch. | Stored once with the run and sent with every batch. |
| "Start from" was lost after the first batch: the next batch restarted from "sent + 1". | Each batch starts at (start − 1) + sent. |
| "Top deposited users" sorted by join date first, so it took the oldest depositors, and its count ignored N. | Biggest depositors first, and at most N members. |
| The batch size had no limit. | 1 to 1,000 members. |
| The member picker JSON sent whole member records (mobile, address, bank details…). | Only id, username, email and full name. |
| "Choose a member" errors and "no recipients" were easy to miss. | Shown under the field / as a toast. |

**Trees:**

- The admin tree uses the member tree component (`GenealogyTree`) with
  `TreePresenter::nodes(..., admin: true)`. Each member's dialog has **View
  Their Tree** (the admin tree) and a new **Open Member Page** button.
  Empty places are not "add a member here" links in the admin tree.
- The header shows the member, their parent in the tree (a link to the
  parent's tree) and a username search.
- **CSS moved:** the tree page rules (header, search, legend, tree,
  dialog) moved from `dashboard.css` to `Components/GenealogyTree.css`, so
  the admin panel (which does not load `dashboard.css`) gets them too. The
  phone rules sit last in the file so they still win.

**Duplicates removed:**

- `admin.users.single.tree` (`/admin/users/tree/{username}`) was a second
  admin tree page. It now redirects to `admin.users.other.tree`.
- The member area's old `templates/basic/user/myTree.blade.php` and
  `myStages.blade.php` were dead (React since an earlier step) and are
  deleted.

**Deleted:** `admin/users/notification_single.blade.php`,
`notification_all.blade.php` and `tree.blade.php`. nicEdit and select2 stay
in the Blade admin layout for the pages that are still Blade.

**Verification:**

- **Server checks** (`p7c3_check.php`):
  - **Tree:** props, parent, admin URLs on the nodes; search by username;
    unknown member → back with a message; old URL redirects; the member's
    own tree still has member URLs only.
  - **Notify one:** missing subject; a switched-off channel refused; email
    sent and logged.
  - **Whitelist:** `delete`, `forceDelete`, `truncate` → `{"count":null}`
    and a validation error; members still all there.
  - **A run:** start 2, batch 2 over 6 members → members 20–24 each get it
    once, in order, over three batches; the run is then gone.
  - **Cancel**, **selected users** (only the chosen two), **top 2
    depositors** (the two biggest), **all channels off** (redirect).
- **Browser checks** (`e2e/phase7c3.mjs`, 40 checks):
  - **Tree:** header, parent link, CSS present in the admin panel, member
    dialog with Open Member Page (Inertia visit), search.
  - **Notify one:** the editor makes bold text; the POST carries the HTML;
    channel switching (subject/image shown or hidden, text carried over and
    escaped); the push image is uploaded; server errors under fields.
  - **Notify all:** audience hint (fetched once per group), the extra boxes
    per group, the member picker (search, chips, remove), the POST fields.
  - **Run:** the numbers and the progress bar; the countdown posts
    `continue`; a failed batch waits a full period; a sidebar link asks
    first; Stop Sending posts `cancel`.
  - **Phone width:** no sideways scroll on all three pages.
- **Regression:** 26 browser suites, 794 checks, all pass. `route:cache`
  and `view:cache` succeed; the route-name scan shows only the 21 known
  `ipn.` references.

### Step 100: The admin Shop pages in React (7d-1)

**Plan for 7d.** The shop, stockist and affiliate admin is about 7,000
lines of Blade, so it goes in three parts:

1. **7d-1:** the Shop group: products, categories, states, orders, repurchase awards.
2. **7d-2:** Manage Stockist.
3. **7d-3:** Affiliate Program, Affiliate Audit, ad materials, welcome packs.

**Pages (`Pages/Admin/Shop/`):**

| Page | Route | Replaces |
|---|---|---|
| `Products.tsx` | `admin.product.index` | `product/index.blade.php` |
| `ProductForm.tsx` | `admin.product.create`, `admin.product.edit` | `product/create.blade.php`, `product/edit.blade.php`, `product/partials/state_price_row.blade.php` |
| `Categories.tsx` | `admin.category.index` | `category/index.blade.php` |
| `States.tsx` | `admin.state.index` | `state/index.blade.php` |
| `Orders.tsx` | `admin.order.index` | `orders.blade.php` and `sales/index.blade.php` |
| `OrderDetail.tsx` (+ `.css`) | `admin.order.show` (new) | `sales/show.blade.php` |
| `RepurchaseAwards.tsx` | `admin.repurchase-award.index` | `repurchase-award/index.blade.php` |
| `RepurchaseQualified.tsx` | `admin.repurchase-award.qualified` | `repurchase-award/qualified.blade.php` |

**New shared pieces (`Components/Admin/`):**

- `Form.tsx`:
  - `FieldError` and `DialogHeader`. Three pages had their own copies; the
    member page, the profile page and the notification fields now use these.
  - `ConfirmButton`: asks "Are you sure?" and then sends the POST or DELETE.
    It replaces the Blade `confirmationBtn` and the `onsubmit="return confirm()"` forms.
  - `withId(url, id)`: fills in the `:id` placeholder in a route URL.
- `Drawer.tsx`: the slide-in form panel used by States and Repurchase
  Awards. It closes on Escape and handles focus.
- `ProductForm` has its own tag box (meta keywords, replacing select2) and
  gallery picker (replacing the jQuery image-uploader plugin).

**Duplicates removed:**

- **Two order pages.** "Manage Sales" and "Manage Order" both listed the
  same `orders` table. One showed collection at stockists; the other showed
  ship/cancel. They are now one **Orders & Sales** page:
  - search by invoice, TRX or member;
  - filter by status and by how much was collected;
  - today's figures at the top.
  - The order page has both the collection history and Ship / Cancel.
  - `/admin/sales` and `/admin/sales/{id}` redirect to it, and
    `AdminSalesController` is deleted.
- **Two product forms.** Create Product and Edit Product were near-copies;
  they are now one page.
- **The sidebar** has one "Orders & Sales" entry instead of two.
- **The dashboard's** Pending / Cancelled order tiles now open the list
  already filtered.

**Toggles say what they mean.** `GlobalStatus::changeStatus()` takes
`state=enable|disable`. Repeating it answers "already on/off" and changes
nothing. Without `state` it still flips, so the remaining Blade pages work
as before. Repurchase awards take `state=on|off` the same way.

**Fixes:**

| Before | Now |
|---|---|
| **Repurchase award double payment:** the "already credited" check ran before the transaction. Its `lockForUpdate()` was outside any transaction, so it locked nothing, and a double click could pay an award twice. | The checks and the payment run in one transaction with the member row locked. The second click answers "already credited". |
| A switched-off award could still be paid. | Refused until it is activated again. The Credit buttons are hidden. |
| Deleting an award that was already paid left its payment records pointing at nothing. | Refused: switch it off instead. Unpaid awards can still be deleted. |
| **Order refunds:** cancelling two orders of one member at the same moment could overwrite one refund with the other (the member row was not locked). | The member row is locked. A repeat cancel from a stale tab answers "no longer pending". |
| The order list showed `orders.total_amount`, which is empty (₦0) for cart orders. | The invoice total, as the refund already used. |
| "Manage Sales" only ever showed its first 10 orders: the page links were only drawn after a search. | One paginated list. |
| **Product gallery on edit:** uploads were checked under `galleryImages.*`, a name nothing sends, so any file was accepted. | `gallery.*`: PNG/JPG only, 3 MB each, at most 10. |
| A replaced thumbnail stayed on disk. | The old file is removed. |
| The `old` gallery ids could name another product's image. | Only this product's images are touched. |
| A maximum order quantity below the minimum was accepted, so the product could not be ordered. | Refused. |
| An affiliate bonus could be over 100%, or have a type but no value. | Refused with a plain message. |
| A failed gallery upload on Create answered with raw JSON. | The product is saved and the page says which part failed. |
| The product and its state prices were saved separately. | Saved together in one transaction. |
| Stock quantity had to be above 0, so a sold-out product could not be saved. | 0 is allowed; the list shows "Out". |
| **Deleting a state** said it "also removes associated product prices" but left them behind. It also left stockists and orders pointing at a missing state. | Its prices are deleted with it. A state that stockists or orders use cannot be deleted; the button is disabled and the server refuses. |
| Two states, or two categories, could share a name. A state code could hold spaces or symbols. | Names are unique; codes are letters and numbers only. |
| Order item names were put into the details dialog as raw HTML. | React escapes them. |

**Stays as it was (noted):**

- Order status 1 means both "Shipped" (set by the admin) and "Collected"
  (set when a stockist hands over everything). The page shows "Collected"
  when the invoice is fully redeemed and "Shipped" otherwise.

**Deleted:**

- `admin/product/{index,create,edit}.blade.php` and `product/partials/state_price_row.blade.php`;
- `admin/category/index.blade.php`, `admin/state/index.blade.php`, `admin/orders.blade.php`;
- `admin/sales/{index,show}.blade.php`, `admin/repurchase-award/{index,qualified}.blade.php`;
- `AdminSalesController`.

**Verification:**

- **Server checks** (`p7d1_check.php`):
  - **Categories:** duplicate name; add; disable; disable again; the old
    flip without `state`.
  - **States:** bad code; add; same name; delete a used state (refused); delete
    an unused one, which also removes its prices.
  - **Products:** list and form props; seven failed creates (no thumbnail,
    max < min, 150%, type without value, same state twice, a non-image in
    the gallery); a full create (state prices, specs, keywords, gallery);
    an update that keeps one image, adds one, replaces the thumbnail and
    sets quantity 0; another product's image left alone; feature twice;
    a state price added.
  - **Orders:** totals from invoices; every filter; one member's orders;
    detail; cancelling a partly collected order (refused); a refund of the
    invoice total, once; the `/admin/sales` redirects.
  - **Repurchase awards:** off → credit refused; on and on again; credit
    once, double click refused (one credit, one transaction, one payout,
    one ACB job); below the PV (refused); a paid award cannot be deleted;
    add and delete an unpaid award.
  - **Sidebar:** one "Orders & Sales", marked active on the order page.
- **Browser checks** (`e2e/phase7d1.mjs`, 65 checks):
  - every dialog, drawer and confirm sends the right request and fields
    (the multipart product form included);
  - errors show in place;
  - picked states are not offered twice;
  - tags and the gallery (wrong file types are skipped with a note);
  - saved state prices can be edited and removed;
  - the order filters and Ship / Cancel;
  - the award toggle, edit, delete and credit, and the switched-off warning;
  - no sideways scroll at phone width on all nine pages.
- **Regression:** 27 browser suites, 859 checks, all pass. `route:cache`
  and `view:cache` succeed; the route-name scan shows only the 21 known
  `ipn.` references.

### Step 101: Manage Stockist in React (7d-2)

**Pages (`Pages/Admin/Stockist/`):**

| Page | Route | Replaces |
|---|---|---|
| `Dashboard.tsx` | `admin.stockist.dashboard` | `stockist/stockist-dashboard.blade.php` and the old list `stockist/index.blade.php` |
| `Activate.tsx` | `admin.stockist.activation-page` | `stockist/stockist-activation.blade.php` |
| `Details.tsx` | `admin.stockist.details` | `stockist/stockist-details.blade.php`, the old `stockist/detail.blade.php` and `stockist/stockist-wallet-topup.blade.php` |
| `Transfers.tsx`, `TransferDetail.tsx` | `admin.stockist.transfers.*` | `stockist/transfers/{index,show}.blade.php` |
| `Orders.tsx`, `OrderDetail.tsx` (+ `.css`) | `admin.stockist-orders.*` | `stockistorders/{index,show}.blade.php` |
| `Report.tsx`, `ReportShow.tsx` | `admin.stockist.report`, `admin.stockist.report.show` | `stockist/report-{index,show}.blade.php` |

`Components/Admin/StockistTopUp.tsx` is the top-up dialog.

**Duplicates removed:**

- **Stockist list.** The old "All / Active / Inactive Stockists" pages listed
  the same stockists as the dashboard. They redirect there (with
  `?status=active` / `inactive`), and the dashboard now has search, status,
  store type and state filters.
- **Stockist page.** The old `stockist/detail/{id}` redirects to the newer
  details page. Its Activate / Deactivate action moved there.
- **Wallet top-up** was its own page; it is now a dialog on the stockist
  page and on the dashboard's "Top up" button. The old URL opens the page
  with the dialog.
- **"Stockist Order Request" and "Supplies"** belonged to the stockist
  ordering system that came before Stockist Orders, and nothing creates
  their records any more. They are removed from the sidebar, their URLs
  redirect to Stockist Orders, and `SorderController` / `SupplyController`
  are deleted.
- **Dead code:** `stockistorders/show1.blade.php`, and
  `StockistController::store()` (an older "promote to stockist" with no
  link to it).

**Fixes:**

| Before | Now |
|---|---|
| **Supplies changed member orders:** its Ship/Cancel action looked up the **member `orders` table** by the supply row's id, refunded the main balance and restocked products. | The page and the action are gone. |
| **Activation page XSS:** the found member's name was written into the page as raw HTML, so a member could put script in their own name. | React shows it as text. select2 no longer loads from an outside CDN. |
| A GET route (`/admin/stockist/changeUserStockistStatus`) changed a stockist's status from a plain link. | Removed; the change is POST only. |
| **Activation** did not check whether the member was already a stockist, and checked the balance with no lock, so a double click could charge twice and create two stockist records. It also allowed banned members. | The member row is locked and every check runs inside the transaction. A second stockist, a banned member or too little in the Money Box is refused under the field. |
| **Stock order Approve / Ship / Deliver** checked the status before the transaction without a lock. Two clicks on Deliver both passed, so the stock was added to the stockist twice and the sponsor bonus paid twice. | One locked transition: the second click answers "cannot be delivered in its current status". |
| The stock order controller added the member `auth` middleware to admin routes, and read the admin id before any middleware ran. | Removed; the admin id is read from the admin guard when it is needed. |
| **Wallet top-up** did a read-then-write of the wallet, so a sale or rebate credited at the same moment could be lost. Zero was accepted, and "topped up by" was always empty (it read the member guard). | The row is locked, the amount must be above 0, and the admin is recorded. |
| **Two "active" flags:** the old toggle set `status`, but the pages and the member stockist search read `is_active`. | Activate / Deactivate set both; a reason is required to deactivate. The status takes `state=activate|deactivate`, so a repeat answers "already". |
| The admin dashboard's "Pending Orders" counted the retired requests and linked to **email-unverified users**. | "Pending Stock Orders" counts pending stock orders and opens that list filtered. "Active Stockists" counts both flags. |
| Stock order filters ran on empty values: `?status=` or `?search=` with nothing in it emptied the list. | Only real values filter. Bad dates and unknown statuses are ignored on every list. |
| Error toasts showed exception messages. | `report($e)` and a plain message. |
| The list's Approve / Ship / Deliver buttons sent AJAX requests that expected JSON, but the server answered with a redirect. | Normal Inertia requests with a confirm step. |

**For the owner to decide:**

1. **Inactive stockists can still use their panel.** Being a stockist
   only needs a stockist record (`User::isStockist()`). Deactivating hides
   the stockist from the member search but does not stop them redeeming or
   selling. If deactivated stockists should be locked out, it is a change
   in `StockistMiddleware`; say so. It was not done here because older
   stockist records may have `is_active` off without anyone meaning it.
2. **Stockist setup bonus.** `StockistEnrollmentBonusService` pays
   ₦240,000 (Mini) or ₦650,000 (Mega) up ten generations. Only the old,
   unlinked `store()` called it; the activation form in use has never paid
   it. The service is kept; turning it on is one line in
   `activateStockist()`.

**Verification:**

- **Server checks** (`p7d2_check.php`):
  - **Old URLs:** each redirects, and the GET status route answers 404.
  - **Dashboard:** stats, rows and every filter (a bogus status is ignored).
  - **Details:** numbers, tabs, direction filter, the `?topup` flag.
  - **Status:** deactivate (reason required, repeat, both flags off),
    activate, and a stockist with only one flag set.
  - **Top-up:** 0 refused; ₦2,500 with the ledger before/after and the
    admin id.
  - **Activation:** the four search answers; refused for too little money,
    an existing stockist and a banned member; ₦600 taken from the Money Box
    into the new stockist wallet; a double click refused (one record).
  - **Transfers:** stats, filters (bad date ignored), completed and
    cancelled detail.
  - **Stock orders:** counts; empty filters no longer empty the list;
    approve twice; ship; deliver twice (stock +1 once, one history row
    with the admin); cancel twice (refund once).
  - **Report:** channel totals, search, per-stockist totals, type and date
    filters (bad values ignored).
  - **Sidebar:** four entries, the right one marked active.
- **Browser checks** (`e2e/phase7d2.mjs`, 51 checks):
  - the dashboard filters, and "Top up" opening the dialog on the
    stockist page;
  - every details tab, with the tab kept in the address;
  - top-up preview and POST; deactivate (reason required) and activate;
  - activation search: errors, the name shown as text, fields prefilled,
    the over-balance guard, the POST, no CDN script;
  - transfer tabs, search and dates; the pending transfer's cancel with
    confirm;
  - stock orders: Approve from the list, filters, the detail steps, the
    allowed actions and cancel;
  - the report search, channel and date filters;
  - the sidebar without the retired entries;
  - no sideways scroll at phone width on all nine pages.
- **Regression:** 28 browser suites, 910 checks, all pass. `route:cache`
  and `view:cache` succeed; the route-name scan shows only the 21 known
  `ipn.` references.

### Step 102: Affiliate Program, ad materials and welcome packs in React (7d-3)

This finishes 7d.

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Affiliate/Dashboard.tsx` | `admin.affiliate.dashboard` | `affiliate/dashboard.blade.php` |
| `Affiliate/Orders.tsx`, `Affiliate/OrderDetail.tsx` | `admin.affiliate.orders`, `admin.affiliate.order.show` | `affiliate/orders.blade.php`, `affiliate/order_show.blade.php` |
| `Affiliate/Affiliates.tsx` | `admin.affiliate.affiliates` | `affiliate/affiliates.blade.php` |
| `Affiliate/Audit.tsx` | `admin.affiliate.audit` | `affiliate/funnel.blade.php` and `affiliate/audit.blade.php` |
| `Affiliate/Settings.tsx` | `admin.affiliate.settings` | `affiliate/settings.blade.php` |
| `Pages/Admin/Ads/Index.tsx`, `Ads/Form.tsx` | `admin.ads.index`, `admin.ads.create`, `admin.ads.edit` | `ads/index.blade.php`, `ads/form.blade.php` |
| `Pages/Admin/WelcomePack/Index.tsx` (+ `.css`) | `admin.welcome-pack.index` | `welcome-pack/index.blade.php` |

The ad form keeps everything the jQuery version did:

- drag and drop;
- the size check before uploading;
- a cover taken from a frame of the clip;
- the live link preview;
- character counts.

It also shows the upload percentage.

`BoxWidget` takes `inertia` for a box that links to a React page.

**Duplicates removed:**

- **Funnel Report and Financial Audit** were two pages with the same date
  form over the same orders. They are one page, "Funnel & Financial
  Audit". `/admin/affiliate/funnel` redirects to it with the same dates,
  and the sidebar's Audit group has one entry.

**Fixes:**

| Before | Now |
|---|---|
| Funnel and audit dates went straight to `$request->date()`, so a bad date gave a server error. | Only real calendar dates are used (`2026-13-45` no longer rolls into 2027); anything else falls back to the last 30 days. From and to are swapped if reversed. |
| Active Affiliates used `HAVING` without `GROUP BY`, which strict MySQL refuses. | `whereHas` on orders or clicks. Search by name added; the order count links to that affiliate's orders. |
| Order filters passed any value through. | Only known statuses, payment methods and states; search by code, buyer or affiliate. |
| Settings allowed both payment methods off (the shop could take no orders) and a pickup fee above 100%. | Both refused, with a message under the field. |
| Ads: Pause / Go live was a blind toggle. | `state=live|paused`; a repeat answers "already". Pause and delete ask first. |
| An ad picture that failed to process was logged with `\Log::error` and its message. | `report($e)`. |
| Welcome packs: an unknown `tab` showed everything without saying so. | Pending, Redeemed or All, explicitly. Search also matches the code; member names link to their page. |

**Checked, not a bug:** an ad upload is checked by its real content (a
PHP file named `.jpg` is refused), not by its name.

**Deleted:**

- `admin/affiliate/{dashboard,orders,order_show,affiliates,funnel,audit,settings}.blade.php`;
- `admin/ads/{index,form}.blade.php`;
- `admin/welcome-pack/index.blade.php`.

**Verification:**

- **Server checks** (`p7d3_check.php`):
  - **Affiliate dashboard:** numbers.
  - **Orders:** every filter (bogus ignored); the detail page with its
    stockist link.
  - **Affiliates:** list and search.
  - **Funnel:** the old URL redirects.
  - **Audit:** a wide period, a bad date, an impossible date, reversed dates.
  - **Settings:** both payments off and 150% refused; a fixed ₦150 accepted.
  - **Ads:** counts, channels and promoters; the form props; store
    without a file; a disguised PHP file refused; a picture stored; update
    keeping the file; pause twice; go live; delete (files removed).
  - **Welcome packs:** every tab, search by code and name, member filter.
  - **Sidebar:** the single Audit entry marked active.
- **Browser checks** (`e2e/phase7d3.mjs`, 37 checks):
  - the dashboard box opens filtered orders as an Inertia visit;
  - order filters; order and affiliate links;
  - the audit dates;
  - settings: both-off guard, percentage max, the POST;
  - ad tabs, pause and delete with confirm;
  - the new-ad preview and counters, the size guard, the multipart POST;
  - editing a clip (cover field, is_active off);
  - welcome pack tabs, search and the scoped CSS;
  - the sidebar;
  - no sideways scroll at phone width on all ten pages.
- **Regression:** 29 browser suites, 947 checks, all pass. `route:cache`
  and `view:cache` succeed; the route-name scan shows only the 21 known
  `ipn.` references.

### Step 103: Deposits and withdrawals in React (7e-1)

This starts 7e, the money pages.

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Money/Deposits.tsx` | `admin.deposit.{list,pending,approved,successful,rejected,initiated}` | `deposit/log.blade.php`, `deposit/widget.blade.php` |
| `Money/DepositDetail.tsx` | `admin.deposit.details` | `deposit/detail.blade.php`, `deposit/gateway_data.blade.php` |
| `Money/Withdrawals.tsx` | `admin.withdraw.data.{pending,approved,rejected,all}` | `withdraw/withdrawals.blade.php`, `withdraw/widget.blade.php` |
| `Money/WithdrawalDetail.tsx` | `admin.withdraw.data.details` | `withdraw/detail.blade.php` |

Each list is one page for every status the sidebar links to. The "All"
page has summary boxes, one per status, that open that status's list.
Clicking a gateway name filters by it. The pending withdrawals list keeps
its checkboxes with Approve Selected and Reject Selected.

**Shared pieces:**

- `Components/Admin/FormAnswers.tsx` shows what a member typed into a
  form built with the form builder: text, a checkbox list, or a file with
  View and Download.
- `AdminPresenter::formFields()` turns those stored answers into rows for
  it. The KYC review page (Step 97) now uses it too instead of its own
  copy.
- `AdminPresenter::flatten()` turns an automatic gateway's reply (nested
  JSON) into "key → value" rows.

**Duplicates removed:**

- `admin.withdraw.data.index_w` (`/admin/withdraw/withdrawals`) rendered
  a view that did not exist, so it always failed. Removed; the four lists
  above cover it.

**Fixes:**

| Before | Now |
|---|---|
| Rejecting a deposit read it, checked "pending", then saved. An approval landing in between was credited and then marked "rejected". | Reject changes the status only if it is still pending at that moment (one `UPDATE … WHERE status = pending`). Approve already used `userDataUpdate()`, which claims the deposit the same way, so a deposit is credited once. |
| Rejecting a withdrawal refunded `balance += amount` with no lock. A double click or two admins refunded twice. | The request is claimed first; only the one that wins refunds, through `WalletService::credit()` (locks the member row, writes the transaction with the right post balance). |
| `POST /admin/withdraw/update-status` set any status on any ids, with no refund and no message to the member. | Removed. Bulk actions go through `bulk-action`, which refunds and notifies. |
| Bulk action stopped with a 404 at the first request already processed, leaving the rest half done. | Each request is processed on its own; the message says how many were done and how many skipped. At most 200 at a time. |
| An unknown `?method=` on the deposit list answered 404. | It shows no rows. |
| Withdrawal approve and reject took an empty note. | A note (up to 255 characters) is required, as the forms always asked (deposit reject already required one). |

**Kept for 7f:** withdraw methods (`withdraw/{index,create,edit}.blade.php`)
use the form builder, so they move with the gateways and settings.

**Deleted:**

- `admin/deposit/{log,detail,widget,gateway_data}.blade.php`;
- `admin/withdraw/{withdrawals,detail,widget}.blade.php`.

**Verification:**

- **Server checks** (`p7e1_check.php`):
  - every list, with search, date, method and member filters (an unknown
    method gives no rows);
  - a manual deposit's answers and an automatic deposit's gateway reply;
  - approving a deposit twice credits once; reject after approve is
    refused;
  - rejecting a withdrawal twice refunds once, with one transaction;
  - bulk approve and reject skip processed and missing ids;
  - `update-status` and `/withdrawals` answer 404.
- **Browser checks** (`e2e/phase7e1.mjs`, 28 checks): summary boxes as
  Inertia visits, filters, approve and reject dialogs and their POSTs,
  bulk selection, the withdrawal's bank details and payable amount, and no
  sideways scroll at phone width on all seven pages.
- **Regression:** 30 browser suites, 975 checks, all pass. `route:cache`
  and `view:cache` succeed; the route-name scan shows only the 21 known
  `ipn.` references.

### Step 104: Pins and the Stage Twice report in React (7e-2)

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Pins/Index.tsx` | `admin.pin.{index,used,unused,generate,user}`, `admin.users.generate.pin`, `admin.users.used.pin` | `pin/index.blade.php` |
| `Pages/Admin/Reports/StageTwice.tsx` (+ `.css`) | `admin.report.stage.twice` | `reports/stage_twice.blade.php` |

**Duplicates removed:**

- **Seven pin pages, one table.** All Pins, Used, Unused, Admin Pins, User
  Pins, and a member's "pins bought" and "pins used" each had their own
  controller method rendering the same Blade view. They are now presets
  of one page:
  - the sidebar links keep their URLs and open the page with a filter
    set (Used Pins = `status=used`, Admin Pins = `source=admin`, ...);
  - two dropdowns change the status (used / unused) and who created the
    pin (admin / members), and they combine: `/admin/pin/used?source=admin`.
  - The two member pages moved from `ManageUsersController` to
    `PinController::generatedBy()` / `usedBy()`. Their route names stay
    under `admin.users.*`, so an admin with "view users" can still open
    them (codes masked, as before), while the pin pages stay super-admin
    only.
- The dashboard's pin boxes all opened All Pins or All Used Pins. Each
  now opens its own list (User Generated, Admin, Used by admin pins, Used
  by member pins).
- `reports/stage_twice_excel.blade.php` was not used by anything (the CSV
  is written by the controller). Deleted.

**Fixes:**

| Before | Now |
|---|---|
| Admin pins were made with `rand()`, which is predictable. A pin is money: whoever holds the code can activate an account with it. | `Pin::newCode()`: `random_int()`, checked unique. The member's "Generate pin" (Step 82) uses the same helper. |
| "Create Pins" had no upper limit: one request for 1,000,000 pins would run until the server gave up, leaving a partial batch. | 1–500 per request, created in one transaction. |
| Stage Twice "Refund" checked only that the transaction was a stage-out bonus. Posting the id of a member paid **once** deducted their only bonus. | The member must have been paid that stage's bonus at least twice. |
| The CSV put names as they are. A name starting with `=` (for example `=HYPERLINK(...)`) ran as a formula when the file was opened in Excel. | Such cells start with `'`, so Excel shows them as text. |
| The download took any number of ids. | Up to 5,000 characters of ids. A ticked id from another stage is ignored (it was before too). |

**For the owner to decide:** Stage Twice allows one deduction per member
and stage. A member paid the bonus **three** times (this happens: see
the Stage 2 test data) can still only be charged back once, so one
extra payment stays with them. Should the deduction cover every extra
payment (count − 1)? Nothing was changed.

**Kept as it was:** only the Owner and super-admin roles see pin codes in
full; other admins see the first 20 characters.

**Deleted:**

- `admin/pin/index.blade.php`;
- `admin/reports/stage_twice.blade.php`, `admin/reports/stage_twice_excel.blade.php`;
- `ManageUsersController::generatePin()`, `usedPin()`.

**Verification:**

- **Server checks** (`p7e2_check.php`):
  - **Pins:** every preset; status and source together; bogus filters
    ignored; search; a member's created and used pins; an unknown member.
  - **Access:** an editor ("view users") sees masked codes and no Create
    button, and may not open `/admin/pin`.
  - **Create:** 0, 501 and amount 0 refused; 3 created, all well-formed
    and unique.
  - **Stage Twice:** stages 1–3; stage 9 falls back to 1; search.
  - **Refund:**
    - a member paid once is refused;
    - a transaction that is not a bonus is refused;
    - one deduction, and a second click on the member's other row is
      refused;
    - balance −4,000 with one deduction row and one Refund record;
    - GET is refused.
  - **CSV:** the ticked row with the formula guard; the whole stage; an
    id from another stage.
  - **Sidebar:** the right entry is active, as a React link.
- **Browser checks** (`e2e/phase7e2.mjs`, 26 checks):
  - the pin filters as Inertia visits; search;
  - the create dialog: its POST and a server error under the field;
  - the editor's view;
  - stage tabs and search;
  - the download: refused with nothing ticked, then a POST in a new tab
    with the CSRF token and the ticked id;
  - select all;
  - the refund dialog (styled, scoped CSS) and its POST;
  - Refunded badges and the details dialog; Escape closes it;
  - no sideways scroll at phone width.
- **Regression:** 31 browser suites, 1001 checks, all pass. `route:cache`
  and `view:cache` succeed; the route-name scan shows only the 21 known
  `ipn.` references.

### Step 105: Savings and loans in React (7e-3)

This finishes 7e.

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Savings/Settings.tsx` | `admin.savings.settings` | `savings/settings.blade.php` |
| `Savings/Index.tsx`, `Savings/Show.tsx` | `admin.savings.index`, `admin.savings.show` | `savings/index.blade.php`, `savings/show.blade.php` |
| `Savings/Cycles.tsx` | `admin.savings.cycles` | `savings/cycles.blade.php`, `savings/_cycle_form.blade.php` |
| `Pages/Admin/Loans/Index.tsx` | `admin.loans.index`, `admin.loans.pending` | `loans/index.blade.php`, `loans/pending.blade.php` |
| `Loans/Show.tsx` | `admin.loans.show` | `loans/show.blade.php` |
| `Loans/Products.tsx` | `admin.loans.products` | `loans/products.blade.php`, `loans/_product_form.blade.php` |

`Components/Admin/LoanReject.tsx` is the "Reject Loan Application"
dialog, used by the loan list and the loan page.

**Duplicates removed:**

- **Pending Loans and All Loans** were two pages over the same loans with
  the same Approve / Reject. Pending Loans is now All Loans with the
  status fixed to pending. It shows the columns a decision needs: total
  repayable, savings at application, purpose. The sidebar link and URL
  are unchanged.
- The add and edit dialogs for loan products, farm cycles and savings
  settings each included the same Blade form twice. The two copies had
  the same element ids, so clicking a label in the edit dialog ticked
  the box in the hidden "new" dialog. Each form is now one React
  component.

**Fixes:**

| Before | Now |
|---|---|
| **Farm cycle Pay Out paid nobody.** The controller marked the cycle `payout_processed` and then queued the payout job. The job skips a cycle that is already marked, so no member was credited, and the cycle stayed "closed", showing "Paid out" with the button gone. | The job alone marks the cycle, under its lock, when it pays. A second click or a second job still finds it done. Migration `2026_10_27_000001_reopen_unpaid_farm_cycles` clears the flag on cycles left in that state ("closed" + flag set can only be one of them), so Pay Out shows again; the actual yield entered is kept. **After deploying, run the migration and press Pay Out on any such cycle.** |
| **A saving could be paid twice.** The maturity runs (`ProcessPayment` for target/fixed savings, the farm cycle job) pay a saving and set it "matured". "Transfer to Money Box" was offered for anything not "closed", so a matured saving could be transferred again: balance plus interest a second time. | Transfer only for an **active** saving whose date has passed, checked in the controller and again in `ProcessSavingsTransfer`. |
| Transfer answered JSON to a jQuery call and reloaded the page after 2.5 seconds. | A normal Inertia POST with a toast. |
| Savings settings were sorted with MySQL's `FIELD()`, and the summaries used `"active"` in double quotes, which strict SQL reads as a column name. | `CASE` ordering and single-quoted strings: the same on any database. |
| Maximum amounts (savings settings, loan products) could be below the minimum; a fixed setting could be saved without a duration; Pay Out accepted a yield of 0 (the form's own check was skipped by Enter). | All refused, with the message under the field. |
| Editing a loan product replaced its `created_by` with the editor. | Kept; set only when the product is created. |
| Enable / disable of a loan product was a blind toggle. | `state=enable|disable`; a repeat says "already". |
| A cycle that had been paid out but not yet marked matured could still be edited (yield, dates). | Refused once paid out. |
| Closing a cycle read, checked, then saved. | One `UPDATE … WHERE status = 'open'`. |
| Weekly loans showed as months ("4 months", "4m") in the lists. | "4 weeks" / "4w". |
| List filters (`type`, `status`) passed any value through. | Only known values; search also matches the reference. |

**For the owner to decide:**

- A farm saving also has a maturity date. The `ProcessPayment` job
  pays any active saving whose date has passed (balance + interest
  earned, which is 0 for farm savings until the cycle pays). If it runs
  before the admin presses Pay Out, those members get their principal
  back with no yield, and the cycle then finds nobody left to pay.
  Should `ProcessPayment` skip farm savings and leave them to the cycle?
- The maturity run does not reduce `savings_wallet`; the admin transfer
  and the farm payout reduce it by the principal. Which is right?

Nothing was changed for either.

**Deleted:** `admin/savings/*` (5 views), `admin/loans/*` (5 views).

**Verification:**

- **Server checks** (`p7e3_check.php`):
  - **Settings:**
    - type order;
    - fixed without a duration, max below min and a duplicate are
      refused;
    - add and edit (switched off).
  - **Savings:**
    - every filter; bogus ignored; search by reference and member;
    - the detail page for active-due, matured and not-yet-due savings;
    - transfer:
      - a matured saving is refused;
      - a not-due saving is refused;
      - a due saving is paid once (+₦8,400), and a second try is refused;
      - the job alone also refuses a matured saving.
  - **Cycles:**
    - list counts;
    - a bad yield range is refused;
    - create; close twice;
    - Pay Out:
      - before maturity, refused;
      - with a yield of 0, refused;
      - at 18%: both members credited principal + 18% for the period,
        two payout transactions, cycle "matured";
      - again, refused;
    - edit after payout is refused;
    - transferring a cycle-paid saving is refused;
    - the migration reopens only the stuck cycle.
  - **Loans:**
    - every list and filter; the pending page ignores `?status=`;
    - weekly tenure labels;
    - approve:
      - refused without KYC;
      - pays once, and a second approve is refused;
    - reject:
      - needs a reason;
      - an active loan is refused.
  - **Products:**
    - max below min and a bad tenure are refused;
    - create normalises tenures;
    - an edit keeps `created_by`;
    - disable twice; a toggle without a state is refused.
  - **Sidebar:** the detail pages light their list's entry.
- **Browser checks** (`e2e/phase7e3.mjs`, 44 checks):
  - every dialog and its POST: settings add and edit, transfer, close,
    pay out, cycle create and edit, approve, reject, product edit and
    disable;
  - the duration field appearing for "fixed";
  - Pay Out disabled until a yield is entered;
  - the pending page's columns and no status filter;
  - weekly labels;
  - no sideways scroll at phone width on all ten pages.
- **Regression:** 32 browser suites, 1045 checks, all pass. `route:cache`
  and `view:cache` succeed; the route-name scan shows only the 21 known
  `ipn.` references.

### Step 106: Security fix: the web server handed out private files

Found while starting 7f. This is fixed before any more pages move.

**What was wrong:** the live site runs on PHP's built-in server
(`php -S 0.0.0.0:$PORT server.php`, from `Procfile` and
`nixpacks.toml`), with the repository root as the web root. That root
also holds `core/` (the whole Laravel app) and internal documents.
`server.php` passed *any* existing file straight to the built-in server.
The built-in server sends a normal file as it is, and **runs** a `.php`
file. So anyone on the internet could:

- download `/core/storage/logs/laravel.log`: errors with stack traces,
  SQL and request data;
- download `/core/.env` on any server where one exists: the app key,
  database password and mail/payment keys;
- download `/core/composer.json`, `/notes.md` and the audit documents
  (`WIIFARM_Executive_Audit_Summary_Action_Plan.pdf`, ...);
- run any PHP file under `core/` (including `vendor/`) by its URL.

Checked against a running server: `/core/composer.json`,
`/core/.env.example` and `/core/storage/logs/laravel.log` answered 200
with their contents.

**Now:** `server.php` sends a file itself only when **all** of these
hold:

- it is inside the site root (after resolving `..` and links);
- no part of its path starts with a dot (`.git`, `.env`);
- it is under `assets/` or `build/`, or is one of `favicon.ico`,
  `robots.txt`, `sitemap.xml`;
- it is not PHP. The one exception is the theme colour stylesheet
  `assets/templates/<theme>/css/color.php`, which only accepts two hex
  colours.

Every other path goes to Laravel, which answers with its normal 404 or
redirect. Re-checked:

- all of the above, plus `/.git/config`, `/index.php` and `/assets/../core/composer.json`, give
  no content;
- the CSS, the React build and `color.php` load as before.

**Action for the owner:**

- Treat everything that was in `laravel.log` as seen.
- If the live server ever had a `core/.env` file, **change the APP_KEY**
  (members will need to log in again), the database password, and
  every payment, mail and SMS key in it.
- Delete or rotate the old log file.

### Step 107: Site settings in React (7f-1)

7f is split into six parts:

| Part | Pages |
|---|---|
| 7f-1 | Settings hub and plain site settings |
| 7f-2 | Notification settings and languages |
| 7f-3 | Payment gateways, withdraw methods and the KYC form (the form builder) |
| 7f-4 | Frontend manager, extensions, cron, system |
| 7f-5 | Admins, roles, permissions, support, notices, flashcards |
| 7f-6 | MLM pages and the dashboard |

**Pages** (all in `Pages/Admin/Settings/`):

| Page | Route | Replaces |
|---|---|---|
| `Hub.tsx` | `admin.setting.system` | `setting/system.blade.php` |
| `General.tsx` | `admin.setting.general` | `setting/general.blade.php` |
| `Configuration.tsx` | `admin.setting.system.configuration` | `setting/configuration.blade.php` |
| `LogoIcon.tsx` | `admin.setting.logo.icon` | `setting/logo_icon.blade.php` |
| `TextFile.tsx` | `admin.setting.custom.css`, `admin.setting.sitemap`, `admin.setting.robot` | `setting/custom_css.blade.php`, `sitemap.blade.php`, `robots.blade.php` |
| `Maintenance.tsx` | `admin.maintenance.mode` | `setting/maintenance.blade.php` |
| `Cookie.tsx` | `admin.setting.cookie` | `setting/cookie.blade.php` |
| `Notice.tsx` | `admin.setting.notice` | `admin/notice.blade.php` |

**Notes on the pages:**

- The **hub** searches titles, subtitles and keywords as you type. Its
  cards open React pages as Inertia visits and Blade pages normally.
- Maintenance Mode, GDPR Cookie and Sitemap XML had no card. They were
  only reachable through the top search. They now have cards
  (`settings.json`).
- **Custom CSS, sitemap and robots.txt** were three copies of one page
  (a CodeMirror box). They are now one `TextFile.tsx`, a monospace box
  where Tab inserts spaces, with an "Open" link to the live file.
- Colours use the browser's colour picker next to the hex field, instead
  of the jQuery Spectrum plugin.
- The on/off modules are switches with clickable labels, instead of
  bootstrap-toggle.

**Fixes:**

| Before | Now |
|---|---|
| **Matching bonus never paid on some schedules.** The cron compared `date('H')` / `date('d')` ("01") with the saved value ("1") as text. A daily time of 01:00–09:00 or 24:00, and the monthly "1st day", never matched. | Compared as numbers ("24:00" is hour 0). Times are read from `now()`, which also honours the app timezone and test clocks. |
| Saving the matching bonus with an unknown schedule failed with "undefined variable"; carry/flush took any number. | Every field is checked: schedule, hour 1–24, weekday, day 1/15, carry/flush 0–2. |
| **robots.txt never reached crawlers.** The page saved to `robots.xml`, a name nobody asks for. | `robots.txt` in the site root, which `server.php` (Step 106) now serves. The page shows the old `robots.xml` content the first time, so nothing is lost. |
| Robots, sitemap and custom CSS were written relative to the process's working directory. | Always the site root (one level above `core/`). A failed write says so instead of claiming success. |
| A broken sitemap was saved as is (search engines then reject the whole file). | Must be well-formed XML. A DOCTYPE/ENTITY is refused. |
| Records per page accepted any number (0 broke every list); the timezone index was not range-checked; a charge percentage could exceed 100. | 20/50/100; a real timezone; 0–100%. The timezone file is written with `var_export`. |
| System Configuration treated a missing field as "off". | All eleven switches must be sent, as true/false. |
| Logo upload errors were swallowed. | `report($e)`. Maintenance mode is switched only after its image is saved. |
| `GeneralSettingController::matchingUpdate()` was a dead copy (the route uses `ManageUsersController`). | Removed. |

**Moved to 7f-3:** the KYC setting page (`kyc/setting.blade.php`). It
is built with the form builder, like the withdraw methods and manual
gateways.

**Deleted:** `setting/{system,general,configuration,logo_icon,custom_css,sitemap,robots,cookie,maintenance}.blade.php`,
`admin/notice.blade.php`.

**Verification:**

- **Server checks** (`p7f1_check.php`):
  - **Hub:** 16 cards (3 new); an admin without "manage settings" may
    not open it.
  - **General:** pagination 0, a timezone out of range and a bad colour
    are refused; save writes `Africa/Lagos` to the timezone file.
  - **Matching bonus:**
    - an unknown schedule, a missing hour and flush 7 are refused;
    - daily 03:00 is saved;
    - against a fixed clock, 03:20 runs and 13:20 skips; 24:00 runs at
      00:10; monthly "1" runs on the 1st and skips the 2nd; weekly
      "sat" runs on a Saturday.
  - **Configuration:** missing keys are refused; registration off and
    agree on are saved.
  - **Logo:** a PHP file named `.png` is refused.
  - **Robots:** reads the old `robots.xml`, saves `robots.txt`.
  - **Sitemap:** not XML and a DOCTYPE are refused; a valid one is
    saved.
  - **Custom CSS:** saved.
  - **Maintenance:** on/off, and a description is required.
  - **Cookie** and **notice:** saved.
  - **Sidebar:** active.
  - The real files the checks write are restored afterwards.
- **Browser checks** (`e2e/phase7f1.mjs`, 34 checks):
  - **Hub:** search by title and keyword, the empty result, and the
    Inertia and full-load cards.
  - **General:** the colour field and swatch; the matching schedule
    fields switching; the POSTs.
  - **Configuration:** the switches via their labels; all 11 sent.
  - **Logo:** only the chosen file is sent.
  - **Robots:** Tab in the editor.
  - **Maintenance, cookie, notice:** their POSTs.
  - No sideways scroll at phone width on all nine pages.
- **Regression:** 33 browser suites, 1080 checks, all pass (one
  ad-video timing check in `phase5c4` failed once and passed on a
  rerun; that page was not touched). `route:cache` and `view:cache`
  succeed; the route-name scan shows only the 21 known `ipn.`
  references.

### Step 108: Notification settings and languages in React (7f-2)

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Notification/Global.tsx` | `admin.setting.notification.global.{email,sms,push}` | `global_email_template`, `global_sms_template`, `global_push_template` (+ their nav and short-code partials) |
| `Notification/Templates.tsx` | `admin.setting.notification.templates` | `template/index.blade.php` |
| `Notification/TemplateEdit.tsx` | `admin.setting.notification.template.edit` | `template/{email,sms,push,nav,shortcodes}.blade.php` |
| `Notification/EmailSetting.tsx` | `admin.setting.notification.email` | `email_setting.blade.php` |
| `Notification/SmsSetting.tsx` | `admin.setting.notification.sms` | `sms_setting.blade.php` |
| `Notification/PushSetting.tsx` | `admin.setting.notification.push` | `push_setting.blade.php` |
| `Pages/Admin/Language/Index.tsx` | `admin.language.manage` | `language/lang.blade.php` |
| `Language/Keywords.tsx` | `admin.language.key` | `language/edit_lang.blade.php` |

`Components/Admin/NotificationNav.tsx` holds the shared pieces:

- the tab bar;
- the short-code table;
- the sandboxed email preview;
- the secret field;
- the "send a test" box.

**Duplicates removed:**

- The three global-template pages are one page with a `channel`.
- The three per-notification template editors are one page; email,
  SMS and push are tabs.

**Fixes:**

| Before | Now |
|---|---|
| **None of the notification pages opened.** The controller rendered `admin.notification.*`, but the views had been committed under `admin/layouts/notification/`, so every page answered "View not found". | React pages; the misplaced views are deleted. |
| **Secrets were sent to the browser.** The email and SMS pages put the SMTP password and every gateway's API secret into the form fields. | They are never sent. A field shows "A value is saved"; leaving it blank keeps the stored value (for every SMS gateway, so switching gateway does not wipe the others' keys). |
| **A language code was used as a file name.** Adding a language with code `../../composer` overwrote `core/composer.json` with the English keywords. | Codes must look like `fr`, `pt-br` or `zh_cn`; anything else is refused before any file is touched. |
| The default language, or English (the file every new language is copied from), could be deleted; deleting a missing id crashed. | Refused, with a message; a missing id is a 404. |
| "Update key" added any key it was given. Deleting a key that was already gone said "deleted". | Only existing keys; both now say when the key is not there. |
| Keyword files were rewritten in place. | Written to a temp file and moved, so a crash cannot leave half a file. Accented text is stored as is (`é`, not `é`). |
| Import answered a bare "success" to a jQuery call. | A toast that says how many keywords were added ("Nothing to import" when none). Keyword search also matches translations. |
| Template edit took any `{type}` and loaded the Blade view of that name. | `email`, `sms` or `push`; anything else is a 404. Status switches are explicit booleans; a sender address must be an email. |
| The email preview loaded admin HTML as a `data:` URL, so its scripts ran. | A sandboxed `srcdoc` iframe: no scripts. |
| SMTP port, Custom API URL and the Firebase upload were not checked. | Port 1–65535; the URL must be `http(s)` (no `javascript:`); the upload must be a service-account key (`private_key` + `client_email`). |
| The Firebase browser script was written relative to the working directory, with plain `json_encode`. | Written in the site root; quotes and `<` are escaped. A failed write is reported. |
| "File not found" on the push key download was a *success* toast. | An error toast. |

**Deleted:**

- `admin/layouts/notification/*` (15 views);
- `admin/language/{lang,edit_lang}.blade.php`.

**Verification:**

- **Server checks** (`p7f2_check.php`):
  - **Global templates:** all three channels; a bad sender address is
    refused.
  - **Templates:**
    - the list;
    - the editor for each channel, with its short codes;
    - type `index` is a 404 (view and update);
    - sms status on, email status off;
    - a bad sender address is refused.
  - **Email setting:**
    - the stored password is not in the page;
    - a blank password keeps it, and a new one replaces it;
    - port 99999 is refused;
    - SendGrid without a key is refused;
    - PHP mail can be selected.
  - **SMS setting:**
    - no secret is in the page;
    - switching to the Custom API keeps the Twilio and Clickatell
      secrets;
    - empty header rows are dropped;
    - a `javascript:` URL is refused.
  - **Push setting:** a quote in the app id is escaped in the script; a
    JSON that is not a key is refused.
  - **Languages:**
    - `../../composer` is refused and `composer.json` is intact;
    - French is added, as a copy of `en.json`;
    - English and the default language cannot be removed;
    - the default cannot be unset without another;
    - Bangla is removed, file and all;
    - a missing id is a 404.
  - **Keywords:**
    - list and search;
    - add, then add again (refused);
    - update with an accent; updating a missing key is refused;
    - delete twice;
    - import from English (translations kept) and from System (2,996
      keys, including React `t()` keys).
  - All real files are restored afterwards.
- **Browser checks** (`e2e/phase7f2.mjs`, 40 checks):
  - **Global templates:** the tab bar and channel cards as Inertia
    visits; the preview is sandboxed and follows the editor.
  - **Templates:** the status ticks; each editor sends only its own
    fields.
  - **Email setting:** the blank-password POST; the Mailjet fields; the
    test mail.
  - **SMS setting:** the custom API header/body rows (add and remove).
  - **Push setting:** the help steps; the key upload.
  - **Languages:**
    - adding a language (code pattern, flag required);
    - the keyword list loaded with `fetch`;
    - remove;
    - edit a key (the key as text);
    - import from System.
  - No sideways scroll at phone width on all ten pages.
- **Regression:** 34 browser suites, 1120 checks, all pass.
  `route:cache` and `view:cache` succeed; the route-name scan shows only
  the 21 known `ipn.` references.

### Step 109: Payment gateways, withdrawal methods and the KYC form in React (7f-3)

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Gateways/Automatic.tsx` | `admin.gateway.automatic.index` | `gateways/automatic/list.blade.php` |
| `Gateways/AutomaticEdit.tsx` | `admin.gateway.automatic.edit` | `gateways/automatic/edit.blade.php` |
| `Gateways/Manual.tsx` | `admin.gateway.manual.index` | `gateways/manual/list.blade.php` |
| `Pages/Admin/Methods/Form.tsx` | `admin.gateway.manual.{create,edit}`, `admin.withdraw.method.{create,edit}` | `gateways/manual/{create,edit}`, `withdraw/{create,edit}` |
| `Pages/Admin/Withdraw/Methods.tsx` | `admin.withdraw.method.index` | `withdraw/index.blade.php` |
| `Pages/Admin/Kyc/Setting.tsx` | `admin.kyc.setting` | `kyc/setting.blade.php` |

**Shared pieces:**

- `Components/Admin/FormBuilder.tsx`: the form members fill in (manual
  deposit, withdrawal, KYC).
  - It replaces `<x-generated-form>` and `<x-form-generator-modal>`
    (jQuery).
  - Fields can be added, edited, removed and moved up or down.
  - File types are ticked from the supported list instead of typed.
  - The server still receives the same `form_generator[...]` arrays.
- `FormProcessor::builderFields()` turns a saved form into the builder's
  rows.
- `Components/Admin/GatewayTabs.tsx` and `MethodTable.tsx` (the
  manual-gateway / withdrawal-method table) are shared too.

**Duplicates removed:**

- Add / edit manual gateway and add / edit withdrawal method were four
  copies of one form. They are now `Methods/Form.tsx` with a `kind`.
- `App\View\Components\FormGenerator` rendered a view that did not
  exist and was not used. Deleted, along with the two Blade builder
  components.

**Fixes:**

| Before | Now |
|---|---|
| **Gateway API secrets were sent to the browser** (secret key, private key, API key ... in the edit form). | Never sent: the field is empty with "A value is saved", and a blank field keeps the saved value. This works for global keys and for per-currency keys. `public_*` keys are still shown. |
| **A gateway's alias came from a hidden form field.** The alias names the code that processes its payments (`Gateway\<Alias>\ProcessController`), so a changed field could break the gateway. | Never taken from the request. |
| The same currency could be added twice; a currency missing from the request deleted every currency. | Refused ("NGN was added twice"); the currency list is only replaced when it is sent. The save is one transaction. |
| Removing a gateway currency with an unknown id crashed; the status routes accepted any gateway id. | 404; each list only changes its own gateways. |
| **Form builder:** an unknown file type threw a plain exception (server error); a file field could have no types; a select/radio/checkbox could have no options; two fields with the same label silently overwrote each other. | Each is refused with a message on the field. Extensions and options are trimmed and lower-cased; `xls` added. |
| A manual gateway's alias was its name with spaces turned into `_`: "Bank/Transfer" went into URLs as is, and two gateways with the same name shared an alias. | `Str::slug`, and a second gateway with the same name is refused. |
| A manual gateway that had lost its currency row was saved without one (it could not be used). | The row is re-created. |
| New withdrawal methods were created with whatever the `status` column defaulted to. | On, like a new manual gateway. |
| Image upload failures used the toast type `errors`, which does not exist (nothing was shown). | `error`, and the exception is reported. |
| A gateway "extra" URL pointing to a removed route crashed the edit page. | Shown as "(route … not found)". |
| **The builder's field dialog submitted the whole page** (found while testing: a form inside the page's form). | The dialog is not a `<form>`; Enter in it saves the field. |

**Also fixed in 7f-1 and 7f-2 pages:** the rich-text boxes (maintenance,
cookie, notices, email template, method instruction) had no visible
label; `RichTextEditor` only uses its `label` as `aria-label`. Each now
has a `<label>` above it.

**Deleted:**

- `admin/gateways/*` (6 views), `admin/withdraw/{index,create,edit}`;
- `admin/kyc/setting`;
- `components/{generated-form,form-generator-modal}.blade.php`;
- `app/View/Components/FormGenerator.php`.

**Verification:**

- **Server checks** (`p7f3_check.php`):
  - **Automatic gateways:**
    - list counts;
    - no secret anywhere in the edit page; a removed route is shown
      as text;
    - a blank secret keeps the saved one, and a tampered alias is
      ignored;
    - min > max, NGN twice and an unsupported EUR are refused;
    - USD is added and the secret rotated;
    - a save without the currency list keeps the currencies;
    - a blank per-currency secret is kept (Flutterwave);
    - remove; remove a missing currency (404);
    - disable twice ("already").
  - **Manual gateways:**
    - a bad extension, a file field without types, a select without
      options and duplicate labels are refused;
    - create (code 1000, alias `bank_transfer_gtb`, form saved with
      trimmed options and types);
    - the same name again is refused;
    - list and edit props;
    - renaming re-creates a lost currency row;
    - the automatic status route refuses a manual gateway.
  - **Withdrawal methods:** a minimum below the fixed charge is refused;
    create (enabled); list; edit; remove all fields; disable.
  - **KYC:** a file field without types is refused; save twice (one
    form row).
- **Browser checks** (`e2e/phase7f3.mjs`, 32 checks):
  - **Automatic gateways:**
    - search; disable (POST);
    - the masked secret and the copy URL;
    - add a currency, submit (multipart, blank secret, no internal
      fields);
    - remove a saved currency;
    - per-currency secret not required when saved.
  - **Manual gateway:**
    - move up; edit file types; the duplicate label refused;
    - Enter adds a field without submitting the page;
    - the multipart POST in the new order.
  - **Withdrawal method:** edit and POST.
  - **KYC:** add a file field; the unsaved warning; the JSON POST.
  - No sideways scroll at phone width on all eight pages.
- **Regression:** 35 browser suites, 1152 checks, all pass.
  `route:cache` and `view:cache` succeed; the route-name scan shows only
  the 21 known `ipn.` references.

### Step 110: Extensions, cron jobs and system information in React (7f-4a)

7f-4 is split in two: 7f-4a (this step) and 7f-4b (the frontend manager
and page builder).

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Extensions.tsx` | `admin.extensions.index` | `extension/index.blade.php` |
| `Pages/Admin/Cron/Index.tsx` | `admin.cron.index` | `cron/index.blade.php` |
| `Cron/Schedules.tsx` | `admin.cron.schedule` | `cron/schedule.blade.php` |
| `Cron/Logs.tsx` | `admin.cron.schedule.logs` | `cron/logs.blade.php` |
| `Pages/Admin/System/Info.tsx` | `admin.system.info` | `system/{info,server,optimize,update,update_log}.blade.php` |

**Duplicates removed:**

- **Application, Server, Clear Cache, System Update and Update Log**
  were five small pages, none of them in the sidebar or the settings
  hub. They are one page, System Information. The old URLs redirect to
  it.
- The settings hub has two new cards: **Cron Jobs** and **System
  Information**. Neither was reachable except through the search.

**Fixes:**

| Before | Now |
|---|---|
| **One-click "System Update"** downloaded the vendor's release, unzipped it over the whole site and ran its SQL. On this heavily customised installation it would overwrite the custom code (everything in these notes) and could break the database. | Switched off. The page shows the version and the update log, with the reason; the POST answers with the same message. Updates go through the repository. **For the owner:** confirm nobody relies on the vendor updater. |
| **"Run Now" on a cron job never worked.** It linked to the public `/cron` URL without the secret token that URL requires, so it always answered 403. | A POST to `admin.cron.run/{id}` runs the job on the server and reports its error, if any. |
| **Built-in cron jobs were created with `new $class`**, so a job class that needs a service in its constructor (as `CronController` does) crashed with an `Error`. The catch only took `Exception`, so it also stopped every later job in the run. | The container builds the class, and any `Throwable` is logged on that job's run and reported; the other jobs still run. `CronController::runJobs()` is shared by the public URL and "Run Now". |
| Pause / play a cron job was a **GET** link, as was "clear cache". | POSTs with a confirm; the job's pause takes an explicit state. |
| Extension secret keys (reCAPTCHA secret ...) were sent to the browser. | Never sent; a blank field keeps the saved value. |
| A cron job could be saved with a schedule that does not exist or a `javascript:` URL; adding one said "updated". | Refused; "added". Editing a built-in job never changes its URL or alias. |
| Editing a disabled cron schedule switched it back on. | Only the name and interval change. |
| The server page received all of `$_SERVER`, which under `php -S` holds environment variables (APP_KEY, DB_PASSWORD). | Only the six values it shows are sent. |

The cron page also shows the crontab command to install. It warns when
`CRON_SECRET` is not set: without it the `/cron` URL refuses every
call, so no scheduled job runs at all.

**Deleted:** `admin/extension/*`, `admin/cron/*`, `admin/system/*` (9
views). Also removed from `SystemController`: the vendor-update code
and its zip helpers.

**Verification:**

- **Server checks** (`p7f4a_check.php`):
  - **Extensions:**
    - no secret in the page;
    - a blank secret keeps the saved one;
    - a missing required key is refused;
    - enable.
  - **Cron:**
    - the list, schedules and command;
    - Run Now:
      - a plain job (logged, next run moved);
      - a job whose class needs a service;
      - a job that throws a `TypeError` (logged, error toast);
    - a full run with the failing job first still runs the next job;
    - pause twice ("already"), and GET pause is refused;
    - an unknown schedule and a `javascript:` URL are refused;
    - add;
    - editing a built-in job ignores its URL; deleting a built-in job
      is a 404;
    - delete;
    - a disabled schedule stays disabled when edited;
    - interval 0 is refused;
    - logs, resolve, flush.
  - **System:**
    - the page sends only the shown values: an env secret in
      `$_SERVER` is not in the page;
    - the five old URLs redirect;
    - clear cache;
    - the update is refused.
  - **Hub:** the two new cards are React.
- **Browser checks** (`e2e/phase7f4a.mjs`, 25 checks):
  - **Extensions:** the masked secret, the POST, the help HTML and
    image, enable.
  - **Cron:**
    - the command and the secret warning; the error icon;
    - Run Now and Pause POSTs;
    - the built-in edit without URL; add;
    - logs as an Inertia visit;
    - schedule edit; log flush.
  - **System:** the info lists; clear cache.
  - No sideways scroll at phone width on all five pages.
- **Regression:** 36 browser suites, 1177 checks, all pass. The 7f-1
  hub test now counts the cards from the fixture, and opens SEO as its
  Blade example: Payment Gateways is a React page now.
  `route:cache` and `view:cache` succeed; the route-name scan shows only
  the 21 known `ipn.` references.

### Step 111: Frontend content, pages and SEO in React (7f-4b)

The frontend manager edits the texts, pictures and lists of items the
website shows. The active template's `sections.json` describes each
section:

- **content**: one set of values, such as the register page heading;
- **element**: a list of items, such as social links, FAQ entries or
  policy pages;
- **field types**: text, textarea, rich text, icon, a choice, images
  with their size, and a slug made from another field.

The page builder chooses which sections each page shows, and in what
order.

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Frontend/Index.tsx` | `admin.frontend.index` | `frontend/index.blade.php` |
| `Frontend/Section.tsx` | `admin.frontend.sections` | `frontend/section.blade.php` |
| `Frontend/Element.tsx` | `admin.frontend.sections.element` | `frontend/element.blade.php` |
| `Frontend/Seo.tsx` | `admin.seo`, `admin.frontend.sections.element.seo`, `admin.frontend.manage.pages.seo` | `frontend/seo.blade.php`, `frontend/frontend_seo.blade.php`, `frontend/builder/seo.blade.php` |
| `Frontend/Pages.tsx` | `admin.frontend.manage.pages` | `frontend/builder/pages.blade.php` |
| `Frontend/PageSections.tsx` | `admin.frontend.manage.section` | `frontend/builder/index.blade.php` |

**New shared pieces:**

- **`Components/Admin/ContentFields.tsx`** draws a section's fields from
  the description the server sends. The section form, the item dialog
  and the item page all use it. It also holds the slug tools:
  - `toSlug()` cleans the slug as you type;
  - `useSlugCheck()` asks the server, after a short pause, whether the
    slug is free;
  - `SlugStatus` shows the answer.
- **`Components/Admin/IconPicker.tsx`** replaces the jQuery
  fontawesome-iconpicker. You can type an icon's class or pick it from
  a searchable grid of the 1,411 Line Awesome icons. The grid's list
  is `resources/js/data/lineIcons.json`, a separate 27 kB file that
  loads only when the picker opens.
- **`Components/Admin/TagInput.tsx`** holds the SEO keywords, replacing
  select2 "tags". Enter or a comma adds a keyword; Backspace or × removes
  one.
- **`App\Support\SeoForm`** holds the server side of the shared SEO
  form: the page props, the rules and the saved values.

**Duplicates removed:**

- **The three SEO pages** (site-wide, a page's, an item's) were copies.
  They are now one page, `Frontend/Seo.tsx`:
  - on the site-wide SEO every field is required;
  - on the other two every field is optional, and a note links to the
    site-wide SEO.

  The site-wide SEO used to save through the section-content endpoint
  (`type=data`). It has its own `POST admin/seo` (`admin.seo.update`)
  now.
- **The Templates page** was switched off (`abort(404)`) because there
  is a single template. Its form could still set `active_template` to
  any name, and that name builds view paths and file paths.
  - `GET frontend/templates` now redirects to the frontend manager.
  - The `POST` route is gone.
  - The `getTemplates()` helper, which sent the purchase code to the
    vendor's server, is removed.
- **Not reachable before:** the settings hub has two new cards,
  **Frontend Content** and **Manage Pages**. Neither page was linked
  anywhere; only Policy Pages had a link, from General Setting. Every
  card in the hub now opens a React page.

**Fixes:**

| Before | Now |
|---|---|
| **Any item could be overwritten through any section.** `frontend-content/{key}` loaded `Frontend::findOrFail($request->id)` with no check, so an id from another section, or the site SEO, cookie or maintenance row, was replaced by this section's fields. | The id must be an item of this section on the active template. Otherwise the answer is 404. |
| **The whole request was saved.** Every input except a few named ones went into `data_values`. That included `has_image` and any key a request added. | Only the fields `sections.json` lists for that part are saved. |
| **The HTML clean-up was undone.** Each value went through HTMLPurifier and then `htmlspecialchars_decode()`. Text typed as `&lt;script&gt;` came out as a real `<script>`. Rich text (policy pages) and icons are printed raw on the site, so a settings admin could put a script on public pages. | Rich text goes through `FormPresenter::safeHtml()` with no decode. An icon is saved as `<i class="…">` built on the server from a checked class name (letters, digits, spaces, `_`, `-`). Plain text is saved as typed, because the site escapes it when printing. |
| **The maintenance message and the cookie policy were saved as typed** and printed raw. | Both go through `safeHtml()` (in `GeneralSettingController`). |
| **"Remove" deleted any frontend row by id**, including a section's content and the site SEO, cookie and maintenance rows. Pages break when those rows are missing. | Only items (`*.element`) can be removed. Their pictures and SEO picture are removed with them. |
| **The item SEO page and its save took any row id**, from any section. | The item must belong to that section, and the section must have SEO. |
| **The page builder saved any text as a section key.** The site includes `sections.<key>` for each key, so a crafted key named any view. | Each key must be a section the template offers on pages, not a fixed one such as login or footer. Repeated keys are dropped, and keys whose section no longer exists are ignored when the page opens. |
| **A page's slug could be the address of another part of the site** (`agreement`, `maintenance-mode`...). Such a page could never be opened. | Refused. The slug check also reports these slugs as taken. |
| **Item slugs were compared before cleaning.** "First Post!" and "first-post" both passed the unique check, then both were saved as `first-post`. Uniqueness also covered every section. | The slug is cleaned first, then checked within the section's items. The same applies to page slugs. |
| **Built-in pages (home, blog...) could be renamed** by posting to the update URL; only the form was hidden. | Refused. The update URL now names the page (`manage-pages/update/{id}`). |
| The "Make Slug" check on the item page (jQuery) asked the server on every keystroke, and the result could arrive out of order. | One request after a 300 ms pause; earlier requests are cancelled. |

**Behaviour changes worth knowing:**

- After adding an item with its own page, you land on that item's edit
  page, not on an empty "add" form. Items with SEO still go to their
  SEO page first, as before.
- Sections are ordered on the page builder with arrow buttons or by
  dragging (HTML5 drag and drop instead of jQuery UI). A section can be
  added only once. A warning appears until "Update Now" is clicked.
- The public pages built from these sections (home, custom pages,
  contact, FAQ, policy) currently redirect to the login page; that was
  already so before this step. The content still drives the parts that
  are live:
  - social links;
  - the login and register texts;
  - KYC messages;
  - site SEO;
  - the maintenance page.

**Deleted:** `admin/frontend/*`, 9 views (6 pages plus 3 builder pages).

**Verification:**

- **Server checks** (`p7f4b_check.php`):
  - **Index:** the 20 editable sections; Templates redirects.
  - **Content (about):**
    - a missing field is refused;
    - extra keys are not saved;
    - the picture is uploaded and kept on the next save;
    - `type=data` and an unknown section answer 404.
  - **Items (service):**
    - an icon containing HTML is refused;
    - the saved icon is `<i class="las la-seedling"></i>` and the form
      gets the class back;
    - the id of a FAQ item or of the SEO row is refused (404) and
      leaves that row unchanged;
    - an item is edited in place.
  - **Blog:**
    - the field types (slug made from title, rich text);
    - `onclick` and `<script>` are stripped while `&amp;` stays;
    - the slug is cleaned to `first-post`; a second "First Post!" is
      refused;
    - the slug check, including the item's own slug;
    - item SEO with keywords trimmed and de-duplicated;
    - the SEO of another section's item is refused.
  - **Site SEO:** keywords are required; saved in place.
  - **Remove:** the SEO row and a section's content cannot be removed;
    an item can.
  - **Pages:**
    - a site address (`agreement`) and a taken slug are refused; add;
    - the slug check;
    - a view path and a fixed section are refused as section keys;
    - a duplicate key is dropped; an empty list saves null;
    - the home page cannot be renamed or deleted;
    - rename; renaming onto a taken slug is refused;
    - page SEO saves with every field optional; delete.
  - **Maintenance:** `<script>` and `onerror` are removed.
  - **Hub:** the three cards are React; a staff admin with only "manage
    account" is refused on every route.
- **Browser checks** (`e2e/phase7f4b.mjs`, 49 checks):
  - **Index:** search, the empty result, and cards that open as
    Inertia visits.
  - **Content form:** the multipart POST with fields at the top level
    and `image_input[about_image]`.
  - **Item dialog:**
    - prefilled fields; the icon picker picks `las la-tree`;
    - the POST carries `type`, `id` and the icon class;
    - add; remove.
  - **Item page:**
    - Make Slug finds a taken slug, and Submit is disabled;
    - typed slugs are cleaned and checked on the server;
    - the rich-text POST;
    - an item's own slug is not flagged.
  - **Policy pages:** no Back link.
  - **SEO:**
    - the keyword tags (comma, Enter, ×);
    - the `keywords[]` POST;
    - the optional note on item SEO.
  - **Pages:** add with the slug check; built-in pages have no Delete
    button.
  - **Page sections:**
    - move up, add, and drag in from the list and within the page;
    - remove; the ordered `secs` POST;
    - rename a custom page;
    - an empty page saves `[]`.
  - No sideways scroll at phone width on all 11 pages.
- **Regression:** 37 browser suites, 1226 checks, all pass. The 7f-1 hub test used SEO as its
  example of a Blade card. Every hub card is React now, so it checks
  that SEO opens as an Inertia visit.
  `route:cache` and `view:cache` succeed; the route-name scan shows only
  the 21 known `ipn.` references.

### Step 112: Admins, roles and permissions in React (7f-5a)

7f-5 is split in two: 7f-5a (staff accounts and access, this step) and
7f-5b (support tickets, notices, flashcards).

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Staff/Admins.tsx` | `admin.admins.index` | `admins/{index,create,edit,show}.blade.php` |
| `Staff/Roles.tsx` | `admin.roles.index` | `roles/{index,create,edit,show}.blade.php` |
| `Staff/Permissions.tsx` | `admin.permissions.index` | `permissions/{index,create,edit,bulk-create}.blade.php` |

Each is one page with dialogs. The old create, edit and show URLs (and
"bulk create" for permissions) redirect to the list, and the old bulk
POST URL still works.

**New shared piece:** `Components/Admin/PermissionOpens.tsx` shows
what a permission opens. The data comes from the new
`AdminAccessMiddleware::grantedBy()`, which reads
`config/admin_access.php`.

**Duplicates removed:**

- **12 Blade views became 3 pages.** The admin "show" page, a 333-line
  profile card, became an "Access" dialog. The role "show" page and the
  per-role permission dialogs are now part of the role editor.
- **"Add permission" and "Bulk create permissions" were two forms.**
  They are now one dialog with "Add another".
- **The sidebar had three groups** (Manage Admin, Admin Roles, Admin
  Permissions), each holding a single link. They are now one group,
  **Staff & Access**, with Admins, Roles and Permissions.

**Fixes:**

| Before | Now |
|---|---|
| **"Deactivate admin" did nothing.** `is_active` was saved, but neither the login nor any middleware read it. A deactivated admin could still sign in and kept full access. | **Login** (`LoginController::authenticated()`): a deactivated admin is signed out right after the password check, with "This admin account has been deactivated". **Already signed in** (`AdminAccessMiddleware`): signed out on their next page. Accounts whose column is empty count as active. |
| **The auth middleware logged every admin request.** `AdminAuthenticate` wrote three lines to `laravel.log` per request, including the admin's record (name, username, e-mail). | No logging. |
| **The owner account (id 1) was hidden only in the page.** Another super-admin could still edit, deactivate or delete it by URL. | Refused on the server; the list shows it with an "Owner" badge and no actions. |
| **Editing an admin required an e-mail address,** although the add form made it optional, and the e-mail was then never saved (the line was commented out). Admins without e-mail could not be edited. | E-mail is optional and saved. |
| **Roles from another guard could be given.** Admins could get `web` roles, and roles could get `web` permissions; the `exists` rule did not check the guard. | Only `admin` roles and permissions are accepted. |
| **Error messages showed raw exception text** ("Error creating admin user: SQLSTATE..."), and all messages used `with('success')`, which the React layout does not show. | Normal toasts. Exceptions are not caught and shown. |
| **Renaming or deleting a permission silently locked pages.** The access map names permissions in code, so a renamed "manage settings" locked the settings pages for everyone but super-admin. | Permissions the map uses can be regrouped but not renamed or deleted. The page explains why. |
| **A new role could be called "super-admin"**, and its unique check covered every guard. | "super-admin" is reserved, and the unique check is per guard. |
| Activate / deactivate flipped the state blindly, so two clicks undid each other. | It takes an explicit `state` and answers "already" on a repeat. |

**What the Permissions page now shows:** for each permission, the route
patterns it opens and whether for viewing, changing or both. For
example, "view users" shows `users.* (view)`. A permission the map
does not name says "opens nothing".

- Most of the seeded permissions ("view deposits", "approve
  withdrawals", ...) open nothing. Only the names in
  `config/admin_access.php` grant access.
- The summary line says how many open pages.
- The role editor has a switch to show only the permissions that open
  pages.

**For the owner:**

- **Fine-grained permissions:** if you want them (for example a deposit
  clerk who can approve deposits but not touch settings), add their
  routes to `config/admin_access.php`. Until then a new permission
  grants nothing. That is safe, but easy to misunderstand.
- **The licence check:** every admin login calls the vendor's licence
  server (`Onumoti::getData()`) with the purchase code and site URL.
  - If that server does not answer, admin login fails with "Something
    went wrong".
  - The server's reply can switch the site into maintenance mode.

  This is a licensing decision and is left as it is; 7g (admin login
  pages) is the place to decide.

**Deleted:** `admin/admins/*`, `admin/roles/*`, `admin/permissions/*`
(12 views). Also deleted: the unused `update1`, `store22` and
`update22` methods, and the controllers' own constructor middleware
(`admin.auth`, "manage roles", "manage permissions"). The routes have
always been super-admin only, and that is unchanged.

**Verification:**

- **Server checks** (`p7f5a_check.php`):
  - **`grantedBy`:** "manage settings" opens 8 route patterns; "view
    users" opens `users.*` for viewing and the two attachment routes;
    "view deposits" opens nothing.
  - **Admins:**
    - adding is refused with no role, a web role, a taken username or
      mismatched passwords;
    - add without e-mail: active, password hashed;
    - edit sets the e-mail and keeps the password when it is blank;
    - editing yourself is refused;
    - a second super-admin cannot edit, deactivate or delete the owner;
    - deactivate, then again ("already"); a missing state is refused;
    - delete removes the role links too;
    - the 8 old URLs redirect.
  - **Deactivated admin:**
    - signed in: the next page signs them out with the message;
    - logging in with the right password is refused;
    - after activation, login works and pages open.
  - **Roles:**
    - "super-admin" as a name, web permissions and a duplicate name are
      refused;
    - add; rename and change permissions; clear permissions;
    - super-admin cannot be edited or deleted;
    - a role in use cannot be deleted; an unused one can;
    - a web role answers 404.
  - **Permissions:**
    - a duplicate in the list (any case) and an existing name are
      refused;
    - add two, trimmed; the old bulk URL works;
    - rename a free one; renaming "manage settings" is refused,
      regrouping it is allowed;
    - deleting map permissions is refused; deleting a free one works.
  - **Access:** a staff admin is refused on all three pages; the sidebar
    shows the one Staff & Access group with Admins active.
- **Browser checks** (`e2e/phase7f5a.mjs`, 26 checks):
  - **Admins:**
    - your row shows Profile, "You" and "Owner";
    - edit prefill and PUT; add, with Submit disabled until a role is
      ticked;
    - deactivate and delete;
    - the Access dialog.
  - **Roles:**
    - no Edit on super-admin, no Delete on admin;
    - ticked permissions, "opens nothing" and the route patterns;
    - the "only useful" switch;
    - group "all" and the PUT; add.
  - **Permissions:**
    - the summary count; Delete only where allowed;
    - search;
    - add two rows (the group is copied) and the POST;
    - a locked name is read-only and the group can change.
  - **Sidebar:** the single group.
  - No sideways scroll at phone width on all three pages.
- **Regression:** 38 browser suites, 1252 checks, all pass.
  `route:cache` and `view:cache` succeed; the route-name scan shows only
  the 21 known `ipn.` references.

### Step 113: Support tickets, direct notices and flashcards in React (7f-5b)

**Pages:**

| Page | Route | Replaces |
|---|---|---|
| `Pages/Admin/Support/Tickets.tsx` | `admin.ticket.index`, `.pending`, `.answered`, `.closed` | `support/tickets.blade.php` |
| `Support/Ticket.tsx` | `admin.ticket.view` | `support/reply.blade.php` |
| `Pages/Admin/Notices.tsx` | `admin.notices.index` | `notices/{index,create}.blade.php` |
| `Pages/Admin/Flashcards.tsx` | `admin.flashcard.index` | `flashcard/index.blade.php` |

**Duplicates removed:**

- **The four ticket lists** (all, pending, answered, closed) shared one
  Blade template. They are one page with a tab per status. The four
  routes stay (the sidebar links to them), and the search keeps the tab.
- **"Send Direct Notice"** was its own page. It is now a dialog on the
  notices list, and `notices/create` redirects there.
  - The dialog uses the same fields as Users → member → Send
    Notification (`NotificationFields`).
  - Both send through `AdminNoticeService`.
- The ticket page reuses the member pages' `AttachmentPicker`. The
  attachment rules come from the shared `SupportTicketManager` trait,
  so they are the same on both sides.

**Fixes:**

| Before | Now |
|---|---|
| **Flashcard upload took any file** up to 50 MB (`required\|file`). The file kept its own extension in the public `assets/images/flashcard` folder, so a `.php` or `.html` file could be uploaded where the web server serves it. | The file must be an image (jpg, jpeg, png, gif, webp) or a video (mp4, webm, ogg, mov) matching the chosen type. Both the content (`mimes`) and the name (`extensions`) are checked: PHP code named `.jpg` and an `.html` file are refused. |
| **Ticket attachments had no size limit.** The 5-file and file-type checks ran in one closure, and an unused `convertToMb()` hinted at a limit that never existed. | At most 5 files, 10 MB each (or PHP's upload limit if lower), checked per file. The forms show the real limit. Member and admin share the rules. |
| **A ticket attachment whose file was missing** gave a PHP warning and a broken download (`mime_content_type` on a missing path, then raw headers and `readfile`). | 404. Downloads use `response()->download()`. |
| **Ticket numbers came from `rand(100000, 999999)`** with no uniqueness check. The member's link opens a ticket by its number, so two tickets could share one. | `random_int`, repeated until the number is unused. |
| **The ticket list's status badge was HTML built on the server** and printed raw (`statusBadge`). | Label and colour are sent; React draws the badge. Messages are shown as text with their line breaks. A `<script>` in a message shows as text, as before. |
| **Direct notices could be sent by a switched-off channel.** The `via` rule allowed email, sms and push whatever the settings said. | Only switched-on channels are offered and accepted. When all are off, the page says so and has no send button. A wrong username is a field error. |
| **Flashcard show/hide flipped the state blindly**, and deleting a card left its "seen" records. | An explicit state, with "already" on a repeat. Delete also removes its views. |

**Behaviour change:** deleting a ticket message used to say "Support
ticket deleted"; it deletes one message, so it now says "Message
deleted".

**Member side:** only the attachment error display changed. The two
ticket pages now show per-file errors (`attachments.0`) as well as
list errors.

**For the owner:**

- **Who can handle tickets and flashcards:** both are super-admin only,
  because the access map does not name them. The seeded "view tickets",
  "reply tickets", "close tickets", "delete tickets" and "manage
  flashcards" permissions open nothing; the Permissions page says so.
  To let support staff handle tickets, map `admin.ticket.*` in
  `config/admin_access.php`. Members' tickets can hold personal
  documents, so this is your call.
- Direct notices need "manage settings", as before.

**Deleted:** `admin/support/*`, `admin/notices/*`, `admin/flashcard/*`
(5 views).

**Verification:**

- **Server checks** (`p7f5b_check.php`):
  - **Lists:** the four tabs show the right tickets; search.
  - **One ticket:**
    - messages newest first, with authors and attachments; `maxUpload`
      shows PHP's 2 MB;
    - replies with a `.php` attachment, a 12 MB pdf or six files are
      refused;
    - a reply with a pdf: saved with the admin id, ticket marked
      answered;
    - download of that file; a missing file is 404;
    - close; delete a message with its attachments;
    - ticket numbers are distinct.
  - **Notices:**
    - stats and rows (HTML stripped to text); search by username;
    - a search for a member who has no notice returns none;
    - `notices/create` redirects;
    - an unknown username, a switched-off channel and an e-mail
      without subject are refused;
    - send logs a notice; with every channel off the send is refused.
  - **Flashcards:**
    - list with "seen" counts;
    - refused: a `.php` file, an `.html` file, PNG bytes named `.mp4`,
      PHP code named `.jpg`, a picture uploaded as a video;
    - an image is saved;
    - hide, then again ("already"); delete removes its views.
  - **Access:** a staff admin is refused on all three areas.
- **Browser checks** (`e2e/phase7f5b.mjs`, 28 checks):
  - **Tickets:**
    - tabs, search and Details are Inertia visits;
    - a closed ticket has no reply form;
    - a script shows as text and line breaks are kept;
    - staff badge and attachment links;
    - reply multipart with `attachments[0]`; close; delete a message.
  - **Notices:**
    - stats; the send dialog with only the switched-on channel;
    - the POST trims the username;
    - with every channel off the page warns and has no button.
  - **Flashcards:**
    - previews;
    - the file chooser's `accept` follows the type;
    - upload multipart, and the form empties afterwards;
    - hide (spoofed PATCH with state) and delete.
  - No sideways scroll at phone width on all seven page states.
- **Regression:** 39 browser suites, 1280 checks, all pass.
  `route:cache` and `view:cache` succeed; the route-name scan shows only
  the 21 known `ipn.` references.

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
     response. (A mistake here no longer shows an error pop-up: since Step
     78 the browser then loads the page normally. That still costs a
     second request.)
   - When the page is in the sidebar menu, remove its route from
     `DashboardLayout::BLADE_ROUTES`.
   - **Admin pages** go in `Pages/Admin/` (they get `AdminLayout`
     automatically) and their route goes in `AdminLayout::REACT_ROUTES`,
     so the sidebar and search open them as Inertia visits. Start the page
     with `<PageHeader title>` (Step 94).
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

- ~~**The sidebar is still Blade.**~~ Fixed in Step 78: the sidebar is React
  and updates on every visit.
- **Every Inertia response includes the whole translation file.** That is
  fine while it is small. If it grows large, cache it per asset version or
  share only the keys a page needs.
- **Session expiry (HTTP 419)** shows Laravel's error page inside an
  Inertia modal. Phase 3 adds a handler that reloads the page with a "session
  expired" toast.
- **Hard-coded strings in Blade**, about 210 of them (see the earlier audit),
  are fixed as each page is migrated, because every React string goes
  through `t()`.
