<?php

namespace App\Domains\Billing\Http\Controllers\Webhook;

use App\Domains\Billing\Gateways\AppyPay\AppyPayWebhookHandler;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /webhooks/appypay?token=… is AppyPay's status notification. Unknown
 * transactions still get a 200 so AppyPay stops retrying them.
 */
class AppyPayWebhookController extends Controller
{
    public function __invoke(Request $request, AppyPayWebhookHandler $handler): JsonResponse
    {
        if (! $handler->isValidToken($request->query('token'))) {
            return response()->json(['success' => false], 401);
        }

        $handler->handle($request->json()->all());

        return response()->json(['success' => true]);
    }
}
