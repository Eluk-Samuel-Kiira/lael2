<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;


class InvoicePaymentWebhook extends Model
{
    use HasTenant;
    
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'provider',
        'provider_event_id',
        'event_type',
        'amount',
        'currency',
        'payload',
        'status',
        'processing_notes',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * Accessor - Convert from stored integer to display float
     */
    public function getAmountAttribute(?int $value): ?float
    {
        return from_base_currency($value);
    }

    /**
     * Mutator - Convert from display float to stored integer
     */
    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = to_base_currency($value);
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    public function markAsProcessed(): self
    {
        $this->status = 'processed';
        $this->processed_at = now();
        $this->save();
        
        return $this;
    }

    public function markAsFailed(string $notes): self
    {
        $this->status = 'failed';
        $this->processing_notes = $notes;
        $this->processed_at = now();
        $this->save();
        
        return $this;
    }

    public function markAsIgnored(string $notes = null): self
    {
        $this->status = 'ignored';
        $this->processing_notes = $notes ?? 'Webhook ignored - no matching invoice found';
        $this->processed_at = now();
        $this->save();
        
        return $this;
    }

    public function getPayloadData(): array
    {
        return $this->payload ?? [];
    }

    public function getReference(): ?string
    {
        // Try to extract reference from payload
        $payload = $this->getPayloadData();
        
        // Common reference fields
        $possibleFields = ['reference', 'invoice_id', 'order_id', 'tx_ref', 'reference_id'];
        
        foreach ($possibleFields as $field) {
            if (isset($payload[$field])) {
                return $payload[$field];
            }
        }
        
        return null;
    }
}