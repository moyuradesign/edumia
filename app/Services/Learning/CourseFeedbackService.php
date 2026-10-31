<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Facades\DB;

final class CourseFeedbackService
{
    public function saveWishlist(User $user, Course $course): void
    {
        abort_unless($user->isActive(), 403);
        abort_unless($course->status === 'published' && $course->published_at !== null && $course->visibility === 'public', 404);

        Wishlist::firstOrCreate(['user_id' => $user->getKey(), 'course_id' => $course->getKey()]);
    }

    public function removeWishlist(User $user, Course $course): void
    {
        abort_unless($user->isActive(), 403);

        Wishlist::query()->where('user_id', $user->getKey())->where('course_id', $course->getKey())->delete();
    }

    public function review(User $user, Course $course, array $data): Review
    {
        abort_unless($user->isActive(), 403);
        abort_unless($course->status === 'published' && $course->visibility === 'public'
            && $course->published_at !== null, 404);

        abort_unless(Enrollment::query()->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())->whereIn('status', ['active', 'completed'])->exists(), 403);

        return DB::transaction(function () use ($user, $course, $data): Review {
            return Review::query()->updateOrCreate(
                ['user_id' => $user->getKey(), 'course_id' => $course->getKey()],
                ['rating' => $data['rating'], 'title' => $data['title'] ?? null, 'body' => $data['body'], 'status' => 'pending']
            );
        });
    }

    public function moderate(User $actor, Review $review, string $status): void
    {
        abort_unless($actor->isAdmin(), 403);
        abort_unless(in_array($status, ['pending', 'approved', 'rejected'], true), 422);

        DB::transaction(function () use ($actor, $review, $status): void {
            $review = Review::query()->lockForUpdate()->findOrFail($review->getKey());
            if ($review->status === $status) {
                return;
            }

            $before = $review->status;
            $review->status = $status;
            $review->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Review::class,
                'subject_id' => $review->getKey(),
                'action' => 'reviews.moderated',
                'previous_values' => ['status' => $before],
                'new_values' => ['status' => $status],
            ]);
        });
    }
}
