<?php

namespace Neurony\Url\Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Schema\Blueprint;
use Neurony\Url\Tests\Models\SlugModel;
use Neurony\Url\Tests\Models\UrlModel;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @var SlugModel
     */
    protected $slugModel;

    /**
     * @var UrlModel
     */
    protected $urlModel;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    /**
     * Get package providers.
     *
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['Neurony\Url\ServiceProvider'];
    }

    /**
     * Define environment setup.
     *
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Set up the database and migrate the necessary tables.
     *
     * @param  $app
     */
    protected function setUpDatabase(Application $app)
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        $schema = $app['db']->connection()->getSchemaBuilder();

        $schema->create('urls', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url')->unique();
            $table->morphs('urlable');
            $table->timestamps();
        });
    }

    /**
     * @param SlugModel|null $model
     * @param array $attributes
     */
    protected function createSlugModel(SlugModel $model = null, $attributes = [])
    {
        $model = $model && $model instanceof SlugModel ? $model : new SlugModel;
        $attributes = $attributes && ! empty($attributes) ? $attributes : [
            'name' => 'Test name',
            'other_field' => 'Other field',
        ];

        $this->slugModel = $model->create($attributes);
    }

    /**
     * @param UrlModel|null $model
     * @param array $attributes
     */
    protected function createUrlModel(UrlModel $model = null, $attributes = [])
    {
        $model = $model && $model instanceof UrlModel ? $model : new UrlModel;
        $attributes = $attributes && ! empty($attributes) ? $attributes : [
            'name' => 'Test name',
        ];

        $this->urlModel = $model->create($attributes);
    }
}
