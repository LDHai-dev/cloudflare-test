<?php

namespace App\Ai;

use Illuminate\Support\Facades\Http;

class DeepSeekProvider implements AiProvider
{
    public function summarize(string $text): string
    {
        $response = Http::withToken(config('services.deepseek.key'))
            ->timeout(120)
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => config('services.deepseek.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Bạn là trợ lý tóm tắt tài liệu. Tóm tắt nội dung sau bằng tiếng Việt, ngắn gọn, theo gạch đầu dòng.',
                    ],
                    ['role' => 'user', 'content' => $text],
                ],
            ])
            ->throw();

        return (string) $response->json('choices.0.message.content');
    }

    public function summarizeImage(string $imageBytes, string $mimeType): string
    {
        // Ảnh gửi thẳng base64 inline trong request — không cần Files API
        // vì upload đã giới hạn 20MB, dưới trần inline 32MiB của DeepSeek
        $response = Http::withToken(config('services.deepseek.key'))
            ->timeout(120)
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => config('services.deepseek.vision_model'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Mô tả và tóm tắt nội dung ảnh này bằng tiếng Việt, ngắn gọn, theo gạch đầu dòng.'],
                            ['type' => 'file', 'file_data' => "data:{$mimeType};base64,".base64_encode($imageBytes)],
                        ],
                    ],
                ],
            ])
            ->throw();

        return (string) $response->json('choices.0.message.content');
    }
}
