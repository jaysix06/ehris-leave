<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        Schema::table('activity_log', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_log', 'severity')) {
                $table->string('severity', 20)->default('info')->after('module');
            }

            if (! Schema::hasColumn('activity_log', 'event_type')) {
                $table->string('event_type', 100)->nullable()->after('severity');
            }

            if (! Schema::hasColumn('activity_log', 'target_user_id')) {
                $table->unsignedBigInteger('target_user_id')->nullable()->after('event_type');
            }

            if (! Schema::hasColumn('activity_log', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('target_user_id');
            }

            if (! Schema::hasColumn('activity_log', 'http_method')) {
                $table->string('http_method', 10)->nullable()->after('ip_address');
            }

            if (! Schema::hasColumn('activity_log', 'route_name')) {
                $table->string('route_name', 255)->nullable()->after('http_method');
            }

            if (! Schema::hasColumn('activity_log', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('route_name');
            }

            if (! Schema::hasColumn('activity_log', 'context')) {
                $table->json('context')->nullable()->after('user_agent');
            }
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index('severity', 'activity_log_severity_index');
            $table->index('event_type', 'activity_log_event_type_index');
            $table->index('target_user_id', 'activity_log_target_user_id_index');
            $table->index('ip_address', 'activity_log_ip_address_index');
            $table->index('created_at', 'activity_log_created_at_index');
        });

        $this->ensureLogIdAutoIncrement();
    }

    public function down(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        Schema::table('activity_log', function (Blueprint $table) {
            foreach ([
                'activity_log_severity_index',
                'activity_log_event_type_index',
                'activity_log_target_user_id_index',
                'activity_log_ip_address_index',
                'activity_log_created_at_index',
            ] as $indexName) {
                try {
                    $table->dropIndex($indexName);
                } catch (Throwable) {
                }
            }

            foreach ([
                'context',
                'user_agent',
                'route_name',
                'http_method',
                'ip_address',
                'target_user_id',
                'event_type',
                'severity',
            ] as $column) {
                if (Schema::hasColumn('activity_log', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function ensureLogIdAutoIncrement(): void
    {
        if (! Schema::hasColumn('activity_log', 'log_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE activity_log MODIFY log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('CREATE SEQUENCE IF NOT EXISTS activity_log_log_id_seq');
            DB::statement("SELECT setval('activity_log_log_id_seq', COALESCE((SELECT MAX(log_id) FROM activity_log), 0) + 1, false)");
            DB::statement("ALTER TABLE activity_log ALTER COLUMN log_id SET DEFAULT nextval('activity_log_log_id_seq')");
        }
    }
};
