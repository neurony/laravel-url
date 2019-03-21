<?php

namespace Neurony\Url\Tests\Controllers;

use Neurony\Url\Models\Url;
use Illuminate\Routing\Controller;

class UrlsController extends Controller
{
    public function show()
    {
        $model = Url::getUrlableOrFail();

        return $model->name;
    }
}
