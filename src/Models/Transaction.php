<?php

namespace Makhmudovazeez\Paymelaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $paycom_transaction_id
 * @property string $paycom_time
 * @property dateTime $paycom_time_datetime
 * @property dateTime $create_time
 * @property dateTime $perform_time
 * @property dateTime $cancel_time
 * @property int $amount
 * @property bool $status
 * @property string $reason
 * @property Order $order_id
 */

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    /** Transaction expiration time in milliseconds. 43 200 000 ms = 12 hours. */
    const TIMEOUT = 43200000;

    const STATE_CREATED                  = 1;
    const STATE_COMPLETED                = 2;
    const STATE_CANCELLED                = -1;
    const STATE_CANCELLED_AFTER_COMPLETE = -2;

    const REASON_RECEIVERS_NOT_FOUND         = 1;
    const REASON_PROCESSING_EXECUTION_FAILED = 2;
    const REASON_EXECUTION_FAILED            = 3;
    const REASON_CANCELLED_BY_TIMEOUT        = 4;
    const REASON_FUND_RETURNED               = 5;
    const REASON_UNKNOWN                     = 10;

}