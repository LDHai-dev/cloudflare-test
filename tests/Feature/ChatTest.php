<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDeepSeek(): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Tóm tắt giả lập']]],
            ]),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_can_send_text_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/messages', ['body' => 'Xin chào'])
            ->assertCreated()
            ->assertJsonPath('body', 'Xin chào');

        $this->assertDatabaseHas('messages', ['user_id' => $user->id, 'body' => 'Xin chào']);
    }

    public function test_upload_stores_file_on_r2_without_summary_by_default(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/messages', [
            'file' => UploadedFile::fake()->createWithContent('bao-cao.txt', 'Nội dung báo cáo quý ba.'),
        ]);

        $response->assertCreated();
        Storage::disk('r2')->assertExists($response->json('file_path'));
        $this->assertNull($response->json('summary'));
    }

    public function test_summarize_on_click_queues_job_that_saves_summary(): void
    {
        Storage::fake('r2');
        $this->fakeDeepSeek();
        $user = User::factory()->create();

        $message = Message::factory()->withFile('bao-cao.txt')->create(['user_id' => $user->id]);
        Storage::disk('r2')->put($message->file_path, 'Nội dung báo cáo quý ba.');

        // queue sync trong test nên job chạy ngay trong request
        $this->actingAs($user)
            ->postJson("/messages/{$message->id}/summarize")
            ->assertStatus(202);

        $this->assertSame('Tóm tắt giả lập', $message->fresh()->summary);
    }

    public function test_summarize_on_upload_mode_saves_summary_immediately(): void
    {
        config(['services.ai.summarize_on_upload' => true]);
        Storage::fake('r2');
        $this->fakeDeepSeek();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/messages', [
            'file' => UploadedFile::fake()->createWithContent('ghi-chu.md', 'Ghi chú cuộc họp.'),
        ]);

        $response->assertCreated();
        $this->assertSame('Tóm tắt giả lập', Message::find($response->json('id'))->summary);
    }

    public function test_unsupported_file_type_returns_error_on_summarize(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $message = Message::factory()->withFile('anh.png')->create(['user_id' => $user->id]);
        Storage::disk('r2')->put($message->file_path, 'khong-phai-van-ban');

        $this->actingAs($user)
            ->postJson("/messages/{$message->id}/summarize")
            ->assertStatus(422);
    }
}
