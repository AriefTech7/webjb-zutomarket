<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class InfoPost extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'caption', 'image_path', 'link_url', 'sort_order'];

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

    /**
     * Caption for the poster viewer as safe HTML: escaped, line breaks kept, and http(s) links made clickable.
     * Falls back to the title when no caption was written.
     */
    public function captionHtml(): HtmlString
    {
        $text = e(trim((string) $this->caption) !== '' ? $this->caption : $this->title);
        $linked = preg_replace_callback('~https?://[^\s<]+[^\s<.,:;!?)\]\'"]~iu', fn (array $match): string => '<a href="'.$match[0].'" target="_blank" rel="noopener noreferrer" class="break-all font-semibold text-lime-300 underline underline-offset-2">'.$match[0].'</a>', $text);

        return new HtmlString(nl2br($linked, false));
    }

    /** Where a click on the poster goes: its own link, otherwise the full-size image. */
    public function targetUrl(): string
    {
        return $this->link_url ?: $this->imageUrl();
    }
}
