<?php

namespace App\Http\Controllers;

use App\Models\Conseil;
use App\Models\ConseilVote;
use App\Support\AdviceNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdviceVoteController extends Controller
{
    public function update(Request $request, Conseil $conseil): JsonResponse|RedirectResponse
    {
        abort_unless($conseil->actif, 404);
        $data = $request->validate([
            'value' => ['required', 'integer', Rule::in([-1, 0, 1])],
            'return' => ['nullable', 'string', 'max:2048'],
        ]);
        $value = (int) $data['value'];
        $result = DB::transaction(function () use ($request, $conseil, $value) {
            $article = Conseil::whereKey($conseil->id)->lockForUpdate()->firstOrFail();
            abort_unless($article->actif, 404);
            if ($value === 0) {
                $article->votes()->where('user_id', $request->user()->id)->delete();
            } else {
                ConseilVote::upsert([[
                    'user_id' => $request->user()->id, 'conseil_id' => $article->id, 'value' => $value,
                    'created_at' => now(), 'updated_at' => now(),
                ]], ['user_id', 'conseil_id'], ['value', 'updated_at']);
            }
            $article->loadVoteCounts();

            return [
                'vote' => $value, 'score' => $article->voteScore(),
                'upvotes' => $article->upvotes_count, 'downvotes' => $article->downvotes_count,
                'message' => $value === 0 ? __('Vote removed.') : __('Thanks for your feedback.'),
            ];
        });
        if ($request->expectsJson()) {
            return response()->json($result)->header('Cache-Control', 'no-store');
        }
        $return = AdviceNavigation::relative(AdviceNavigation::returnUrl($data['return'] ?? null));

        return redirect()->to(route('advice.show', ['conseil' => $conseil, 'return' => $return]).'#article-feedback')
            ->with('status', $result['message']);
    }
}
