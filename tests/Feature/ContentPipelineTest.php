<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPipelineTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: \App\Models\Channel, 2: Content} */
    private function makeContent(string $status = 'idea', ?User $user = null): array
    {
        $user ??= User::factory()->create();
        $channel = $user->channels()->create(['platform' => 'youtube', 'handle_name' => '@tech']);
        $content = $user->contents()->create([
            'channel_id' => $channel->id,
            'title' => 'Judul Uji',
            'format_type' => 'short_video',
            'status' => $status,
            'priority' => 'medium',
        ]);

        return [$user, $channel, $content];
    }

    /** TC-01 */
    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'a@example.com']);

        $this->post(route('register'), [
            'name' => 'B', 'email' => 'a@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'creator',
        ])->assertSessionHasErrors(['email' => 'Email sudah digunakan.']);
    }

    public function test_register_and_login_work_with_hashed_password(): void
    {
        $this->post(route('register'), [
            'name' => 'Budi', 'email' => 'budi@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'creator',
        ])->assertRedirect(route('contents.index'));

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertNotSame('password123', $user->password_hash);
        $this->assertSame('creator', $user->fresh()->role);

        auth()->logout();
        $this->post(route('login'), ['email' => 'budi@example.com', 'password' => 'password123'])
            ->assertRedirect(route('contents.index'));
        $this->assertAuthenticatedAs($user);
    }

    /** TC-02 */
    public function test_create_content_requires_title(): void
    {
        [$user, $channel] = $this->makeContent();

        $this->actingAs($user)->post(route('contents.store'), [
            'channel_id' => $channel->id, 'format_type' => 'short_video', 'priority' => 'medium',
        ])->assertSessionHasErrors('title');

        $this->assertDatabaseCount('contents', 1); // hanya konten dari makeContent()
    }

    public function test_new_content_always_starts_as_idea(): void
    {
        [$user, $channel] = $this->makeContent();

        $this->actingAs($user)->post(route('contents.store'), [
            'title' => 'Baru', 'channel_id' => $channel->id,
            'format_type' => 'carousel', 'priority' => 'high', 'status' => 'published',
        ])->assertRedirect();

        $this->assertSame('idea', Content::where('title', 'Baru')->first()->status);
    }

    /** TC-03 */
    public function test_cannot_publish_without_valid_url(): void
    {
        [$user, , $content] = $this->makeContent('ready');

        $this->actingAs($user)->patch(route('contents.transition', $content), ['status' => 'published'])
            ->assertSessionHasErrors('published_url');

        $this->actingAs($user)->patch(route('contents.transition', $content), [
            'status' => 'published', 'published_url' => 'bukan-url',
        ])->assertSessionHasErrors('published_url');

        $this->assertSame('ready', $content->fresh()->status);
    }

    public function test_can_publish_with_valid_url(): void
    {
        [$user, , $content] = $this->makeContent('ready');

        $this->actingAs($user)->patch(route('contents.transition', $content), [
            'status' => 'published', 'published_url' => 'https://youtube.com/watch?v=abc',
        ])->assertSessionHasNoErrors();

        $content->refresh();
        $this->assertSame('published', $content->status);
        $this->assertNotNull($content->published_date);
    }

    public function test_status_cannot_skip_stages(): void
    {
        [$user, , $content] = $this->makeContent('idea');

        $this->actingAs($user)->patch(route('contents.transition', $content), ['status' => 'editing'])
            ->assertSessionHasErrors('status');

        $this->assertSame('idea', $content->fresh()->status);
    }

    /** TC-04 */
    public function test_deleting_content_removes_checklists_and_metrics(): void
    {
        [$user, , $content] = $this->makeContent('published');
        foreach (['A', 'B', 'C'] as $task) {
            $content->checklists()->create(['task_name' => $task, 'is_completed' => false]);
        }
        foreach (['2026-10-01', '2026-10-08'] as $date) {
            $content->metrics()->create(['recorded_at' => $date, 'views_count' => 10]);
        }

        $this->actingAs($user)->delete(route('contents.destroy', $content))->assertRedirect(route('contents.index'));

        $this->assertDatabaseMissing('contents', ['id' => $content->id]);
        $this->assertDatabaseCount('content_checklists', 0);
        $this->assertDatabaseCount('content_metrics', 0);
    }

    public function test_checklist_can_be_added_toggled_and_deleted_via_json(): void
    {
        [$user, , $content] = $this->makeContent();

        $id = $this->actingAs($user)
            ->postJson(route('contents.checklists.store', $content), ['task_name' => 'Rekam B-Roll'])
            ->assertCreated()->json('id');

        $this->actingAs($user)->patchJson(route('checklists.toggle', $id))
            ->assertOk()->assertJson(['is_completed' => true]);

        $this->actingAs($user)->deleteJson(route('checklists.destroy', $id))->assertNoContent();
        $this->assertDatabaseCount('content_checklists', 0);
    }

    public function test_metrics_only_for_published_content(): void
    {
        [$user, , $content] = $this->makeContent('editing');
        $payload = ['recorded_at' => now()->toDateString(), 'views_count' => 5, 'likes_count' => 1, 'comments_count' => 0, 'shares_count' => 0];

        $this->actingAs($user)->post(route('contents.metrics.store', $content), $payload)
            ->assertSessionHasErrors('recorded_at');
        $this->assertDatabaseCount('content_metrics', 0);

        $content->update(['status' => 'published', 'published_url' => 'https://example.com/p']);
        $this->actingAs($user)->post(route('contents.metrics.store', $content), $payload)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('content_metrics', 1);
    }

    public function test_user_cannot_access_other_users_content(): void
    {
        [, , $content] = $this->makeContent();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('contents.show', $content))->assertForbidden();
        $this->actingAs($intruder)->delete(route('contents.destroy', $content))->assertForbidden();
    }

    public function test_index_filters_by_status_and_platform(): void
    {
        [$user, $channel] = $this->makeContent('idea');
        $user->contents()->create([
            'channel_id' => $channel->id, 'title' => 'Siap Tayang',
            'format_type' => 'carousel', 'status' => 'ready', 'priority' => 'low',
        ]);

        $this->actingAs($user)->get(route('contents.index', ['status' => 'ready']))
            ->assertOk()->assertSee('Siap Tayang')->assertDontSee('Judul Uji');

        $this->actingAs($user)->get(route('contents.index', ['platform' => 'tiktok']))
            ->assertOk()->assertDontSee('Siap Tayang');
    }
}
