<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\ProfileInformationUpdatedResponse;

class AccountInformationResponse implements ProfileInformationUpdatedResponse
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json([], 200);
        }

        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('account.edit')->with('status', 'Account information updated.')
            : redirect()->route('verification.notice');
    }
}
