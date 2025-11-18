<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Check if the request is an API request
     */
    protected function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson() || $request->wantsJson();
    }

    /**
     * Return JSON response for API requests, or redirect for web requests
     */
    protected function apiResponse($data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($data, $status, $headers);
    }

    /**
     * Return success response for API requests
     */
    protected function apiSuccess($data = null, string $message = null, int $status = 200): JsonResponse
    {
        $response = [];
        if ($message) {
            $response['message'] = $message;
        }
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->apiResponse($response, $status);
    }

    /**
     * Return error response for API requests
     */
    protected function apiError(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = ['message' => $message];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        return $this->apiResponse($response, $status);
    }

    /**
     * Handle response for both API and web requests
     */
    protected function handleResponse(Request $request, $data, string $successMessage = null, string $redirectRoute = null): JsonResponse|RedirectResponse
    {
        if ($this->isApiRequest($request)) {
            return $this->apiSuccess($data, $successMessage);
        }

        if ($redirectRoute) {
            return redirect()->route($redirectRoute)->with('success', $successMessage);
        }

        return back()->with('success', $successMessage);
    }
}
