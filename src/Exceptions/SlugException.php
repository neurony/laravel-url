<?php

namespace Zbiller\Url\Exceptions;

use Exception;

class SlugException extends Exception
{
    /**
     * The exception to be thrown when the "from field" has not been supplied to the sluggable functionality.
     *
     * @param string $class
     * @return static
     */
    public static function mandatoryFromField($class)
    {
        return new static(
            'The model '.$class.' uses the HasSlug trait'.PHP_EOL.
            'You are required to set the field from where to generate the slug ($fromField)'.PHP_EOL.
            'You can do this from inside the getSlugOptions() method defined on the model.'
        );
    }

    /**
     * The exception to be thrown when the "to field" has not been supplied to the sluggable functionality.
     *
     * @param string $class
     * @return static
     */
    public static function mandatoryToField($class)
    {
        return new static(
            'The model '.$class.' uses the HasSlug trait'.PHP_EOL.
            'You are required to set the field where to store the generated slug ($toField)'.PHP_EOL.
            'You can do this from inside the getSlugOptions() method defined on the model.'
        );
    }
}
