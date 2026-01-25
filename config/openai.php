<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the OpenAI API for ChatGPT integration.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    'default_model' => env('OPENAI_MODEL', 'gpt-4-turbo-preview'),

    'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1000),

    'temperature' => (float) env('OPENAI_TEMPERATURE', 0.7),

];
