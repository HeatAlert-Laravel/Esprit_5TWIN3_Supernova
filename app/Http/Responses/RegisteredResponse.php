<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse;

class RegisteredResponse implements RegisterResponse
{
    public function toResponse($request)
    {
        return $request->wantsJson() ? response()->json([], 201) : redirect()->route('verification.notice');
    }
}
