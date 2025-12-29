<?php

namespace Pyle\Webhooks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Pyle\Webhooks\Database\Factories\WebhookEndpointFactory;

class WebhookEndpoint extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return WebhookEndpointFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webhook_endpoints';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'description',
        'enabled',
        'secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'secret' => 'encrypted',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (WebhookEndpoint $endpoint) {
            if (empty($endpoint->secret)) {
                $endpoint->secret = static::generateSecret();
            }
        });
    }

    /**
     * Generate a new webhook secret.
     */
    public static function generateSecret(): string
    {
        return 'whsec_' . Str::random(32);
    }

    /**
     * Get the event subscriptions for this endpoint.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(WebhookEndpointEventSubscription::class);
    }

    /**
     * Check if the endpoint is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Rotate the secret to a new value.
     */
    public function rotateSecret(): void
    {
        $this->secret = static::generateSecret();
        $this->save();
    }

    /**
     * Get subscribed event keys.
     *
     * @return array<string>
     */
    public function getSubscribedEventKeys(): array
    {
        return $this->subscriptions()->pluck('event_key')->toArray();
    }
}
