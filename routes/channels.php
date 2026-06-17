<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('company.{companyId}.handoffs', function ($user, $companyId) {
    return (int) $user->company_id === (int) $companyId;
});

Broadcast::channel('company.{companyId}.conversations', function ($user, $companyId) {
    return (int) $user->company_id === (int) $companyId;
});
