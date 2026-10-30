<?php
namespace App\Http\Controllers\Student;
use App\Models\Quiz;
use App\Models\Enrollment;
use App\Models\QuizSubmission;
use App\Services\CourseCompletionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
class QuizAttemptController extends Controller {
 public function show(Request $request,Quiz $quiz) {
   $this->authorizeAccess($request,$quiz);
   abort_unless($quiz->questions()->exists(),404);
   $attempts=QuizSubmission::where('quiz_id',$quiz->id)->where('user_id',$request->user()->id)->count();
   return view('student.quiz',['quiz'=>$quiz->load('questions'),'attempts'=>$attempts,
     'past'=>QuizSubmission::where('quiz_id',$quiz->id)->where('user_id',$request->user()->id)->latest()->get()]);
 }
 public function submit(Request $request,Quiz $quiz,CourseCompletionService $service) {
   $enrollment=$this->authorizeAccess($request,$quiz);
   $questions=$quiz->questions()->get();abort_unless($questions->isNotEmpty(),404);
   $data=$request->validate(['answers'=>['required','array'],'answers.*'=>['required','integer','min:0','max:3']]);
   $selected=$data['answers'];$max=0;$score=0;$saved=[];
   foreach($questions as $question) {
     abort_unless($question->type==='single_choice' && is_array($question->options),422,'Unsupported question type.');
     $answer=$selected[(string)$question->id]??$selected[$question->id]??null;
     abort_unless($answer!==null && array_key_exists((int)$answer,$question->options),422,'Answer every question.');
     $max+=$question->points;$score+=((int)$answer===(int)($question->correct_answer[0]??-1))?$question->points:0;
     $saved[(string)$question->id]=(int)$answer;
   }
   $percentage=$max>0?round(100*$score/$max,2):0;
   DB::transaction(function() use($request,$quiz,$saved,$percentage) {
     $user=$request->user();
     // Serialize submissions for the same user to enforce attempts allowed.
     $locked=\App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
     $attempt=QuizSubmission::where('quiz_id',$quiz->id)->where('user_id',$locked->id)->count()+1;
     abort_if($attempt>$quiz->attempts_allowed,403,'No attempts remain.');
     QuizSubmission::create(['quiz_id'=>$quiz->id,'user_id'=>$locked->id,'attempt_number'=>$attempt,
       'answers'=>$saved,'score'=>$percentage,'passed'=>$percentage >= $quiz->passing_score,
       'started_at'=>now(),'submitted_at'=>now()]);
   });
   $service->refresh($enrollment);
   return redirect()->route('learning.quiz',$quiz)->with('success','Your quiz has been graded.');
 }
 private function authorizeAccess(Request $request,Quiz $quiz): Enrollment {
   abort_unless($quiz->is_published && $quiz->course->status==='published',404);
   return Enrollment::where('course_id',$quiz->course_id)->where('user_id',$request->user()->id)->firstOrFail();
 }
}
