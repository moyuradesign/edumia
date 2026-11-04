<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TeamPackageMember;
use App\Models\TeamPackagePurchase;
use App\Models\TeamTrainingPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TeamTrainingService
{
    public function activate(User $owner, TeamTrainingPackage $package, string $organization): TeamPackagePurchase
    {
        abort_unless($owner->isActive(), 403);

        return DB::transaction(function () use ($owner, $package, $organization): TeamPackagePurchase {
            $package = TeamTrainingPackage::query()->lockForUpdate()->findOrFail($package->getKey());
            $course = $package->course;
            abort_unless($package->status === 'active' && (float) $package->price === 0.0
                && $course instanceof Course && $course->status === 'published'
                && $course->visibility === 'public' && $course->published_at !== null
                && $course->isFree(), 422, 'This free team package is unavailable.');
            abort_unless($package->seats >= 1, 422);

            $purchase = TeamPackagePurchase::query()->create([
                'package_id' => $package->getKey(),
                'organization_name' => $organization,
                'purchaser_id' => $owner->getKey(),
                'seats_purchased' => $package->seats,
                'amount' => 0,
                'status' => 'active',
                'purchased_at' => now(),
            ]);
            TeamPackageMember::query()->create([
                'purchase_id' => $purchase->getKey(),
                'user_id' => $owner->getKey(),
                'status' => 'active',
                'accepted_at' => now(),
                'joined_at' => now(),
            ]);
            Enrollment::query()->firstOrCreate(
                ['user_id' => $owner->getKey(), 'course_id' => $course->getKey()],
                ['enrolled_at' => now(), 'status' => 'active', 'progress_percent' => 0]
            );

            return $purchase;
        }, attempts: 3);
    }

    public function invite(User $owner, TeamPackagePurchase $team, string $email): TeamPackageMember
    {
        return DB::transaction(function () use ($owner, $team, $email): TeamPackageMember {
            $team = TeamPackagePurchase::query()->lockForUpdate()->findOrFail($team->getKey());
            abort_unless($owner->isActive() && $team->purchaser_id === $owner->getKey(), 403);
            abort_unless($team->status === 'active' && (float) $team->amount === 0.0, 422);

            $member = User::query()->where('email', $email)->where('status', 'active')->first();
            if (! $member) {
                throw ValidationException::withMessages(['email' => 'No active Edumia account has this email address.']);
            }
            if ($team->members()->whereIn('status', ['active', 'pending'])->count() >= $team->seats_purchased) {
                throw ValidationException::withMessages(['email' => 'All available team seats are reserved.']);
            }
            $existing = TeamPackageMember::query()->where('purchase_id', $team->getKey())
                ->where('user_id', $member->getKey())->first();
            if ($existing && in_array($existing->status, ['active','pending'], true)) {
                throw ValidationException::withMessages(['email' => 'This account already has a team seat or invitation.']);
            }
            if ($existing) {
                $existing->update(['status' => 'pending', 'accepted_at' => null, 'joined_at' => null]);
                return $existing;
            }

            return TeamPackageMember::query()->create([
                'purchase_id' => $team->getKey(),
                'user_id' => $member->getKey(),
                'status' => 'pending',
            ]);
        }, attempts: 3);
    }

    public function respond(User $member, TeamPackageMember $invitation, bool $accept): void
    {
        DB::transaction(function () use ($member, $invitation, $accept): void {
            abort_unless($member->isActive() && $invitation->user_id === $member->getKey(), 403);

            $team = TeamPackagePurchase::query()->lockForUpdate()->findOrFail($invitation->purchase_id);
            $invitation = TeamPackageMember::query()->lockForUpdate()->findOrFail($invitation->getKey());
            abort_unless($invitation->status === 'pending', 422);

            if (! $accept) {
                $invitation->update(['status' => 'declined']);
                return;
            }
            $package = $team->package;
            $course = $package?->course;
            abort_unless($team->status === 'active' && (float) $team->amount === 0.0
                && $course instanceof Course && $course->status === 'published'
                && $course->visibility === 'public' && $course->published_at !== null
                && $course->isFree(), 422, 'This team course is not currently available.');
            abort_unless($team->members()->where('status', 'active')->count() < $team->seats_purchased, 422);

            Enrollment::query()->firstOrCreate(
                ['user_id' => $member->getKey(), 'course_id' => $course->getKey()],
                ['enrolled_at' => now(), 'status' => 'active', 'progress_percent' => 0]
            );
            $invitation->update(['status' => 'active', 'accepted_at' => now(), 'joined_at' => now()]);
        }, attempts: 3);
    }
}
