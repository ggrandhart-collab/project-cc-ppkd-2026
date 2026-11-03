<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Alur kreator → editor (setujui / revisi) → kreator publish. */
class ContentReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create(['role' => 'editor']);
    }

    /** @return array{0: User, 1: Channel, 2: Content} */
    private function makeContent(string $status, ?User $creator = null): array
    {
        $creator ??= User::factory()->create();
        $channel = $creator->channels()->create(['platform' => 'youtube', 'handle_name' => '@tech']);
        $content = $creator->contents()->create([
            'channel_id' => $channel->id,
            'title' => 'Konten '.$status,
            'format_type' => 'short_video',
            'status' => $status,
            'priority' => 'medium',
        ]);

        return [$creator, $channel, $content];
    }

    private function move(User $user, Content $content, string $status, array $extra = [])
    {
        return $this->actingAs($user)->patch(route('contents.transition', $content), ['status' => $status] + $extra);
    }

    public function test_creator_can_send_content_through_to_editing(): void
    {
        [$creator, , $content] = $this->makeContent('idea');

        foreach (['scripting', 'recording', 'editing'] as $status) {
            $this->move($creator, $content, $status)->assertSessionHasNoErrors();
        }

        $this->assertSame('editing', $content->fresh()->status);
    }

    public function test_creator_cannot_move_content_out_of_editing(): void
    {
        [$creator, , $content] = $this->makeContent('editing');

        $this->move($creator, $content, 'ready')->assertSessionHasErrors('status');
        $this->move($creator, $content, 'recording')->assertSessionHasErrors('status');

        $this->assertSame('editing', $content->fresh()->status);
    }

    public function test_creator_cannot_approve_own_content_via_review_endpoint(): void
    {
        [$creator, , $content] = $this->makeContent('editing');

        $this->actingAs($creator)->post(route('contents.review', $content), ['decision' => 'approved'])
            ->assertForbidden();

        $this->assertSame('editing', $content->fresh()->status);
    }

    public function test_creator_cannot_skip_the_ready_stage_by_moving_forward_from_recording(): void
    {
        [$creator, , $content] = $this->makeContent('recording');

        $this->move($creator, $content, 'ready')->assertSessionHasErrors('status');
    }

    public function test_editor_approves_and_content_goes_to_ready(): void
    {
        [, , $content] = $this->makeContent('editing');
        $editor = $this->editor();

        $this->actingAs($editor)->post(route('contents.review', $content), [
            'decision' => 'approved', 'note' => 'Mantap, siap tayang.',
        ])->assertRedirect(route('contents.show', $content))->assertSessionHasNoErrors();

        $this->assertSame('ready', $content->fresh()->status);
        $this->assertDatabaseHas('content_reviews', [
            'content_id' => $content->id, 'reviewer_id' => $editor->id,
            'decision' => 'approved', 'note' => 'Mantap, siap tayang.',
        ]);
    }

    public function test_editor_can_approve_without_a_note(): void
    {
        [, , $content] = $this->makeContent('editing');

        $this->actingAs($this->editor())->post(route('contents.review', $content), ['decision' => 'approved'])
            ->assertSessionHasNoErrors();

        $this->assertSame('ready', $content->fresh()->status);
    }

    public function test_revision_requires_a_note_and_leaves_status_untouched(): void
    {
        [, , $content] = $this->makeContent('editing');

        $this->actingAs($this->editor())->post(route('contents.review', $content), ['decision' => 'revision_requested'])
            ->assertSessionHasErrors('note');

        $this->assertSame('editing', $content->fresh()->status);
        $this->assertDatabaseCount('content_reviews', 0);
    }

    public function test_editor_requests_revision_and_creator_sees_the_note(): void
    {
        [$creator, , $content] = $this->makeContent('editing');
        $editor = $this->editor();

        $this->actingAs($editor)->post(route('contents.review', $content), [
            'decision' => 'revision_requested', 'note' => 'Audio intro berisik, rekam ulang.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('recording', $content->fresh()->status);

        $this->actingAs($creator)->get(route('contents.show', $content))
            ->assertOk()
            ->assertSee('Editor meminta revisi')
            ->assertSee('Audio intro berisik, rekam ulang.');
    }

    public function test_full_cycle_revision_then_approval_then_publish(): void
    {
        [$creator, , $content] = $this->makeContent('recording');
        $editor = $this->editor();

        $this->move($creator, $content, 'editing')->assertSessionHasNoErrors();

        $this->actingAs($editor)->post(route('contents.review', $content), [
            'decision' => 'revision_requested', 'note' => 'Perbaiki warna.',
        ]);
        $this->assertSame('recording', $content->fresh()->status);

        $this->move($creator, $content, 'editing')->assertSessionHasNoErrors();
        $this->actingAs($editor)->post(route('contents.review', $content), ['decision' => 'approved']);
        $this->assertSame('ready', $content->fresh()->status);

        $this->move($creator, $content, 'published', ['published_url' => 'https://youtube.com/watch?v=abc'])
            ->assertSessionHasNoErrors();

        $content->refresh();
        $this->assertSame('published', $content->status);
        $this->assertNotNull($content->published_date);
        $this->assertDatabaseCount('content_reviews', 2);
    }

    public function test_editor_cannot_review_content_that_is_not_in_editing(): void
    {
        $editor = $this->editor();

        foreach (['idea', 'scripting', 'recording', 'ready', 'published'] as $status) {
            [, , $content] = $this->makeContent($status);
            // Konten di luar tahap editing tidak terlihat oleh editor → 403.
            $this->actingAs($editor)->post(route('contents.review', $content), ['decision' => 'approved'])
                ->assertForbidden();
            $this->assertSame($status, $content->fresh()->status);
        }
    }

    public function test_editor_cannot_publish_or_use_regular_transitions(): void
    {
        [, , $ready] = $this->makeContent('ready');
        $editor = $this->editor();

        $this->move($editor, $ready, 'published', ['published_url' => 'https://example.com/x'])->assertForbidden();
        $this->assertSame('ready', $ready->fresh()->status);
    }

    public function test_editor_cannot_edit_delete_or_create_content(): void
    {
        [$creator, $channel, $content] = $this->makeContent('editing');
        $editor = $this->editor();

        $this->actingAs($editor)->get(route('contents.edit', $content))->assertForbidden();
        $this->actingAs($editor)->delete(route('contents.destroy', $content))->assertForbidden();
        $this->actingAs($editor)->get(route('contents.create'))->assertForbidden();
        $this->actingAs($editor)->post(route('contents.store'), [
            'title' => 'Ide dari editor', 'channel_id' => $channel->id,
            'format_type' => 'carousel', 'priority' => 'low',
        ])->assertForbidden();

        $this->assertDatabaseMissing('contents', ['title' => 'Ide dari editor']);
        $this->assertDatabaseHas('contents', ['id' => $content->id]);
    }

    public function test_editor_cannot_modify_checklist(): void
    {
        [, , $content] = $this->makeContent('editing');
        $item = $content->checklists()->create(['task_name' => 'A', 'is_completed' => false]);
        $editor = $this->editor();

        $this->actingAs($editor)->postJson(route('contents.checklists.store', $content), ['task_name' => 'B'])->assertForbidden();
        $this->actingAs($editor)->patchJson(route('checklists.toggle', $item))->assertForbidden();
        $this->actingAs($editor)->deleteJson(route('checklists.destroy', $item))->assertForbidden();
    }

    public function test_editor_queue_shows_only_editing_or_previously_reviewed_content(): void
    {
        [$creator, $channel, $editing] = $this->makeContent('editing');
        $idea = $creator->contents()->create([
            'channel_id' => $channel->id, 'title' => 'Masih Ide Rahasia',
            'format_type' => 'carousel', 'status' => 'idea', 'priority' => 'low',
        ]);
        $editor = $this->editor();

        $this->actingAs($editor)->get(route('contents.index'))
            ->assertOk()->assertSee('Konten editing')->assertDontSee('Masih Ide Rahasia');

        $this->actingAs($editor)->get(route('contents.show', $editing))->assertOk();
        $this->actingAs($editor)->get(route('contents.show', $idea))->assertForbidden();
    }

    public function test_editor_keeps_access_to_content_after_reviewing_it(): void
    {
        [, , $content] = $this->makeContent('editing');
        $editor = $this->editor();

        $this->actingAs($editor)->post(route('contents.review', $content), ['decision' => 'approved']);

        // Sudah "ready", tapi editor yang mereview tetap bisa melihatnya; editor lain tidak.
        $this->actingAs($editor)->get(route('contents.show', $content))->assertOk();
        $this->actingAs($this->editor())->get(route('contents.show', $content))->assertForbidden();
    }

    public function test_second_editor_cannot_review_after_first_decided(): void
    {
        [, , $content] = $this->makeContent('editing');

        $this->actingAs($this->editor())->post(route('contents.review', $content), ['decision' => 'approved']);
        $this->actingAs($this->editor())->post(route('contents.review', $content), [
            'decision' => 'revision_requested', 'note' => 'Terlambat',
        ])->assertForbidden();

        $this->assertSame('ready', $content->fresh()->status);
        $this->assertDatabaseCount('content_reviews', 1);
    }

    public function test_show_page_renders_review_panel_only_for_reviewer(): void
    {
        [$creator, , $content] = $this->makeContent('editing');

        $this->actingAs($this->editor())->get(route('contents.show', $content))
            ->assertOk()->assertSee('Review editor')->assertSee('Minta revisi')
            ->assertDontSee('>Hapus</button>', false)->assertDontSee('>Edit</a>', false);

        $this->actingAs($creator)->get(route('contents.show', $content))
            ->assertOk()->assertDontSee('Review editor')->assertSee('Menunggu keputusan editor');
    }

    public function test_deleting_content_removes_its_reviews(): void
    {
        [$creator, , $content] = $this->makeContent('editing');
        $this->actingAs($this->editor())->post(route('contents.review', $content), ['decision' => 'approved']);
        $this->assertDatabaseCount('content_reviews', 1);

        $content->refresh();
        $this->actingAs($creator)->delete(route('contents.destroy', $content))->assertRedirect(route('contents.index'));

        $this->assertDatabaseCount('content_reviews', 0);
    }

    public function test_register_only_accepts_creator_or_editor_roles(): void
    {
        $base = ['name' => 'X', 'email' => 'x@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'];

        $this->post(route('register'), $base + ['role' => 'admin'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);

        $this->post(route('register'), $base + ['role' => 'editor'])->assertRedirect(route('contents.index'));
        $this->assertSame('editor', User::where('email', 'x@example.com')->first()->role);
    }
}
