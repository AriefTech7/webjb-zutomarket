<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class InfoPost extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'image_path', 'link_url', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /**
     * Order posts the way admins arranged them, newest first within the same position.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    /** Where a click on the poster goes: its own link, otherwise the full-size image. */
    public function targetUrl(): string
    {
        return $this->link_url ?: $this->imageUrl();
    }
}
