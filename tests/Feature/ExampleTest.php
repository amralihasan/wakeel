<?php

test('returns a successful response', function () {
    $response = $this->get(route('marketing.home'));

    $response->assertOk();
});
