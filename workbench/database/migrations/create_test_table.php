<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('first_name', 255);
            $table->string('email', 255);
            $table->boolean('has_consumer_ability')->default(false);
            $table->string('preferred_locale', 255)->nullable();
            $table->string('preferred_timezone', 255)->nullable();
            $table->timestamp('last_login_at');
            $table->timestamp('verified_at');
            $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('status', 15);
            $table->json('languages');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');
    }
};
