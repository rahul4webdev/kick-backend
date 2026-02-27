<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_redeem_request', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->default(0)->after('coin_value');
            $table->string('commission_amount', 100)->nullable()->after('commission_percentage');
            $table->string('net_amount', 100)->nullable()->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_redeem_request', function (Blueprint $table) {
            $table->dropColumn(['commission_percentage', 'commission_amount', 'net_amount']);
        });
    }
};
