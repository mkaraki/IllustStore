<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Illust extends Model
{
    use HasFactory;

    protected $table = 'illusts';

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tagAssign', 'illustId', 'tagId')->withPivot('autoAssigned');
    }

    public function negativeTags()
    {
        return $this->belongsToMany(Tag::class, 'tagNegativeAssign', 'illustId', 'tagId');
    }
}
