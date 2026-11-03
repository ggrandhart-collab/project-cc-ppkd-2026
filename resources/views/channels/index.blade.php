@extends('layouts.app')
@section('title', 'Channel')
@section('content')
<h1 class="mb-4 text-2xl font-semibold">Channel / Media Sosial</h1>

<div class="grid gap-6 md:grid-cols-3">
    <form method="POST" action="{{ route('channels.store') }}" class="card space-y-4 md:col-span-1 self-start">
        @csrf
        <h2 class="font-semibold">Tambah channel</h2>
        <div>
            <label class="label" for="platform">Platform</label>
            <select id="platform" name="platform" class="input">
                <option value="">— pilih —</option>
                @foreach ($platforms as $p)
                    <option value="{{ $p }}" @selected(old('platform') === $p)>{{ \App\Models\Channel::PLATFORM_LABELS[$p] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="handle_name">Nama akun / handle</label>
            <input id="handle_name" name="handle_name" value="{{ old('handle_name') }}" class="input" placeholder="@@techcreator" maxlength="100">
        </div>
        <button class="btn-primary">Simpan</button>
    </form>

    <div class="card md:col-span-2">
        <h2 class="mb-3 font-semibold">Channel kamu</h2>
        @if ($channels->isEmpty())
            <p class="text-sm text-slate-500">Belum ada channel. Tambahkan satu untuk mulai membuat konten.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-slate-500">
                        <tr><th class="py-2">Platform</th><th>Handle</th><th>Konten</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($channels as $channel)
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $channel->platformLabel() }}</td>
                            <td>{{ $channel->handle_name }}</td>
                            <td>{{ $channel->contents_count }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('channels.destroy', $channel) }}" onsubmit="return confirm('Hapus channel ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
