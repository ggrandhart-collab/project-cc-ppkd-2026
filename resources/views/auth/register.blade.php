@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
<div class="mx-auto mt-10 max-w-md">
    <h1 class="mb-4 text-2xl font-semibold">Buat Akun</h1>

    <form method="POST" action="{{ route('register') }}" class="card space-y-4">
        @csrf

        <div>
            <label class="label" for="name">Nama lengkap</label>
            <input id="name"
                   name="name"
                   value="{{ old('name') }}"
                   class="input"
                   maxlength="100"
                   required
                   autofocus>
        </div>

        <div>
            <label class="label" for="email">Email</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   class="input"
                   maxlength="191"
                   required>
        </div>

        {{-- ROLE --}}
        <div>
            <label class="label" for="role">Pilih Role</label>

            <select id="role" name="role" class="input" required>
                <option value="">-- Pilih Role --</option>

                <option value="creator" {{ old('role') == 'creator' ? 'selected' : '' }}>
                    Creator
                </option>

                <option value="editor" {{ old('role') == 'editor' ? 'selected' : '' }}>
                    Editor
                </option>
            </select>

            <p class="mt-1 text-xs text-slate-500">
                Role Admin hanya dapat diberikan oleh administrator.
            </p>
        </div>

        <div>
            <label class="label" for="password">
                Password (min. 8 karakter)
            </label>
            <input id="password"
                   type="password"
                   name="password"
                   class="input"
                   minlength="8"
                   required>
        </div>

        <div>
            <label class="label" for="password_confirmation">
                Konfirmasi password
            </label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   class="input"
                   required>
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            Daftar
        </button>
    </form>

    <p class="mt-4 text-center text-sm">
        Sudah punya akun?
        <a class="text-indigo-600 underline" href="{{ route('login') }}">
            Masuk
        </a>
    </p>
</div>
@endsection