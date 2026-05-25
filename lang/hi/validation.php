<?php

return [

    /*
  |--------------------------------------------------------------------------
  | सत्यापन (Validation) भाषा पंक्तियाँ
  |--------------------------------------------------------------------------
  |
  | निम्न पंक्तियाँ डिफ़ॉल्ट त्रुटि संदेश हैं जो सत्यापन क्लास द्वारा उपयोग की जाती हैं।
  | आप इन्हें अपनी एप्लिकेशन की आवश्यकताओं के अनुसार संशोधित कर सकते हैं।
  |
  */

    'accepted' => 'फ़ील्ड :attribute को स्वीकार करना आवश्यक है।',
    'accepted_if' => 'जब :other का मान :value हो, तो :attribute को स्वीकार करना आवश्यक है।',
    'active_url' => ':attribute एक मान्य URL होना चाहिए।',
    'after' => ':attribute :date के बाद की एक तिथि होनी चाहिए।',
    'after_or_equal' => ':attribute :date के बाद या उसके बराबर की एक तिथि होनी चाहिए।',
    'alpha' => ':attribute में केवल अक्षर होने चाहिए।',
    'alpha_dash' => ':attribute में केवल अक्षर, अंक, डैश और अंडरस्कोर हो सकते हैं।',
    'alpha_num' => ':attribute में केवल अक्षर और अंक हो सकते हैं।',
    'array' => ':attribute एक एरे होना चाहिए।',

    'before' => ':attribute :date से पहले की एक तिथि होनी चाहिए।',
    'before_or_equal' => ':attribute :date से पहले या उसके बराबर की एक तिथि होनी चाहिए।',

    'between' => [
        'numeric' => ':attribute की मान :min और :max के बीच होनी चाहिए।',
        'file' => ':attribute का आकार :min और :max किलोबाइट के बीच होना चाहिए।',
        'string' => ':attribute में :min से :max अक्षर होने चाहिए।',
        'array' => ':attribute में :min से :max आइटम होने चाहिए।',
    ],

    'boolean' => ':attribute का मान true या false होना चाहिए।',
    'confirmed' => ':attribute पुष्टिकरण मेल नहीं खा रहा है।',
    'date' => ':attribute एक मान्य तिथि होनी चाहिए।',
    'date_equals' => ':attribute की तिथि :date के बराबर होनी चाहिए।',
    'date_format' => ':attribute का फ़ॉर्मेट :format से मेल नहीं खाता।',
    'different' => ':attribute और :other अलग-अलग होने चाहिए।',
    'digits' => ':attribute में :digits अंक होने चाहिए।',
    'digits_between' => ':attribute में :min से :max अंकों के बीच होना चाहिए।',
    'email' => ':attribute एक मान्य ईमेल पता होना चाहिए।',
    'exists' => ':attribute का चयन अमान्य है।',
    'file' => ':attribute एक फ़ाइल होनी चाहिए।',
    'filled' => ':attribute फ़ील्ड में मान होना चाहिए।',
    'image' => ':attribute एक छवि होनी चाहिए।',
    'in' => ':attribute का चयन अमान्य है।',
    'integer' => ':attribute एक पूर्णांक होना चाहिए।',
    'ip' => ':attribute एक मान्य IP पता होना चाहिए।',
    'json' => ':attribute एक मान्य JSON स्ट्रिंग होनी चाहिए।',

    'max' => [
        'numeric' => ':attribute :max से अधिक नहीं हो सकता।',
        'file' => ':attribute :max किलोबाइट से बड़ा नहीं हो सकता।',
        'string' => ':attribute में :max से अधिक अक्षर नहीं होने चाहिए।',
        'array' => ':attribute में :max से अधिक आइटम नहीं होने चाहिए।',
    ],

    'min' => [
        'numeric' => ':attribute कम से कम :min होना चाहिए।',
        'file' => ':attribute कम से कम :min किलोबाइट होना चाहिए।',
        'string' => ':attribute में कम से कम :min अक्षर होने चाहिए।',
        'array' => ':attribute में कम से कम :min आइटम होने चाहिए।',
    ],

    'not_in' => ':attribute का चयन अमान्य है।',
    'numeric' => ':attribute एक संख्या होनी चाहिए।',
    'required' => ':attribute फ़ील्ड आवश्यक है।',
    'same' => ':attribute और :other मेल खाने चाहिए।',

    'size' => [
        'numeric' => ':attribute का मान :size होना चाहिए।',
        'file' => ':attribute :size किलोबाइट का होना चाहिए।',
        'string' => ':attribute में :size अक्षर होने चाहिए।',
        'array' => ':attribute में :size आइटम होने चाहिए।',
    ],

    'string' => ':attribute एक स्ट्रिंग होनी चाहिए।',
    'unique' => ':attribute पहले ही लिया जा चुका है।',
    'url' => ':attribute का फ़ॉर्मेट अमान्य है।',
    'device_category_cannot_be_own_parent' => 'डिवाइस श्रेणी को स्वयं अपनी मूल श्रेणी के रूप में सेट नहीं किया जा सकता।',

    /*
  |--------------------------------------------------------------------------
  | कस्टम फ़ील्ड नाम
  |--------------------------------------------------------------------------
  */

    'attributes' => [],

];
