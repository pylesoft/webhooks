<?php

use Illuminate\Support\Facades\Route;
use Pyle\Webhooks\Livewire\WebhooksPage;

Route::middleware(config('webhooks.ui.middleware', ['web', 'auth']))->group(function () {
    Route::get(config('webhooks.ui.path', '/webhooks'), WebhooksPage::class)->name('webhooks.index');
});

