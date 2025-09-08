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
            $method = $request->getMethod();

            if (in_array($method, $methods)) {
                $actions = [
                    'POST' => 'Menambahkan',
                    'PUT' => 'Memperbarui',
                    'PATCH' => 'Memperbarui',
                    'DELETE' => 'Menghapus',
                ];

                $action = $actions[$method] ?? $method;

                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'description' => $action.' '.$request->path(),
                    'ip_address' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'method' => $method,
                ]);
            }
        }

        return $response;
    }
}
