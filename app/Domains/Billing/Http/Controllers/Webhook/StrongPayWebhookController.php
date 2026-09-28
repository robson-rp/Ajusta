<?php

namespace App\Domains\Billing\Http\Controllers\Webhook;

use App\Domains\Billing\Gateways\StrongPay\StrongPayWebhookHandler;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /webhooks/strongpay is StrongPay's payment.updated notification,
 * signed with the system's webhook secret. Unknown payments still get a 200
 * so StrongPay stops retrying them.
 */
class StrongPayWebhookController extends Controller
{
    public function __invoke(Request $request, StrongPayWebhookHandler $handler): JsonResponse
    {
        if (! $handler->isValidSignature($request->getContent(), $request->header('X-StrongPay-Signature'))) {
            return response()->json(['success' => false], 401);
        }

        $handler->handle($request->json()->all());

        return response()->json(['success' => true]);
    }
}
