<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Order extends Model
{
    const REFUND_STATUS_PENDING    = 'pending';
    const REFUND_STATUS_APPLIED    = 'applied';
    const REFUND_STATUS_PROCESSING = 'processing';
    const REFUND_STATUS_SUCCESS    = 'success';
    const REFUND_STATUS_FAILED     = 'failed';

    const SHIP_STATUS_PENDING   = 'pending';
    const SHIP_STATUS_DELIVERED = 'delivered';
    const SHIP_STATUS_RECEIVED  = 'received';

    const TYPE_NORMAL       = 'normal';
    const TYPE_CROWDFUNDING = 'crowdfunding';

    public static $typeMap = [
        self::TYPE_NORMAL       => '普通商品订单',
        self::TYPE_CROWDFUNDING => '众筹商品订单',
    ];

    public static $refundStatusMap = [
        self::REFUND_STATUS_PENDING    => '未退款',
        self::REFUND_STATUS_APPLIED    => '已申请退款',
        self::REFUND_STATUS_PROCESSING => '退款中',
        self::REFUND_STATUS_FAILED     => '退款失败',
        self::REFUND_STATUS_SUCCESS    => '退款成功',
    ];

    public static $shipStatusMap = [
        self::SHIP_STATUS_PENDING   => '未发货',
        self::SHIP_STATUS_DELIVERED => '已发货',
        self::SHIP_STATUS_RECEIVED  => '已收货',
    ];

    protected $fillable = [
        'no', 'address', 'total_amount', 'remark',
        'paid_at', 'payment_method', 'payment_no',
        'refund_status', 'refund_no', 'closed', 'reviewed',
        'ship_status', 'ship_data', 'extra','type',
    ];

    protected $casts = [
        'closed'    => 'boolean',
        'reviewed'  => 'boolean',
        'address'   => 'array',
        'ship_data' => 'array',
        'extra'     => 'array',
    ];

    protected $dates = ['paid_at'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->no) {
                $model->no = static::findAvailableNo();
                if (!$model->no) {
                    return false;
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function findAvailableNo()
    {
        $prefix = date('YmdHis');
        for ($i = 0; $i < 10; $i++) {
            $no = $prefix . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (!static::query()->where('no', $no)->exists()) {
                return $no;
            }
        }

        \Log::warning('find order no failed');

        return false;
    }

    // 生成唯一退款订单号
    public static function getAvailableRefundNo()
    {
        do {
            $no = Uuid::uuid4()->getHex();
        } while (self::query()->where('refund_no', $no)->exists());

        return $no;
    }

    public function couponCode()
    {
        return $this->belongsTo(CouponCode::class);
    }
}
