<?php

it('does not load fonts from an external CDN', function () {
    $response = $this->get('/');

    $response->assertOk()->assertDontSee('fonts.bunny.net', false);

    expect(file_get_contents(resource_path('js/components/Logo.vue')))
        ->not->toContain('fonts.googleapis.com');
});

it('bundles icons used by application components', function () {
    expect(file_get_contents(base_path('vite.config.ts')))
        ->toContain('clientBundle')
        ->toContain('scan: true');
});

it('includes an accessible mobile animation for the relationship description', function () {
    $menuItem = file_get_contents(resource_path('js/components/authenticated/MenuItem.vue'));

    expect($menuItem)->toContain('relationship-description-marquee')
        ->toContain('prefers-reduced-motion: reduce');
});
