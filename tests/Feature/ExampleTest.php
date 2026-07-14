<?php

test('a guest is redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
