<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Read access to someone's reviews and feedback for a person other than their team lead. */
class RecordAccess extends Model
{
    protected $table = 'record_access';

    protected $fillable = ['person_id', 'user_id', 'reason', 'granted_by'];

    public function person(): BelongsTo
    {
        return $this->belongsTo(User::class, 'person_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
