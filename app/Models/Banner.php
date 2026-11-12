<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** One slide of the home page banner slider (Admin > Banner). */
#[Fillable(['image_path', 'width', 'height', 'link_url', 'sort_order'])]
class Banner extends Model
{
    public const DEFAULT_IMAGE = 'images/zuto-banner.webp';

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Slides for the home page; the built-in banner when the admin has not added any.
     *
     * @return list<array{url: string, width: int, height: int, link: ?string}>
     */
    public static function slides(): array
    {
        $slides = static::ordered()->get()->map(fn (self $banner) => [
            'url' => $banner->imageUrl(), 'width' => $banner->width, 'height' => $banner->height, 'link' => $banner->link_url,
        ])->all();

        return $slides ?: [['url' => asset(self::DEFAULT_IMAGE), 'width' => 1280, 'height' => 430, 'link' => null]];
    }
}
