<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Requests\Admin\KnowledgeBaseRequest;
use App\Http\Requests\Admin\KnowledgeBaseTopicRequest;
use App\Models\AuditEvent;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeBaseTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class KnowledgeBaseController extends Controller
{
    public function index(): View
    {
        return view('admin.content.knowledge-base.index', [
            'bases' => KnowledgeBase::query()->withCount('topics')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.content.knowledge-base.form', ['knowledgeBase' => new KnowledgeBase]);
    }

    public function store(KnowledgeBaseRequest $request): RedirectResponse
    {
        $base = DB::transaction(function () use ($request): KnowledgeBase {
            $base = KnowledgeBase::create(array_merge($request->safe()->except('published'), [
                'published' => $request->boolean('published'),
            ]));
            $this->audit($request->user()->getKey(), 'knowledge_base.created', $base);

            return $base;
        });

        return to_route('admin.knowledge-base.edit', $base)->with('success', 'Help center created.');
    }

    public function edit(KnowledgeBase $knowledgeBase): View
    {
        return view('admin.content.knowledge-base.form', [
            'knowledgeBase' => $knowledgeBase->load('topics'),
        ]);
    }

    public function update(KnowledgeBaseRequest $request, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        DB::transaction(function () use ($request, $knowledgeBase): void {
            $base = KnowledgeBase::query()->lockForUpdate()->findOrFail($knowledgeBase->getKey());
            $base->fill(array_merge($request->safe()->except('published'), [
                'published' => $request->boolean('published'),
            ]))->save();
            $this->audit($request->user()->getKey(), 'knowledge_base.updated', $base);
        });

        return to_route('admin.knowledge-base.edit', $knowledgeBase)->with('success', 'Help center saved.');
    }

    public function destroy(KnowledgeBase $knowledgeBase): RedirectResponse
    {
        DB::transaction(function () use ($knowledgeBase): void {
            $base = KnowledgeBase::query()->lockForUpdate()->findOrFail($knowledgeBase->getKey());
            $this->audit(auth()->id(), 'knowledge_base.deleted', $base);
            $base->delete();
        });

        return to_route('admin.knowledge-base.index')->with('success', 'Help center deleted.');
    }

    public function storeTopic(KnowledgeBaseTopicRequest $request, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        DB::transaction(function () use ($request, $knowledgeBase): void {
            $topic = $knowledgeBase->topics()->create($request->validated());
            $this->audit($request->user()->getKey(), 'knowledge_topic.created', $topic);
        });

        return to_route('admin.knowledge-base.edit', $knowledgeBase)->with('success', 'Topic created.');
    }

    public function updateTopic(KnowledgeBaseTopicRequest $request, KnowledgeBase $knowledgeBase, KnowledgeBaseTopic $topic): RedirectResponse
    {
        $this->belongsToBase($knowledgeBase, $topic);
        DB::transaction(function () use ($request, $topic): void {
            $topic = KnowledgeBaseTopic::query()->lockForUpdate()->findOrFail($topic->getKey());
            $topic->fill($request->validated())->save();
            $this->audit($request->user()->getKey(), 'knowledge_topic.updated', $topic);
        });

        return to_route('admin.knowledge-base.edit', $knowledgeBase)->with('success', 'Topic saved.');
    }

    public function destroyTopic(KnowledgeBase $knowledgeBase, KnowledgeBaseTopic $topic): RedirectResponse
    {
        $this->belongsToBase($knowledgeBase, $topic);
        DB::transaction(function () use ($topic): void {
            $topic = KnowledgeBaseTopic::query()->lockForUpdate()->findOrFail($topic->getKey());
            $this->audit(auth()->id(), 'knowledge_topic.deleted', $topic);
            $topic->delete();
        });

        return to_route('admin.knowledge-base.edit', $knowledgeBase)->with('success', 'Topic deleted.');
    }

    private function belongsToBase(KnowledgeBase $base, KnowledgeBaseTopic $topic): void
    {
        abort_unless((int) $topic->knowledge_base_id === (int) $base->getKey(), 404);
    }

    private function audit(int $actor, string $action, KnowledgeBase|KnowledgeBaseTopic $subject): void
    {
        AuditEvent::create([
            'actor_id' => $actor, 'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(), 'action' => $action,
            'new_values' => ['title' => $subject->title],
        ]);
    }
}
