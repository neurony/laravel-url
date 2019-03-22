<?php

namespace Neurony\Url\Tests;

use Illuminate\Support\Facades\Route;
use Neurony\Url\Tests\Models\UrlModel;

class RouteUrlTest extends TestCase
{
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

        Route::customUrl();

        $this->urlModel = UrlModel::create([
            'name' => 'Test name',
        ]);
    }

    /** @test */
    public function it_dispatches_the_url_to_the_specified_endpoint()
    {
        $this->get($this->urlModel->slug)->assertStatus(200);
    }

    /** @test */
    public function it_supplies_the_urlable_model_when_dispatching()
    {
        $this->get($this->urlModel->slug)->assertSee('Test name');
    }
}
