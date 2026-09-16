<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->index(['tags', 'views']);
            } else {
                $table->index('views');
            }

            $table->index('user_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index('post_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {

        Schema::table('posts', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->dropIndex(['tags', 'views']);
            } else {
                $table->dropIndex(['views']);
            }

            $table->dropIndex(['user_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['post_id']);
            $table->dropIndex(['user_id']);
        });
    }
};
