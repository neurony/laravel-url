<?php

namespace Neurony\Url\Tests\Controllers;

use Illuminate\Routing\Controller;
use Neurony\Url\Models\Url;

class UrlsController extends Controller
{
    public function show()
    {
        $model = Url::getUrlableOrFail();

        return $model->name;
    }
}
