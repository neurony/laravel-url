<?php

namespace Zbiller\Url\Contracts;

interface UrlModelContract
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function urlable();

    /**
     * @param bool $silent
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function getUrlable($silent = true);

    /**
     * Get the model instance correlated with the accessed url.
     * Throw a ModelNotFoundException if the model doesn't exist.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function getUrlableOrFail();
}
