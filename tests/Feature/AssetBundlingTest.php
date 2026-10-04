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

it('constrains menu descriptions and hides the animation duplicate on desktop', function () {
    $menuItem = file_get_contents(resource_path('js/components/authenticated/MenuItem.vue'));

    expect($menuItem)->toContain('min-w-0 flex-1 md:w-full')
        ->toMatch('/\.description-marquee__duplicate\s*\{\s*display: none;/')
        ->toMatch('/@media \(max-width: 767px\)[\s\S]*\.description-marquee__duplicate\s*\{\s*display: inline;/');
});
