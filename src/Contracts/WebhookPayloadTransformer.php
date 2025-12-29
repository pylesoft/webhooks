<?php

namespace Pyle\Webhooks\Contracts;

interface WebhookPayloadTransformer
{
    /**
     * Transform the event into a webhook payload array.
     */
    public function transform(object $event): array;
}
