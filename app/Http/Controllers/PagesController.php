<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function root()
    {
        \Log::debug(Carbon::now()->toDateTimeString());
    }
}
