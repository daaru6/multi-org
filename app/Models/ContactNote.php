<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactNote extends Model
{
    protected $fillable = [
        'contact_id',
        'user_id',
        'body',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($note) {
            if (empty($note->user_id) && auth()->id()) {
                $note->user_id = auth()->id();
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
