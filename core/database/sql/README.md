# Standalone SQL scripts

Plain MySQL versions of database changes, for running by hand (for example
in phpMyAdmin) on a server where `php artisan migrate` / `db:seed` cannot be
run. **The migrations and seeders are the source of truth**; prefer them.

| Script | Same change as |
|---|---|
| `leadership_bonus_tables.sql` | `migrations/2026_10_13_000001_create_leadership_bonus_tables.php` |
| `phase3_blueprint_features.sql` | `migrations/2026_10_13_000002_phase3_blueprint_features.php` |
| `phase4_financial_governance_pools.sql` | `migrations/2026_10_13_000003_phase4_financial_governance_pools.php` |
| `dedicated_multi_pool_ledgers.sql` | `migrations/2026_10_14_000001_create_dedicated_multi_pool_ledgers.php` |
| `compensation_plan_projects.sql` | `seeders/CompensationPlanProjectsSeeder.php` |
| `compensation_plan_awards.sql` | `seeders/CompensationPlanAwardsSeeder.php` |
| `compensation_plan_unilevel.sql` | `seeders/CompensationPlanUnilevelSeeder.php` |
| `compensation_plan_repurchase_awards.sql` | `seeders/CompensationPlanRepurchaseAwardsSeeder.php` |

The four table scripts check before creating (`IF NOT EXISTS`), like their
migrations. The four `compensation_plan_*` scripts insert rows without
checking: run each one once, and not on a database that already ran its
seeder, or the plan's rows are duplicated. Background for each is in
`docs/remediation-notes.md`.
