<?php

namespace App\Http\Controllers;

use App\Models\Conseil;
use App\Support\AdviceNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdviceBookmarkController extends Controller
{
    public function store(Request $request, Conseil $conseil): RedirectResponse|JsonResponse
    {
        abort_unless($conseil->actif, 404);
        $this->validateReturn($request);
        DB::table('conseil_user')->upsert([
            'user_id' => $request->user()->id, 'conseil_id' => $conseil->id,
            'created_at' => now(), 'updated_at' => now(),
        ], ['user_id', 'conseil_id'], ['updated_at']);

        $message = __('Article saved. Find it in My saved advice.');

        return $request->expectsJson() ? response()->json(['saved' => true, 'message' => $message])
            : $this->returnAfterAction($request, $conseil)->with('status', $message);
    }

    public function destroy(Request $request, Conseil $conseil): RedirectResponse|JsonResponse
    {
        $this->validateReturn($request);
        $request->user()->savedConseils()->detach($conseil->id);

        $message = __('Article removed from your saved advice.');

        return $request->expectsJson() ? response()->json(['saved' => false, 'message' => $message])
            : $this->returnAfterAction($request, $conseil)->with('status', $message);
    }

    private function validateReturn(Request $request): void
    {
        $request->validate(['return' => ['nullable', 'string', 'max:2048'], 'article' => ['nullable', 'boolean']]);
    }

    private function returnAfterAction(Request $request, Conseil $conseil): RedirectResponse
    {
        $back = AdviceNavigation::returnUrl($request->input('return'));
        if ($request->boolean('article') && $conseil->actif) {
            return redirect()->route('advice.show', [
                'conseil' => $conseil, 'return' => AdviceNavigation::relative($back),
            ]);
        }

        return redirect()->to($back);
    }
}
