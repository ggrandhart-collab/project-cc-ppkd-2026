@extends('layouts.app')
@section('title', 'Edit Konten')
@section('content')
<div class="mx-auto max-w-3xl">
    <h1 class="mb-4 text-2xl font-semibold">Edit Konten</h1>
    <form method="POST" action="{{ route('contents.update', $content) }}" class="card space-y-4">
        @csrf @method('PUT')
        @include('contents._form')
        <div class="flex gap-2">
            <button class="btn-primary">Simpan perubahan</button>
            <a href="{{ route('contents.show', $content) }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
