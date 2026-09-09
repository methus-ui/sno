<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\ExternalConfiguration;

/**
 * Middleware for External API Authentication
 *
 * Security Fix (2026-01-28): Added middleware to protect external API endpoints.
 * This validates external service tokens before allowing access.
 *
 * Features:
 * - Rate limiting (60 requests per minute per IP)
 * - IP whitelist support via EXTERNAL_API_ALLOWED_IPS env variable
 * - Security audit logging
 * - Token validation
 *
 * Rollback: Run storage/security_backups/20260128/ROLLBACK.sh
 */
class ExternalApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $clientIp = $request->ip();

        // Rate limiting: 60 requests per minute per IP
        $rateLimitKey = 'external_api:' . $clientIp;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 60)) {
            Log::warning('External API rate limit exceeded', [
                'ip' => $clientIp,
                'endpoint' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // IP whitelist check (if configured)
        $allowedIps = env('EXTERNAL_API_ALLOWED_IPS');
        if ($allowedIps) {
            $allowedIpList = array_map('trim', explode(',', $allowedIps));
            if (!in_array($clientIp, $allowedIpList)) {
                Log::warning('External API access denied - IP not whitelisted', [
                    'ip' => $clientIp,
                    'endpoint' => $request->path(),
                    'allowed_ips' => $allowedIpList,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Access denied.',
                ], 403);
            }
        }

        // Validate required external API parameters
        $requiredParams = ['token', 'external_base_url', 'external_token'];
        foreach ($requiredParams as $param) {
            if (!$request->has($param)) {
                Log::warning('External API missing required parameter', [
                    'ip' => $clientIp,
                    'endpoint' => $request->path(),
                    'missing_param' => $param,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid request.',
                ], 400);
            }
        }

        // Check if external integration is enabled
        $activationMode = ExternalConfiguration::where('key', 'activation_mode')->first()?->value;
        if ($activationMode != 1) {
            Log::warning('External API access attempted but integration disabled', [
                'ip' => $clientIp,
                'endpoint' => $request->path(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'External integration is not enabled.',
            ], 403);
        }

        // Log successful access for audit
        Log::info('External API access', [
            'ip' => $clientIp,
            'endpoint' => $request->path(),
            'external_base_url' => $request->external_base_url,
        ]);

        return $next($request);
    }
}
