<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkspaceAudienceAndGlobalErrorTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $name): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'@edumia.test',
            'password' => 'Strong#Password123',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_admin_dashboard_retains_admin_console_while_explicit_member_routes_use_correct_audience(): void
    {
        $admin = $this->member('admin', 'Existing Administrator');

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('ADMIN CONSOLE')
            ->assertSee('data-admin-shell', false)
            ->assertSee('ADMINISTRATION / WORKSPACE');

        $this->get('/account')->assertOk()
            ->assertSee('STUDENT ACCOUNT')
            ->assertSee('STUDENT / WORKSPACE')
            ->assertDontSee('ADMIN CONSOLE')
            ->assertDontSee('Learning management')
            ->assertDontSee('data-admin-shell', false)
            ->assertSee('data-member-shell', false)
            ->assertSee('href="'.route('account.certificates').'"', false);

        $this->get('/instructor')->assertOk()
            ->assertSee('INSTRUCTOR STUDIO')
            ->assertSee('INSTRUCTOR / WORKSPACE')
            ->assertDontSee('ADMIN CONSOLE')
            ->assertDontSee('Ebook approvals')
            ->assertSee('href="'.route('instructor.students.index').'"', false);

        $this->get('/admin/settings/general')->assertOk()->assertSee('ADMIN CONSOLE');
        $this->get('/account/preferences')->assertOk()->assertSee('ADMIN CONSOLE');
    }

    public function test_student_and_instructor_have_separate_workspaces_and_active_navigation(): void
    {
        $student = $this->member('student', 'Learner Test');
        $teacher = $this->member('instructor', 'Teacher Test');

        $this->actingAs($student)->get('/account')->assertOk()
            ->assertSee('STUDENT ACCOUNT')->assertDontSee('INSTRUCTOR STUDIO')
            ->assertDontSee('ADMIN CONSOLE')
            ->assertSee('href="'.route('account.courses').'"', false)
            ->assertSee('href="'.route('account.certificates').'"', false);
        $this->get('/instructor')->assertForbidden();
        $this->get('/instructor/students')->assertForbidden();

        $this->actingAs($teacher)->get('/instructor')->assertOk()
            ->assertSee('INSTRUCTOR STUDIO')
            ->assertDontSee('ADMIN CONSOLE')
            ->assertSee('href="'.route('instructor.courses.index').'"', false)
            ->assertSee('href="'.route('instructor.students.index').'"', false);
        $this->get('/account')->assertOk()
            ->assertSee('STUDENT ACCOUNT')
            ->assertDontSee('INSTRUCTOR STUDIO');
    }

    public function test_instructor_student_roster_is_scoped_to_owned_courses(): void
    {
        $teacher = $this->member('instructor', 'Teaching One');
        $otherTeacher = $this->member('instructor', 'Teaching Two');
        $myStudent = $this->member('student', 'Visible Learner');
        $otherStudent = $this->member('student', 'Private Learner');

        $mine = Course::create(['instructor_id' => $teacher->id,
            'title' => 'Teaching Course', 'slug' => 'teaching-course', 'status' => 'draft']);
        $theirs = Course::create(['instructor_id' => $otherTeacher->id,
            'title' => 'Private Course', 'slug' => 'private-course', 'status' => 'draft']);

        Enrollment::create(['user_id' => $myStudent->id, 'course_id' => $mine->id,
            'status' => 'active', 'progress_percent' => 46]);
        Enrollment::create(['user_id' => $otherStudent->id, 'course_id' => $theirs->id,
            'status' => 'active', 'progress_percent' => 99]);

        $this->actingAs($teacher)->get('/instructor/students')->assertOk()
            ->assertSee('Visible Learner')->assertSee('Teaching Course')
            ->assertDontSee('Private Learner')->assertDontSee('Private Course')
            ->assertSee('46%');
        $this->get('/instructor/students?course='.$theirs->id)->assertOk()
            ->assertSee('No matching enrollments')
            ->assertDontSee('Private Learner');
        $this->get('/instructor/students?q=Visible')->assertOk()
            ->assertSee('Visible Learner')->assertDontSee('Private Learner');
    }

    public function test_student_certificates_show_only_their_nonrevoked_records(): void
    {
        $teacher = $this->member('instructor', 'Certificate Teacher');
        $student = $this->member('student', 'Certificate Learner');
        $another = $this->member('student', 'Other Certificate Learner');
        $course = Course::create(['instructor_id' => $teacher->id,
            'title' => 'Completion Course', 'slug' => 'completion-course', 'status' => 'published']);

        Certificate::create(['course_id' => $course->id, 'user_id' => $student->id,
            'certificate_number' => 'EDM-CERT-ONE', 'verification_token' => str_repeat('A', 48),
            'issued_at' => now()]);
        $otherCourse = Course::create(['instructor_id' => $teacher->id,
            'title' => 'Private Completion', 'slug' => 'private-completion', 'status' => 'published']);
        Certificate::create(['course_id' => $otherCourse->id, 'user_id' => $another->id,
            'certificate_number' => 'EDM-CERT-TWO', 'verification_token' => str_repeat('B', 48),
            'issued_at' => now()]);
        $revokedCourse = Course::create(['instructor_id' => $teacher->id,
            'title' => 'Revoked Completion', 'slug' => 'revoked-completion', 'status' => 'published']);
        Certificate::create(['course_id' => $revokedCourse->id, 'user_id' => $student->id,
            'certificate_number' => 'EDM-CERT-REVOKED', 'verification_token' => str_repeat('C', 48),
            'revoked_at' => now()]);

        $this->actingAs($student)->get('/account/certificates')->assertOk()
            ->assertSee('EDM-CERT-ONE')
            ->assertSee('Completion Course')
            ->assertDontSee('EDM-CERT-TWO')->assertDontSee('EDM-CERT-REVOKED')
            ->assertSee('href="'.route('certificate.show', Certificate::where('certificate_number', 'EDM-CERT-ONE')->firstOrFail()).'"', false);
        $this->actingAs($another)->get('/account/certificates')->assertOk()
            ->assertSee('EDM-CERT-TWO')->assertDontSee('EDM-CERT-ONE');
    }

    public function test_global_not_found_uses_standard_site_layout_with_no_admin_chrome(): void
    {
        $this->get('/edumia-page-that-does-not-exist')->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('404 · Not found')
            ->assertSee('data-premium-header', false)
            ->assertSee('class="site-footer premium-footer"', false)
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('courses.index').'"', false)
            ->assertDontSee('ADMIN CONSOLE')
            ->assertDontSee('data-member-shell', false);
    }
}
