<?php

namespace Dcat\Laravel\Database\Tests\Models;

use Dcat\Laravel\Database\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}
