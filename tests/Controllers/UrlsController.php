<?php

namespace Zbiller\Url\Tests\Controllers;

use Zbiller\Url\Models\Url;
use Illuminate\Routing\Controller;

class UrlsController extends Controller
{
    public function show()
    {
        $model = Url::getUrlableOrFail();

        return $model->name;
    }
}
