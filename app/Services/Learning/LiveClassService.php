<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class LiveClassService
{
    public function save(User $actor, Course $course, array $data, ?LiveClass $class = null): LiveClass
    {
        Gate::forUser($actor)->authorize('update', $course);

        return DB::transaction(function () use ($actor, $course, $data, $class): LiveClass {
            if ($class) {
                $class = LiveClass::query()->lockForUpdate()->findOrFail($class->getKey());
                abort_unless((int) $class->course_id === (int) $course->getKey(), 404);
            } else {
                $class = new LiveClass;
                $class->course_id = $course->getKey();
                $class->instructor_id = $course->instructor_id;
            }

            $oldStatus = $class->exists ? $class->status : null;
            $class->fill([
                'title' => $data['title'],
                'provider' => $data['provider'],
                'meeting_url' => $data['meeting_url'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'status' => $data['status'] ?? 'scheduled',
            ]);
            $class->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => LiveClass::class,
                'subject_id' => $class->getKey(),
                'action' => $oldStatus === null ? 'live_classes.created' : 'live_classes.updated',
                'previous_values' => $oldStatus === null ? null : ['status' => $oldStatus],
                'new_values' => ['status' => $class->status, 'title' => $class->title],
            ]);

            return $class;
        });
    }
}
