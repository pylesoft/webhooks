<?php

namespace Pyle\Webhooks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pyle\Webhooks\Database\Factories\WebhookEndpointEventSubscriptionFactory;

class WebhookEndpointEventSubscription extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return WebhookEndpointEventSubscriptionFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webhook_endpoint_event_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'webhook_endpoint_id',
        'event_key',
    ];

    /**
     * Get the webhook endpoint that owns this subscription.
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
