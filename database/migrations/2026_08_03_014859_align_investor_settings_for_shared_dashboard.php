<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasSettingKey = Schema::hasColumn(
            'investor_settings',
            'setting_key'
        );

        $hasUpdatedBy = Schema::hasColumn(
            'investor_settings',
            'updated_by'
        );

        $hasUserId = Schema::hasColumn(
            'investor_settings',
            'user_id'
        );

        Schema::table('investor_settings', function (Blueprint $table) use (
            $hasSettingKey,
            $hasUpdatedBy,
            $hasUserId
        ) {
            if (! $hasSettingKey) {
                $table->string('setting_key')
                    ->nullable()
                    ->after('id');
            }

            if (! $hasUpdatedBy) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            /*
             * Old investor_settings structure may require user_id.
             * Make it nullable because settings are now shared.
             */
            if ($hasUserId) {
                $table->unsignedBigInteger('user_id')
                    ->nullable()
                    ->change();
            }
        });

        /*
         * Assign a unique setting key to existing records.
         */
        if (! $hasSettingKey) {
            $recordIds = DB::table('investor_settings')
                ->orderBy('id')
                ->pluck('id');

            foreach ($recordIds as $index => $id) {
                DB::table('investor_settings')
                    ->where('id', $id)
                    ->update([
                        'setting_key' => $index === 0
                            ? 'main'
                            : 'legacy-' . $id,
                    ]);
            }

            Schema::table(
                'investor_settings',
                function (Blueprint $table) {
                    $table->unique('setting_key');
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn(
                'investor_settings',
                'updated_by'
            )
        ) {
            Schema::table(
                'investor_settings',
                function (Blueprint $table) {
                    $table->dropForeign(['updated_by']);
                    $table->dropColumn('updated_by');
                }
            );
        }

        if (
            Schema::hasColumn(
                'investor_settings',
                'setting_key'
            )
        ) {
            Schema::table(
                'investor_settings',
                function (Blueprint $table) {
                    $table->dropUnique([
                        'setting_key',
                    ]);

                    $table->dropColumn(
                        'setting_key'
                    );
                }
            );
        }
    }
};