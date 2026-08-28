<?php

namespace App\Ai;

interface AiProvider
{
    /**
     * Summarize the given plain text and return the summary.
     */
    public function summarize(string $text): string;

    /**
     * Summarize an image by sending its raw bytes directly to the AI provider.
     */
    public function summarizeImage(string $imageBytes, string $mimeType): string;
}
