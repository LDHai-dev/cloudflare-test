<?php

namespace App\Http\Controllers;

use App\Ai\FileSummarizer;
use App\Events\ChatUpdated;
use App\Jobs\SummarizeFile;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ChatController extends Controller
{
    public function showLogin(): View
    {
        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Sai email hoặc mật khẩu.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('chat');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function index(): View
    {
        return view('chat');
    }

    public function fetch(): JsonResponse
    {
        $messages = Message::with('user:id,name')
            ->latest('id')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function store(Request $request, FileSummarizer $summarizer): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required_without:files', 'nullable', 'string', 'max:5000'],
            'files' => ['required_without:body', 'nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:20480'],
        ]);

        $files = $request->file('files') ?? [];
        $messages = [];

        if ($body = $data['body'] ?? null) {
            $messages[] = Message::create(['user_id' => $request->user()->id, 'body' => $body]);
        }

        foreach ($files as $file) {
            $message = Message::create([
                'user_id' => $request->user()->id,
                'file_path' => $file->store('uploads', 'r2'),
                'file_name' => $file->getClientOriginalName(),
                'file_mime' => $file->getClientMimeType(),
            ]);

            // Phương án 1 (SUMMARIZE_ON_UPLOAD=true): 1 file thì tóm tắt sync lưu DB rồi mới trả về;
            // nhiều file thì đẩy queue — không thể bắt 1 request đợi N lần gọi AI
            if (config('services.ai.summarize_on_upload') && FileSummarizer::supports((string) $message->file_name)) {
                if (count($files) === 1) {
                    try {
                        $summarizer->summarize($message);
                    } catch (Throwable $e) {
                        report($e); // upload vẫn thành công, người dùng có thể bấm nút tóm tắt lại sau
                    }
                } else {
                    SummarizeFile::dispatch($message);
                }
            }

            $messages[] = $message;
        }

        broadcast(new ChatUpdated);

        return response()->json($messages, 201);
    }

    /**
     * Tóm tắt hàng loạt các file cũ được chọn trong lịch sử chat — mỗi file một job.
     */
    public function summarizeBatch(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:20'],
            'ids.*' => ['integer'],
        ])['ids'];

        $queued = 0;

        $messages = Message::whereIn('id', $ids)
            ->whereNotNull('file_path')
            ->whereNull('summary')
            ->get();

        foreach ($messages as $message) {
            if (FileSummarizer::supports((string) $message->file_name)) {
                SummarizeFile::dispatch($message);
                $queued++;
            }
        }

        return response()->json(['queued' => $queued], 202);
    }

    /**
     * Phương án 2: chỉ tóm tắt khi bấm nút — đẩy job queue tải file từ R2 về tạm rồi gửi AI.
     */
    public function summarize(Message $message): JsonResponse
    {
        abort_unless((bool) $message->file_path, 422, 'Tin nhắn không có tệp đính kèm.');

        if ($message->summary) {
            return response()->json($message->load('user:id,name'));
        }

        abort_unless(FileSummarizer::supports((string) $message->file_name), 422, 'Chưa hỗ trợ tóm tắt loại tệp này.');

        SummarizeFile::dispatch($message);

        return response()->json(['status' => 'queued'], 202);
    }

    public function download(Message $message): RedirectResponse
    {
        abort_unless((bool) $message->file_path, 404);

        return redirect()->away(
            Storage::disk('r2')->temporaryUrl($message->file_path, now()->addMinutes(5))
        );
    }
}
