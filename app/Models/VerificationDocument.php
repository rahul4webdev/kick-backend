<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationDocument extends Model
{
    use HasFactory;
    public $table = "verification_documents";

    protected $fillable = ['user_id', 'document_type', 'document_url', 'status', 'rejection_reason', 'verified_at'];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
}
