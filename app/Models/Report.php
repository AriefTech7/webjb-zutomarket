<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A visitor's report about a problem game ID or WhatsApp number. Only approved reports are public, and only the
 * suspect's details, the story and the evidence photos: the reporter's name / WhatsApp / email stay admin-only.
 */
class Report extends Model
{
    /**
     * "pengguna" = a person (WhatsApp number required); "id_game" and "akun_mafia" = a game ID (WhatsApp number
     * optional). "Akun Mafia" is an account that was sold and then taken back by its old owner.
     */
    public const CATEGORIES = ['pengguna' => 'Pengguna Bermasalah', 'id_game' => 'ID Game Bermasalah', 'akun_mafia' => 'Akun Mafia'];

    /** Categories about a game ID, with the label of the ID field. */
    public const GAME_ID_CATEGORIES = ['id_game' => 'ID game bermasalah', 'akun_mafia' => 'ID akun mafia'];

    public static function needsGameId(?string $category): bool
    {
        return array_key_exists((string) $category, self::GAME_ID_CATEGORIES);
    }

    public const STATUSES = ['pending' => 'Menunggu', 'approved' => 'Tampil', 'rejected' => 'Ditolak'];

    public const MAX_EVIDENCE = 10;

    protected $fillable = ['category', 'title', 'game_id', 'wa_number', 'description', 'evidence', 'reporter_name', 'reporter_email', 'reporter_whatsapp'];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'approved_at' => 'datetime'];
    }

    /** "0812-3456 789", "+62 812…", "62812…" → "62812…"; '' when there are no digits. */
    public static function normaliseWhatsapp(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        return $digits === '' ? '' : (str_starts_with($digits, '0') ? '62'.substr($digits, 1) : (str_starts_with($digits, '8') ? '62'.$digits : $digits));
    }

    /** Game IDs are compared without spaces and case: "1234 5678 (2001)" → "12345678(2001)". */
    public static function normaliseGameId(?string $id): string
    {
        return mb_strtolower(preg_replace('/\s+/', '', (string) $id));
    }

    /** @param  Builder<self>  $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    /**
     * Match a game ID or WhatsApp number, typed any way.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }
        $gameId = self::normaliseGameId($term);
        $wa = self::normaliseWhatsapp($term);
        $query->where(function (Builder $query) use ($gameId, $wa, $term): void {
            $query->where('game_id', 'like', '%'.$gameId.'%')->orWhere('title', 'like', '%'.$term.'%');
            if (strlen($wa) >= 6) {
                $query->orWhere('wa_number', 'like', '%'.$wa.'%');
            }
        });
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Laporan';
    }

    /** "0812-3456-7890" for display, from the stored "6281234567890". */
    public function displayWhatsapp(): string
    {
        $local = preg_replace('/^62/', '0', (string) $this->wa_number);

        return trim(implode('-', str_split($local, 4)), '-');
    }

    /** @return list<string> */
    public function evidenceUrls(): array
    {
        return array_map(fn (string $path): string => Storage::disk('public')->url($path), $this->evidence ?? []);
    }
}
