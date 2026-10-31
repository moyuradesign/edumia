<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Support;

use App\Models\Forum;
use App\Models\ForumReply;
use App\Services\Learning\ForumDiscussionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ForumModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.support.forums.index', [
            'discussions' => Forum::query()->with(['course', 'author'])->latest()->paginate(25),
        ]);
    }

    public function topic(Request $request, Forum $forum, ForumDiscussionService $service): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['open', 'closed', 'hidden'])]]);
        $service->moderateTopic($request->user(), $forum, $data['status']);

        return back()->with('success', 'Discussion moderation updated.');
    }

    public function reply(Request $request, ForumReply $reply, ForumDiscussionService $service): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['visible', 'hidden'])]]);
        $service->moderateReply($request->user(), $reply, $data['status']);

        return back()->with('success', 'Reply moderation updated.');
    }
}
