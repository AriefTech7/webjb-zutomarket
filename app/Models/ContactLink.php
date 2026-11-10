<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactLink extends Model
{
    use HasFactory;

    /** @var array<string, string> */
    public const GROUPS = ['admin' => 'WA Admin', 'social' => 'Sosmed', 'channel' => 'Saluran'];

    protected $fillable = ['group', 'title', 'subtitle', 'value', 'badge', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function url(): string
    {
        return $this->group === 'admin' ? 'https://wa.me/'.$this->value : $this->value;
    }

    public function badgeLabel(): string
    {
        if ($this->badge) {
            return $this->badge;
        }
        $host = (string) parse_url($this->value, PHP_URL_HOST);

        return match (true) {
            $this->group === 'admin' => 'WA',
            $this->group === 'channel' => 'SALURAN',
            str_contains($host, 'instagram') => 'IG',
            str_contains($host, 'tiktok') => 'TIKTOK',
            str_contains($host, 'youtube') || str_contains($host, 'youtu.be') => 'YOUTUBE',
            str_contains($host, 'facebook') => 'FB',
            default => 'LINK',
        };
    }
}
