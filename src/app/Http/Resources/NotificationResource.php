<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
          'id' => $this->id,
          'subscriber_id' => $this->subscriber_id,
          'channel' => $this->channel,
          'type' => $this->type,
          'recipient' => $this->recipient,
          'message' => $this->message,
          'status' => $this->status,
          'created_at' => $this->created_at,
        ];
    }
}