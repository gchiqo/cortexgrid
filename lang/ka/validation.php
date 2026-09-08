<?php

/*
 * Georgian validation messages.
 *
 * Only the rules this app actually uses are translated. Anything else falls
 * back to the framework's English strings via APP_FALLBACK_LOCALE, so a rule
 * added later renders readable text rather than a raw "validation.*" key.
 */
return [
    'accepted' => ':attribute უნდა იყოს დადასტურებული.',
    'after' => ':attribute უნდა იყოს :date-ის შემდეგ.',
    'array' => ':attribute უნდა იყოს მასივი.',
    'before' => ':attribute უნდა იყოს :date-მდე.',
    'boolean' => ':attribute უნდა იყოს true ან false.',
    'confirmed' => ':attribute — განმეორება არ ემთხვევა.',
    'date' => ':attribute არ არის სწორი თარიღი.',
    'different' => ':attribute და :other უნდა განსხვავდებოდეს.',
    'email' => ':attribute უნდა იყოს სწორი ელ. ფოსტა.',
    'exists' => 'არჩეული :attribute არასწორია.',
    'file' => ':attribute უნდა იყოს ფაილი.',
    'filled' => ':attribute უნდა იყოს შევსებული.',
    'image' => ':attribute უნდა იყოს სურათი.',
    'in' => 'არჩეული :attribute არასწორია.',
    'integer' => ':attribute უნდა იყოს მთელი რიცხვი.',
    'json' => ':attribute უნდა იყოს სწორი JSON.',
    'max' => [
        'array' => ':attribute არ უნდა შეიცავდეს :max-ზე მეტ ელემენტს.',
        'file' => ':attribute არ უნდა აღემატებოდეს :max კილობაიტს.',
        'numeric' => ':attribute არ უნდა აღემატებოდეს :max-ს.',
        'string' => ':attribute არ უნდა აღემატებოდეს :max სიმბოლოს.',
    ],
    'mimes' => ':attribute უნდა იყოს ტიპის: :values.',
    'mimetypes' => ':attribute უნდა იყოს ტიპის: :values.',
    'min' => [
        'array' => ':attribute უნდა შეიცავდეს მინიმუმ :min ელემენტს.',
        'file' => ':attribute უნდა იყოს მინიმუმ :min კილობაიტი.',
        'numeric' => ':attribute უნდა იყოს მინიმუმ :min.',
        'string' => ':attribute უნდა იყოს მინიმუმ :min სიმბოლო.',
    ],
    'numeric' => ':attribute უნდა იყოს რიცხვი.',
    'present' => ':attribute უნდა იყოს გადმოცემული.',
    'prohibited' => ':attribute აკრძალულია.',
    'required' => ':attribute სავალდებულოა.',
    'required_without' => ':attribute სავალდებულოა, როცა :values არ არის მითითებული.',
    'same' => ':attribute და :other უნდა ემთხვეოდეს.',
    'size' => [
        'array' => ':attribute უნდა შეიცავდეს :size ელემენტს.',
        'file' => ':attribute უნდა იყოს :size კილობაიტი.',
        'numeric' => ':attribute უნდა იყოს :size.',
        'string' => ':attribute უნდა იყოს :size სიმბოლო.',
    ],
    'string' => ':attribute უნდა იყოს ტექსტი.',
    'unique' => ':attribute უკვე დაკავებულია.',
    'url' => ':attribute უნდა იყოს სწორი ბმული.',
    'uploaded' => ':attribute ვერ აიტვირთა.',

    'attributes' => [
        'name' => 'სახელი',
        'email' => 'ელ. ფოსტა',
        'password' => 'პაროლი',
        'password_confirmation' => 'პაროლის დადასტურება',
        'file' => 'ფაილი',
        'question' => 'კითხვა',
        'message' => 'შეტყობინება',
        'phone' => 'ტელეფონი',
        'system_prompt' => 'სისტემური ინსტრუქცია',
        'model_tier' => 'მოდელის დონე',
        'dataset_id' => 'დატასეტი',
        'source_name' => 'წყაროს სახელი',
        'description' => 'აღწერა',
        'label' => 'სახელი',
    ],
];
