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
use Carbon\Carbon;

class TenantBillingReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tenant;
    public $admin;
    public $expiryDate;
    public $daysUntilExpiry;

    public function __construct(Tenant $tenant, User $admin, Carbon $expiryDate, int $daysUntilExpiry)
    {
        $this->tenant = $tenant;
        $this->admin = $admin;
        $this->expiryDate = $expiryDate;
        $this->daysUntilExpiry = $daysUntilExpiry;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Billing Reminder: {$this->tenant->name} - {$this->daysUntilExpiry} Days Until Expiry",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-billing-reminder',
            with: [
                'tenant' => $this->tenant,
                'admin' => $this->admin,
                'expiryDate' => $this->expiryDate,
                'daysUntilExpiry' => $this->daysUntilExpiry,
                'billingUrl' => route('tenant.index'),
            ]
        );
    }
}