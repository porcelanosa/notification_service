<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    const string CHANNEL_SMS        = 'sms';
    const string CHANNEL_EMAIL      = 'email';
    const string TYPE_TRANSACTIONAL = 'transactional';
    const string TYPE_BULK          = 'bulk';
    const string STATUS_QUEUED      = 'queued';
    const string STATUS_SENT        = 'sent';
    const string STATUS_DELIVERED   = 'delivered';
    const string STATUS_DROPPED     = 'dropped';

    protected $fillable = [
      'id',
      'idempotency_key',
      'subscriber_id',
      'channel',
      'type',
      'message',
      'recipient',
      'status',
      'failure_reason',
      'attempts',
    ];
}
