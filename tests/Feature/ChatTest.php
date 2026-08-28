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
            ->assertJsonPath('0.body', 'Xin chào');

        $this->assertDatabaseHas('messages', ['user_id' => $user->id, 'body' => 'Xin chào']);
    }

    public function test_upload_stores_file_on_r2_without_summary_by_default(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/messages', [
            'files' => [UploadedFile::fake()->createWithContent('bao-cao.txt', 'Nội dung báo cáo quý ba.')],
        ]);

        $response->assertCreated();
        Storage::disk('r2')->assertExists($response->json('0.file_path'));
        $this->assertNull($response->json('0.summary'));
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
            'files' => [UploadedFile::fake()->createWithContent('ghi-chu.md', 'Ghi chú cuộc họp.')],
        ]);

        $response->assertCreated();
        $this->assertSame('Tóm tắt giả lập', Message::find($response->json('0.id'))->summary);
    }

    public function test_multiple_files_create_one_message_each(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/messages', [
            'body' => 'Gửi 2 tài liệu',
            'files' => [
                UploadedFile::fake()->createWithContent('a.txt', 'Nội dung A'),
                UploadedFile::fake()->createWithContent('b.txt', 'Nội dung B'),
            ],
        ]);

        $response->assertCreated();
        $this->assertCount(3, $response->json()); // 1 tin nhắn text + 2 tin nhắn file
        $this->assertSame(3, Message::count());
    }

    public function test_batch_summarize_queues_selected_old_files(): void
    {
        Storage::fake('r2');
        $this->fakeDeepSeek();
        $user = User::factory()->create();

        $first = Message::factory()->withFile('a.txt')->create(['user_id' => $user->id]);
        $second = Message::factory()->withFile('b.txt')->create(['user_id' => $user->id]);
        Storage::disk('r2')->put($first->file_path, 'Nội dung A');
        Storage::disk('r2')->put($second->file_path, 'Nội dung B');

        // queue sync trong test nên job chạy ngay
        $this->actingAs($user)
            ->postJson('/messages/summarize-batch', ['ids' => [$first->id, $second->id]])
            ->assertStatus(202)
            ->assertJsonPath('queued', 2);

        $this->assertSame('Tóm tắt giả lập', $first->fresh()->summary);
        $this->assertSame('Tóm tắt giả lập', $second->fresh()->summary);
    }

    public function test_summarize_image_sends_it_directly_to_vision_model(): void
    {
        Storage::fake('r2');
        $this->fakeDeepSeek();
        $user = User::factory()->create();

        $message = Message::factory()->withFile('hoa-don.png')->create(['user_id' => $user->id]);
        Storage::disk('r2')->put($message->file_path, 'fake-png-bytes');

        $this->actingAs($user)
            ->postJson("/messages/{$message->id}/summarize")
            ->assertStatus(202);

        $this->assertSame('Tóm tắt giả lập', $message->fresh()->summary);

        // ảnh phải đi thẳng dạng base64 tới model vision, không qua bước trích text
        Http::assertSent(function ($request) {
            return $request['model'] === config('services.deepseek.vision_model')
                && str_contains($request['messages'][0]['content'][1]['file_data'], base64_encode('fake-png-bytes'));
        });
    }

    public function test_unsupported_file_type_returns_error_on_summarize(): void
    {
        Storage::fake('r2');
        $user = User::factory()->create();

        $message = Message::factory()->withFile('nen.zip')->create(['user_id' => $user->id]);
        Storage::disk('r2')->put($message->file_path, 'khong-phai-van-ban');

        $this->actingAs($user)
            ->postJson("/messages/{$message->id}/summarize")
            ->assertStatus(422);
    }
}
