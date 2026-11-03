<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\ContentChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChecklistController extends Controller
{
    public function store(Request $request, Content $content): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $content);

        $data = $request->validate([
            'task_name' => ['required', 'string', 'max:255'],
        ], [
            'task_name.required' => 'Nama tugas wajib diisi.',
            'task_name.max' => 'Nama tugas maksimal 255 karakter.',
        ]);

        $item = $content->checklists()->create([
            'task_name' => $data['task_name'],
            'is_completed' => false,
        ]);

        return $request->expectsJson()
            ? response()->json($item, 201)
            : back()->with('success', 'Item checklist ditambahkan.');
    }

    public function toggle(Request $request, ContentChecklist $checklist): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $checklist->content);

        $checklist->update(['is_completed' => ! $checklist->is_completed]);

        return $request->expectsJson()
            ? response()->json(['id' => $checklist->id, 'is_completed' => $checklist->is_completed])
            : back();
    }

    public function destroy(Request $request, ContentChecklist $checklist): \Illuminate\Http\Response|RedirectResponse
    {
        Gate::authorize('update', $checklist->content);

        $checklist->delete();

        return $request->expectsJson()
            ? response()->noContent()
            : back()->with('success', 'Item checklist dihapus.');
    }
}
