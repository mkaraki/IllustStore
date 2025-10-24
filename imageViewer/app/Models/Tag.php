<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['autoAssigned', 'tagId'];

    public function illusts()
    {
        return $this->belongsToMany(Illust::class, 'tagAssign', 'tagId', 'illustId');
    }

    /**
     * Get the autoAssigned attribute.
     *
     * @return bool|null
     */
    public function getAutoAssignedAttribute()
    {
        // The 'pivot' attribute is only available when the tag is loaded
        // through a many-to-many relationship that includes pivot data.
        return $this->pivot->autoAssigned ?? null;
    }

    /**
     * Get the tagId attribute.
     *
     * @return int
     */
    public function getTagIdAttribute()
    {
        return $this->id;
    }
}
