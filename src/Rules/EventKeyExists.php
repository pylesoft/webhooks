<?php

namespace Pyle\Webhooks\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Pyle\Webhooks\EventCatalog;

class EventKeyExists implements ValidationRule
{
    public function __construct(
        protected EventCatalog $catalog
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (!$this->catalog->has($value)) {
            $fail("The event key '{$value}' is not configured in the webhooks catalog.");
        }
    }
}

