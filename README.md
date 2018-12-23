# Overview 

**Generate custom URLs for any Eloquent model**.   

This package allows any Eloquent model record to have a custom URL associated to it through a polymorphic one-to-one relationship.   
   
The URL will be automatically saved inside the `urls` table, along with the model using the `created` and `updated` Eloquent events.   
When the model is force deleted, the corresponding URL will also be deleted by leveraging the `deleted` Eloquent event.

# Installation

Install the package via Composer:

```
composer require zbiller/laravel-url
```

Publish the migration file with:

```
php artisan vendor:publish --provider="Zbiller\Url\ServiceProvider" --tag="migrations"
```

After the migration has been published you can create the `urls` table by running:

```
php artisan migrate
```

# Usage

### Step 1

Insert the following as your **last** line in your `routes/web.php` file:

```
Route::customUrl();
```

This route will catch any URL and will compare it against the `urls` table.   
If the URL matches a record in that table, it will dispatch the request to the designated controller and action specified in step 2.

### Step 2

Your Eloquent models should use the `Zbiller\Url\Traits\HasUrl` trait and the `Zbiller\Url\Options\UrlOptions` class.

The trait contains an abstract method `getUrlOptions()` that you must implement yourself.

Here's an example of how to implement the trait:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Zbiller\Url\Options\UrlOptions;
use Zbiller\Url\Traits\HasUrl;

class YourModel extends Model
{
    use HasUrl;
    
    /**
     * Get the options for generating the url.
     *
     * @return UrlOptions
     */
    public function getUrlOptions() : UrlOptions
    {
        return UrlOptions::instance()
            ->routeUrlTo(YourController::class, 'show') // mandatory --- a controller and action specifying where to dispatch the request when the custom URL is accessed in the browser
            ->generateUrlSlugFrom('name') // mandatory --- a field present in your model's table from which to generate the slug that will be later used to generate the URL
            ->saveUrlSlugTo('slug'); // mandatory --- a field present in your model's table that will be used to store the slug
    }
}
```

The `getUrlOptions()` should always return a `Zbiller\Url\Options\UrlOptions` instance.   
You can view all options and their methods of implementation inside the `Zbiller\Url\Options\UrlOptions` class.

### Step 3

Create the controller and action specified in the `getUrlOptions()` method.    
   
Once created, you can fetch your Eloquent model instance corresponding to the URL visited by using the `getUrlable()` or `getUrlableOrFail()` methods, present in the `Zbiller\Url\Models\Url` class.

```php
<?php

namespace App\Controllers;

use Illuminate\Routing\Controller;
use Zbiller\Url\Models\Url;

class PostsController extends Controller
{
    public function show()
    {
        $post = Url::getUrlableOrFail();

        return view('posts.show')->with([
            'post' => $post
        ]);
    }
}
```
> The difference between `getUrlable()` and `getUrlableOrFail()` is that when no record is found, the first returns `null` while the second throws a `ModelNotFoundException`.

# Customisations

### Set a prefix

You can define a prefix for your URLs using the `prefixUrlWith()` method in your definition of the `getUrlOptions()` method.

```php
/**
 * Get the options for generating the url.
 *
 * @return UrlOptions
 */
public function getUrlOptions() : UrlOptions
{
    return UrlOptions::instance()
        ->routeUrlTo(YourController::class, 'show')
        ->generateUrlSlugFrom('name')
        ->saveUrlSlugTo('slug')
        ->prefixUrlWith(function ($prefix, $model) {
            foreach ($model->parents as $parent) {
                $prefix[] = $parent->slug;
            }

            return implode('/' , (array)$prefix);
        });
}
```

The example above illustrates how to prefix an URL using a callable.   
Please note that you can also pass a string or an array to the `prefixUrlWith()` method:   

```php
...->prefixUrlWith('some-prefix'); // URL will be "some-prefix/your-model-slug"
...->prefixUrlWith(['some', 'prefix']); // URL will be "some/prefix/your-model-slug"
```

### Set a suffix

You can define a suffix for your URLs using the `suffixUrlWith()` method in your definition of the `getUrlOptions()` method.

```php
/**
 * Get the options for generating the url.
 *
 * @return UrlOptions
 */
