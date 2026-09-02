<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ClientTokenRecoveryMail;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientTokenRecoveryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
            'client' => ['required', 'string', 'max:140'],
        ], [
            'email.required' => 'O e-mail cadastrado (email) é obrigatório.',
            'email.email' => 'O e-mail informado possui um formato inválido.',
            'client.required' => 'O nome ou slug do cliente (client) é obrigatório.',
            'client.string' => 'O identificador do cliente deve ser um texto.',
        ]);

        $clientParam = trim($validated['client']);
        $emailParam = trim($validated['email']);

        $client = Client::query()
            ->where('email', $emailParam)
            ->where(function ($query) use ($clientParam): void {
                $query->where('slug', Str::slug($clientParam))
                    ->orWhere('slug', $clientParam)
                    ->orWhere('name', $clientParam);
            })
            ->first();

        if ($client) {
            $newPlainToken = Client::generateToken();
            $client->api_token = Client::hashToken($newPlainToken);
            $client->save();

            Mail::to($client->email)->send(new ClientTokenRecoveryMail($client, $newPlainToken));
        }

        return response()->json([
            'message' => 'Se os dados informados estiverem corretos, um e-mail com as credenciais e o token de API foi enviado para o endereço cadastrado.',
        ]);
    }
}
