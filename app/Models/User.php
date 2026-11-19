<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// is_admin, is_super_admin and google_id are deliberately not fillable: they are only ever set explicitly.
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
            'is_super_admin' => 'boolean',
            'wishlist' => 'array',
        ];
    }

    /** @return HasMany<GameAccount, $this> */
    public function ownedAccounts(): HasMany
    {
        return $this->hasMany(GameAccount::class, 'owner_id');
    }

    /** The super admin edits every account; other admins only the accounts they own. */
    public function canManage(GameAccount $account): bool
    {
        return $this->is_admin && ($this->is_super_admin || $account->owner_id === $this->id);
    }

    /** @return list<int> */
    public function wishlistIds(): array
    {
        return array_values(array_filter($this->wishlist ?? [], fn ($id): bool => is_int($id) && $id > 0));
    }
}
