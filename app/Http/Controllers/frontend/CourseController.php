<?php
namespace App\Http\Controllers\frontend;
use App\Helpers\Common_helper as Text;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;
class CourseController extends Controller {
 public function index(Request $request,string $locale='en'): View {
  $locale=Text::language($locale);App::setLocale($locale);
  $query=substr(trim((string)$request->query('q','')),0,100);
  $courses=Course::published()->with('category')->withCount('lessons')
   ->when($query!=='',fn($q)=>$q->where(fn($sub)=>$sub->where('title','like','%'.$query.'%')->orWhere('summary','like','%'.$query.'%')))
   ->latest('published_at')->paginate(12)->withQueryString();
  return view('frontend.default.course.index',compact('locale','query','courses')+['languages'=>Text::LANGUAGES]);
 }
 public function show(Request $request,string $slug): View {return $this->renderShow($slug,'en');}
 public function localizedShow(Request $request,string $locale,string $slug): View {return $this->renderShow($slug,$locale);}
 private function renderShow(string $slug,string $locale): View {
  $locale=Text::language($locale);App::setLocale($locale);
  $course=Course::published()->where('slug',$slug)->with(['instructor','category','sections'=>fn($q)=>$q->where('is_published',true),
    'sections.lessons'=>fn($q)=>$q->where('is_published',true)])->withCount('lessons')->firstOrFail();
  return view('frontend.default.course.course_details',['locale'=>$locale,'languages'=>Text::LANGUAGES,'course'=>$course]);
 }
}
