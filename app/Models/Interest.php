<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    use HasFactory;
    public $table = "interests";

    protected $fillable = ['name', 'icon', 'is_active', 'sort_order'];
}
