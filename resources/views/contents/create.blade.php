@extends('layouts.app')
@section('title', 'Ide Konten Baru')
@section('content')
<div class="mx-auto max-w-3xl">
    <h1 class="mb-4 text-2xl font-semibold">Ide Konten Baru</h1>
    <form method="POST" action="{{ route('contents.store') }}" class="card space-y-4">
        @csrf
        @include('contents._form')
        <div class="flex gap-2">
            <button class="btn-primary">Simpan ide</button>
            <a href="{{ route('contents.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
