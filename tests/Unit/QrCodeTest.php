<?php

use App\Support\QrCode;

it('produces a scannable qr code matrix', function () {
    $matrix = QrCode::matrix('KW-20261022-0001');

    // Versi 2 (25x25) untuk 16 byte.
    expect(count($matrix))->toBe(25);
    expect(count($matrix[0]))->toBe(25);

    // Finder pattern di tiga sudut.
    expect($matrix[0][0])->toBeTrue()
        ->and($matrix[0][6])->toBeTrue()
        ->and($matrix[6][0])->toBeTrue()
        ->and($matrix[6][6])->toBeTrue()
        ->and($matrix[1][1])->toBeFalse()
        ->and($matrix[3][3])->toBeTrue();

    // Modul gelap permanen di kiri bawah area format.
    expect($matrix[count($matrix) - 8][8])->toBeTrue();
});

it('grows the matrix as the payload grows', function () {
    expect(count(QrCode::matrix('A')))->toBe(21)
        ->and(count(QrCode::matrix(str_repeat('X', 100))))->toBe(41);
});

it('renders an svg with a viewbox and a single path', function () {
    $svg = QrCode::svg('KW-20261022-0001');

    expect($svg)->toStartWith('<svg')
        ->and($svg)->toContain('viewBox="0 0 ')
        ->and($svg)->toContain('<path d="M');
});

it('produces a stable matrix for the same input', function () {
    expect(QrCode::matrix('PKW-20261022-0001'))->toBe(QrCode::matrix('PKW-20261022-0001'));
});
