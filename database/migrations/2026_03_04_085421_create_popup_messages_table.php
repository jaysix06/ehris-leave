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
        if (Schema::hasTable('popup_messages')) {
            return;
        }

        Schema::create('popup_messages', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->string('link')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popup_messages');
    }
};
