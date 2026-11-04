<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckIdempotency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey) {
            $idempotencyKeyRecord = IdempotencyKey::where('idempotency_key', $idempotencyKey)->first();

            if ($idempotencyKeyRecord) {
                $modelClass = app("\\App\\Models\\$idempotencyKeyRecord->model_class");

                $entity = $modelClass::select('id', 'idempotency_key')
                    ->where('idempotency_key', $idempotencyKey)->first();

                if ($entity) {
                    Log::info('The request is already successful | id: ' . $entity->id . ' | idempotency_key: ' . $entity->idempotency_key . ' | response_data: ' . print_r($idempotencyKeyRecord->response_data, true));

                    return response()->json(json_decode($idempotencyKeyRecord->response_data), 200);
                }

                Log::info('Idempotency Key column value does not exists | Idempotency Key: ' . $idempotencyKey);
            }
        } else {
            Log::info('Idempotency-Key Header does not Exists');

            return response()->json([
                'success' => false,
                'message' => 'Missing Idempotency-Key',
                'error' => 'The Idempotency-Key is missing in the Header!'
            ], 422);
        }

        return $next($request);
    }
}
