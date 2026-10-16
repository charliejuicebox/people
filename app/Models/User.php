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

    /** Grants letting other people (besides the team lead) read this person's records. */
    public function accessGrants(): HasMany
    {
        return $this->hasMany(RecordAccess::class, 'person_id');
    }

    /** Grants letting this user read other people's records. */
    public function sharedAccess(): HasMany
    {
        return $this->hasMany(RecordAccess::class, 'user_id');
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

    /** Has this user been given extra access to $person's records (cross-border ops, account leads)? */
    public function hasSharedAccessTo(User $person): bool
    {
        return $this->sharedAccess()->where('person_id', $person->id)->exists();
    }

    /** Can this user read performance records (reviews, feedback) about $person? */
    public function canSeeRecordsOf(User $person): bool
    {
        return $this->id === $person->id || $this->seesEveryone() || $this->leads($person) || $this->hasSharedAccessTo($person);
    }

    /** HR and directors decide who else can see someone's records. Admins can't, so they can't grant themselves access. */
    public function managesAccess(): bool
    {
        return $this->seesEveryone();
    }

    public function hasReports(): bool
    {
        return $this->directReports()->exists();
    }

    /** Leads someone, or has been given access to someone else's records. */
    public function hasPeopleToSee(): bool
    {
        return $this->hasReports() || $this->sharedAccess()->exists();
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
