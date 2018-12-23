<?php

namespace Zbiller\Url\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Zbiller\Url\Exceptions\UrlException;
use Zbiller\Url\Models\Url;
use Zbiller\Url\Options\SlugOptions;
use Zbiller\Url\Options\UrlOptions;

trait HasUrl
{
    use HasSlug;

    /**
     * The container for all the options necessary for this trait.
     * Options can be viewed in the Zbiller\Url\Options\UrlOptions file.
     *
     * @var UrlOptions
     */
    protected $urlOptions;

    /**
     * Flag to manually enable/disable the url generation only for the current request.
     *
     * @var bool
     */
    protected static $generateUrl = true;

    /**
     * Set the options for the HasUrl trait.
     *
     * @return UrlOptions
     */
    abstract public function getUrlOptions(): UrlOptions;

    /**
     * Boot the trait.
     *
     * Check if the "getUrlOptions" method has been implemented on the underlying model class.
     * Eager load urls through anonymous global scope.
     * Trigger eloquent events to create, update, delete url.
     *
     * @return void
     */
    public static function bootHasUrl()
    {
        static::addGlobalScope('url', function (Builder $builder) {
            $builder->with('url');
        });

        static::created(function (Model $model) {
            if (self::$generateUrl === true) {
                $model->createUrl();
            }
        });

        static::updated(function (Model $model) {
            if (self::$generateUrl === true) {
                $model->updateUrl();
            }
        });

        static::saved(function (Model $model) {
            if (self::$generateUrl === false) {
                self::$generateUrl = true;
            }
        });

        static::deleted(function (Model $model) {
            if ($model->forceDeleting !== false) {
                $model->deleteUrl();
            }
        });
    }

    /**
     * Get the model's url.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function url()
    {
        return $this->morphOne(Url::class, 'urlable');
    }

    /**
     * Get the model's direct url string.
     *
     * @param bool|null $secure
     * @return string|null
     */
    public function getUrl($secure = null)
    {
        if ($this->url && $this->url->exists) {
            return url($this->url->url, [], $secure);
        }

        return null;
    }

    /**
     * Get the model's direct uri string.
     *
     * @return string|null
     */
    public function getUri()
    {
        return optional($this->url)->url ?: null;
    }

    /**
     * Disable the url generation manually only for the current request.
     *
     * @return static
     */
    public function doNotGenerateUrl()
    {
        self::$generateUrl = false;

        return $this;
    }

    /**
     * Get the options for the HasSlug trait.
     *
     * @return SlugOptions
     * @throws Exception
     */
    public function getSlugOptions()
    {
        $this->initUrlOptions();

        return SlugOptions::instance()
            ->generateSlugFrom($this->urlOptions->fromField)
            ->saveSlugTo($this->urlOptions->toField);
    }

    /**
     * @return void
     * @throws UrlException
     * @throws Exception
     */
    public function saveUrl()
    {
        $this->initUrlOptions();

        if ($this->url && $this->url->exists) {
            $this->updateUrl();
        } else {
            $this->createUrl();
        }
    }

    /**
     * Create a new url for the model.
     *
     * @return void
     * @throws Exception
     */
    public function createUrl()
    {
        $this->initUrlOptions();

        if (!$this->getAttribute($this->urlOptions->toField)) {
            return;
        }

        try {
            $this->url()->create([
                'url' => $this->buildFullUrl()
            ]);
        } catch (Exception $e) {
            throw UrlException::createFailed();
        }
    }

    /**
     * Update the existing url for the model.
     *
     * @return void
     * @throws Exception
     */
    public function updateUrl()
    {
        $this->initUrlOptions();

        if (!$this->getAttribute($this->urlOptions->toField)) {
            return;
        }

        try {
            DB::transaction(function () {
                if ($this->url()->count() == 0) {
                    $this->createUrl();
                }

                $this->url()->update([
                    'url' => $this->buildFullUrl()
                ]);

                if ($this->urlOptions->cascadeUpdate === true) {
                    $this->updateUrlsInCascade();
                }
            });
        } catch (Exception $e) {
            throw UrlException::updateFailed();
        }
    }

    /**
     * Delete the url for the just deleted model.
     *
     * @return void
     */
    public function deleteUrl()
    {
        try {
            $this->url()->delete();
        } catch (Exception $e) {
            throw UrlException::deleteFailed();
        }
    }

    /**
     * Synchronize children urls for the actual model's url.
     * Saves all children urls of the model in use with the new parent model's slug.
     *
     * @return void
     */
    protected function updateUrlsInCascade()
    {
        $old = trim($this->getOriginal($this->urlOptions->toField), '/');
        $new = trim($this->getAttribute($this->urlOptions->toField), '/');

        $children = URL::where('urlable_type', static::class)->where(function ($query) use ($old) {
            $query->where('url', 'like', "{$old}/%")->orWhere('url', 'like', "%/{$old}/%");
        })->get();

        foreach ($children as $child) {
            $child->update([
                'url' => str_replace($old . '/', $new . '/', $child->url)
            ]);
        }
    }

    /**
     * Get the full relative url.
     * The full url will also include the prefix and suffix if any was provided.
     *
     * @return string
     */
    protected function buildFullUrl()
    {
        $prefix = $this->buildUrlSegment('prefix');
        $suffix = $this->buildUrlSegment('suffix');

        return
            (str_is('/', $prefix) ? '' : ($prefix ? $prefix . $this->urlOptions->urlGlue : '')) .
            $this->getAttribute($this->urlOptions->toField) .
            (str_is('/', $suffix) ? '' : ($suffix ? $this->urlOptions->urlGlue . $suffix : ''));
    }

    /**
     * Build the url segment.
     * This can be either "prefix" or "suffix".
     * The accepted parameter $type accepts only "prefix" and "suffix" as it's value.
     * Otherwise, the method will return an empty string.
     *
     * @param string $type
     * @return mixed|string
     */
    protected function buildUrlSegment($type)
    {
        if ($type != 'prefix' && $type != 'suffix') {
            return '';
        }

        $segment = $this->urlOptions->{'url' . ucwords($type)};

        if (is_callable($segment)) {
            return call_user_func_array($segment, [[], $this]);
        } elseif (is_array($segment)) {
            return implode($this->urlOptions->urlGlue, $segment);
        } elseif (is_string($segment)) {
            return $segment;
        } else {
            return '';
        }
    }

    /**
     * Both instantiate the url options as well as validate their contents.
     *
     * @return void
     * @throws Exception
     */
    protected function initUrlOptions()
    {
        if ($this->urlOptions === null) {
            $this->urlOptions = $this->getUrlOptions();
        }

        $this->validateUrlOptions();
    }

    /**
     * Check if mandatory slug options have been properly set from the model.
     * Check if $fromField and $toField have been set.
     *
     * @return void
     */
    protected function validateUrlOptions()
    {
        if (!$this->urlOptions->routeController || !$this->urlOptions->routeAction) {
            throw UrlException::mandatoryRouting(static::class);
        }

        if (!$this->urlOptions->fromField) {
            throw UrlException::mandatoryFromField(static::class);
        }

        if (!$this->urlOptions->toField) {
            throw UrlException::mandatoryToField(static::class);
        }
    }
}
