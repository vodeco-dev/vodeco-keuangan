<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (auth()->check()) {
            $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];
            if (in_array($request->getMethod(), $methods)) {
                ActivityLog::create([
                    'user_id'    => auth()->id(),
                    'description'=> $request->getMethod().' '.$request->path(),
                    'ip_address' => $request->ip(),
                    'url'        => $request->fullUrl(),
                    'method'     => $request->getMethod(),
                ]);
            }
        }

        return $response;
    }
}

