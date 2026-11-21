<?php

declare(strict_types=1);

use App\Enums\Generations\GenerationStatus;
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
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pixelforge_id')->nullable()->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('status')->default(GenerationStatus::Queued->value);
            $table->text('prompt');
            $table->string('size');
            $table->string('quality');
            $table->json('reference_image_urls');
            $table->unsignedBigInteger('estimated_cost_nanodollars');
            $table->unsignedBigInteger('estimated_minimum_cost_nanodollars');
            $table->unsignedBigInteger('estimated_maximum_cost_nanodollars');
            $table->json('estimated_tokens');
            $table->unsignedInteger('input_text_tokens')->nullable();
            $table->unsignedInteger('input_image_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('reasoning_tokens')->nullable();
            $table->json('rates')->nullable();
            $table->unsignedBigInteger('provider_cost_nanodollars')->nullable();
            $table->unsignedBigInteger('charged_nanodollars')->nullable();
            $table->text('image_url')->nullable();
            $table->timestamp('image_url_expires_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
