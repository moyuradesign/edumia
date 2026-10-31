<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Forum;
use App\Models\ForumReply;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ForumDiscussionService
{
    public function assertCanRead(User $actor, Course $course): void
    {
        abort_unless($actor->isActive(), 403);

        if ($actor->isAdmin() || (int) $course->instructor_id === (int) $actor->getKey()) {
            return;
        }

        abort_unless($course->status === 'published' && $course->visibility === 'public'
            && $course->published_at !== null, 404);

        abort_unless(Enrollment::query()->where('course_id', $course->getKey())
            ->where('user_id', $actor->getKey())
            ->whereIn('status', ['active', 'completed'])->exists(), 404);
    }

    public function assertCanReadTopic(User $actor, Course $course, Forum $forum): void
    {
        $this->assertCanRead($actor, $course);
        abort_unless((int) $forum->course_id === (int) $course->getKey(), 404);
        abort_if($forum->status === 'hidden' && ! $actor->isAdmin(), 404);
    }

    public function create(User $actor, Course $course, array $data): Forum
    {
        $this->assertCanRead($actor, $course);

        return DB::transaction(function () use ($actor, $course, $data): Forum {
            return Forum::create([
                'course_id' => $course->getKey(),
                'user_id' => $actor->getKey(),
                'title' => $data['title'],
                'body' => $data['body'],
                'status' => 'open',
            ]);
        });
    }

    public function reply(User $actor, Course $course, Forum $forum, string $body): ForumReply
    {
        $this->assertCanReadTopic($actor, $course, $forum);

        return DB::transaction(function () use ($actor, $course, $forum, $body): ForumReply {
            $forum = Forum::query()->lockForUpdate()->findOrFail($forum->getKey());
            $this->assertCanReadTopic($actor, $course, $forum);
            abort_unless($forum->status === 'open', 422, 'The discussion is closed.');

            return $forum->replies()->create([
                'user_id' => $actor->getKey(),
                'body' => $body,
                'status' => 'visible',
            ]);
        });
    }

    public function moderateTopic(User $admin, Forum $forum, string $status): void
    {
        abort_unless($admin->isAdmin(), 403);
        abort_unless(in_array($status, ['open', 'closed', 'hidden'], true), 422);

        DB::transaction(function () use ($admin, $forum, $status): void {
            $forum = Forum::query()->lockForUpdate()->findOrFail($forum->getKey());
            $prior = $forum->status;
            $forum->status = $status;
            $forum->save();
            AuditEvent::create([
                'actor_id' => $admin->getKey(),
                'subject_type' => Forum::class,
                'subject_id' => $forum->getKey(),
                'action' => 'forums.moderated',
                'previous_values' => ['status' => $prior],
                'new_values' => ['status' => $status],
            ]);
        });
    }

    public function moderateReply(User $admin, ForumReply $reply, string $status): void
    {
        abort_unless($admin->isAdmin(), 403);
        abort_unless(in_array($status, ['visible', 'hidden'], true), 422);

        DB::transaction(function () use ($admin, $reply, $status): void {
            $reply = ForumReply::query()->lockForUpdate()->findOrFail($reply->getKey());
            $prior = $reply->status;
            $reply->status = $status;
            $reply->save();
            AuditEvent::create([
                'actor_id' => $admin->getKey(),
                'subject_type' => ForumReply::class,
                'subject_id' => $reply->getKey(),
                'action' => 'forum_replies.moderated',
                'previous_values' => ['status' => $prior],
                'new_values' => ['status' => $status],
            ]);
        });
    }
}
