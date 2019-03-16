<?php
/**
 * User: shea
 * Date: 19-3-10
 * Time: 下午5:30
 */

function route_class()
{
    return str_replace('.','-',Route::currentRouteName());
}
