<?php

namespace Neurony\Url\Exceptions;

use Exception;

class UrlException extends Exception
{
    /**
     * The exception to be thrown when the process of creating a url has failed.
     *
     * @return static
     */
    public static function createFailed(): self
    {
        return new static('Failed creating the url!');
    }

    /**
     * The exception to be thrown when the process of updating a url has failed.
     *
     * @return static
     */
    public static function updateFailed(): self
    {
        return new static('Failed updating the url!');
    }

    /**
     * The exception to be thrown when the process of deleting a url has failed.
     *
     * @return static
     */
    public static function deleteFailed(): self
    {
        return new static('Failed deleting the url!');
    }

    /**
     * The exception to be thrown when the "route url to" has not been supplied to the urlable functionality.
     *
     * @param string $class
     * @return static
     */
    public static function mandatoryRouting(string $class): self
    {
        return new static(
            'The model '.$class.' uses the HasUrl trait'.PHP_EOL.
            'You are required to set the routing from where Laravel will dispatch it\'s route requests.'.PHP_EOL.
            'You can do this from inside the getUrlOptions() method defined on the model.'
        );
    }

    /**
     * The exception to be thrown when the "from field" has not been supplied to the urlable functionality.
     *
     * @param string $class
     * @return static
     */
    public static function mandatoryFromField(string $class): self
    {
        return new static(
            'The model '.$class.' uses the HasUrl trait'.PHP_EOL.
            'You are required to set the field from where to generate the url slug ($fromField)'.PHP_EOL.
            'You can do this from inside the getUrlOptions() method defined on the model.'
        );
    }

    /**
     * The exception to be thrown when the "to field" has not been supplied to the urlable functionality.
     *
     * @param string $class
     * @return static
     */
    public static function mandatoryToField(string $class): self
    {
        return new static(
            'The model '.$class.' uses the HasUrl trait'.PHP_EOL.
            'You are required to set the field where to store the generated url slug ($toField)'.PHP_EOL.
            'You can do this from inside the getUrlOptions() method defined on the model.'
        );
    }
}
