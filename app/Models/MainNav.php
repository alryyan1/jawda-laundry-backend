<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainNav extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'path',
        'icon',
    ];
}
