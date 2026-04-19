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

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_email',
        'user_password',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'user_password',
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
            'user_password' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->user_password;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->user_email;
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
