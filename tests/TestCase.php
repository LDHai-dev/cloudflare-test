<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // chặn test lỡ gọi HTTP thật ra ngoài (đã từng bắn request thật tới DeepSeek)
        Http::preventStrayRequests();
    }
}
