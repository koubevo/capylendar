<?php

use App\Enums\Capybara;

describe('Capybara Enum', function () {
    it('has expected cases', function () {
        expect(Capybara::cases())->toHaveCount(3);
        expect(Capybara::cases())->toContain(Capybara::Pink);
        expect(Capybara::cases())->toContain(Capybara::Blue);
        expect(Capybara::cases())->toContain(Capybara::Yellow);
    });

    it('returns correct value for cases', function () {
        expect(Capybara::Pink->value)->toBe('pink');
        expect(Capybara::Blue->value)->toBe('blue');
        expect(Capybara::Yellow->value)->toBe('yellow');
    });

    it('can retrieve all options', function () {
        $options = Capybara::options();

        expect($options)->toHaveCount(3);
        expect($options->first())->toBeArray();
        expect($options->first())->toHaveKeys(['value', 'label', 'avatar', 'classes']);
    });

    it('returns correct info for Pink', function () {
        $info = Capybara::Pink->info();

        expect($info['value'])->toBe('pink');
        expect($info['avatar']['src'])->toContain('pink.jpg');
        expect($info['classes'])->toContain('bg-pink-100');
    });

    it('returns correct info for Blue', function () {
        $info = Capybara::Blue->info();

        expect($info['value'])->toBe('blue');
        expect($info['avatar']['src'])->toContain('blue.jpg');
        expect($info['classes'])->toContain('bg-blue-100');
    });

    it('returns correct info for Yellow', function () {
        $info = Capybara::Yellow->info();

        expect($info['value'])->toBe('yellow');
        expect($info['avatar']['src'])->toContain('yellow.jpg');
        expect($info['classes'])->toContain('bg-yellow-100');
    });

    it('helper methods functionality', function () {
        $capybara = Capybara::Pink;

        expect($capybara->getLabel())->not->toBeEmpty();
        expect($capybara->getAvatar())->toBeArray();
        expect($capybara->getClasses())->toContain('bg-pink-100');
    });
});
