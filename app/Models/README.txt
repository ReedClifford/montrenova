PHASE 1 — INVESTOR DASHBOARD 2

Copy these files into your Laravel project:

1. database/migrations/2026_08_03_124700_create_investors2_table.php
2. database/migrations/2026_08_03_124701_create_investor_settings2_table.php
3. app/Models/Investor2.php
4. app/Models/InvestorSetting2.php

Then run:

php artisan migrate
php artisan optimize:clear

Verify in php artisan tinker:

Schema::hasTable('investors2');
Schema::hasTable('investor_settings2');

Both should return true.
