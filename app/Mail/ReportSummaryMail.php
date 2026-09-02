<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ReportSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reportData;
    public $user;
    public $period;
    public $periodLabel;

    public function __construct($reportData, $user, $period)
    {
        $this->reportData = $reportData;
        $this->user = $user;
        $this->period = $period;
        
        $periodLabels = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly'
        ];
        $this->periodLabel = $periodLabels[$period] ?? ucfirst($period);
    }

    public function envelope(): Envelope
    {
        $periodLabels = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly'
        ];
        
        $label = $periodLabels[$this->period] ?? ucfirst($this->period);
        $date = now()->setTimezone('Africa/Nairobi')->format('M d, Y');
        
        return new Envelope(
            subject: "📊 {$label} Report - {$date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.report-summary',
            with: [
                'reportData' => $this->reportData,
                'user' => $this->user,
                'period' => $this->period,
                'periodLabel' => $this->periodLabel,
            ]
        );
    }
}