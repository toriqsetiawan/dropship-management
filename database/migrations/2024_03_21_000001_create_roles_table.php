<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert default roles
        DB::table('roles')->insert([
            ['name' => 'Product Owner', 'slug' => 'product_owner', 'description' => 'Product owner with full access'],
            ['name' => 'Distributor', 'slug' => 'distributor', 'description' => 'Distributor with limited access'],
            ['name' => 'Reseller', 'slug' => 'reseller', 'description' => 'Reseller with basic access'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
