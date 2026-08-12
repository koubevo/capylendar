<?php

it('does not load fonts from an external CDN', function () {
    $response = $this->get('/');

    $response->assertOk()->assertDontSee('fonts.bunny.net', false);
});
