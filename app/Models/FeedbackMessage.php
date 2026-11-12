<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'name', 'contact', 'message', 'user_id'])]
class FeedbackMessage extends Model
{
    public const TYPES = ['Kritik', 'Saran', 'Laporan masalah'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A wa.me link when the contact looks like a phone number (08… or 62…). */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->contact);
        if (! preg_match('/^(0|62)8\d{7,12}$/', $digits)) {
            return null;
        }

        return 'https://wa.me/'.(str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits);
    }

    public function emailUrl(): ?string
    {
        return filter_var($this->contact, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$this->contact : null;
    }
}
