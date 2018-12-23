<?php

namespace Zbiller\Url\Tests\Controllers;

use Illuminate\Routing\Controller;
use Zbiller\Url\Models\Url;

class UrlsController extends Controller
{
    public function show()
    {
        $model = Url::getUrlableOrFail();

        return $model->name;
    }
}