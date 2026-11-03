<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChannelController extends Controller
{
    public function index(Request $request): View
    {
        $channels = $request->user()->channels()
            ->withCount('contents')
            ->orderBy('platform')
            ->orderBy('handle_name')
            ->get();

        return view('channels.index', [
            'channels' => $channels,
            'platforms' => Channel::PLATFORMS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'platform' => ['required', Rule::in(Channel::PLATFORMS)],
            'handle_name' => [
                'required', 'string', 'max:100',
                Rule::unique('channels')->where(fn ($q) => $q
                    ->where('user_id', $userId)
                    ->where('platform', $request->input('platform'))),
            ],
        ], [
            'platform.required' => 'Platform wajib dipilih.',
            'platform.in' => 'Platform tidak valid.',
            'handle_name.required' => 'Nama akun / handle wajib diisi.',
            'handle_name.unique' => 'Channel ini sudah terdaftar.',
        ]);

        $request->user()->channels()->create($data);

        return back()->with('success', 'Channel berhasil ditambahkan.');
    }

    public function destroy(Channel $channel): RedirectResponse
    {
        Gate::authorize('delete', $channel);

        if ($channel->contents()->exists()) {
            return back()->with('error', 'Channel tidak bisa dihapus karena masih memiliki konten.');
        }

        $channel->delete();

        return back()->with('success', 'Channel dihapus.');
    }
}
