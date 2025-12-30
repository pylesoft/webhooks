<?php

namespace Pyle\Webhooks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pyle\Webhooks\Models\WebhookEndpoint;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Pyle\Webhooks\Models\WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = WebhookEndpoint::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => 'https://' . fake()->domainName() . '/webhook',
            'description' => fake()->optional()->sentence(),
            'enabled' => true,
            'secret' => WebhookEndpoint::generateSecret(),
        ];
    }

    /**
     * Indicate that the endpoint is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
