<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute عندما يكون :other :value.',
    'active_url' => ':attribute يجب أن يكون رابطاً صالحاً.',
    'after' => ':attribute يجب أن يكون تاريخاً بعد :date.',
    'after_or_equal' => ':attribute يجب أن يكون تاريخاً بعد أو يساوي :date.',
    'alpha' => ':attribute يجب أن يحتوي على أحرف فقط.',
    'alpha_dash' => ':attribute يجب أن يحتوي على أحرف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => ':attribute يجب أن يحتوي على أحرف وأرقام فقط.',
    'array' => ':attribute يجب أن يكون مصفوفة.',
    'ascii' => ':attribute يجب أن يحتوي على أحرف وأرقام ورموز أحادية البايت فقط.',
    'before' => ':attribute يجب أن يكون تاريخاً قبل :date.',
    'before_or_equal' => ':attribute يجب أن يكون تاريخاً قبل أو يساوي :date.',
    'between' => [
        'array' => ':attribute يجب أن يحتوي على :min إلى :max عنصر.',
        'file' => ':attribute يجب أن يكون بين :min و :max كيلوبايت.',
        'numeric' => ':attribute يجب أن يكون بين :min و :max.',
        'string' => ':attribute يجب أن يحتوي على :min إلى :max حرف.',
    ],
    'boolean' => ':attribute يجب أن يكون صحيح أو خطأ فقط.',
    'can' => ':attribute يحتوي على قيمة غير صالحة.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'contains' => ':attribute مفقود قيمة مطلوبة.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute يجب أن يكون تاريخاً صالحاً.',
    'date_equals' => ':attribute يجب أن يكون نفس تاريخ :date.',
    'date_format' => ':attribute يجب أن يتطابق مع تنسيق :format.',
    'decimal' => ':attribute يجب أن يحتوي على :decimal منازل عشرية.',
    'declined' => ':attribute يجب ألا يتم قبوله.',
    'declined_if' => ':attribute يجب ألا يتم قبوله عندما يكون :other :value.',
    'different' => ':attribute و :other يجب أن يكونا مختلفين.',
    'digits' => ':attribute يجب أن يحتوي على :digits رقم.',
    'digits_between' => ':attribute يجب أن يحتوي على :min إلى :max رقم.',
    'dimensions' => ':attribute له أبعاد صورة غير صالحة.',
    'distinct' => ':attribute مكرر.',
    'doesnt_end_with' => ':attribute يجب ألا ينتهي بأحد: :values.',
    'doesnt_start_with' => ':attribute يجب ألا يبدأ بأحد: :values.',
    'email' => ':attribute يجب أن يكون عنوان بريد إلكتروني صالح.',
    'ends_with' => ':attribute يجب أن ينتهي بأحد: :values.',
    'enum' => ':attribute المحدد غير صالح.',
    'exists' => ':attribute المحدد غير صالح.',
    'extensions' => ':attribute يجب أن يكون له أحد الامتدادات: :values.',
    'file' => ':attribute يجب أن يكون ملفاً.',
    'filled' => ':attribute يجب أن يحتوي على قيمة.',
    'gt' => [
        'array' => ':attribute يجب أن يحتوي على أكثر من :value عنصر.',
        'file' => ':attribute يجب أن يكون أكبر من :value كيلوبايت.',
        'numeric' => ':attribute يجب أن يكون أكبر من :value.',
        'string' => ':attribute يجب أن يكون أطول من :value حرف.',
    ],
    'gte' => [
        'array' => ':attribute يجب أن يحتوي على :value عنصر أو أكثر.',
        'file' => ':attribute يجب أن يكون :value كيلوبايت أو أكبر.',
        'numeric' => ':attribute يجب أن يكون :value أو أكبر.',
        'string' => ':attribute يجب أن يحتوي على :value حرف أو أكثر.',
    ],
    'hex_color' => ':attribute يجب أن يكون لون hex صالح.',
    'image' => ':attribute يجب أن يكون صورة.',
    'in' => ':attribute المحدد غير صالح.',
    'in_array' => ':attribute يجب أن يكون موجوداً في :other.',
    'integer' => ':attribute يجب أن يكون رقماً صحيحاً.',
    'ip' => ':attribute يجب أن يكون عنوان IP صالح.',
    'ipv4' => ':attribute يجب أن يكون عنوان IPv4 صالح.',
    'ipv6' => ':attribute يجب أن يكون عنوان IPv6 صالح.',
    'json' => ':attribute يجب أن يكون نص JSON صالح.',
    'list' => ':attribute يجب أن يكون قائمة.',
    'lowercase' => ':attribute يجب أن يكون أحرف صغيرة.',
    'lt' => [
        'array' => ':attribute يجب أن يحتوي على أقل من :value عنصر.',
        'file' => ':attribute يجب أن يكون أقل من :value كيلوبايت.',
        'numeric' => ':attribute يجب أن يكون أقل من :value.',
        'string' => ':attribute يجب أن يكون أقصر من :value حرف.',
    ],
    'lte' => [
        'array' => ':attribute يجب أن يحتوي على :value عنصر أو أقل.',
        'file' => ':attribute يجب أن يكون :value كيلوبايت أو أقل.',
        'numeric' => ':attribute يجب أن يكون :value أو أقل.',
        'string' => ':attribute يجب أن يحتوي على :value حرف أو أقل.',
    ],
    'mac_address' => ':attribute يجب أن يكون عنوان MAC صالح.',
    'max' => [
        'array' => ':attribute يجب ألا يحتوي على أكثر من :max عنصر.',
        'file' => ':attribute يجب ألا يكون أكبر من :max كيلوبايت.',
        'numeric' => ':attribute يجب ألا يكون أكبر من :max.',
        'string' => ':attribute يجب ألا يكون أطول من :max حرف.',
    ],
    'max_digits' => ':attribute يجب ألا يحتوي على أكثر من :max رقم.',
    'mimes' => ':attribute يجب أن يكون من الأنواع: :values.',
    'mimetypes' => ':attribute يجب أن يكون من الأنواع: :values.',
    'min' => [
        'array' => ':attribute يجب أن يحتوي على :min عنصر على الأقل.',
        'file' => ':attribute يجب أن يكون :min كيلوبايت على الأقل.',
        'numeric' => ':attribute يجب أن يكون :min على الأقل.',
        'string' => ':attribute يجب أن يحتوي على :min حرف على الأقل.',
    ],
    'min_digits' => ':attribute يجب أن يحتوي على :min رقم على الأقل.',
    'missing' => ':attribute يجب أن يكون مفقوداً.',
    'missing_if' => ':attribute يجب أن يكون مفقوداً عندما يكون :other :value.',
    'missing_unless' => ':attribute يجب أن يكون مفقوداً ما لم يكن :other :value.',
    'missing_with' => ':attribute يجب أن يكون مفقوداً عندما تكون :values موجودة.',
    'missing_with_all' => ':attribute يجب أن يكون مفقوداً عندما تكون :values موجودة.',
    'multiple_of' => ':attribute يجب أن يكون مضاعفاً لـ :value.',
    'not_in' => ':attribute المحدد غير صالح.',
    'not_regex' => 'تنسيق :attribute غير صالح.',
    'numeric' => ':attribute يجب أن يكون رقماً.',
    'password' => [
        'letters' => ':attribute يجب أن يحتوي على حرف واحد على الأقل.',
        'mixed' => ':attribute يجب أن يحتوي على حرف صغير وحرف كبير واحد على الأقل.',
        'numbers' => ':attribute يجب أن يحتوي على رقم واحد على الأقل.',
        'symbols' => ':attribute يجب أن يحتوي على رمز واحد على الأقل.',
        'uncompromised' => ':attribute تم اكتشافه في تسريب بيانات. يرجى اختيار :attribute مختلف.',
    ],
    'present' => ':attribute يجب أن يكون موجوداً.',
    'present_if' => ':attribute يجب أن يكون موجوداً عندما يكون :other :value.',
    'present_unless' => ':attribute يجب أن يكون موجوداً ما لم يكن :other :value.',
    'present_with' => ':attribute يجب أن يكون موجوداً عندما تكون :values موجودة.',
    'present_with_all' => ':attribute يجب أن يكون موجوداً عندما تكون :values موجودة.',
    'prohibited' => ':attribute يجب ألا يتم إرساله.',
    'prohibited_if' => ':attribute يجب ألا يتم إرساله عندما يكون :other :value.',
    'prohibited_if_accepted' => ':attribute يجب ألا يتم إرساله عندما يتم قبول :other.',
    'prohibited_if_declined' => ':attribute يجب ألا يتم إرساله عندما يتم رفض :other.',
    'prohibited_unless' => ':attribute يجب ألا يتم إرساله ما لم يكن :other :values.',
    'prohibits' => ':attribute يجب ألا يتم إرساله مع :other.',
    'regex' => 'تنسيق :attribute غير صالح.',
    'required' => ':attribute مطلوب.',
    'required_array_keys' => ':attribute يجب أن يحتوي على: :values.',
    'required_if' => ':attribute مطلوب عندما يكون :other :value.',
    'required_if_accepted' => ':attribute مطلوب عندما يتم قبول :other.',
    'required_if_declined' => ':attribute مطلوب عندما يتم رفض :other.',
    'required_unless' => ':attribute مطلوب ما لم يكن :other :values.',
    'required_with' => ':attribute مطلوب عندما تكون :values موجودة.',
    'required_with_all' => ':attribute مطلوب عندما تكون :values موجودة.',
    'required_without' => ':attribute مطلوب عندما لا تكون :values موجودة.',
    'required_without_all' => ':attribute مطلوب عندما لا يكون أي من :values موجوداً.',
    'same' => ':attribute يجب أن يتطابق مع :other.',
    'size' => [
        'array' => ':attribute يجب أن يحتوي على :size عنصر.',
        'file' => ':attribute يجب أن يكون :size كيلوبايت.',
        'numeric' => ':attribute يجب أن يكون :size.',
        'string' => ':attribute يجب أن يكون :size حرف.',
    ],
    'starts_with' => ':attribute يجب أن يبدأ بأحد: :values.',
    'string' => ':attribute يجب أن يكون نصاً.',
    'timezone' => ':attribute يجب أن يكون منطقة زمنية صالحة.',
    'unique' => ':attribute مستخدم مسبقاً.',
    'uploaded' => 'فشل في رفع :attribute.',
    'uppercase' => ':attribute يجب أن يكون أحرف كبيرة.',
    'url' => ':attribute يجب أن يكون رابطاً صالحاً.',
    'ulid' => ':attribute يجب أن يكون ULID صالح.',
    'uuid' => ':attribute يجب أن يكون UUID صالح.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
