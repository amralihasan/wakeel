<?php

it('has a welcome page that returns a successful status code', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
