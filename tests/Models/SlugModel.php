<?php

namespace Zbiller\Url\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Zbiller\Url\Options\SlugOptions;
use Zbiller\Url\Traits\HasSlug;

class SlugModel extends Model
{
    use HasSlug;

    /**
     * The database table.
     *
     * @var string
     */
    protected $table = 'slug_models';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'other_field',
    ];

    /**
     * Get the options for the HasSlug trait.
     *
     * @return SlugOptions
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::instance()
            ->generateSlugFrom('name')
            ->saveSlugTo('slug');
    }
}