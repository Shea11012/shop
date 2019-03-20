<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('优惠券标题');
            $table->string('code')->unique();
            $table->string('type')->comment('优惠券类型，支持固定金额和百分比');
            $table->decimal('value',10,2)->comment('折扣值');
            $table->unsignedInteger('total')->comment('全站可兑换数量');
            $table->unsignedInteger('used')->default(0)->comment('当前已兑换数量');
            $table->decimal('min_amount',10,2)->comment('使用该优惠券的最低订单金额');
            $table->dateTime('not_before')->comment('在此之前不可用')->nullable();
            $table->dateTime('not_after')->comment('在此之后不可用')->nullable();
            $table->boolean('enabled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_codes');
    }
}
