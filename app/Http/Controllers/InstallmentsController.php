<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installment = Installment::query()
            ->where('user_id', $request->user()->id)
            ->paginate(10);

        return view('installments.index', compact('installment'));
    }

    public function show(Installment $installment)
    {
        $this->authorize('own', $installment);
        $items = $installment->items()->orderBy('sequence')->get();
        // 下一个未完成还款计划
        $nextItem = $items->where('paid_at', null)->first();

        return view('installments.show', compact('items', 'installment', 'nextItem'));
    }

    // 支付宝发起支付接口
    public function payByAlipay(Installment $installment)
    {
        if ($installment->order->closed) {
            throw new InvalidRequestException('对应的商品订单已被关闭');
        }

        if ($installment->status === Installment::STATUS_FINISHED) {
            throw new InvalidRequestException('该分期订单已结清');
        }

        if (!$nextItem = $installment->items()->where('paid_at')->orderBy('sequence')->first()) {
            throw new InvalidRequestException('该分期订单已结清');
        }

        return app('alipay')->web([
            'out_trade_no' => $installment->no . '_' . $nextItem->sequence,
            'total_amount' => $nextItem->total,
            'subject'      => '支付 Laravel shop 的分期订单：' . $installment->no,
            'notify_url'   => route('installments.alipay.notify'),
            'return_url'   => route('installments.alipay.return'),
        ]);
    }

    // 支付宝前端回调
    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    // 支付宝后端回调
    public function alipayNotify()
    {
        $data = app('alipay')->verify();
        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        list($no, $sequence) = explode('_', $data->out_trade_no);
        if (!$installment = Installment::where('no', $no)->first()) {
            return 'fail';
        }
        if (!$item = $installment->items()->where('sequence', $sequence)->first()) {
            return 'fail';
        }

        if ($item->paid_at) {
            return app('alipay')->success();
        }

        \DB::transaction(function () use ($data, $no, $installment, $item) {
            $item->update([
                'paid_at'        => Carbon::now(),
                'payment_method' => 'alipay',
                'payment_no'     => $data->trade_no,
            ]);

            if ($item->sequence === 0) {
                $installment->update(['status' => Installment::STATUS_REPAYING]);
                $installment->order->update([
                    'paid_at'        => Carbon::now(),
                    'payment_method' => 'installment',
                    'payment_no'     => $no,
                ]);
                event(new OrderPaid($installment->order));
            }

            if ($item->sequence === $installment->count - 1) {
                $installment->update(['status' => Installment::STATUS_FINISHED]);
            }
        });

        return app('alipay')->success();
    }

    public function wechatRefundNotify(Request $request)
    {
        $failXml = '<xml><return_code>![CDATA[FAIL]]</return_code><return_msg>![CDATA[FAIL]]</return_msg></xml>';
        $data = app('wechat_pay')->verify(null, true);
        list($no, $sequence) = explode('_', $data['out_refund_no']);

        $item = InstallmentItem::query()
            ->whereHas('installment', function ($query) use ($no) {
                $query->whereHas('order', function ($query) use ($no) {
                    $query->where('refund_no', $no);
                });
            })
            ->where('sequence', $sequence)
            ->first();

        if (!$item) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
            ]);
            $item->installment->refreshRefundStatus();
        } else {
            $item->update([
                'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
            ]);
        }

        return app('wechat_pay')->success();
    }
}
