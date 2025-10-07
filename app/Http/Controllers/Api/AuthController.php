<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle a login request to the application.
     *
     * @param  \App\Http\Requests\Api\LoginRequest  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(LoginRequest $request)
    {
        // Busca o cliente pelo email
        $customer = Customer::where('email', $request->email)->first();

        // Verifica se o cliente existe e se a senha está correta
        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Revoga tokens antigos e cria um novo token
        $customer->tokens()->delete();
        $token = $customer->createToken('auth-token')->plainTextToken;

        // Retorna o cliente e o token
        return response()->json([
            'message' => 'Autenticação realizada com sucesso.',
            'customer' => $customer,
            'token' => $token,
        ]);
    }
}
