<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_request_id',
        'from_user_id',
        'to_user_id',
        'offered_item_id',
        'cash_adjustment',
        'message',
        'status',
    ];

    public function exchangeRequest()
    {
        return $this->belongsTo(ExchangeRequest::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function offeredItem()
    {
        return $this->belongsTo(Item::class, 'offered_item_id');
    }
}
