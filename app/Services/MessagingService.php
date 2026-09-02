<?php

namespace App\Services;

use App\Models\Invoice;
use MessageBird\Bird;
use MessageBird\Exception\BirdException;
use MessageBird\Wire\Model\WhatsAppMessageTemplateComponent;
use MessageBird\Wire\Model\WhatsAppMessageTemplateComponentParameter;

class MessagingService
{
    private Bird $bird;

    public function __construct()
    {
        $this->bird = new Bird(config('services.whatsapp.messagebird.api_key'));
    }

    /**
     * Send an invoice (or later: receipt, quote...) via WhatsApp.
     */
    public function sendWhatsApp(string $phone, array $templateParams): array
    {
        try {
            $message = $this->bird->whatsapp->send(
                to: $phone,
                template: config('services.whatsapp.messagebird.default_template'),
                language: config('services.whatsapp.messagebird.default_language'),
                components: [
                    (new WhatsAppMessageTemplateComponent())
                        ->setType('body')
                        ->setParameters(
                            collect($templateParams)->map(fn ($value, $name) =>
                                (new WhatsAppMessageTemplateComponentParameter())
                                    ->setType('text')->setName($name)->setText((string) $value)
                            )->values()->all()
                        ),
                ],
            );

            return ['success' => true, 'provider_message_id' => $message->getId(), 'status' => $message->getStatus()];
        } catch (BirdException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send an invoice via SMS. Placeholder until you pick a provider.
     */
    public function sendSms(Invoice $invoice, string $phone): array
    {
        // TODO: wire up when you pick an SMS provider (could also be Bird's $bird->sms->send)
        return ['success' => false, 'error' => 'SMS not implemented yet'];
    }
}