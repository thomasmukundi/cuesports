<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSupport extends Model
{
    use HasFactory;

    protected $table = 'contact_support';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
    ];

    /**
     * Get the user that submitted the support request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
