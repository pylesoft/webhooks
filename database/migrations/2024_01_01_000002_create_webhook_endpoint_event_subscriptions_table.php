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
        if (Schema::hasTable('webhook_endpoint_event_subscriptions')) {
            return;
        }
        Schema::create('webhook_endpoint_event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->onDelete('cascade');
            $table->string('event_key');
            $table->timestamps();

            $table->unique(['webhook_endpoint_id', 'event_key'], 'webhook_endpoint_event_unique');
            $table->index('event_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoint_event_subscriptions');
    }
};
