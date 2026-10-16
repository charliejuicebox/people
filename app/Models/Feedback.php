<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anytime feedback. Named, visible to the recipient, their team lead, HR and directors.
 */
class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = ['author_id', 'recipient_id', 'value_tag', 'body'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function visibleTo(User $user): bool
    {
        return $user->id === $this->author_id || $user->canSeeRecordsOf($this->recipient);
    }
}
