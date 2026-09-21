<?php

namespace App\Domains\Billing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Shared shape of the billing e-mails: a subject, a few short lines and one
 * button. Texts come from lang/{locale}/billing.php.
 */
abstract class BillingMail extends Mailable
{
    use Queueable, SerializesModels;

    abstract protected function subjectLine(): string;

    /** @return array<int, string> */
    abstract protected function lines(): array;

    protected function buttonLabel(): ?string
    {
        return __('billing.mail.open_billing');
    }

    protected function buttonUrl(): ?string
    {
        return url('/admin/settings/billing');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.billing.notice', with: [
            'lines' => $this->lines(),
            'buttonLabel' => $this->buttonLabel(),
            'buttonUrl' => $this->buttonUrl(),
        ]);
    }

    protected static function kz(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' Kz';
    }

    protected static function date($date): string
    {
        return $date ? $date->format('d/m/Y') : '—';
    }
}
