# WIIFARMVEST REMEDIATION & IMPLEMENTATION NOTES

This document logs all fixes, architectural changes, code modifications, and database additions performed on the Wiifarmvest Platform.

---

## Phase 1: Immediate Financial & Calculation Hotfixes
**Date:** October 13, 2026  
**Status:** Completed  

### 1.1 Recalibration of Indirect Referral Commission (CRP Budget Alignment)
* **Target File:** `core/app/Http/Helpers/helpers.php` (Lines 2315–2361: `indirectBonus()`)
* **Problem Identified:**
  * The Compensation Engineering Blueprint allocates a maximum of **5.00%** to indirect referral incentives.
  * The codebase previously had hardcoded rates across 5 tiers totaling **13.00%** (`0.06`, `0.03`, `0.02`, `0.01`, `0.01`).
  * Combined with Direct Referral and Binary Matching, new registration acquisition bonuses were exceeding 30%, leaking 8.00% cash on every registration and package upgrade.
* **Changes Applied:**
  * Recalibrated the 5 upline tier commission rates to equal exactly **5.00%**:
    * **Tier 1 (Sponsor's Sponsor):** `2.00%` (`round($pv * 0.02, 2)`)
    * **Tier 2:** `1.00%` (`round($pv * 0.01, 2)`)
    * **Tier 3:** `1.00%` (`round($pv * 0.01, 2)`)
    * **Tier 4:** `0.50%` (`round($pv * 0.005, 2)`)
    * **Tier 5:** `0.50%` (`round($pv * 0.005, 2)`)
    * **Sum:** `2.00% + 1.00% + 1.00% + 0.50% + 0.50% = 5.00%`
  * Added 2-decimal currency rounding (`round(..., 2)`) to eliminate fractional Kobo precision drift.
* **Verification / Test Result:**
  * For a 450 PV registration (`₦270,000` volume at `₦600/PV`):
    * Tier 1 receives: `₦5,400.00`
    * Tier 2 receives: `₦2,700.00`
    * Tier 3 receives: `₦2,700.00`
    * Tier 4 receives: `₦1,350.00`
    * Tier 5 receives: `₦1,350.00`
    * **Total Disbursed:** `₦13,500.00` (5.00%), saving the platform `₦21,600.00` in overpayment per 450 PV enrollment.

---

### 1.2 Registration Key-In Bonus Correction (CRP Budget Alignment)
* **Target File:** `core/app/Http/Controllers/User/DistributorController.php` (Lines 289–300)
* **Problem Identified:**
  * The data-entry operator ("Key-In") bonus was hardcoded at `2.00%`: `round(($pv * 0.02 * 600), 2)`.
  * The Blueprint strictly specifies a **1.00%** budget allocation.
* **Changes Applied:**
  * Reduced multiplier from `0.02` to `0.01`:
    ```php
    // Key-In Bonus: 1.00% of registration PV to the operator who keyed in the registration
    // Blueprint limit: 1.00% (0.01) * PV * ₦600/PV
    $user_to_Get_keyInBonus = auth()->user();
    $pv = (float) $project->pv;
    $keyInBonus = round(($pv * 0.01 * 600), 2);
    ```
* **Verification / Test Result:**
  * For a 450 PV registration:
    * Key-In payout is now `₦2,700.00` (1.00%) instead of `₦5,400.00` (2.00%), cutting data-entry operator overhead by 50%.

---

### 1.3 Affiliate Store Percentage Bonus Payout Bug Fix
* **Target File:** `core/app/Services/AffiliateOrderService.php` (Lines 179–186: `creditAffiliateBonus()`)
* **Problem Identified:**
  * When completing an affiliate order, line 180 was calculating:
    ```php
    $total = $order->items->sum(
        fn ($item) => (float) ($item->product?->affiliate_bonus_value ?? 0) * $item->quantity
    );
    ```
  * For products with a percentage-type bonus (e.g., 10% on a ₦40,000 item), `affiliate_bonus_value` stores `10`. The code treated `10` as a flat Naira amount, paying the affiliate `₦10` instead of `₦4,000`.
* **Changes Applied:**
  * Updated `creditAffiliateBonus()` to sum the already computed, item-level `bonus_amount` column generated at checkout, with backward-compatible fallback to product calculation:
    ```php
    $order->loadMissing('items.product');
    $total = round((float) $order->items->sum(function ($item) {
        if ((float) $item->bonus_amount > 0) {
            return (float) $item->bonus_amount;
        }
        return (float) ($item->product ? $item->product->calculateAffiliateBonus($item->unit_price, $item->quantity) : 0.0);
    }), 2);
    ```
* **Verification / Test Result:**
  * Affiliate marketers now receive their full legitimate commission on percentage-based products while flat-rate bonuses remain intact.

---

## Phase 2: System Stability & Missing Models
**Date:** October 13, 2026  
**Status:** Completed  

### 2.1 Missing Leadership Models & Database Schema
* **Problem Identified:**
  * `core/app/Services/LeadershipBonusService.php` imports three models: `App\Models\LeadershipSetting`, `LeadershipLeader`, and `LeadershipBonusLog`.
  * None of these models existed in `core/app/Models/`, and neither did their database tables.
  * Whenever a member earned a binary matching bonus, `MatchingBonusService::processMatrix()` invoked `payLeaders()`, causing continuous runtime errors and logging `[Leadership] Paying leaders failed`.
* **Changes Applied:**
  1. **Created Model `core/app/Models/LeadershipSetting.php`:**
     * Implemented singleton accessor `LeadershipSetting::current()` with Blueprint defaults (`required_pv: 1000.00`, `percentage: 4.00`, `max_levels: 5`, `is_active: true`, `enabled_at: now()`).
     * Added helper methods: `isActive()`, `percentageLabel()`, `levelsLabel()`.
  2. **Created Model `core/app/Models/LeadershipLeader.php`:**
     * Tracks leaders who meet binary PV qualifications (`user_id`, `pv_left`, `pv_right`, `required_pv`, `status`, `qualified_at`, `notified_at`).
     * Added `scopeActive()` and user relationship.
  3. **Created Model `core/app/Models/LeadershipBonusLog.php`:**
     * Tracks each leadership distribution (`leader_id`, `source_user_id`, `matching_bonus_log_id`, `level`, `leg`, `matching_amount`, `percentage`, `amount`, `trx`).
  4. **Updated Model `core/app/Models/MatchingBonusLog.php`:**
     * Added `'leadership_processed_at'` to `$fillable` and `'leadership_processed_at' => 'datetime'` to `$casts`.
  5. **Created Console Command `core/app/Console/Commands/ProcessLeadershipBonusCommand.php`:**
     * Artisan signature `leadership:process` to sweep pending matching bonus logs and auto-qualify eligible leaders.
  6. **Created Database Migration & SQL Script:**
     * Laravel migration: `core/database/migrations/2026_10_13_000001_create_leadership_bonus_tables.php`
     * Standalone SQL file: `core/database/leadership_bonus_tables.sql` for direct database execution.

---

### 2.2 Upline Repurchase Volume Sharing Restored
* **Target File:** `core/app/Http/Controllers/User/StockistController.php` (Lines 586–636)
* **Problem Identified:**
  * When members purchased products from stockists, `recordRepurchasePv()` saved personal repurchase PV but never distributed volume upward along the binary matrix parents.
  * `shareRepurchasePvUpline()` contained a commented-out self-call (`//$this->shareRepurchasePvUpline($user, $pvEarned);`) and was disconnected from the stockist invoice redemption process.
  * As a consequence, uplines were not earning their repurchase PV toward rank awards or ACB (Achievers Celebrated Bonus).
* **Changes Applied:**
  * Connected `shareRepurchasePvUpline($user, $pvEarned)` inside `recordRepurchasePv()`.
  * Removed the dead recursive comment in `shareRepurchasePvUpline()`, ensuring safe upward tree traversal bounded by `MAX_UPLINE_PV_LEVELS` (50 levels).
  * Repurchase PV is now properly credited to qualifying uplines' `RepurchasePv` records and logged to `pv_logs` against the correct binary leg.

---

## Phase 3: Blueprint Compensation Feature Delivery
**Date:** October 13, 2026  
**Status:** Completed  

### 3.1 Stockist Sponsorship Override Bonus (3.00%)
* **Target Files:**
  * `core/app/Models/Stockist.php`
  * `core/app/Services/StockistSponsorBonusService.php` (New)
  * `core/app/Services/WalletService.php`
  * `core/app/Services/StockistRebateService.php`
* **Problem Identified:**
  * The Compensation Engineering Blueprint dictates a **3.00%** sponsorship override in the Commerce Pool (CP) for distributors who refer and onboard operating stockists.
  * In the existing code, stockists had no `sponsor_user_id` linkage, and no commission was ever calculated or disbursed when stockists transacted product volume.
* **Changes Applied:**
  1. **Stockist Model Updates (`Stockist.php`):**
     * Added `sponsor_user_id` to `$fillable` array.
     * Added `public const SPONSOR_BONUS_PERCENT = 3.00;`.
     * Added Eloquent relationship `public function sponsor() { return $this->belongsTo(User::class, 'sponsor_user_id'); }`.
  2. **Created `StockistSponsorBonusService.php`:**
     * Implemented `calculateBonus(Product $product, int $quantity)` using Blueprint formula: `PV × 3.00% × ₦600/PV × quantity`.
     * Implemented `credit()` to disburse bonuses directly into the introducing distributor's `stockist_sponsor_bonus` wallet.
  3. **Wallet Integration (`WalletService.php`):**
     * Registered `'stockist_sponsor_bonus'` in `$BONUS_WALLETS` to support user bonus viewing and wallet transfers.
  4. **Automated Pipeline Trigger (`StockistRebateService.php`):**
     * Linked `StockistSponsorBonusService::credit()` to execute automatically whenever stockists redeem or dispense inventory via `StockistRebateService::credit()`.
* **Verification / Test Result:**
  * When a sponsored stockist redeems an order (e.g., 50 packs of a 10 PV product = 500 PV = `₦300,000` volume), the sponsoring distributor automatically receives `₦9,000.00` (`500 × 0.03 × 600`), logged with ledger bonus type `30`.

---

### 3.2 Global Profit-Sharing Pool Engine (1.25%)
* **Target Files:**
  * `core/app/Models/GlobalPoolLedger.php` (New)
  * `core/app/Services/GlobalSharePoolService.php` (New)
  * `core/app/Console/Commands/DistributeGlobalSharePoolCommand.php` (New)
  * `core/app/Http/Controllers/User/DistributorController.php`
  * `core/app/Http/Controllers/User/ProjectController.php`
  * `core/app/Services/StockistRebateService.php`
* **Problem Identified:**
  * The Strategic Reserve budget allocates **1.25%** of all platform PV volume to a shared leadership pool.
  * No model, ledger, accumulator, or scheduled payout engine existed in the platform for global revenue sharing.
* **Changes Applied:**
  1. **Created `GlobalPoolLedger.php` Model:**
     * Tracks quarterly accumulation periods (e.g., `Q4-2026`), accumulated sums, distributed amounts, and status (`accumulating`, `distributed`, `closed`).
     * Added static method `GlobalPoolLedger::accrue(float $pvVolume)` using Blueprint formula: `round($pvVolume * 0.0125 * 600, 2)`.
  2. **Automated PV Volume Accumulation:**
     * Registered distributors (`DistributorController.php`): accrues 1.25% of new package registration PV.
     * Package upgrades (`ProjectController.php`): accrues 1.25% of upgrade PV difference.
     * Product repurchases and stockist sales (`StockistRebateService.php`): accrues 1.25% of inventory volume PV.
  3. **Created `GlobalSharePoolService.php`:**
     * Queries leaders who have achieved Diamond Director or higher rank (`Award::where('sort_order', '>=', 5)`).
     * Divides the accumulated pool equally among qualified leaders in an atomic database transaction.
     * Disburses funds into each qualified leader's main balance with full audit ledger tracking.
  4. **Created CLI Command `DistributeGlobalSharePoolCommand.php`:**
     * Artisan command `pool:distribute {--period=}` for automated quarterly cron scheduling or manual admin distribution.

---

### 3.3 Wholesale Carton Packaging Standardization (15 Packs)
* **Target Files:**
  * `core/app/Models/Product.php`
* **Problem Identified:**
  * Operating specifications require wholesale inventory to be traded in standardized 15-pack cartons, with prices calculated at the carton tier.
  * The codebase lacked carton unit definitions and helpers.
* **Changes Applied:**
  1. Defined `Product::DEFAULT_CARTON_SIZE = 15`.
  2. Added casts for `carton_size` (integer) and `price_per_carton` (decimal:2).
  3. Added helper methods:
     * `getPacksPerCartonAttribute()`: returns configured carton size or defaults to 15.
     * `getCartonPriceAttribute()`: returns configured carton price or calculates unit price × carton size.
     * `cartonsToUnits(int $cartons)`: converts wholesale carton quantities into individual unit counts.

---

### 3.4 Database Schema Migrations & Scripts
* **Target Files:**
  * `core/database/migrations/2026_10_13_000002_phase3_blueprint_features.php` (Laravel Migration)
  * `core/database/phase3_blueprint_features.sql` (Standalone MySQL Script)
* **Applied Schemas:**
  1. `ALTER TABLE stockists ADD COLUMN sponsor_user_id BIGINT UNSIGNED NULL;`
  2. `ALTER TABLE users ADD COLUMN stockist_sponsor_bonus DECIMAL(12, 2) NOT NULL DEFAULT 0.00;`
  3. `CREATE TABLE global_pool_ledgers (...);`
  4. `ALTER TABLE products ADD COLUMN carton_size SMALLINT UNSIGNED DEFAULT 15, ADD COLUMN price_per_carton DECIMAL(12, 2) DEFAULT 0.00;`

---

## Phase 4: Financial Governance & Pool Separation
**Date:** October 13, 2026  
**Status:** Completed  

### 4.1 Automated 4-Pool Revenue Allocation
* **Target Files:**
  * `core/app/Models/CompanyPoolLedger.php` (New)
  * `core/app/Services/RevenueAllocationService.php` (New)
  * `core/app/Http/Controllers/Gateway/PaymentController.php`
* **Problem Identified:**
  * Previously, all incoming cashflow pooled into a general wallet balance without segregated accounting.
  * The Blueprint mandates rigid segregation of all incoming revenue into four separate reserves before any bonus payouts occur:
    * **30.00%** Company Reserve Pool
    * **10.00%** Executive Leadership Pool
    * **5.00%** Operational Expenses Pool
    * **55.00%** Member Compensation Reserve Pool
* **Changes Applied:**
  1. **Created `CompanyPoolLedger.php` Model:**
     * Implemented tracking for every transaction's gross inflow, exact 4-way allocation, and post-transaction running balances.
     * Added `getBalances()` accessor for real-time reporting.
     * Added `hasSufficientCompensationReserve()` check to prevent bonus runs during cashflow deficit.
  2. **Created `RevenueAllocationService.php`:**
     * Implemented atomic `allocate(float $grossAmount, string $sourceType, string $trx, string $description)` which locks the ledger and computes exact penny-perfect allocations with remainder protection against floating-point drift.
     * Implemented `disburseCompensation(float $payoutAmount, string $trx, string $description)` to log debits against the compensation reserve when commissions are distributed.
  3. **Gateway Interception (`PaymentController.php`):**
     * Connected `RevenueAllocationService::allocate()` to `userDataUpdate()`.
     * Every successful fiat deposit (Paystack, Stripe, manual transfer, bank cards) is instantly divided across the 4 pools.
* **Verification / Test Result:**
  * When a user deposits ₦100,000.00:
    * ₦30,000.00 credited to Company Reserve (30%)
    * ₦10,000.00 credited to Executive Pool (10%)
    * ₦5,000.00 credited to Operational Pool (5%)
    * ₦55,000.00 credited to Member Compensation Reserve (55%)
    * Sum: exactly ₦100,000.00 (100.00%).

---

### 4.2 Enforce Payout Clearance & Compensation Reserve Tracking
* **Target Files:**
  * `core/app/Services/MatchingBonusService.php`
  * `core/app/Services/RevenueAllocationService.php`
* **Problem Identified:**
  * Member commissions were being paid from unsegregated cashflow without debiting or monitoring against the dedicated 55% Compensation Reserve budget.
* **Changes Applied:**
  * Integrated `disburseCompensation()` into `MatchingBonusService::processMatrix()`.
  * Every binary match payout automatically debits the `compensation_reserve_balance`, maintaining a synchronized audit trail of reserve usage against income.

---

### 4.3 Executive & Financial Admin Dashboard Widgets
* **Target Files:**
  * `core/app/Http/Controllers/Admin/AdminController.php`
  * `core/resources/views/admin/dashboard.blade.php`
* **Problem Identified:**
  * Financial officers and executives had no visibility into the current capital reserves across the Blueprint's 4 financial pools.
* **Changes Applied:**
  * Updated `AdminController::dashboard()` to pass `$widget['pools'] = CompanyPoolLedger::getBalances();`.
  * Designed and added a prominent 4-card metric dashboard widget to `dashboard.blade.php` with customized color badges and icons:
    * **Company Reserve Pool (30%):** Blue card with Landmark icon.
    * **Executive Leadership Pool (10%):** Cyan card with User-Tie icon.
    * **Operational Expenses Pool (5%):** Amber card with Cogs icon.
    * **Compensation Reserve Pool (55%):** Green card with Shield icon.

---

### 4.4 Database Schema Migrations & Scripts
* **Target Files:**
  * `core/database/migrations/2026_10_13_000003_phase4_financial_governance_pools.php` (Laravel Migration)
  * `core/database/phase4_financial_governance_pools.sql` (Standalone MySQL Script)
* **Applied Schemas:**
  * Created `company_pool_ledgers` table with indexed `trx`, `source_type`, `created_at`, running balances, and split amounts.

---

## Remediation Summary & Status
All 4 phases of the Technical Audit & Compensation Blueprint Remediation Plan have been executed and documented:
* **Phase 1 (Immediate Financial & Calculation Hotfixes):** Complete
* **Phase 2 (System Stability & Missing Models):** Complete
* **Phase 3 (Blueprint Compensation Feature Delivery):** Complete
* **Phase 4 (Financial Governance & Pool Separation):** Complete

---

## 5. Affiliate Marketing Engine & Member Ad Center Alignment
**Date:** October 13, 2026  
**Status:** Aligned & Enabled  

### 5.1 Strategic & Architectural Alignment
* **Alignment with Blueprint:**
  * Fully aligns with the **Commerce Engine** (Financing Engine 2) and the **Commerce Pool (24.75%)**.
  * The retail sales and stockist redemption model drives non-MLM guest consumers directly to local stockists, fueling repeat volume across the membership network.
* **Component Verification & Integration:**
  1. **Member Content Center (Ad Center):**
     * Route: `user.affiliate.ads` (`/user/affiliate/ads`).
     * Interface: Ready-made promotional banners, copy captions, and 1-click sharing buttons for WhatsApp, Facebook, X (Twitter), Telegram, TikTok, and Instagram.
     * Dynamic Tracking: Automatically generates and appends each member's unique affiliate tracking link (`?ref=WII-XXXXXX`).
  2. **Public Storefront (Ad Termination Point):**
     * Routes: `/shop` and `/shop/ad/{ad}` with OpenGraph social preview tags for instant link rendering on Facebook/WhatsApp cards.
     * Attribution: `TrackAffiliateClick` middleware logs device, IP, and binds buyer attribution to the referring member with customizable cookie expiry.
  3. **Affiliate Reward Allocation Engine:**
     * Products specify flat or percentage commissions (`affiliate_bonus_type` and `affiliate_bonus_value`).
     * `AffiliateOrderService` calculates and credits the marketer's wallet (`affiliate_bonus`) upon order placement.
     * *(Fixed in Phase 1: Percentage bug resolved so 10% on ₦40,000 credits ₦4,000 instead of ₦10).*
  4. **Local Stockist Fulfillment & Foot Traffic:**
     * Guests select their State during checkout.
     * Order codes route to local stockists via `StockistAffiliateRedemptionController` (`/user/stockist/affiliate`).
     * Stockist verifies pickup code in-person, dispenses products, and earns their **3.75% Stockist Product Rebate**, while the stockist's sponsor earns their **3.00% Sponsor Bonus**.
  5. **Admin Management & Creative Center (Enabled):**
     * Linked `Admin\AdController` and `Admin\AffiliateController` into `admin/partials/sidenav.json` and `config/admin_access.php`.
     * Admin marketing managers can now publish ad media, monitor conversion funnels, inspect audit logs, and configure attribution parameters.

---

## 6. Multi-Ledger Financial Architecture & Compensation Plan Calibration
**Date:** October 14, 2026  
**Status:** Completed  

### 6.1 Isolated Multi-Ledger Accounting Architecture
* **Target Files:**
  * `core/app/Models/CompanyReserveLedger.php` (New)
  * `core/app/Models/ExecutivePoolLedger.php` (New)
  * `core/app/Models/OperationalPoolLedger.php` (New)
  * `core/app/Models/CompensationReserveLedger.php` (New)
  * `core/app/Services/RevenueAllocationService.php`
  * `core/database/migrations/2026_10_14_000001_create_dedicated_multi_pool_ledgers.php`
  * `core/database/dedicated_multi_pool_ledgers.sql`
* **Architectural Rationale:**
  * Having distinct ledgers completely ring-fences company reserves from member payouts, preventing bonus overages from eroding operating or executive capital.
  * Meets Nigerian Cooperative Societies regulatory accounting mandates for distinct balance-sheet line items.
  * Eliminates database lock contention on high-volume concurrent registrations.
* **Implementation Details:**
  * Every incoming payment (deposit, order) is split atomically across the 4 isolated ledgers:
    * 30% &rarr; `CompanyReserveLedger`
    * 10% &rarr; `ExecutivePoolLedger`
    * 5% &rarr; `OperationalPoolLedger`
    * 55% &rarr; `CompensationReserveLedger`
  * Every member commission (Direct, Indirect, Binary Matching, Upgrade, Key-In) logs an isolated debit against `CompensationReserveLedger`, tracking the recipient `user_id`, `sub_pool` (CRP, CP, SR), and `bonus_type`.

---

### 6.2 7 Registration Packages & Matching Caps Seeding
* **Target Files:**
  * `core/database/seeders/CompensationPlanProjectsSeeder.php` (New)
  * `core/database/compensation_plan_projects.sql` (New)
* **Packages Seeded (1 PV = ₦600):**
  1. **Wiifarm-Pre Starter:** ₦15,000 | 15 PV | Direct: ₦2,250 | Match: ₦900 (3%) | Daily Cap: 5 matches (₦4,500)
  2. **Wiifarm-Starter:** ₦30,000 | 30 PV | Direct: ₦4,500 | Match: ₦1,500 (5%) | Daily Cap: 7 matches (₦10,500)
  3. **Wiifarm-Builder:** ₦60,000 | 60 PV | Direct: ₦9,000 | Match: ₦2,100 (7%) | Daily Cap: 10 matches (₦21,000)
  4. **Wiifarm-Booster:** ₦105,000 | 105 PV | Direct: ₦15,750 | Match: ₦2,400 (8%) | Daily Cap: 30 matches (₦72,000)
  5. **Wiifarm-Executive:** ₦150,000 | 150 PV | Direct: ₦22,500 | Match: ₦2,700 (9%) | Daily Cap: 60 matches (₦162,000)
  6. **Wiifarm-Advance:** ₦255,000 | 255 PV | Direct: ₦38,250 | Match: ₦3,000 (10%) | Daily Cap: 90 matches (₦270,000)
  7. **Wiifarm-Mega:** ₦450,000 | 450 PV | Direct: ₦67,500 | Match: ₦3,600 (12%) | Daily Cap: 120 matches (₦432,000)

---

### 6.3 10-Generation Indirect Referral Bonus (13.00%)
* **Target Files:**
  * `core/app/Http/Helpers/helpers.php` (`indirectBonus`)
* **Rates Implemented Across 10 Generations:**
  * **Gen 1:** 3.00% (`round($baseNaira * 0.03, 2)`) &rarr; ₦8,100 on Mega 450 PV
  * **Gen 2:** 2.00% (`round($baseNaira * 0.02, 2)`) &rarr; ₦5,400 on Mega 450 PV
  * **Gen 3 to 10:** 1.00% each (`round($baseNaira * 0.01, 2)`) &rarr; ₦2,700 each on Mega 450 PV
  * **Total:** 13.00%
  * Each tier credits the qualifying upline and debits `CompensationReserveLedger`.

---

### 6.4 Key-In Bonus & Upgrade Commission Alignment
* **Target Files:**
  * `core/app/Http/Controllers/User/DistributorController.php`
  * `core/app/Http/Controllers/User/ProjectController.php`
  * `core/app/Http/Helpers/helpers.php` (`upgradeBonus`, `upgradeIndirectBonus`)
* **Changes Applied:**
  * **Key-In Bonus:** Set to **2.00%** (`round($pv * 0.02 * 600, 2)`), yielding ₦5,400 on a 450 PV enrollment per proposed plan, debited from Compensation Reserve.
  * **Direct Upgrade Bonus:** Direct upline receives **25.00%** of the PV difference (`round($uplinePVDiff * 0.25 * 600, 2)`).
  * **Indirect Upgrade Bonus:** Generation 2 receives **2.00%** and Generations 3 to 10 receive **1.00%** each of the PV difference.

---

## 7. Binary Awards, UCB & 15-Generation Unilevel Calibration
**Date:** October 14, 2026  
**Status:** Completed  

### 7.1 13 Binary Recognition Awards Seeding & Eligibility
* **Target Files:**
  * `core/database/seeders/CompensationPlanAwardsSeeder.php` (New)
  * `core/database/compensation_plan_awards.sql` (New)
  * `core/app/Jobs/CheckUserAwardJob.php`
  * `core/app/Http/Controllers/Admin/AwardController.php`
* **Ranks & Eligibility Seeded:**
  1. **Sapphire Leader:** 500 PV Left / 500 PV Right | ₦20,000 cash | No min package
  2. **Ruby Leader:** 1,250 PV Left / 1,250 PV Right | ₦50,000 cash | No min package
  3. **Emerald Leader:** 2,500 PV Left / 2,500 PV Right | ₦100,000 (Phone) | Min 60 PV (Builder)
  4. **Silver Director:** 5,000 PV Left / 5,000 PV Right | ₦200,000 (Laptop) | Min 105 PV (Booster)
  5. **Gold Director:** 10,000 PV Left / 10,000 PV Right | ₦400,000 (Fridge/Trip) | Min 450 PV (Mega)
  6. **Jade Director:** 20,000 PV Left / 20,000 PV Right | ₦800,000 (International Trip) | Min 150 PV (Executive)
  7. **Pearl Director:** 40,000 PV Left / 40,000 PV Right | ₦1,500,000 (Mini Car) | Min 450 PV (Mega)
  8. **Diamond Director:** 90,000 PV Left / 90,000 PV Right | ₦3,500,000 (First Car) | Min 450 PV (Mega)
  9. **Executive Ambassador:** 180,000 PV Left / 180,000 PV Right | ₦7,500,000 (Car + Trip) | Min 450 PV (Mega) + 1 Diamond L & R
  10. **Crown Ambassador:** 360,000 PV Left / 360,000 PV Right | ₦15,000,000 (SUV) | Min 450 PV (Mega) + 1 Exec Ambassador L & R
  11. **Royal Crown Ambassador:** 800,000 PV Left / 800,000 PV Right | ₦30,000,000 (Prime SUV) | Min 450 PV (Mega) + 1 Crown Amb L & R
  12. **Imperial Crown Ambassador:** 1,800,000 PV Left / 1,800,000 PV Right | ₦75,000,000 (House Award) | Min 450 PV (Mega) + 1 Royal Crown Amb L & R
  13. **Global Presidential Ambassador:** 4,000,000 PV Left / 4,000,000 PV Right | ₦150,000,000 (Estate Award) | Min 450 PV (Mega) + 1 Imperial Crown Amb L & R
* **Enforcement:**
  * `CheckUserAwardJob` checks member package PV requirements and genealogy child rank prerequisites before granting awards.
  * `AwardController::approve()` and `pay()` debit `CompensationReserveLedger` under Strategic Reserve (`SR`).

---

### 7.2 Upline Celebration Bonus (UCB / ACB) (5%, 2%, 1%)
* **Target Files:**
  * `core/app/Jobs/ProcessAwardAcbJob.php`
  * `core/app/Jobs/ProcessAcbBonusJob.php`
* **Implementation Details:**
  * Whenever a member qualifies for a Binary Award or Repurchase Award:
    * **Generation 1 Upline:** receives **5.00%** of the award amount
    * **Generation 2 Upline:** receives **2.00%** of the award amount
    * **Generation 3 Upline:** receives **1.00%** of the award amount
  * All disbursements check that the upline is active and debit `CompensationReserveLedger` under sub-pool `SR`.

---

### 7.3 15-Generation Unilevel Commission Matrix
* **Target Files:**
  * `core/database/seeders/CompensationPlanUnilevelSeeder.php` (New)
  * `core/database/compensation_plan_unilevel.sql` (New)
  * `core/app/Services/UnilevelService.php`
* **Matrix Seeded ($1 PV = ₦600):**
  * **Level 1:** 6.00% (₦36 / PV)
  * **Level 2:** 5.00% (₦30 / PV)
  * **Level 3:** 4.00% (₦24 / PV)
  * **Level 4:** 3.00% (₦18 / PV)
  * **Level 5:** 2.00% (₦12 / PV)
  * **Levels 6 to 10:** 1.00% each (₦6 / PV)
  * **Levels 11 to 15:** 0.50% each (₦3 / PV)
  * **Total Distributed:** 30.00%
* **Package Level Access:**
  * Pre-Starter (15 PV): Levels 1–3
  * Starter (30 PV): Levels 1–5
  * Builder (60 PV): Levels 1–7
  * Booster (105 PV): Levels 1–9
  * Executive (150 PV): Levels 1–11
  * Advance (255 PV): Levels 1–13
  * Mega (450 PV): Levels 1–15 (All levels)
* **Ledger Tracking:** Debits `CompensationReserveLedger` under Commerce Pool (`CP`).

---

### 7.4 Stockist Setup Bonuses & Standardized Rebates
* **Target Files:**
  * `core/app/Services/StockistEnrollmentBonusService.php` (New)
  * `core/app/Services/StockistRebateService.php`
  * `core/app/Http/Controllers/Admin/StockistController.php`
* **Stockist Types & Setup Bonuses:**
  * **Basic Stockist (₦3,000,000 setup | 1,000 Store PV):**
    * Total bonus: ₦240,000 (Gen 1: ₦120,000, Gen 2: ₦40,000, Gen 3–5: ₦20,000 each, Gen 6–10: ₦4,000 each).
    * Re-order Rebate: **6.00%** of PV volume.
  * **Mega Stockist (₦10,000,000 setup | 3,500 Store PV):**
    * Total bonus: ₦650,000 (Gen 1: ₦300,000, Gen 2: ₦100,000, Gen 3–5: ₦50,000 each, Gen 6–10: ₦20,000 each).
    * Re-order Rebate: **8.00%** of PV volume.
  * Disbursed automatically upon stockist activation via `StockistEnrollmentBonusService::distribute()`, with isolated debits from `CompensationReserveLedger`.

---

## 8. Repurchase / Unilevel Cumulative Awards
**Date:** October 14, 2026  
**Status:** Completed  

### 8.1 10-Tier Repurchase Award Seeding
* **Target Files:**
  * `core/database/seeders/CompensationPlanRepurchaseAwardsSeeder.php` (New)
  * `core/database/compensation_plan_repurchase_awards.sql` (New)
  * `core/app/Http/Controllers/Admin/RepurchaseAwardController.php`
  * `core/app/Jobs/ProcessAcbBonusJob.php`
* **Cumulative Repurchase PV Awards:**
  1. **750 PV:** ₦50,000 (Phone or cash)
  2. **1,500 PV:** ₦100,000 (Laptop or cash)
  3. **3,000 PV:** ₦200,000 (Refrigerator / Generator or cash)
  4. **6,000 PV:** ₦400,000 (International Trip or cash)
  5. **10,000 PV:** ₦700,000 (Mini Car or cash)
  6. **15,000 PV:** ₦1,200,000 (Car Award or cash)
  7. **20,000 PV:** ₦1,800,000 (SUV Award or cash)
  8. **25,000 PV:** ₦2,500,000 (Prime SUV Award or cash)
  9. **35,000 PV:** ₦3,500,000 (House Fund or cash)
  10. **45,000 PV:** ₦5,000,000 (Estate Fund or cash)
* **Financial Ledger Integration:**
  * Payouts in `RepurchaseAwardController::creditUser()` atomically debit `CompensationReserveLedger` under Strategic Reserve (`SR`).
  * ACB celebration bonuses in `ProcessAcbBonusJob` (Gen 1: 5%, Gen 2: 2%, Gen 3: 1%) atomically debit `CompensationReserveLedger` under Strategic Reserve (`SR`).






