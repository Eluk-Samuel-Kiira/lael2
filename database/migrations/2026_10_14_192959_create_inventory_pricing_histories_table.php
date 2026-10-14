<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_pricing_history', function (Blueprint $table) {
            $table->id();

            // ── Scope keys ───────────────────────────────────────────
            $table->unsignedBigInteger('tenant_id')->index();
            // Nullable so history survives if the row is ever hard-deleted.
            // The variant_id, location_id and department_id below keep the
            // context legible even when the inventory row is gone.
            $table->unsignedBigInteger('inventory_id')->nullable()->index();
            $table->unsignedBigInteger('variant_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();

            // ── What changed ─────────────────────────────────────────
            // 'enabled'   → user turned custom pricing ON
            // 'disabled'  → user turned it OFF (reverted to variant)
            // 'updated'   → values changed while it was already ON
            // 'auto'      → system-driven (e.g. bulk operation)
            $table->string('change_type', 20)->index();

            // Snapshot of has_custom_pricing BEFORE and AFTER, so a
            // report can tell "created the override" from "cleared it".
            $table->boolean('had_custom_pricing_before')->default(false);
            $table->boolean('has_custom_pricing_after')->default(false);

            // Full column snapshots as JSON. Keys mirror the columns on
            // inventory_items that carry pricing/override data.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Only the fields that actually changed, as a JSON diff. This
            // is what a UI would render as "Cost: 100 → 120".
            $table->json('changed_fields')->nullable();

            // ── The narrative ────────────────────────────────────────
            // A full human-readable sentence. If you ever build a
            // "Pricing History" panel, render this directly.
            $table->text('narrative');

            // ── Who did it ───────────────────────────────────────────
            $table->unsignedBigInteger('changed_by')->nullable()->index();
            $table->string('changed_by_name', 191)->nullable();
            $table->string('changed_by_email', 191)->nullable();
            $table->string('scope_level', 20)->default('variant');

            // ── Free text (user-supplied reason, bulk-op note, etc.) ─
            $table->string('reason', 191)->nullable();

            // ── When ─────────────────────────────────────────────────
            // Redundant with created_at, but explicit so reports can
            // filter on it without joining. Laravel fills both.
            $table->timestamp('changed_at')->useCurrent()->index();

            $table->timestamps();

            // ── Helpful composite indexes ────────────────────────────
            $table->index(['tenant_id', 'variant_id', 'changed_at'], 'iph_tenant_variant_time');
            $table->index(['tenant_id', 'inventory_id', 'changed_at'], 'iph_tenant_inventory_time');
            $table->index(['tenant_id', 'changed_by', 'changed_at'],   'iph_tenant_actor_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_pricing_history');
    }
};