<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Requests\Student\ForumReplyRequest;
use App\Http\Requests\Student\ForumTopicRequest;
use App\Models\Course;
use App\Models\Forum;
use App\Services\Learning\ForumDiscussionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CourseForumController extends Controller
{
    public function index(Request $request, Course $course, ForumDiscussionService $service): View
    {
        $service->assertCanRead($request->user(), $course);

        return view('student.forums.index', [
            'course' => $course,
            'discussions' => Forum::query()->where('course_id', $course->getKey())
                ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('status', '!=', 'hidden'))
                ->with('author')->withCount('replies')->latest()->paginate(20),
        ]);
    }

    public function store(
        ForumTopicRequest $request, Course $course, ForumDiscussionService $service
    ): RedirectResponse {
        $forum = $service->create($request->user(), $course, $request->validated());

        return to_route('learning.forums.show', [$course, $forum])->with('success', 'Discussion created.');
    }

    public function show(Request $request, Course $course, Forum $forum, ForumDiscussionService $service): View
    {
        $service->assertCanReadTopic($request->user(), $course, $forum);

        return view('student.forums.show', [
            'course' => $course,
            'forum' => $forum->load('author'),
            'replies' => $forum->replies()
                ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('status', 'visible'))
                ->with('author')->paginate(25),
        ]);
    }

    public function reply(
        ForumReplyRequest $request, Course $course, Forum $forum,
        ForumDiscussionService $service
    ): RedirectResponse {
        $service->reply($request->user(), $course, $forum, $request->validated('body'));

        return to_route('learning.forums.show', [$course, $forum])->with('success', 'Reply posted.');
    }
}
