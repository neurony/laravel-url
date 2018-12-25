<?php

namespace Zbiller\Url\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface UrlModelContract
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function urlable();

    /**
     * @param bool $silent
     * @return Model|null
     * @throws ModelNotFoundException
     */
    public static function getUrlable(bool $silent = true): ?Model;

    /**
     * Get the model instance correlated with the accessed url.
     * Throw a ModelNotFoundException if the model doesn't exist.
     *
     * @return Model|null
     * @throws ModelNotFoundException
     */
    public static function getUrlableOrFail(): ?Model;
}
