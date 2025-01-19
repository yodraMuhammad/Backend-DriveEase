<?php

// app/Http/Middleware/CheckJWTToken.php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Cek apakah token ada di header Authorization
            $token = $request->header('Authorization');

            // Pastikan token dimulai dengan "Bearer"
            if (!$token || !preg_match('/^Bearer\s/', $token)) {
                return response()->json(['error' => 'Token not provided or invalid'], 401);
            }

            // Menghapus prefix "Bearer " dari token
            $token = str_replace('Bearer ', '', $token);

            // Verifikasi token
            JWTAuth::setToken($token); // Set token untuk pemeriksaan
            if (!JWTAuth::check()) {
                return response()->json(['error' => 'Token is invalid'], 401);
            }

            // Melanjutkan ke request berikutnya jika token valid
            return $next($request);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not decode token'], 500);
        }
    }
}
