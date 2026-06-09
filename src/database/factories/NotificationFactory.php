<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
          'id'              => Str::uuid(),
          'idempotency_key' => $this->faker->uuid(),
          'subscriber_id'   => $this->faker->uuid(),
          'channel'         => $this->faker->randomElement(['sms', 'email']),
          'type'            => 'bulk',
          'message'         => $this->faker->sentence(),
          'recipient'       => $this->faker->email(),
          'status'          => 'queued',
        ];
    }
}