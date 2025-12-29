<?php

namespace Pyle\Webhooks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pyle\Webhooks\Models\WebhookEndpoint;
use Pyle\Webhooks\Models\WebhookEndpointEventSubscription;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Pyle\Webhooks\Models\WebhookEndpointEventSubscription>
 */
class WebhookEndpointEventSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = WebhookEndpointEventSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'event_key' => fake()->word() . '.' . fake()->word(),
        ];
    }
}
