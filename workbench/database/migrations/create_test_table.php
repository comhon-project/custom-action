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
            $table->string('status', 63);
            $table->string('translation', 63);
            $table->boolean('has_consumer_ability')->default(false);
            $table->string('preferred_locale', 255)->nullable();
            $table->string('preferred_timezone', 255)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('outputs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 63);
            $table->string('setting_id', 63);
            $table->string('setting_class', 63);
            $table->string('localized_setting_id', 63)->nullable();
            $table->json('output');
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
