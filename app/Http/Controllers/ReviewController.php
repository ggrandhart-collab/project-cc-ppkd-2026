<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\ContentReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Keputusan editor pada tahap "editing":
 *  - approved           → status "ready" (giliran kreator mem-publish)
 *  - revision_requested → status kembali ke "recording" dengan catatan revisi wajib
 */
class ReviewController extends Controller
{
    public function store(Request $request, Content $content): RedirectResponse
    {
        Gate::authorize('review', $content);

        $data = $request->validate([
            'decision' => ['required', Rule::in(ContentReview::DECISIONS)],
            'note' => [
                Rule::requiredIf($request->input('decision') === ContentReview::REVISION_REQUESTED),
                'nullable', 'string', 'max:2000',
            ],
        ], [
            'decision.required' => 'Pilih keputusan: setujui atau minta revisi.',
            'decision.in' => 'Keputusan tidak valid.',
            'note.required' => 'Catatan revisi wajib diisi supaya kreator tahu apa yang perlu diperbaiki.',
            'note.max' => 'Catatan maksimal 2000 karakter.',
        ]);

        $approved = $data['decision'] === ContentReview::APPROVED;

        DB::transaction(function () use ($content, $data, $request, $approved) {
            // Kunci baris & cek ulang status, agar dua editor tidak memutuskan konten yang sama bersamaan.
            $fresh = Content::whereKey($content->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status !== 'editing') {
                throw ValidationException::withMessages([
                    'decision' => 'Konten ini sudah diputuskan editor lain atau tidak lagi berstatus editing.',
                ]);
            }

            $fresh->reviews()->create([
                'reviewer_id' => $request->user()->id,
                'decision' => $data['decision'],
                'note' => filled($data['note'] ?? null) ? trim($data['note']) : null,
            ]);

            $fresh->update(['status' => $approved ? 'ready' : 'recording']);
        });

        return redirect()->route('contents.show', $content)->with('success', $approved
            ? 'Konten disetujui dan masuk tahap Ready. Kreator bisa mempublikasikannya.'
            : 'Revisi diminta. Konten dikembalikan ke kreator (tahap Recording).');
    }
}
