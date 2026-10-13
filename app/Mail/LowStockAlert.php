<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [10, 60, 300];

    public function __construct(public string $productName, public int $stock) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Low stock: '.$this->productName);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.low-stock');
    }
}
