<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizSubmission;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class DashboardController extends Controller {
 public function index(Request $request) {
  $user=$request->user();abort_unless($user->isActive(),403);
  if($user->isAdmin()) return view('dashboard.admin',['users'=>User::count(),'courses'=>Course::count(),
   'pending'=>Course::where('status','pending')->latest()->get(),
   'students'=>Enrollment::distinct('user_id')->count('user_id'),
   'recentCourses'=>Course::with('instructor')->latest()->limit(10)->get(),
   'instructors'=>User::where('role','instructor')->count(),
   'members'=>User::latest()->limit(15)->get()]);
  if($user->canTeach()) return view('dashboard.instructor',['courses'=>Course::where('instructor_id',$user->id)->withCount(['enrollments','lessons'])->latest()->get(),
   'enrollments'=>Enrollment::whereHas('course',fn($q)=>$q->where('instructor_id',$user->id))->count()]);
  return view('dashboard.student',['enrollments'=>Enrollment::where('user_id',$user->id)->with('course')->latest()->get(),
    'certificates'=>Certificate::where('user_id',$user->id)->whereNull('revoked_at')->with('course')->get(),
    'passed'=>QuizSubmission::where('user_id',$user->id)->where('passed',true)->count()]);
 }
 public function setRole(Request $request,User $user) {
   abort_unless($request->user()->isAdmin(),403);
   abort_if($user->id===$request->user()->id,422,'Cannot change your own administrator role.');
   $data=$request->validate(['role'=>['required','in:student,instructor,admin'],'status'=>['required','in:active,suspended']]);
   if ($user->isAdmin() && ($data['role']!=='admin' || $data['status']!=='active')) {
     abort_if(User::where('role','admin')->where('status','active')->count()<=1,422,'At least one active administrator must remain.');
   }
   $user->forceFill($data)->save();return back()->with('success','Member updated.');
 }
}
