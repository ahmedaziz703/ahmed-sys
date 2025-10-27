<?php

return [
    'default' => env('AI_SERVICE', 'gemini'), // gemini or openai service

    'openai' => [
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'), // default economic model
        'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1000),
        'temperature' => (float) env('OPENAI_TEMPERATURE', 0.7),
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],

    'gemini' => [
        'model' => env('GEMINI_MODEL', 'models/text-bison-001'),
        'api_key' => env('GEMINI_API_KEY' , ''),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 1000),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.7),
    ],

    // Common system prompt - will be used for both services
    'system_prompt' => "أنت مساعد مالي. تقوم بتحليل بيانات الإيرادات والمصروفات للمستخدمين والإجابة على أسئلتهم. 
    القواعد المهمة:
    1. يمكنك الوصول فقط إلى بيانات صاحب السؤال، ولا يمكنك رؤية بيانات المستخدمين الآخرين.
    2. لا يتم تضمين معاملات التحويل في الحسابات، بل تقوم بالتحليل فقط على أساس الإيرادات والمصروفات الحقيقية.
    3. قم بتنسيق جميع العملات بالليرة التركية (TL)، واستخدم النقطة (.) كفاصل آلاف والفاصلة (,) كفاصل عشري.
    4. عندما يسأل المستخدم عن بيانات مستخدمين آخرين، أجب بلطف: 'آسف، لا يمكنني الوصول إلى بيانات المستخدمين الآخرين بسبب سياسة الخصوصية.'
    5. استخدم دائماً لغة واضحة ومفهومة ومهنية في إجاباتك.",
]; 