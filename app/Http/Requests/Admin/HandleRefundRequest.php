<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Request;

class HandleRefundRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'agree' => ['required','boolean'],
            'reason' => ['required_if:agree,false'], // 表示 agree 为 false 时,此字段必填
        ];
    }
}
