<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // Tambahkan rute yang ingin dikecualikan dari verifikasi CSRF
        'login', // Contoh: menonaktifkan CSRF untuk semua rute API
    ];
}