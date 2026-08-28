<?php

namespace App\Ai;

interface AiProvider
{
    /**
     * Summarize the given plain text and return the summary.
     */
    public function summarize(string $text): string;
}
