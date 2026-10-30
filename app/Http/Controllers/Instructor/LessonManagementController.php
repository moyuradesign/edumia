<?php
namespace App\Http\Controllers\Instructor;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
class LessonManagementController extends Controller {
 public function section(Request $request,Course $course) {
   Gate::authorize('update',$course);$data=$request->validate(['title'=>['required','string','max:190']]);
   $course->sections()->create(['title'=>$data['title'],'sort_order'=>(int)$course->sections()->max('sort_order')+1]);
   return back()->with('success','Section created.');
 }
 public function lesson(Request $request,Course $course,Section $section) {
   Gate::authorize('update',$course);abort_unless((int)$section->course_id===(int)$course->id,404);
   $data=$request->validate(['title'=>['required','string','max:190'],'content'=>['nullable','string','max:30000'],
     'video_url'=>['nullable','url','max:255'],'duration_seconds'=>['nullable','integer','min:0','max:10000000']]);
   $base=Str::slug($data['title'])?:'lesson';$slug=$base;$i=1;
   while($course->lessons()->where('slug',$slug)->exists()){$slug=$base.'-'.(++$i);}
   $course->lessons()->create($data+['section_id'=>$section->id,'slug'=>$slug,
      'sort_order'=>(int)$section->lessons()->max('sort_order')+1]);
   return back()->with('success','Lesson created.');
 }
 public function updateLesson(Request $request,Course $course,Lesson $lesson) {
   Gate::authorize('update',$course);abort_unless((int)$lesson->course_id===(int)$course->id,404);
   $data=$request->validate(['title'=>['required','string','max:190'],'content'=>['nullable','string','max:30000'],
      'video_url'=>['nullable','url','max:255'],'is_published'=>['sometimes','boolean']]);
   $data['is_published']=$request->boolean('is_published');
   $lesson->update($data);return back()->with('success','Lesson updated.');
 }
}
