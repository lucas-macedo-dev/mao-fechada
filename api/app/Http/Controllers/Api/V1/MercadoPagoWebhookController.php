<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function handle(Request $request): JsonResponse
    {
        $topic = $request->input('topic') ?? $request->input('type');
        $id = $request->input('id') ?? $request->input('data.id');

        $this->activityLogger->log('billing.webhook_received', null, [
            'topic' => $topic,
            'id'    => $id,
        ]);

        try {
            // Webhook processing logic goes here

            $this->activityLogger->log('billing.webhook_processed', null, [
                'topic' => $topic,
                'id'    => $id,
            ]);
        } catch (\Throwable $e) {
            $this->activityLogger->log('billing.webhook_failed', null, [
                'exception_class' => get_class($e),
                'message'         => mb_substr($e->getMessage(), 0, 500),
            ]);

            throw $e;
        }

        return response()->json(['ok' => true]);
    }
}
