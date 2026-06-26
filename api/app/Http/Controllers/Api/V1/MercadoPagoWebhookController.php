<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        Log::channel('stack')->info('MercadoPago webhook received', $request->all());

        return response()->json(['ok' => true]);
    }
}
