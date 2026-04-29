<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_email',
        'user_password_hash',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'user_password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_password_hash' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->user_password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'user_password_hash';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->user_email;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user_email;
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->user_password_hash;
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'user_id', 'user_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'user_id', 'user_id');
    }
}
