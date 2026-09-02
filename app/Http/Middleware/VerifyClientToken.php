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

        $tokenHash = hash('sha256', $token);

        $client = Client::query()
            ->where('active', true)
            ->where(function ($query) use ($token, $tokenHash): void {
                $query->where('api_token', $tokenHash)
                    ->orWhere('api_token', $token);
            })
            ->first();

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
