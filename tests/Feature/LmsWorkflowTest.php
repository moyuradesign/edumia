<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Certificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class LmsWorkflowTest extends TestCase {
 use RefreshDatabase;
 private function member(string $role): User {
  $member=User::create(['name'=>ucfirst($role),'email'=>$role.'@edumia.test','password'=>'Password#12345']);
  $member->forceFill(['role'=>$role,'status'=>'active'])->save();
  return $member;
 }
 public function test_registration_cannot_assign_admin_role(): void {
  $this->post('/register',['name'=>'New Student','email'=>'new@edumia.test','password'=>'Password#12345',
    'password_confirmation'=>'Password#12345','role'=>'admin'])->assertRedirect('/dashboard');
  $this->assertDatabaseHas('users',['email'=>'new@edumia.test','role'=>'student']);
 }
 public function test_student_cannot_use_instructor_or_admin_routes(): void {
  $student=$this->member('student');$this->actingAs($student);
  $this->get('/instructor/courses/create')->assertForbidden();
  $this->get('/dashboard')->assertOk()->assertSee('My learning');
  $this->put('/admin/users/'.$student->id,['role'=>'admin','status'=>'active'])->assertForbidden();
 }
 public function test_administrator_and_instructor_dashboards_render(): void {
  $admin=$this->member('admin');
  $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Course approval queue');

  $instructor=$this->member('instructor');
  $this->actingAs($instructor)->get('/dashboard')->assertOk()->assertSee('Your courses');
 }
 public function test_instructor_can_create_course_but_cannot_publish(): void {
  $teacher=$this->member('instructor');$this->actingAs($teacher);
  $this->post('/instructor/courses',['title'=>'Edumia Fundamentals','level'=>'beginner','language_code'=>'en'])->assertRedirect();
  $course=Course::firstOrFail();$this->assertSame('draft',$course->status);
  $this->post('/admin/courses/'.$course->id.'/publish')->assertForbidden();
 }
 public function test_only_own_instructor_can_edit_course(): void {
  $first=$this->member('instructor');
  $second=User::create(['name'=>'Second','email'=>'second@edumia.test','password'=>'Password#12345']);
  $second->forceFill(['role'=>'instructor'])->save();
  $course=Course::create(['instructor_id'=>$first->id,'title'=>'Restricted','slug'=>'restricted','status'=>'draft']);
  $this->actingAs($second)->get('/instructor/courses/'.$course->id.'/edit')->assertForbidden();
 }
 public function test_free_enrollment_lessons_quiz_and_certificate(): void {
  $teacher=$this->member('instructor');$student=$this->member('student');
  $course=Course::create(['instructor_id'=>$teacher->id,'title'=>'Complete Me','slug'=>'complete-me',
    'status'=>'published','published_at'=>now(),'visibility'=>'public','price'=>0]);
  $section=$course->sections()->create(['title'=>'Module One','is_published'=>true]);
  $lesson=$section->lessons()->create(['course_id'=>$course->id,'title'=>'First lesson','slug'=>'first-lesson','is_published'=>true]);
  $quiz=$course->quizzes()->create(['title'=>'Checkpoint','is_published'=>true,'passing_score'=>70,'attempts_allowed'=>3]);
  $question=$quiz->questions()->create(['question'=>'Two plus two?','options'=>['2','3','4','5'],'correct_answer'=>[2],'points'=>1]);
  $this->actingAs($student)->post('/courses/'.$course->id.'/enroll')->assertRedirect();
  $this->post('/learn/'.$course->id.'/lessons/'.$lesson->id.'/complete')->assertRedirect();
  $this->assertDatabaseCount('certificates',0);
  $this->post('/learn/quizzes/'.$quiz->id,['answers'=>[$question->id=>2]])->assertRedirect();
  $this->assertDatabaseCount('certificates',1);
  $cert=Certificate::firstOrFail();
  $this->get('/certificates/verify/'.$cert->verification_token)->assertOk()->assertSee('Valid certificate');
 }
 public function test_paid_course_cannot_be_enrolled_without_checkout(): void {
  $teacher=$this->member('instructor');$student=$this->member('student');
  $course=Course::create(['instructor_id'=>$teacher->id,'title'=>'Premium','slug'=>'premium',
    'status'=>'published','published_at'=>now(),'visibility'=>'public','price'=>15]);
  $this->actingAs($student)->post('/courses/'.$course->id.'/enroll')->assertForbidden();
 }
}
