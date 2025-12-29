# Laravel Webhooks

**Turn your Laravel Events into webhooks. Zero code changes.**

Register your events in config and they're automatically webhook-enabled. Beautiful UI, secure signatures, queued delivery.

## Why Use This Package?

-   **Use your existing Laravel Events** - Just like Broadcasting, register your current events in the config and they automatically become webhook-enabled. No need to modify or create new event classes
-   **Sync data to third-party services** when orders are created, payments are processed, or users register
-   **Notify partners and integrations** in real-time without polling
-   **Build event-driven architectures** where your Laravel app triggers actions in other systems
-   **Manage webhooks visually** through a built-in admin interface

## Installation

```bash
composer require pylesoft/webhooks
php artisan vendor:publish --tag=webhooks.config
php artisan migrate
```

Enable the UI in `.env`:

```env
WEBHOOKS_UI_ENABLED=true
```

## Basic Usage

**1. Register your event** in `config/webhooks.php`:

```php
'events' => [
    'orders.created' => [
        'event' => \App\Events\OrderCreated::class,
        'group' => 'Orders',
        'label' => 'Order created',
    ],
],
```

**2. Create an endpoint** at `/webhooks` in the UI.

**3. Fire your event** — webhooks dispatch automatically:

```php
event(new OrderCreated($order));
```

Done. The package listens for configured events, finds subscribed endpoints, and queues signed POST requests via [Spatie's webhook server](https://github.com/spatie/laravel-webhook-server).

## Payload Format

```json
{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "event_key": "orders.created",
    "occurred_at": "2024-01-15T10:30:00+00:00",
    "data": { "order_id": 123, "amount": 99.99 },
    "meta": { "app_name": "My App", "environment": "production" }
}
```

Payload `data` is extracted in this order:

1. **`webhookPayload()` method** on your event
2. **Transformer class** if configured
3. **Public properties** of the event (auto-serialized)

### Customizing Payload

Add a `webhookPayload()` method to your event:

```php
class OrderCreated
{
    public function __construct(public Order $order) {}

    public function webhookPayload(): array
    {
        return [
            'order_id' => $this->order->id,
            'customer' => $this->order->customer->only(['id', 'email']),
            'total' => $this->order->total,
        ];
    }
}
```

Or use a transformer for complex logic:

```bash
php artisan make:webhooks-transformer OrderTransformer --event=\App\Events\OrderCreated
```

```php
// config/webhooks.php
'orders.created' => [
    'event' => \App\Events\OrderCreated::class,
    'transformer' => \App\Webhooks\Transformers\OrderTransformer::class,
],
```

## Eloquent Model Events

This package listens for class-based events, not string-based Eloquent events like `eloquent.created`. Bridge them with `$dispatchesEvents`:

```php
// app/Models/Order.php
protected $dispatchesEvents = [
    'created' => \App\Events\OrderCreated::class,
];
```

```php
// app/Events/OrderCreated.php
class OrderCreated
{
    public function __construct(public Order $order) {}
}
```

Now `Order::create()` fires `OrderCreated`, which triggers webhooks.

For conditional logic, use an observer instead:

```php
class OrderObserver
{
    public function created(Order $order): void
    {
        if ($order->total >= 1000) {
            event(new HighValueOrderCreated($order));
        }
    }
}
```

## Configuration

### UI

```php
'ui' => [
    'enabled' => env('WEBHOOKS_UI_ENABLED', false),
    'path' => env('WEBHOOKS_UI_PATH', '/webhooks'),
    'middleware' => ['web', 'auth'],
],
```

### Spatie Webhook Server

Override delivery settings:

```php
'webhook_server' => [
    'queue' => 'webhooks',         // dedicated queue
    'tries' => 5,                  // retry attempts
    'timeout_in_seconds' => 10,
    'verify_ssl' => true,
],
```

See [Spatie's docs](https://github.com/spatie/laravel-webhook-server) for all options.

### Event Catalog Options

```php
'events' => [
    'orders.created' => [
        'event' => \App\Events\OrderCreated::class,  // required
        'group' => 'Orders',                          // UI grouping
        'label' => 'Order created',                   // display name
        'description' => 'When an order is placed',   // tooltip
        'transformer' => OrderTransformer::class,     // custom payload
    ],
],
```

## Manual Dispatch

Dispatch without an event object:

```php
use Pyle\Webhooks\Facades\Webhooks;

Webhooks::dispatch('orders.created', [
    'order_id' => 123,
    'amount' => 99.99,
]);
```

## Signature Verification

Webhooks are signed with HMAC SHA256. Each endpoint has its own secret (viewable in the UI).

Verify on the receiving end:

```php
function verifySignature(Request $request, string $secret): bool
{
    $expected = hash_hmac('sha256', $request->getContent(), $secret);
    return hash_equals($expected, $request->header('Signature'));
}
```

## WebhookSubscribableEvent Contract

For self-documenting events, implement the contract:

```php
use Pyle\Webhooks\Contracts\WebhookSubscribableEvent;

class OrderCreated implements WebhookSubscribableEvent
{
    public static function webhookEventKey(): string { return 'orders.created'; }
    public static function webhookEventGroup(): string { return 'Orders'; }
    public static function webhookEventLabel(): string { return 'Order created'; }
    public static function webhookEventDescription(): ?string { return null; }

    public function webhookPayload(): array
    {
        return ['order_id' => $this->order->id];
    }
}
```

Config becomes minimal:

```php
'orders.created' => ['event' => \App\Events\OrderCreated::class],
```

## FAQ

### Performance impact?

None. Webhooks are queued — your request returns immediately.

### Including relationships in payload?

Eager load before dispatch, or use `webhookPayload()`:

```php
public function webhookPayload(): array
{
    return $this->order->load('customer', 'items')->toArray();
}
```

### Retry behavior?

3 attempts with exponential backoff (configurable via `tries`). Listen to `WebhookCallFailedEvent` for monitoring.

### Testing locally without HTTPS?

Use [ngrok](https://ngrok.com), [webhook.site](https://webhook.site), or set `verify_ssl` to `false` in dev.

### Testing in feature tests?

```php
it('dispatches webhook', function () {
    Queue::fake();
    event(new OrderCreated($order));
    Queue::assertPushed(\Spatie\WebhookServer\CallWebhookJob::class);
});
```

### Pausing webhooks?

Toggle "Enabled" in the UI, or: `WebhookEndpoint::query()->update(['enabled' => false])`

## Troubleshooting

| Problem                | Check                                                     |
| ---------------------- | --------------------------------------------------------- |
| Webhooks not delivered | Queue worker running? Endpoint enabled? Event subscribed? |
| HTTPS errors locally   | Use a tunnel or disable `verify_ssl` in dev               |
| Signature mismatch     | Using raw body? Correct secret? Header is `Signature`?    |
| Config not updating    | Run `php artisan config:clear`                            |

## Requirements

-   PHP 8.3+, Laravel 12, Livewire 3.4+, Flux UI
-   Queue worker for production

## Testing

```bash
composer test
```

## License

MIT. See [license.md](license.md).
