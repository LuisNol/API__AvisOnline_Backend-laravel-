<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('type_user')->default('CLIENT'); // ADMIN o CLIENT
            $table->string('avatar')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->string('uniqd')->nullable();
            $table->string('code_verified')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address_city')->nullable();
            $table->text('bio')->nullable();
            $table->string('fb')->nullable();
            $table->string('sexo')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
