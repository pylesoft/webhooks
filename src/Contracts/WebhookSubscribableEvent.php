<?php

namespace Pyle\Webhooks\Contracts;

interface WebhookSubscribableEvent
{
    /**
     * Get the event key used for webhook subscriptions.
     */
    public static function webhookEventKey(): string;

    /**
     * Get the event group/category for UI display.
     */
    public static function webhookEventGroup(): string;

    /**
     * Get the event label for UI display.
     */
    public static function webhookEventLabel(): string;

    /**
     * Get the event description for UI display.
     */
    public static function webhookEventDescription(): ?string;

    /**
     * Build the webhook payload from this event instance.
     */
    public function webhookPayload(): array;
}
