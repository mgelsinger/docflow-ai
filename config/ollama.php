<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ollama Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL where your Ollama instance is running.
    | Default: http://127.0.0.1:11434
    |
    */

    'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),

    /*
    |--------------------------------------------------------------------------
    | Ollama Model
    |--------------------------------------------------------------------------
    |
    | The vision model to use for document processing.
    | Default: qwen3-vl:8b
    |
    */

    'model' => env('OLLAMA_MODEL', 'qwen3-vl:8b'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for Ollama API requests.
    | Vision models can take longer to process, so set this accordingly.
    |
    */

    'timeout' => env('OLLAMA_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Maximum Image Width
    |--------------------------------------------------------------------------
    |
    | Maximum width in pixels for images sent to Ollama.
    | Images will be resized proportionally if they exceed this width.
    |
    */

    'max_image_width' => env('OLLAMA_MAX_IMAGE_WIDTH', 1600),

];
