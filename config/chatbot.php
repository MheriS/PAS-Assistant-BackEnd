<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Python inference service (FastAPI)
    |--------------------------------------------------------------------------
    |
    | Kosongkan untuk selalu memakai shell_exec ke predict.py (lambat per pesan).
    | Set ke base URL layanan, mis. http://127.0.0.1:8765 — jalankan inference_server.py.
    |
    */
    'inference_url' => rtrim((string) (env('CHATBOT_INFERENCE_URL') ?: ''), '/'),

    'inference_timeout' => (int) env('CHATBOT_INFERENCE_TIMEOUT', 120),

    'inference_connect_timeout' => (int) env('CHATBOT_INFERENCE_CONNECT_TIMEOUT', 3),
];
