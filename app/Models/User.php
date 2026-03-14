<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable, TwoFactorAuthenticatable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_user';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'userId';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'id',
        'name',
        'avatar_url',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'hrId',
        'email',
        'personal_email',
        'password',
        'lastname',
        'firstname',
        'middlename',
        'extname',
        'avatar',
        'job_title',
        'role',
        'active',
        'date_created',
        'fullname',
        'department_id',
        'remember_token',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * Family information records for this user (keyed by hrId).
     */
    public function familyInfo(): HasMany
    {
        return $this->hasMany(FamilyInfo::class, 'hrid', 'hrId');
    }

    protected function casts(): array
    {
        return [
            'userId' => 'integer',
            'hrId' => 'integer',
            'department_id' => 'integer',
            'active' => 'boolean',
            'date_created' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Backward-compatible `id` attribute mapped to `userId`.
     */
    protected function id(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getAttribute($this->getKeyName()),
        );
    }

    /**
     * Backward-compatible `name` attribute backed by `fullname`.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fullName = trim((string) ($this->attributes['fullname'] ?? ''));
                if ($fullName !== '') {
                    return $fullName;
                }

                $parts = array_filter([
                    $this->attributes['firstname'] ?? null,
                    $this->attributes['middlename'] ?? null,
                    $this->attributes['lastname'] ?? null,
                    $this->attributes['extname'] ?? null,
                ], fn ($value) => is_string($value) && trim($value) !== '');

                if ($parts !== []) {
                    return trim(implode(' ', $parts));
                }

                return (string) ($this->attributes['email'] ?? '');
            },
            set: function ($value) {
                $normalized = trim((string) $value);
                $this->attributes['fullname'] = $normalized !== '' ? $normalized : null;

                if ($normalized === '') {
                    return;
                }

                $parts = preg_split('/\s+/', $normalized) ?: [];
                if ($parts === []) {
                    return;
                }

                $firstName = array_shift($parts);
                $lastName = count($parts) > 0 ? array_pop($parts) : null;
                $middleName = count($parts) > 0 ? implode(' ', $parts) : null;

                $this->attributes['firstname'] = $firstName ?: $this->attributes['firstname'] ?? null;
                $this->attributes['lastname'] = $lastName ?: $this->attributes['lastname'] ?? null;
                $this->attributes['middlename'] = $middleName ?: $this->attributes['middlename'] ?? null;
            },
        );
    }

    /**
     * URL path for the user's profile avatar (served by GET /avatars/{filename}).
     * Returns null when no avatar is set.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $avatar = $this->attributes['avatar'] ?? null;
                if (! is_string($avatar) || trim($avatar) === '') {
                    return null;
                }
                $base = trim($avatar);

                // If a relative public path is stored (e.g. "uploads/avatars/1_123.jpg"),
                // serve it directly from /public.
                if (str_contains($base, '/')) {
                    $clean = ltrim($base, '/');
                    return '/'.$clean;
                }

                // Otherwise treat it as a filename served by GET /avatars/{filename}.
                if (preg_match('/[^a-zA-Z0-9_.-]/', $base)) {
                    return null;
                }

                return '/avatars/'.$base;
            },
        );
    }

    /**
     * Treat "active" accounts as email-verified so we rely on
     * admin activation instead of the built-in email flow.
     */
    public function hasVerifiedEmail(): bool
    {
        if (array_key_exists('active', $this->attributes)) {
            return (bool) $this->attributes['active'];
        }

        return ! is_null($this->email_verified_at ?? null);
    }

    /**
     * Get the family member records for this user (by hrId).
     *
     * @return HasMany<EmpFamilyInfo, $this>
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmpFamilyInfo::class, 'hrid', 'hrId');
    }
}
