<?php

namespace App\Jobs;

use App\Ai\FileSummarizer;
use App\Events\ChatUpdated;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SummarizeFile implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public Message $message) {}

    /**
     * Execute the job.
     */
    public function handle(FileSummarizer $summarizer): void
    {
        try {
            if (! $this->message->refresh()->summary) {
                $summarizer->summarize($this->message);
            }
        } finally {
            // luôn broadcast để client render lại (nút "Tóm tắt" reset nếu job lỗi)
            broadcast(new ChatUpdated);
        }
    }
}
