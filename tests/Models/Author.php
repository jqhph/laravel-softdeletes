<?php

namespace Dcat\Laravel\Database\Tests\Models;

use Dcat\Laravel\Database\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
