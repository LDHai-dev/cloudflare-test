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
}
