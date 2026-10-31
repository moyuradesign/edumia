<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canTeach();
    }

    public function create(User $user): bool
    {
        return $user->canTeach();
    }

    public function view(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->canTeach() && (int) $course->instructor_id === (int) $user->id);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->view($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->view($user, $course) && $course->enrollments()->doesntExist();
    }

    public function publish(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }
}
