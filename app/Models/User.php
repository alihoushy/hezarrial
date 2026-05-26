<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'mobile', 'password', 'pin_hash', 'settings'];

    protected $hidden = ['password', 'pin_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    public function accounts() { return $this->hasMany(Account::class); }
    public function categories() { return $this->hasMany(Category::class); }
    public function people() { return $this->hasMany(Person::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }
    public function backups() { return $this->hasMany(Backup::class); }
}
