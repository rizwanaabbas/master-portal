<?php

namespace App\Http\Middleware;

use App\Modules\Security\Services\SecurityConfigService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeClientErrorResponses
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!SecurityConfigService::hideExceptionDetails()) {
            return $response;
        }

        if (!$response instanceof JsonResponse) {
            $contentType = $response->headers->get('Content-Type', '');
            if (stripos($contentType, 'json') === false) {
                return $response;
            }
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return $response;
        }

        $decoded = $this->sanitize($decoded);
        $response->setContent(json_encode($decoded));

        return $response;
    }

    private function sanitize($value)
    {
        if (is_string($value)) {
            return SecurityConfigService::sanitizePublicText($value);
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (in_array($key, ['error', 'message', 'exception', 'trace', 'file'], true) || is_array($item)) {
                $value[$key] = $this->sanitize($item);
            }
        }

        return $value;
    }
}
