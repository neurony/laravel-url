<?php

namespace Zbiller\Url\Tests;

use Zbiller\Url\Options\UrlOptions;
use Zbiller\Url\Tests\Models\UrlModel;
use Zbiller\Url\Exceptions\UrlException;

class HasUrlTest extends TestCase
{
    /** @test */
    public function it_generates_an_url_when_creating_a_record()
    {
        $this->createUrlModel();

        $this->assertEquals('test-name', $this->urlModel->url->url);
    }

    /** @test */
    public function it_updates_the_url_when_modifying_a_record()
    {
        $this->createUrlModel();

        $this->urlModel->update(['name' => 'Test name modified']);

        $this->assertEquals('test-name-modified', $this->urlModel->url->url);
    }

    /** @test */
    public function it_creates_exactly_one_url_per_record()
    {
        $this->createUrlModel();

        $this->urlModel->update(['name' => 'Another test name']);

        $this->assertEquals(1, $this->urlModel->url()->count());
    }

    /** @test */
    public function it_can_return_the_relative_url()
    {
        $this->createUrlModel();

        $this->assertEquals('test-name', $this->urlModel->getUri());
    }

    /** @test */
    public function it_can_return_the_absolute_url()
    {
        $this->createUrlModel();

        $this->assertTrue(
            starts_with($this->urlModel->getUrl(), 'http') &&
            ends_with($this->urlModel->getUrl(), '/'.$this->urlModel->getUri())
        );
    }

    /** @test */
    public function it_eager_loads_the_url_relation_for_the_given_eloquent_model()
    {
        $this->createUrlModel();

        $this->assertTrue(array_key_exists(
            'url', UrlModel::find($this->urlModel->id)->first()->relationsToArray()
        ));
    }

    /** @test */
    public function it_can_ignore_creating_an_url_if_specified()
    {
        $model = (new UrlModel)->doNotGenerateUrl()->create([
            'name' => 'Test name',
        ]);

        $this->assertEquals(0, $model->url()->count());
    }

    /** @test */
    public function it_can_ignore_updating_an_url_if_specified()
    {
        $this->createUrlModel();

        $this->urlModel->doNotGenerateUrl()->update([
            'name' => 'Modified test name',
        ]);

        $this->assertEquals('test-name', $this->urlModel->url->url);
    }

    /** @test */
    public function it_has_a_method_that_allows_specifying_a_prefix_for_the_url()
    {
        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return parent::getUrlOptions()->prefixUrlWith('prefix');
            }
        };

        $this->createUrlModel($model, ['name' => 'String prefix test']);

        $this->assertEquals('prefix/string-prefix-test', $this->urlModel->url->url);

        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return parent::getUrlOptions()->prefixUrlWith(['array', 'prefix']);
            }
        };

        $this->createUrlModel($model, ['name' => 'Array prefix test']);

        $this->assertEquals('array/prefix/array-prefix-test', $this->urlModel->url->url);

        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return parent::getUrlOptions()->prefixUrlWith(function ($prefix, $model) {
                    return implode('/', ['callable', 'prefix']);
                });
            }
        };

        $this->createUrlModel($model, ['name' => 'Callable prefix test']);

        $this->assertEquals('callable/prefix/callable-prefix-test', $this->urlModel->url->url);
    }

    /** @test */
    public function it_has_a_method_that_allows_specifying_a_suffix_for_the_url()
    {
        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return parent::getUrlOptions()->suffixUrlWith('suffix');
            }
        };

        $this->createUrlModel($model, ['name' => 'String suffix test']);

        $this->assertEquals('string-suffix-test/suffix', $this->urlModel->url->url);

        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return parent::getUrlOptions()->suffixUrlWith(['array', 'suffix']);
            }
        };

        $this->createUrlModel($model, ['name' => 'Array suffix test']);

        $this->assertEquals('array-suffix-test/array/suffix', $this->urlModel->url->url);

        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return parent::getUrlOptions()->suffixUrlWith(function ($suffix, $model) {
                    return implode('/', ['callable', 'suffix']);
                });
            }
        };

        $this->createUrlModel($model, ['name' => 'Callable suffix test']);

        $this->assertEquals('callable-suffix-test/callable/suffix', $this->urlModel->url->url);
    }

    /** @test */
    public function it_has_a_method_that_allows_specifying_the_url_glue()
    {
        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return parent::getUrlOptions()
                    ->prefixUrlWith(['testing', 'glue'])
                    ->glueUrlWith('_');
            }
        };

        $this->createUrlModel($model);

        $this->assertEquals('testing_glue_test-name', $this->urlModel->url->url);
    }

    /** @expectedException UrlException */
    public function it_expects_a_controller_and_action_to_be_specified_in_the_options()
    {
        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return UrlOptions::instance()
                    ->generateUrlSlugFrom('name')
                    ->saveUrlSlugTo('slug');
            }
        };

        $this->createUrlModel($model);
    }

    /** @expectedException UrlException */
    public function it_expects_a_from_field_to_be_specified_in_the_options()
    {
        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return UrlOptions::instance()
                    ->routeUrlTo('Controller', 'action')
                    ->saveUrlSlugTo('slug');
            }
        };

        $this->createUrlModel($model);
    }

    /** @expectedException UrlException */
    public function it_expects_a_to_field_to_be_specified_in_the_options()
    {
        $model = new class extends UrlModel {
            public function getUrlOptions() : UrlOptions
            {
                return UrlOptions::instance()
                    ->routeUrlTo('Controller', 'action')
                    ->generateUrlSlugFrom('name');
            }
        };

        $this->createUrlModel($model);
    }
}
