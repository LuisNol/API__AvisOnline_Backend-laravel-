<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Product\Product;

class CheckProductLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Admin users have no limit
        if ($user->hasRole('Admin')) {
            return $next($request);
        }

        $count = Product::where('user_id', $user->id)->count();
        if ($count >= 3) {
            return response()->json([
                'message' => 'Solo puedes crear hasta 3 productos'
            ], 403);
        }

        return $next($request);
    }
}
