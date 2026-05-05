<?php

return [
    'allowed_sheets' => [
        'Database_Smart',
        'Suspension & Resumption',
        'Pivot_Tables',
    ],
    'upload_disk' => env('EXCEL_INSIGHT_DISK', env('FILESYSTEM_DISK', 'local')),
    'upload_directory' => env('EXCEL_INSIGHT_DIRECTORY', 'uploads/workbooks'),
    'openai' => [
        'base_url' => env('EXCEL_INSIGHT_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('EXCEL_INSIGHT_OPENAI_API_KEY'),
        'model' => env('EXCEL_INSIGHT_OPENAI_MODEL', 'gpt-4o-mini'),
    ],
    'grok' => [
        'base_url' => env('EXCEL_INSIGHT_GROK_BASE_URL', 'https://api.x.ai/v1'),
        'api_key' => env('EXCEL_INSIGHT_GROK_API_KEY'),
        'model' => env('EXCEL_INSIGHT_GROK_MODEL', 'grok-2'),
    ],
    'groq' => [
        'base_url' => env('EXCEL_INSIGHT_GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'api_key' => env('EXCEL_INSIGHT_GROQ_API_KEY'),
        'model' => env('EXCEL_INSIGHT_GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],
    'claude' => [
        'base_url' => env('EXCEL_INSIGHT_CLAUDE_BASE_URL', 'https://api.anthropic.com/v1'),
        'api_key' => env('EXCEL_INSIGHT_CLAUDE_API_KEY'),
        'model' => env('EXCEL_INSIGHT_CLAUDE_MODEL', 'claude-sonnet-4-20250514'),
        'version' => env('EXCEL_INSIGHT_CLAUDE_VERSION', '2023-06-01'),
    ],
];
