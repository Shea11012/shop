<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function root()
    {
        $params = [
            'index' => 'products',
            'type'  => '_doc',
            'body'  => [
                'from'  => 0,
                'size'  => 5,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['on_sale' => true]],
                        ],
                    ],
                ],
            ],
        ];
        $a = app('es')->search($params);
        dd($a);
    }
}
