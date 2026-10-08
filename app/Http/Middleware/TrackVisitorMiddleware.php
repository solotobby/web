<?php

namespace App\Http\Middleware;

use App\Models\VisitorLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET requests that return HTML, ignoring internal / admin / asset endpoints
        if ($request->isMethod('GET') && ! $request->ajax() && ! $request->is('livewire*', '_debugbar*', 'admin*', 'api*', 'up*')) {
            $path = '/' . ltrim($request->path(), '/');

            // Skip static file extensions
            if (! preg_match('/\.(ico|css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|webp|map)$/i', $path)) {
                try {
                    $userAgent = $request->userAgent() ?? '';
                    $ip = $request->ip() ?? '127.0.0.1';

                    // Determine Device
                    $deviceType = 'Desktop';
                    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
                        $deviceType = 'Tablet';
                    } elseif (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop)/i', $userAgent)) {
                        $deviceType = 'Mobile';
                    }

                    // Determine OS
                    $os = 'Other';
                    if (preg_match('/macintosh|mac os x/i', $userAgent)) {
                        $os = 'macOS';
                    } elseif (preg_match('/windows|win32/i', $userAgent)) {
                        $os = 'Windows';
                    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
                        $os = 'iOS';
                    } elseif (preg_match('/android/i', $userAgent)) {
                        $os = 'Android';
                    } elseif (preg_match('/linux/i', $userAgent)) {
                        $os = 'Linux';
                    }

                    // Determine Browser
                    $browser = 'Other';
                    if (preg_match('/edg/i', $userAgent)) {
                        $browser = 'Edge';
                    } elseif (preg_match('/chrome|crios/i', $userAgent) && ! preg_match('/edg/i', $userAgent)) {
                        $browser = 'Chrome';
                    } elseif (preg_match('/safari/i', $userAgent) && ! preg_match('/chrome|crios/i', $userAgent)) {
                        $browser = 'Safari';
                    } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
                        $browser = 'Firefox';
                    } elseif (preg_match('/opera|opr/i', $userAgent)) {
                        $browser = 'Opera';
                    }

                    // Cloudflare or custom geo headers fallback
                    $countryCode = strtoupper($request->header('cf-ipcountry') ?? $request->header('x-country-code') ?? 'US');
                    $countryNames = [
                        'US' => 'United States',
                        'GB' => 'United Kingdom',
                        'CA' => 'Canada',
                        'NG' => 'Nigeria',
                        'DE' => 'Germany',
                        'JP' => 'Japan',
                        'FR' => 'France',
                        'AU' => 'Australia',
                        'NL' => 'Netherlands',
                        'BR' => 'Brazil',
                    ];
                    $countryName = $countryNames[$countryCode] ?? ($countryCode === 'US' ? 'United States' : 'International');

                    VisitorLog::create([
                        'ip_address' => $ip,
                        'country_code' => $countryCode,
                        'country_name' => $countryName,
                        'city' => $request->header('cf-ipcity') ?? ($countryCode === 'US' ? 'San Francisco' : 'Local'),
                        'device_type' => $deviceType,
                        'browser' => $browser,
                        'os' => $os,
                        'path' => $path,
                        'referer' => $request->headers->get('referer') ? parse_url($request->headers->get('referer'), PHP_URL_HOST) : 'Direct',
                        'user_agent' => substr($userAgent, 0, 500),
                    ]);
                } catch (\Throwable $e) {
                    // Silently ignore tracking errors to avoid interfering with response
                }
            }
        }

        return $response;
    }
}
