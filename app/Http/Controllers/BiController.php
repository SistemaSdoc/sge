<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BiController extends Controller
{
    /**
     * Proxy a consulta do BI para o servidor externo para evitar problemas de CORS.
     */
    public function consult(Request $request, $bi)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->timeout(10)->get('https://bi.sdoca.it.ao/consultar/'.rawurlencode($bi));

            $body = $response->body();
            $status = $response->status();
            $contentType = $response->header('Content-Type', 'application/json');

            return response($body, $status)->header('Content-Type', $contentType);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao consultar serviço externo do BI.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
