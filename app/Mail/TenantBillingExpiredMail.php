<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Tenant;
use App\Models\User;

class TenantBillingExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tenant;
    public $admin;
    public $reason;

    public function __construct(Tenant $tenant, User $admin, string $reason)
    {
        $this->tenant = $tenant;
        $this->admin = $admin;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚫 Billing Expired: {$this->tenant->name} - Action Required",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-billing-expired',
            with: [
                'tenant' => $this->tenant,
                'admin' => $this->admin,
                'reason' => $this->reason,
                'billingUrl' => route('tenant.index'),
            ]
        );
    }
}