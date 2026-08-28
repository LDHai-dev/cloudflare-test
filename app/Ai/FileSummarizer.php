<?php

namespace App\Ai;

use App\Models\Message;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class FileSummarizer
{
    public const SUPPORTED_EXTENSIONS = ['pdf', 'docx', 'txt', 'md', 'csv', 'json', 'xml', 'html', 'log'];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(public AiProvider $aiProvider) {}

    public static function supports(string $fileName): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($extension, self::SUPPORTED_EXTENSIONS, true)
            || in_array($extension, self::IMAGE_EXTENSIONS, true);
    }

    /**
     * Summarize the message's file via the configured AI provider and persist the summary.
     * Images are sent directly to the provider; documents are downloaded from R2
     * to a temp file and have their text extracted first.
     */
    public function summarize(Message $message): string
    {
        if (! self::supports((string) $message->file_name)) {
            throw new RuntimeException('Chưa hỗ trợ tóm tắt loại tệp này.');
        }

        $extension = strtolower(pathinfo((string) $message->file_name, PATHINFO_EXTENSION));

        if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            $mimeType = $extension === 'jpg' ? 'image/jpeg' : 'image/'.$extension;
            $summary = $this->aiProvider->summarizeImage(Storage::disk('r2')->get($message->file_path), $mimeType);

            $message->update(['summary' => $summary]);

            return $summary;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'sum');

        try {
            file_put_contents($tempPath, Storage::disk('r2')->get($message->file_path));
            $text = $this->extractText($tempPath, (string) $message->file_name);
        } finally {
            @unlink($tempPath);
        }

        if (trim($text) === '') {
            throw new RuntimeException('Không trích xuất được nội dung văn bản từ tệp.');
        }

        // ponytail: cắt ở 100k ký tự; nếu cần tóm tắt file lớn hơn thì chuyển sang chunk + map-reduce
        $summary = $this->aiProvider->summarize(mb_substr($text, 0, 100_000));

        $message->update(['summary' => $summary]);

        return $summary;
    }

    private function extractText(string $path, string $fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => (new Parser)->parseFile($path)->getText(),
            'docx' => $this->extractDocxText($path),
            default => (string) file_get_contents($path), // các loại text thuần trong SUPPORTED_EXTENSIONS
        };
    }

    private function extractDocxText(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Không đọc được nội dung tệp docx.');
        }

        try {
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }

        if ($xml === false) {
            throw new RuntimeException('Không đọc được nội dung tệp docx.');
        }

        return strip_tags(str_replace(['</w:p>', '<w:tab/>'], ["\n", "\t"], $xml));
    }
}
