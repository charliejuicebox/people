<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['employee', 'team_lead', 'hr', 'director', 'admin'];

    public const ENTITIES = ['AU' => 'Juicebox Australia', 'ID' => 'Juicebox Indonesia (PT Island Management)'];

    protected $fillable = [
        'employee_id', 'name', 'email', 'entity', 'department_id', 'job_title', 'team_lead_id', 'role',
        'employment_type', 'start_date', 'contract_end_date', 'left_at', 'sign_in_provider', 'locale', 'active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'contract_end_date' => 'date',
            'left_at' => 'date',
            'active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'team_lead_id');
    }

    public function identities(): HasMany
    {
        return $this->hasMany(SocialIdentity::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(QuarterlyReview::class, 'employee_id');
    }

    public function feedbackReceived(): HasMany
    {
        return $this->hasMany(Feedback::class, 'recipient_id');
    }

    public function praiseReceived(): HasMany
    {
        return $this->hasMany(Praise::class, 'recipient_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->whereNull('left_at');
    }

    /** HR and directors see every record in both entities (decided 16 Oct 2026). */
    public function seesEveryone(): bool
    {
        return in_array($this->role, ['hr', 'director'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHr(): bool
    {
        return $this->role === 'hr';
    }

    public function leads(User $person): bool
    {
        return $person->team_lead_id !== null && $person->team_lead_id === $this->id;
    }

    /** Can this user read performance records (reviews, feedback) about $person? */
    public function canSeeRecordsOf(User $person): bool
    {
        return $this->id === $person->id || $this->seesEveryone() || $this->leads($person);
    }

    public function hasReports(): bool
    {
        return $this->directReports()->exists();
    }

    public function entityName(): string
    {
        return self::ENTITIES[$this->entity] ?? $this->entity;
    }

    public function firstName(): string
    {
        return explode(' ', $this->name)[0];
    }
}
