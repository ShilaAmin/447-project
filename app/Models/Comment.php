<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'user_id', 'content', 'mac'];

    public function post()
    {
        return $this->belongsTo(\App\Models\Post::class);
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
