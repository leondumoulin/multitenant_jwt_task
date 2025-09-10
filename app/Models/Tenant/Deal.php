<?php

namespace App\Models\Tenant;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deal extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'contact_id',
        'title',
        'value',
        'status',
        'probability',
        'expected_close_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function isWon(): bool
    {
        return $this->status === 'won';
    }
}
