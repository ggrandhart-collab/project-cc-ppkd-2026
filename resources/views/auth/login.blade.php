@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<div class="mx-auto mt-10 max-w-md">
    <h1 class="mb-1 text-2xl font-semibold">Masuk</h1>
    <p class="mb-4 text-sm text-slate-500">Kelola ide, produksi, dan performa kontenmu.</p>
    <form method="POST" action="{{ route('login') }}" class="card space-y-4">
        @csrf
        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" autofocus>
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input id="password" type="password" name="password" class="input">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Ingat saya</label>
        <button class="btn-primary w-full justify-center">Masuk</button>
    </form>
    <p class="mt-4 text-center text-sm">Belum punya akun? <a class="text-indigo-600 underline" href="{{ route('register') }}">Daftar</a></p>
</div>
@endsection
