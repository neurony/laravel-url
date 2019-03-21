<?php

namespace Neurony\Url\Traits;

use Neurony\Url\Options\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Neurony\Url\Exceptions\SlugException;

trait HasSlug
{
    /**
     * The container for all the options necessary for this trait.
     * Options can be viewed in the Neurony\Url\Options\SlugOptions file.
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
    public static function bootHasSlug(): void
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
     * @throws SlugException
     */
    protected function generateSlugOnCreate(): void
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
     * @throws SlugException
     */
    protected function generateSlugOnUpdate(): void
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
     * @throws SlugException
     */
    public function generateSlug(): void
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
    protected function generateNonUniqueSlug(): string
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
    protected function makeSlugUnique(string $slug): string
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
    protected function slugHasBeenSupplied(): bool
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
    protected function slugHasChanged(): bool
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
    protected function getSlugSource(): string
    {
        if (is_callable($this->slugOptions->fromField)) {
            return call_user_func($this->slugOptions->fromField, $this);
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
    protected function slugAlreadyExists(string $slug): bool
    {
        return (bool) static::withoutGlobalScopes()->where($this->slugOptions->toField, $slug)
            ->where($this->getKeyName(), '!=', $this->getKey() ?: '0')
            ->first();
    }

    /**
     * Both instantiate the slug options as well as validate their contents.
     *
     * @return void
     * @throws SlugException
     */
    protected function initSlugOptions(): void
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
     * @throws SlugException
     */
    protected function validateSlugOptions(): void
    {
        if (! $this->slugOptions->fromField) {
            throw SlugException::mandatoryFromField(static::class);
        }

        if (! $this->slugOptions->toField) {
            throw SlugException::mandatoryToField(static::class);
        }
    }
}
