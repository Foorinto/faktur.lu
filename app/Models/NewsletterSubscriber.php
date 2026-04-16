<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'locale',
        'source',
        'confirmed_at',
        'confirm_token',
        'unsubscribed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function isActive(): bool
    {
        return $this->isConfirmed() && $this->unsubscribed_at === null;
    }

    public static function subscribe(string $email, string $locale = 'fr', string $source = 'footer'): static
    {
        $subscriber = static::withTrashed()->firstOrNew(['email' => $email]);

        $subscriber->locale = $locale;
        $subscriber->source = $source;
        $subscriber->unsubscribed_at = null;
        $subscriber->confirm_token = Str::random(64);
        $subscriber->save();

        return $subscriber;
    }

    public function confirm(): void
    {
        $this->update([
            'confirmed_at' => now(),
            'confirm_token' => null,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }
}