public function getUrlOptions() : UrlOptions
{
    return UrlOptions::instance()
        ->routeUrlTo(YourController::class, 'show')
        ->generateUrlSlugFrom('name')
        ->saveUrlSlugTo('slug')
        ->suffixUrlWith(function ($suffix, $model) {
            foreach ($model->children as $child) {
                $prefix[] = $child->slug;
            }

            return implode('/' , (array)$prefix);
        });
}
```

The example above illustrates how to suffix an URL using a callable.   
Please note that you can also pass a string or an array to the `prefixUrlWith()` method:   

```php
...->suffixUrlWith('some-suffix'); // URL will be "your-model-slug/some-suffix"
...->suffixUrlWith(['some', 'suffix']); // URL will be "your-model-slug/some/suffix"
```

### Set a separator

You can specify with what string to separate the different segments of an URL by using the `glueUrlWith()` method in your definition of the `getUrlOptions()` method.   
   
The default glue value is `/`.

```php
/**
 * Get the options for generating the url.
 *
 * @return UrlOptions
 */
public function getUrlOptions() : UrlOptions
{
    return UrlOptions::instance()
        ->routeUrlTo(YourController::class, 'show')
        ->generateUrlSlugFrom('name')
        ->saveUrlSlugTo('slug')
        ->glueUrlWith('_');
}
```

### Disable cascade update

Let's say that in your `urls` table you have the following 2 URLs: `posts/my-posts` and `posts/my-posts/post-one`.   
   
By default, when updating an URL, all its "children" URLs will be updated also.   
   
So if you update the `posts/my-posts` URL to become `posts/my-latest-posts`, the trait will automatically update all the underlying URLs, meaning that the URL `posts/my-posts/post-one` will become `posts/my-latest-posts/post-one`.   
   
To disable this automation, you can use the `doNotUpdateCascading()` method in your definition of the `getUrlOptions()` method.   

```php
/**
 * Get the options for generating the url.
 *
 * @return UrlOptions
 */
public function getUrlOptions() : UrlOptions
{
    return UrlOptions::instance()
        ->routeUrlTo(YourController::class, 'show')
        ->generateUrlSlugFrom('name')
        ->saveUrlSlugTo('slug')
        ->doNotUpdateCascading();
}
```

### Disable creating/updating an URL

If you want to save an Eloquent model that uses the `HasUrl` trait, but you don't want to create or update its corresponding URL, you can do so by calling the `doNotGenerateUrl()` method.

```php
// create a model record without creating an URL for it
$model = (new YourModel)->doNotGenerateUrl()->create(...);

// update a model record without updating its URL
$model->doNotGenerateUrl()->update(...);
```

# Extra

You can get the absolute URL of your Eloquent model record by using:

```php
$model = YourModel::first();
$model->getUrl(); // returns "http://domain.tld/your-model-slug"
```

You can get the relative URL of your Eloquent model record by using:

```php
$model = YourModel::first();
$model->getUri(); // returns "your-model-slug"
```

You can access the `Zbiller\Url\Models\Url` instance from your Eloquent models using the `url` relationp:

```php
$model = YourModel::first();
$url = $model->url; // returns a loaded instance of the Zbiller\Url\Models\Url model
```

You can access your Eloquent model instance from the `Zbiller\Url\Models\Url` model using the `urlable` relation:

```php
use Zbiller\Url\Models\Url;

$url = Url::first();
$model = $url->urlable; // returns a loaded instance of your Eloquent model
```

You can find a specific URL by using the following query scope:

```php
use Zbiller\Url\Models\Url;

$url = Url::whereUrl('some-url')->first();
```

# Security

If you discover any security related issues, please email zbiller@gmail.com instead of using the issue tracker.

# License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.

# Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

# Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.