<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InstructorApplicationService
{
    public function submit(User $student, array $data): Application
    {
        abort_unless($student->isActive() && ! $student->canTeach(), 403);

        return DB::transaction(function () use ($student, $data): Application {
            $locked = User::query()->lockForUpdate()->findOrFail($student->getKey());
            abort_unless($locked->isActive() && ! $locked->canTeach(), 403);

            if (Application::query()->where('user_id', $locked->getKey())
                ->where('type', 'instructor')->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'application' => 'An instructor application is already awaiting review.',
                ]);
            }

            $application = Application::create([
                'user_id' => $locked->getKey(),
                'type' => 'instructor',
                'details' => $data,
                'status' => 'pending',
            ]);

            AuditEvent::create([
                'actor_id' => $locked->getKey(),
                'subject_type' => Application::class,
                'subject_id' => $application->getKey(),
                'action' => 'instructor_application.submitted',
                'new_values' => ['status' => 'pending'],
            ]);

            return $application;
        });
    }

    public function decide(User $administrator, Application $application, string $decision, ?string $note): void
    {
        abort_unless($administrator->isAdmin(), 403);
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);

        DB::transaction(function () use ($administrator, $application, $decision, $note): void {
            $application = Application::query()->lockForUpdate()->findOrFail($application->getKey());
            abort_unless($application->type === 'instructor', 404);

            if ($application->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This application has already been reviewed.']);
            }

            $applicant = User::query()->lockForUpdate()->findOrFail($application->user_id);
            if ($decision === 'approved') {
                if (! $applicant->isActive() || ! in_array($applicant->role, ['student', 'instructor'], true)) {
                    throw ValidationException::withMessages([
                        'decision' => 'Only an active student or existing instructor may be approved.',
                    ]);
                }

                $applicant->forceFill(['role' => 'instructor'])->save();
            }

            $details = (array) ($application->details ?? []);
            if ($note !== null && trim($note) !== '') {
                $details['review_note'] = trim($note);
            }
            $application->forceFill([
                'status' => $decision,
                'reviewed_by' => $administrator->getKey(),
                'reviewed_at' => now(),
                'details' => $details,
            ])->save();

            AuditEvent::create([
                'actor_id' => $administrator->getKey(),
                'subject_type' => Application::class,
                'subject_id' => $application->getKey(),
                'action' => 'instructor_application.reviewed',
                'previous_values' => ['status' => 'pending'],
                'new_values' => ['status' => $decision],
            ]);
        }, 3);
    }
}
