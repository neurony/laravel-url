<?php

namespace Zbiller\Url\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Request;
use Zbiller\Url\Contracts\UrlModelContract;

class Url extends Model implements UrlModelContract
{
    /**
     * The database table.
     *
     * @var string
     */
    protected $table = 'urls';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url',
        'urlable_id',
        'urlable_type',
    ];

    /**
     * Get all of the owning urlable models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function urlable()
    {
        return $this->morphTo();
    }

    /**
     * Filter the query by url.
     *
     * @param Builder $query
     * @param string $url
     */
    public function scopeWhereUrl($query, $url)
    {
        $query->where('url', $url);
    }

    /**
     * Filter the query by the urlable morph relation.
     *
     * @param Builder $query
     * @param int $id
     * @param string $type
     */
    public function scopeWhereUrlable($query, $id, $type)
    {
        $query->where([
            'urlable_id' => $id,
            'urlable_type' => $type,
        ]);
    }

    /**
     * Sort the query alphabetically by url.
     *
     * @param Builder $query
     */
    public function scopeInAlphabeticalOrder($query)
    {
        $query->orderBy('url', 'asc');
    }

    /**
     * Get the model instance correlated with the accessed url.
     *
     * @param bool $silent
     * @return Model|null
     * @throws ModelNotFoundException
     */
    public static function getUrlable($silent = true)
    {
        $model = Request::route()->action['model'] ?? null;

        if ($model && $model instanceof Model && $model->exists) {
            return $model;
        }

        if ($silent === false) {
            throw new ModelNotFoundException;
        }

        return null;
    }

    /**
     * Get the model instance correlated with the accessed url.
     * Throw a ModelNotFoundException if the model doesn't exist.
     *
     * @return Model|null
     * @throws ModelNotFoundException
     */
    public static function getUrlableOrFail()
    {
        return static::getUrlable(false);
    }
}
