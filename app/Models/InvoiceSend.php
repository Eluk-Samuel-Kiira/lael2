<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;


class InvoiceSend extends Model
{
    use HasTenant;

    protected $fillable = [
        'invoice_id',
        'channel',
        'recipient',
        'status',
        'provider',
        'provider_message_id',
        'error_message',
        'sent_by',
        'sent_at',
        'delivered_at',
        'tenant_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    public function markAsSent(): self
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
        
        return $this;
    }

    public function markAsDelivered(): self
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();
        
        return $this;
    }

    public function markAsFailed(string $error): self
    {
        $this->status = 'failed';
        $this->error_message = $error;
        $this->save();
        
        return $this;
    }

    public function isEmail(): bool
    {
        return $this->channel === 'email';
    }

    public function isSms(): bool
    {
        return $this->channel === 'sms';
    }

    public function isWhatsApp(): bool
    {
        return $this->channel === 'whatsapp';
    }
}