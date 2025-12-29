<?php

/**
 * @var array{
 *     secret: string,
 *     events: array<string, array{event: class-string, group?: string, label?: string, description?: string, transformer?: class-string}>,
 *     webhook_server: array<string, mixed>
 * }
 */
return [
    /*
     * The secret key used for signing webhooks.
     * This is a fallback if per-endpoint secrets are not used.
     */
    'secret' => env('WEBHOOK_SECRET', ''),

    /*
     * Configured events that can be subscribed to.
     *
     * Each event should have:
     * - 'event': The event class to listen to
     * - 'group': Optional group/category for UI (e.g., 'Orders', 'Users')
     * - 'label': Optional display label (e.g., 'Order created')
     * - 'description': Optional description
     * - 'transformer': Optional transformer class implementing WebhookPayloadTransformer
     *
     * Example:
     * 'events' => [
     *     'orders.created' => [
     *         'event' => \App\Events\OrderCreated::class,
     *         'group' => 'Orders',
     *         'label' => 'Order created',
     *         'description' => 'Fired when a new order is created.',
     *         'transformer' => \App\Webhooks\Transformers\OrderCreatedTransformer::class,
     *     ],
     * ],
     */
    'events' => [],

    /*
     * UI Configuration
     */
    'ui' => [
        /*
         * Enable the webhooks UI routes.
         */
        'enabled' => env('WEBHOOKS_UI_ENABLED', false),

        /*
         * The path where the webhooks UI will be accessible.
         */
        'path' => env('WEBHOOKS_UI_PATH', '/webhooks'),

        /*
         * Middleware to apply to the webhooks UI routes.
         */
        'middleware' => ['web', 'auth'],
    ],

    /*
     * Override Spatie's webhook-server configuration.
     * Any keys here will be merged into the 'webhook-server' config.
     * See https://github.com/spatie/laravel-webhook-server for available options.
     */
    'webhook_server' => [
        // 'queue' => 'default',
        // 'http_verb' => 'post',
        // 'timeout_in_seconds' => 3,
        // 'tries' => 3,
        // 'verify_ssl' => true,
        // ... other webhook-server config options
    ],
];
