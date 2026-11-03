<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun dummy: creator@example.com / password
        $creator = User::create([
            'name' => 'Demo Creator',
            'email' => 'creator@example.com',
            'password_hash' => 'password',
        ]);

        // Akun admin dummy: admin@example.com / password
        User::forceCreate([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
            'password_hash' => 'password',
            'role' => 'admin',
        ]);

        // Akun editor dummy: editor@example.com / password
        User::forceCreate([
            'name' => 'Demo Editor',
            'email' => 'editor@example.com',
            'password_hash' => 'password',
            'role' => 'editor',
        ]);

        // 2 channel dummy
        $youtube = $creator->channels()->create(['platform' => 'youtube', 'handle_name' => '@techcreator']);
        $tiktok = $creator->channels()->create(['platform' => 'tiktok', 'handle_name' => '@techcreator.id']);

        // 3 konten contoh
        $idea = $creator->contents()->create([
            'channel_id' => $tiktok->id,
            'title' => '5 Shortcut Excel yang Jarang Diketahui',
            'description' => 'Video singkat tips produktivitas Excel.',
            'format_type' => 'short_video',
            'status' => 'idea',
            'priority' => 'low',
        ]);

        $recording = $creator->contents()->create([
            'channel_id' => $youtube->id,
            'title' => 'Review Laptop Kreator 2026',
            'description' => 'Review mendalam laptop untuk editing video.',
            'script_draft' => "Hook: Laptop mana yang benar-benar kuat untuk editing 4K?\nIsi: spesifikasi, benchmark, kesimpulan.",
            'format_type' => 'long_video',
            'status' => 'recording',
            'priority' => 'high',
            'scheduled_date' => now()->addDays(7)->toDateString(),
        ]);
        foreach ([['Draft hook naskah selesai', true], ['B-roll direkam', false], ['Thumbnail disetujui', false]] as [$task, $done]) {
            $recording->checklists()->create(['task_name' => $task, 'is_completed' => $done]);
        }

        // Konten yang sedang menunggu review editor
        $editing = $creator->contents()->create([
            'channel_id' => $youtube->id,
            'title' => 'Unboxing Kamera Mirrorless',
            'description' => 'Unboxing dan kesan pertama kamera mirrorless entry-level.',
            'script_draft' => "Hook: Kamera 8 juta yang bisa 4K?\nIsi: unboxing, tes autofocus, kesimpulan.",
            'format_type' => 'long_video',
            'status' => 'editing',
            'priority' => 'high',
            'scheduled_date' => now()->addDays(3)->toDateString(),
        ]);
        $editing->checklists()->create(['task_name' => 'Semua footage direkam', 'is_completed' => true]);

        $published = $creator->contents()->create([
            'channel_id' => $youtube->id,
            'title' => 'Setup Meja Kerja Minimalis',
            'format_type' => 'long_video',
            'status' => 'published',
            'priority' => 'medium',
            'scheduled_date' => now()->subDays(10)->toDateString(),
            'published_date' => now()->subDays(10)->toDateString(),
            'published_url' => 'https://www.youtube.com/watch?v=dummy12345',
        ]);
        $published->metrics()->create([
            'recorded_at' => now()->subDays(7)->toDateString(),
            'views_count' => 1200, 'likes_count' => 140, 'comments_count' => 18, 'shares_count' => 9,
        ]);
        $published->metrics()->create([
            'recorded_at' => now()->subDays(1)->toDateString(),
            'views_count' => 3400, 'likes_count' => 410, 'comments_count' => 52, 'shares_count' => 31,
        ]);
    }
}
