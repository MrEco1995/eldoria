<?php

return [
    'url' => env('COMFYUI_URL', 'http://comfyui:8188'),
    'workflow_path' => env('COMFYUI_WORKFLOW_PATH', 'resources/comfyui/character_workflow.json'),
    'style_prompt' => env('COMFYUI_STYLE_PROMPT', 'high fantasy, cinematic lighting, detailed, painterly, consistent style'),
    'negative_prompt' => env('COMFYUI_NEGATIVE_PROMPT', 'lowres, blurry, watermark, text, logo'),
];
