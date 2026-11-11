<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// is_admin and google_id are deliberately not fillable: they are only ever set explicitly.
#[Fillable(['name', 'username', 'email', 'whatsapp', 'password', 'wishlist'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'wishlist' => 'array',
        ];
    }

    /** @return list<int> */
    public function wishlistIds(): array
    {
        return array_values(array_filter($this->wishlist ?? [], fn ($id): bool => is_int($id) && $id > 0));
    }
}
