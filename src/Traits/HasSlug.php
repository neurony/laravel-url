<?php

namespace Zbiller\Url\Traits;

use Closure;
use Zbiller\Url\Options\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Zbiller\Url\Exceptions\SlugException;

trait HasSlug
{
    /**
     * The container for all the options necessary for this trait.
     * Options can be viewed in the Zbiller\Url\Options\SlugOptions file.
     *
     * @var SlugOptions
     */
    protected $slugOptions;

    /**
     * Set the options for the HasSlug trait.
     *
     * @return SlugOptions
     */
    abstract public function getSlugOptions(): SlugOptions;

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootHasSlug()
    {
        static::creating(function (Model $model) {
            $model->generateSlugOnCreate();
        });

        static::updating(function (Model $model) {
            $model->generateSlugOnUpdate();
        });
    }

    /**
     * Handle setting the slug on model creation.
     *
     * @return void
     */
    protected function generateSlugOnCreate()
    {
        $this->initSlugOptions();

        if ($this->slugOptions->generateSlugOnCreate === false) {
            return;
        }

        $this->generateSlug();
    }

    /**
     * Handle setting the slug on model update.
     *
     * @return void
     */
    protected function generateSlugOnUpdate()
    {
        $this->initSlugOptions();

        if ($this->slugOptions->generateSlugOnUpdate === false) {
            return;
        }

        $this->generateSlug();
    }

    /**
     * The logic for actually setting the slug.
     *
     * @return void
     */
    public function generateSlug()
    {
        $this->initSlugOptions();

        if ($this->slugHasBeenSupplied()) {
            $slug = $this->generateNonUniqueSlug();

            if ($this->slugOptions->uniqueSlugs) {
                $slug = $this->makeSlugUnique($slug);
            }

            $this->setAttribute($this->slugOptions->toField, $slug);
        }
    }

    /**
     * Generate a non unique slug for this record.
     *
     * @return string
     */
    protected function generateNonUniqueSlug()
    {
        if ($this->slugHasChanged()) {
            $source = $this->getAttribute($this->slugOptions->toField);

            return str_is('/', $source) ? $source : str_slug($source);
        }

        $source = $this->getSlugSource();

        return str_is('/', $source) ? $source : str_slug(
            $source, $this->slugOptions->slugSeparator, $this->slugOptions->slugLanguage
        );
    }

    /**
     * Make the given slug unique.
     *
     * @param string $slug
     * @return string
     */
    protected function makeSlugUnique($slug)
    {
        $original = $slug;
        $i = 1;

        while ($this->slugAlreadyExists($slug) || $slug === '') {
            $slug = $original.$this->slugOptions->slugSeparator.$i++;
        }

        return $slug;
    }

    /**
     * Check if the $fromField slug has been supplied.
     * If not, then skip the entire slug generation.
     *
     * @return bool
     */
    protected function slugHasBeenSupplied()
    {
        if (is_array($this->slugOptions->fromField)) {
            foreach ($this->slugOptions->fromField as $field) {
                if ($this->getAttribute($field) !== null) {
                    return true;
                }
            }

            return false;
        }

        return $this->getAttribute($this->slugOptions->fromField) !== null;
    }

    /**
     * Determine if a custom slug has been saved.
     *
     * @return bool
     */
    protected function slugHasChanged()
    {
        return
            $this->getOriginal($this->slugOptions->toField) &&
            $this->getOriginal($this->slugOptions->toField) != $this->getAttribute($this->slugOptions->toField);
    }

    /**
     * Get the string that should be used as base for the slug.
     *
     * @return string
     */
    protected function getSlugSource()
    {
        if ($this->slugOptions->fromField instanceof Closure) {
            $source = call_user_func($this->slugOptions->fromField, $this);

            return substr($source, 0, $this->slugOptions->fromField);
        }

        return collect($this->slugOptions->fromField)->map(function ($field) {
            return $this->getAttribute($field) ?: '';
        })->implode($this->slugOptions->slugSeparator);
    }

    /**
     * Check if the given slug already exists on another record.
     *
     * @param string $slug
     * @return bool
     */
    protected function slugAlreadyExists($slug)
    {
        return (bool) static::withoutGlobalScopes()->where($this->slugOptions->toField, $slug)
            ->where($this->getKeyName(), '!=', $this->getKey() ?: '0')
            ->first();
    }

    /**
     * Both instantiate the slug options as well as validate their contents.
     *
     * @return void
     */
    protected function initSlugOptions()
    {
        if ($this->slugOptions === null) {
            $this->slugOptions = $this->getSlugOptions();
        }

        $this->validateSlugOptions();
    }

    /**
     * Check if mandatory slug options have been properly set from the model.
     * Check if $fromField and $toField have been set.
     *
     * @return void
     */
    protected function validateSlugOptions()
    {
        if (! $this->slugOptions->fromField) {
            throw SlugException::mandatoryFromField(static::class);
        }

        if (! $this->slugOptions->toField) {
            throw SlugException::mandatoryToField(static::class);
        }
    }
}
