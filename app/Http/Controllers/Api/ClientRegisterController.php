<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientRegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Se já existir com esse slug, retornar erro amigável
        if (Client::where('slug', $slug)->exists()) {
            return response()->json([
                'message' => 'Já existe um cliente cadastrado com este nome/slug.',
                'error' => 'client_already_exists',
            ], 422);
        }

        $apiToken = Client::generateToken();

        $client = Client::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'api_token' => $apiToken,
            'active' => true,
        ]);

        return response()->json([
            'message' => 'Cliente cadastrado com sucesso! Guarde este token de API com segurança, ele é necessário para enviar pings e logs de monitoramento.',
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'slug' => $client->slug,
            ],
            'api_token' => $apiToken,
        ], 201);
    }
}
