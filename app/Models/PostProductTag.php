<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostProductTag extends Model
{
    protected $table = 'tbl_post_product_tags';

    protected $fillable = ['post_id', 'product_id', 'label'];

    public function post()
    {
        return $this->belongsTo(Posts::class, 'post_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
