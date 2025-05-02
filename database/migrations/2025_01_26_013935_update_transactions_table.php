<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('shipping_number')->after('id')->nullable();
            $table->decimal('total_price', 10, 2)->after('id'); // Total price for the transaction
            $table->string('transaction_code')->after('id')->unique(); // Unique code for the transaction
            $table->string('status')->default('pending')->change(); // pending/paid/cancel/return
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_number', 'transaction_code', 'status', 'total_price'
            ]);
        });
    }
}
