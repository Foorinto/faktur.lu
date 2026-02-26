<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'name',
        'color',
    ];

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_tag');
    }
}
