<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MetricController extends Controller
{
    public function store(Request $request, Content $content): RedirectResponse
    {
        Gate::authorize('update', $content);

        if (! $content->isPublished()) {
            throw ValidationException::withMessages([
                'recorded_at' => 'Metrik hanya bisa dicatat untuk konten yang sudah berstatus published.',
            ]);
        }

        $int = ['required', 'integer', 'min:0', 'max:2147483647'];

        $data = $request->validate([
            'recorded_at' => ['required', 'date', 'before_or_equal:today'],
            'views_count' => $int,
            'likes_count' => $int,
            'comments_count' => $int,
            'shares_count' => $int,
        ], [
            'recorded_at.required' => 'Tanggal pencatatan wajib diisi.',
            'recorded_at.before_or_equal' => 'Tanggal pencatatan tidak boleh di masa depan.',
            '*.required' => 'Kolom ini wajib diisi (isi 0 jika belum ada).',
            '*.integer' => 'Nilai harus berupa bilangan bulat.',
            '*.min' => 'Nilai tidak boleh negatif.',
        ]);

        $content->metrics()->create($data);

        return back()->with('success', 'Metrik performa berhasil dicatat.');
    }
}
