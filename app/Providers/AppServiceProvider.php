<?php

namespace App\Providers;

use App\Http\ViewComposers\CategoryTreeComposer;
use Monolog\Logger;
use Illuminate\Support\ServiceProvider;
use Yansongda\Pay\Pay;
use Carbon\Carbon;
use Elasticsearch\ClientBuilder as ESClientBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('alipay', function () {
            $config = config('pay.alipay');
            $config['notify_url'] = route('payment.alipay.notify');
            $config['return_url'] = route('payment.alipay.return');

            if (app()->environment() !== 'production') {
                $config['mode'] = 'dev';
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }
            return Pay::alipay($config);
        });

        $this->app->singleton('wechat_pay', function () {
            $config = config('pay.wechat');
            $config['notify_url'] = route('payment.wechat.notify');
            if (app()->environment() !== 'production') {
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }
            return Pay::wechat($config);
        });

        $this->app->singleton('es',function () {
            $builder = ESClientBuilder::create()->setHosts(config('database.elasticsearch.host'));
            if (app()->environment() === 'local') {
                $builder->setLogger(app('log')->driver());
            }

            return $builder->build();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Carbon::setLocale('zh');
        /*
         * 当 laravel 渲染 products.index 和 products.show 模板时，就会使用 CategoryTreeComposer 这个来注入类目树变量
         * 同时 laravel 还支持通配符，例如 products.*
         * */
        \View::composer(['products.index','products.show'],CategoryTreeComposer::class);
    }
}
