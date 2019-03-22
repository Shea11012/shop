<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use App\Events\OrderPaid;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        return app('alipay')->web([
            'out_trade_no' => $order->no,   // 订单编号，需要保证商户端不重复
            'total_amount' => $order->total_amount, // 订单金额，单位元，支持小数点后两位
            'subject'      => '支付 Laravel shop 的订单：' . $order->no, // 订单标题
        ]);
    }

    // 前段页面回调
    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }
        return view('pages.success', ['msg' => '付款成功']);
    }

    // 服务器端回调
    public function alipayNotify()
    {
        $data = app('alipay')->verify();
        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }
        $order = Order::where('no', $data->out_trade_no)->first();
        if (!$order) {
            return 'fail';
        }

        // 判断这笔订单是否支付过
        if ($order->paid_at) {
            return app('alipay')->success();
        }
        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'alipay',
            'payment_no'     => $data->trade_no,
        ]);

        $this->afterPaid($order);

        return app('alipay')->success();
    }

    public function payByWechat(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        $wechatOrder = app('wechat_pay')->scan([
            'out_trade_no' => $order->no,
            'total_fee'    => $order->total_amount * 100,
            'body'         => '支付 Laravel shop 的订单：' . $order->no,
        ]);

        $qrCode = new QrCode($wechatOrder->code_url);

        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);
    }

    public function wechatNotify()
    {
        $data = app('wechat_pay')->verify();
        $order = Order::where('no', $data->out_trade_no)->first();

        if (!$order) {
            return 'fail';
        }

        if ($order->paid_at) {
            return app('wechat_pay')->success();
        }

        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no'     => $data->transaction_id,
        ]);

        $this->afterPaid($order);
        return app('wechat_pay')->success();
    }

    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    public function wechatRefundNotify(Request $request)
    {
        $failXml = '<xml><return_code>![CDATA[FAIL]]</return_code><return_msg>![CDATA[FAIL]]</return_msg></xml>';
        $data = app('wechat_pay')->verify(null, true);

        if (!$order = Order::where('no', $data['out_trade_no'])->first()) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            $order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        } else {
            $extra = $order->extra;
            $extra['refund_failed_code'] = $data['refund_status'];
            $order->update([
                'refund_status' => Order::REFUND_STATUS_FAILED,
                'extra'         => $extra,
            ]);
        }

        return app('wechat_pay')->success();
    }

    public function payByInstallment(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        if ($order->total_amount < config('app.min_installment_amount')) {
            throw new InvalidRequestException('订单金额低于最低分期金额');
        }

        $this->validate($request, [
            'count' => ['required', Rule::in(array_keys(config('app.installment_fine_rate')))],
        ]);

        // 删除同一笔商品订单发起过多次未支付的分期付款，避免同一笔商品订单有多个分期付款
        Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();

        $count = $request->input('count');
        $installment = new Installment([
            'total_amount' => $order->total_amount,
            'count'        => $count,
            'fee_rate'     => config('app.installment_fee_rate')[$count],
            'find_rate'    => config('app.installment_fine_rate'),
        ]);

        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();

        $dueDate = Carbon::tomorrow(); // 第一期的还款截止日期为明天凌晨 0 点
        $base = big_number($order->total_amount)->divide($count)->getValue(); // 每期本金
        $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue(); // 每期手续费
        // 根据用户选择期数，创建对应数量的还款计划
        for ($i = 0; $i < $count; $i++) {
            if ($i === $count - 1) { // 最后一期的本金需要总本金减去前面几期的本金
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count - 1));
            }
            $installment->items()->create([
                'sequence' => $i,
                'base'     => $base,
                'fee'      => $fee,
                'due_date' => $dueDate,
            ]);
            $dueDate = $dueDate->copy()->addDay(30);
        }

        return $installment;
    }
}
