<?php

use App\Models\User;
use Database\Seeders\RbacSeeder;

it('highlights only the active master data entry', function () {
    $this->seed(RbacSeeder::class);
    $admin = User::query()->where('email', 'admin@sekolah.test')->firstOrFail();

    $active = function (string $html, string $resource): bool {
        return (bool) preg_match(
            '/href="'.preg_quote(route('master.index', ['resource' => $resource]), '/').'"[^>]*aria-current="page"/',
            $html,
        );
    };

    $books = $this->actingAs($admin)->get('/master/books')->content();
    expect($active($books, 'books'))->toBeTrue()
        ->and($active($books, 'publishers'))->toBeFalse()
        ->and($active($books, 'schools'))->toBeFalse()
        ->and($active($books, 'settings'))->toBeFalse();

    // Create/edit pages keep the resource's entry highlighted too.
    $create = $this->actingAs($admin)->get('/master/categories/create')->content();
    expect($active($create, 'categories'))->toBeTrue()
        ->and($active($create, 'books'))->toBeFalse();
});
