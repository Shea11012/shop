<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installment = Installment::query()
            ->where('user_id',$request->user()->id)
            ->paginate(10);

        return view('installments.index',compact('installment'));
    }

    public function show(Installment $installment)
    {
        $this->authorize('own',$installment);
        $items = $installment->items()->orderBy('sequence')->get();
        // 下一个未完成还款计划
        $nextItem = $items->where('paid_at',null)->first();

        return view('installments.show',compact('items','installment','nextItem'));
    }
}
