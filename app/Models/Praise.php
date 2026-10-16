<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Public praise, tagged with a company value and posted to #juicebox.
 */
class Praise extends Model
{
    protected $table = 'praise';

    protected $fillable = ['author_id', 'recipient_id', 'value_tag', 'message', 'slack_posted_at', 'slack_error'];

    protected function casts(): array
    {
        return ['slack_posted_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
