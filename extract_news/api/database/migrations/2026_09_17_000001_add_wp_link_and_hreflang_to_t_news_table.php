<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_news', function (Blueprint $table) {
            $table->string('wp_link')->nullable()->after('wp_post_id');
            $table->boolean('hreflang_linked')->default(false)->after('wp_link');
        });
    }

    public function down(): void
    {
        Schema::table('t_news', function (Blueprint $table) {
            $table->dropColumn(['wp_link', 'hreflang_linked']);
        });
    }
};
