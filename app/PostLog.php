<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'action',
        'details',
    ];
}
