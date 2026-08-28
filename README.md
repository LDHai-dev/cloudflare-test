# Chat + Tóm tắt tài liệu bằng AI

Ứng dụng chat 2 người dùng trên Laravel: nhắn tin realtime, upload tệp lên Cloudflare R2, và tóm tắt nội dung tệp bằng DeepSeek AI.

## Tính năng

- **Chat realtime** giữa 2 user qua Laravel Reverb (websocket), có polling dự phòng khi mất kết nối.
- **Upload tệp** (chọn được nhiều tệp một lần, tối đa 10 tệp / 20MB mỗi tệp) — lưu trên Cloudflare R2, tải về qua signed URL có hạn 5 phút.
- **Tóm tắt AI** theo 2 phương án, chuyển bằng biến môi trường `SUMMARIZE_ON_UPLOAD`:
  - `true` — **tự động khi upload**: tóm tắt xong, lưu DB rồi mới trả về (upload nhiều tệp thì tự chuyển qua queue).
  - `false` (mặc định) — **bấm nút mới tóm tắt**: đẩy job vào queue, tải tệp từ R2 về thư mục tạm, gửi AI, lưu DB, phát kết quả realtime.
- **Panel "📁 Tệp đã gửi"**: xem mọi tệp trong lịch sử chat, tick chọn nhiều tệp cũ để tóm tắt hàng loạt, bấm "✓ Xem tóm tắt" mở dialog nội dung.
- Giao diện Blade thuần (không cần build frontend), tự theo dark mode của hệ điều hành.

## Loại tệp tóm tắt được

| Loại | Cách trích text |
|---|---|
| `.pdf` | smalot/pdfparser |
| `.docx` | ZipArchive đọc `word/document.xml` |
| `.txt` `.md` `.csv` `.json` `.xml` `.html` `.log` | đọc trực tiếp |

Tệp ngoài danh sách vẫn gửi/tải về bình thường, chỉ không tóm tắt được (DeepSeek không có vision).

## Kiến trúc AI — Adapter pattern

```
app/Ai/
├── AiProvider.php       # interface: summarize(string $text): string
├── DeepSeekProvider.php # implementation gọi api.deepseek.com
└── FileSummarizer.php   # tải tệp từ R2 → trích text → gọi provider → lưu DB
```

Đổi provider AI: viết class mới implement `AiProvider`, thêm một nhánh `match` trong `AppServiceProvider::register()`, đổi `AI_PROVIDER` trong `.env`. Không phải sửa chỗ nào khác.

## Cài đặt

Yêu cầu: PHP 8.3 (bật extension `zip`), Composer, SQLite.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Điền vào `.env`:

```env
# DeepSeek
DEEPSEEK_API_KEY=sk-...

# Cloudflare R2 (tạo API token ở dashboard R2)
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_BUCKET=...
R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com

# Reverb (điền chuỗi ngẫu nhiên bất kỳ)
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
```

## Chạy

```bash
composer run dev
```

Một lệnh chạy đủ 3 process: web server (`:8000`), Reverb websocket (`:8080`), queue worker.

Đăng nhập tại `http://localhost:8000` — 2 tài khoản seed sẵn (mở 2 trình duyệt để chat 2 phía):

| Email | Mật khẩu |
|---|---|
| `user1@example.com` | `password` |
| `user2@example.com` | `password` |

## Test

```bash
php artisan test
```

Test fake sẵn R2 (`Storage::fake`) và DeepSeek (`Http::fake`) — chạy không cần credentials.

## Ghi chú vận hành

- Queue mặc định 1 worker chạy tuần tự — tóm tắt nhiều tệp sẽ nối đuôi nhau. Cần song song thì mở thêm terminal chạy `php artisan queue:work`.
- Độ trễ tóm tắt chủ yếu là thời gian DeepSeek sinh kết quả (10–60s tùy độ dài tệp).
- Text gửi AI bị cắt ở 100.000 ký tự đầu; tệp dài hơn cần chuyển sang tóm tắt theo chunk.
