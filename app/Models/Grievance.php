<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grievance extends Model
{
    public $table = "tbl_grievances";

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function responses()
    {
        return $this->hasMany(GrievanceResponse::class, 'grievance_id');
    }

    public static function generateTicketNumber(): string
    {
        return 'GRV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}
