@extends('layouts.panel')
@section('title',$course->exists?'Edit course':'Create course')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR · COURSE BUILDER</span><h1>{{ $course->exists?'Manage '.$course->title:'Create a course' }}</h1>
<p>Build sections, lessons, and quizzes. Administrator approval is required for publication.</p></div><a class="btn btn-quiet" href="{{ route('dashboard') }}">← Dashboard</a></div>
<section class="panel-card"><h2>Course information</h2>
<form method="post" action="{{ $course->exists?route('instructor.courses.update',$course):route('instructor.courses.store') }}" class="stack-form">@csrf @if($course->exists) @method('PUT') @endif
<div class="form-two"><label>Course title<input name="title" value="{{ old('title',$course->title) }}" required maxlength="190"></label>
<label>Level<select name="level" required>@foreach(['beginner','intermediate','advanced'] as $level)<option value="{{ $level }}" @selected(old('level',$course->level??'beginner')===$level)>{{ ucfirst($level) }}</option>@endforeach</select></label></div>
<label>Summary<textarea name="summary" rows="3" maxlength="2000">{{ old('summary',$course->summary) }}</textarea></label>
<label>Description<textarea name="description" rows="5" maxlength="20000">{{ old('description',$course->description) }}</textarea></label>
<div class="form-two"><label>Category<select name="category_id"><option value="">Uncategorized</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id',$course->category_id)==$category->id)>{{ $category->name }}</option>@endforeach</select></label>
<label>Teaching language<select name="language_code">@foreach(\App\Helpers\Localization::LANGUAGES as $code=>$name)<option value="{{ $code }}" @selected(old('language_code',$course->language_code??'en')===$code)>{{ $name }}</option>@endforeach</select></label></div>
<p class="subtle">Edumia currently supports free enrollments only. Paid checkout is not enabled.</p>
<button class="btn btn-primary">{{ $course->exists?'Save changes':'Create course draft' }}</button></form>
@if($course->exists)<p class="subtle">Status: <strong>{{ ucfirst($course->status) }}</strong> · Public slug: {{ $course->slug }}</p>
@if(auth()->user()->isAdmin())<form class="inline-form" action="{{ route('admin.courses.publish',$course) }}" method="post">@csrf<button class="btn btn-primary">Publish course</button></form>
@elseif($course->status!=='pending')<form class="inline-form" action="{{ route('instructor.courses.submit',$course) }}" method="post">@csrf<button class="btn btn-secondary">Submit for approval</button></form>@endif
@if(in_array($course->status,['draft','pending'],true) && $course->enrollments()->doesntExist())
<form method="post" action="{{ route('instructor.courses.destroy',$course) }}" onsubmit="return confirm('Permanently delete this unpublished course?')">@csrf @method('DELETE')<button class="btn btn-quiet" type="submit">Delete draft course</button></form>@endif
@endif</section>
@if($course->exists)
<section class="panel-card"><div class="panel-heading"><div><h2>Curriculum</h2><p>Each course needs at least one section and one lesson.</p></div></div>
<form class="inline-form form-row" method="post" action="{{ route('instructor.sections.store',$course) }}">@csrf
<label class="grow">New section title<input name="title" required maxlength="190" placeholder="e.g. Introduction"></label><button class="btn btn-secondary">Add section</button></form>
@foreach($course->sections as $section)<div class="builder-section"><h3>{{ $section->title }}</h3>
@if($section->lessons->isNotEmpty())<div class="table-scroll"><table><thead><tr><th>Lesson</th><th>Content</th></tr></thead><tbody>@foreach($section->lessons as $lesson)<tr><td>{{ $lesson->title }}<details class="lesson-editor"><summary>Edit lesson</summary><form method="post" action="{{ route('instructor.lessons.update',[$course,$lesson]) }}" class="stack-form compact-form">@csrf @method('PUT')
<label>Title<input name="title" required maxlength="190" value="{{ $lesson->title }}"></label><label>Content<textarea name="content" maxlength="30000" rows="3">{{ $lesson->content }}</textarea></label>
<label>Video URL<input type="url" name="video_url" value="{{ $lesson->video_url }}" maxlength="255"></label>
<label class="checkline"><input name="is_published" type="checkbox" value="1" @checked($lesson->is_published)> Published</label>
<button class="btn btn-small btn-secondary">Save lesson</button></form></details></td><td>{{ $lesson->is_published?'Published':'Draft' }} · {{ $lesson->duration_seconds }} sec</td></tr>@endforeach</tbody></table></div>@endif
<form class="stack-form compact-form" action="{{ route('instructor.lessons.store',[$course,$section]) }}" method="post">@csrf<h4>Add lesson</h4>
<label>Lesson title<input name="title" maxlength="190" required></label><label>Lesson content<textarea name="content" maxlength="30000" rows="3" placeholder="Text lesson content"></textarea></label>
<div class="form-two"><label>Optional video URL<input type="url" name="video_url" maxlength="255" placeholder="https://"></label><label>Duration in seconds<input type="number" min="0" name="duration_seconds" value="0"></label></div>
<button class="btn btn-small btn-secondary">Add lesson</button></form></div>@endforeach
</section>
<section class="panel-card"><h2>Live class sessions</h2>
<p>Schedule external meetings. Joining links are visible only to enrolled learners.</p>
<a class="btn btn-secondary" href="{{ route('instructor.live-classes.index', $course) }}">Manage live classes</a></section>
<section class="panel-card"><h2>Knowledge checks</h2><form class="form-row" action="{{ route('instructor.quizzes.store',$course) }}" method="post">@csrf
<label class="grow">Quiz title<input name="title" required maxlength="190"></label><label>Pass %<input type="number" name="passing_score" min="0" max="100" value="70" required></label><label>Attempts<input type="number" name="attempts_allowed" min="1" max="20" value="3" required></label><button class="btn btn-secondary">Create quiz</button></form>
@foreach($course->quizzes as $quiz)<div class="builder-section"><h3>{{ $quiz->title }} <small>({{ $quiz->questions->count() }} questions · {{ $quiz->is_published?'published':'draft' }})</small></h3>
@if($quiz->questions->isNotEmpty())<ol>@foreach($quiz->questions as $question)<li>{{ $question->question }}</li>@endforeach</ol>@endif
@if(!$quiz->is_published)<form class="stack-form compact-form" action="{{ route('instructor.questions.store',[$course,$quiz]) }}" method="post">@csrf
<label>Question text<input name="question" required maxlength="2000"></label><div class="form-two">@for($n=0;$n<4;$n++)<label>Option {{ $n+1 }}<input name="options[{{ $n }}]" required maxlength="350"></label>@endfor</div>
<label>Correct option<select name="correct"><option value="0">Option 1</option><option value="1">Option 2</option><option value="2">Option 3</option><option value="3">Option 4</option></select></label>
<button class="btn btn-small">Add question</button></form>
@if($quiz->questions->isNotEmpty())<form action="{{ route('instructor.quizzes.publish',[$course,$quiz]) }}" method="post" class="inline-form">@csrf<button class="btn btn-secondary btn-small">Publish quiz</button></form>@endif
@endif</div>@endforeach</section>
<section class="panel-card">
    <h2>Assignments</h2>
    <p>Create published assignments that enrolled learners can submit and instructors can grade.</p>
    <form class="stack-form compact-form" method="post" action="{{ route('instructor.assignments.store', $course) }}">
        @csrf
        <label>Assignment title <input name="title" required maxlength="190"></label>
        <label>Instructions <textarea name="instructions" required maxlength="30000" rows="5"></textarea></label>
        <div class="form-two">
            <label>Optional deadline <input name="due_at" type="datetime-local"></label>
            <label>Maximum score <input name="max_score" type="number" min="1" max="1000" value="100" required></label>
        </div>
        <label>Optional lesson <select name="lesson_id">
            <option value="">No linked lesson</option>
            @foreach($course->lessons as $lesson)
                <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
            @endforeach
        </select></label>
        <label class="checkline"><input type="checkbox" name="published" value="1"> Publish for enrolled learners</label>
        <button class="btn btn-primary" type="submit">Create assignment</button>
    </form>
    @foreach($course->assignments as $assignment)
    <div class="builder-section">
        <h3>{{ $assignment->title }} <small>({{ $assignment->published ? 'Published' : 'Draft' }})</small></h3>
        <p>{{ $assignment->max_score }} points · {{ $assignment->due_at?->format('M d, Y H:i') ?? 'No deadline' }}</p>
        <p><a href="{{ route('instructor.assignments.gradebook', [$course, $assignment]) }}">Open gradebook</a></p>
        <details><summary>Edit assignment</summary>
            <form class="stack-form compact-form" method="post" action="{{ route('instructor.assignments.update', [$course, $assignment]) }}">
                @csrf @method('PUT')
                <label>Title <input name="title" required maxlength="190" value="{{ $assignment->title }}"></label>
                <label>Instructions <textarea name="instructions" rows="5" required maxlength="30000">{{ $assignment->instructions }}</textarea></label>
                <label>Deadline <input type="datetime-local" name="due_at" value="{{ $assignment->due_at?->format('Y-m-d\TH:i') }}"></label>
                <label>Maximum score <input type="number" name="max_score" min="1" max="1000" value="{{ $assignment->max_score }}" required></label>
                <label>Linked lesson <select name="lesson_id">
                    <option value="">None</option>
                    @foreach($course->lessons as $lesson)
                        <option value="{{ $lesson->id }}" @selected($lesson->id === $assignment->lesson_id)>{{ $lesson->title }}</option>
                    @endforeach
                </select></label>
                <label class="checkline"><input type="checkbox" name="published" value="1" @checked($assignment->published)> Published</label>
                <button class="btn btn-secondary" type="submit">Update assignment</button>
            </form>
        </details>
    </div>
    @endforeach
</section>
@endif
@endsection
