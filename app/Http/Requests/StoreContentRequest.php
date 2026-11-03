<?php

namespace App\Http\Requests;

use App\Models\Content;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Dipakai untuk membuat (store) dan memperbarui (update) konten. */
class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $content = $this->route('content');

        // Membuat ide: hanya kreator/admin (editor tidak). Mengubah: hanya pemilik/admin.
        return $content
            ? Gate::allows('update', $content)
            : Gate::allows('create', Content::class);
    }

    public function rules(): array
    {
        /** @var Content|null $content */
        $content = $this->route('content');
        $ownerId = $content?->user_id ?? $this->user()->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'channel_id' => [
                'required',
                Rule::exists('channels', 'id')->where('user_id', $ownerId),
            ],
            'format_type' => ['required', Rule::in(Content::FORMATS)],
            'priority' => ['required', Rule::in(Content::PRIORITIES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'script_draft' => ['nullable', 'string', 'max:65000'],
            'scheduled_date' => ['nullable', 'date'],
            // Hanya relevan untuk konten yang sudah published.
            'published_url' => [
                Rule::requiredIf(fn () => $content?->isPublished() ?? false),
                'nullable', 'url:http,https', 'max:255',
            ],
            'published_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul konten wajib diisi.',
            'title.max' => 'Judul maksimal 255 karakter.',
            'channel_id.required' => 'Channel wajib dipilih.',
            'channel_id.exists' => 'Channel tidak valid.',
            'format_type.required' => 'Format konten wajib dipilih.',
            'format_type.in' => 'Format konten tidak valid.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'priority.in' => 'Prioritas tidak valid.',
            'scheduled_date.date' => 'Tanggal rilis tidak valid.',
            'published_url.required' => 'published_url wajib diisi untuk konten yang sudah published.',
            'published_url.url' => 'published_url harus berupa tautan http(s) yang valid.',
        ];
    }
}
