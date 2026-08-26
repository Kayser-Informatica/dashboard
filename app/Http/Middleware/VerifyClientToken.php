<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyClientToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) ($request->bearerToken() ?: $request->header('X-Client-Token'));

        if ($token === '') {
            return response()->json([
                'message' => 'Token de cliente não fornecido. Envie como Bearer token ou no header X-Client-Token.',
                'error' => 'token_missing',
            ], 401);
        }

        $client = Client::where('api_token', $token)->where('active', true)->first();

        if (! $client) {
            return response()->json([
                'message' => 'Token de cliente inválido ou cliente inativo.',
                'error' => 'unauthorized',
            ], 401);
        }

        $request->attributes->set('client', $client);

        return $next($request);
    }
}
