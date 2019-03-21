<?php

namespace App\Http\Controllers;

use Faker\Generator;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function root()
    {
        $a = app(Generator::class);
        $b = $a->sentences;
        dump($a,$b);
    }
}
