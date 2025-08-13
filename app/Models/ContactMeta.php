<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ContactMeta extends Model
{
    protected $table = 'contact_meta';
    
    protected $fillable = [
        'contact_id',
        'key',
        'value',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($meta) {
            // Enforce max 5 meta fields per contact
            $existingCount = static::where('contact_id', $meta->contact_id)->count();
            if ($existingCount >= 5) {
                throw ValidationException::withMessages([
                    'meta' => 'A contact can have a maximum of 5 custom fields.'
                ]);
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
