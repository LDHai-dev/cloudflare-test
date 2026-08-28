<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat', fn ($user) => true);
