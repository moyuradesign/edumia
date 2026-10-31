<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Tests\TestCase;

final class IdentityEnumTest extends TestCase
{
    public function test_roles_are_explicit_and_only_instructors_and_admins_can_teach(): void
    {
        $this->assertSame(['student', 'instructor', 'admin'], UserRole::values());
        $this->assertFalse(UserRole::Student->canTeach());
        $this->assertTrue(UserRole::Instructor->canTeach());
        $this->assertTrue(UserRole::Administrator->canTeach());
    }

    public function test_account_statuses_are_explicit(): void
    {
        $this->assertSame(['active', 'suspended'], AccountStatus::values());
    }
}
