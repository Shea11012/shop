<?php
/**
 * User: shea
 * Date: 19-3-18
 * Time: 下午8:40
 */

namespace App\Services;

use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\uri_for;

class OrderService
{
    public function store(User $user, UserAddress $userAddress, $remark, $items,CouponCode $couponCode)
    {
        $user = \Auth::user();
        // 如果传入优惠券，先检查是否可用
        if ($couponCode) {
            $couponCode->checkAvailable($user);
        }

        $order = \DB::transaction(function () use ($user, $userAddress, $remark, $items,$couponCode) {
            $userAddress->update(['last_update_at' => Carbon::now()]);

            $order = new Order([
                'address'      => [
                    'address'       => $userAddress->full_address,
                    'zip'           => $userAddress->zip,
                    'contact_name'  => $userAddress->contact_name,
                    'contact_phone' => $userAddress->contact_phone,
                ],
                'remark'       => $remark,
                'total_amount' => 0,
            ]);

            $order->user()->associate($user);
            $order->save();

            $totalAmount = 0;

            foreach ($items as $data) {
                $sku = ProductSku::find($data['sku_id']);
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price'  => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }
            if ($couponCode) {
                // 计算出总金额后，判断是否符合优惠券规则
                $couponCode->checkAvailable($user,$totalAmount);
                // 将订单金额修改为优惠券后的金额
                $totalAmount = $couponCode->getAdjustedPrice($totalAmount);
                $order->couponCode()->associate($couponCode);

                if ($couponCode->changeUsed() <= 0) {
                    throw new CouponCodeUnavailableException('该优惠券已被兑完');
                }
            }
            $order->update(['total_amount' => $totalAmount]);
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);
            return $order;
        });

        dispatch(new CloseOrder($order,config('app.order_ttl')));

        return $order;
    }
}
