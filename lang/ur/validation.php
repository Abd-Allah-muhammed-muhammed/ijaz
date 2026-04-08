<?php

return [

    /*
  |--------------------------------------------------------------------------
  | تصدیق کے پیغامات کی سطریں
  |--------------------------------------------------------------------------
  |
  | درج ذیل زبان کی سطریں تصدیق کلاس میں استعمال ہونے والے
  | ڈیفالٹ ایرر میسجز ہیں۔ آپ ان کو اپنی ایپلیکیشن کی ضروریات کے مطابق
  | تبدیل کر سکتے ہیں۔
  |
  */

    'accepted' => ':attribute کو قبول کرنا ضروری ہے۔',
    'accepted_if' => ':attribute کو قبول کرنا ضروری ہے جب :other کی قیمت :value ہو۔',
    'active_url' => ':attribute ایک درست URL ہونا چاہیے۔',
    'after' => ':attribute کی تاریخ :date کے بعد کی ہونی چاہیے۔',
    'after_or_equal' => ':attribute کی تاریخ :date کے بعد یا اس کے برابر ہونی چاہیے۔',
    'alpha' => ':attribute میں صرف حروف ہونے چاہییں۔',
    'alpha_dash' => ':attribute میں صرف حروف، اعداد، ڈیشز اور انڈر اسکورز ہونے چاہییں۔',
    'alpha_num' => ':attribute میں صرف حروف اور اعداد ہونے چاہییں۔',
    'array' => ':attribute ایک صف (array) ہونی چاہیے۔',

    'before' => ':attribute کی تاریخ :date سے پہلے ہونی چاہیے۔',
    'before_or_equal' => ':attribute کی تاریخ :date سے پہلے یا اس کے برابر ہونی چاہیے۔',

    'between' => [
        'numeric' => ':attribute کی قیمت :min اور :max کے درمیان ہونی چاہیے۔',
        'file' => ':attribute کا سائز :min اور :max کلو بائٹس کے درمیان ہونا چاہیے۔',
        'string' => ':attribute میں :min سے :max حروف ہونے چاہییں۔',
        'array' => ':attribute میں :min سے :max آئٹمز ہونے چاہییں۔',
    ],

    'boolean' => ':attribute صحیح یا غلط ہونا چاہیے۔',
    'confirmed' => ':attribute کی تصدیق مماثل نہیں ہے۔',
    'date' => ':attribute ایک درست تاریخ ہونی چاہیے۔',
    'date_equals' => ':attribute کی تاریخ :date کے برابر ہونی چاہیے۔',
    'date_format' => ':attribute فارمیٹ :format سے میل نہیں کھاتا۔',
    'different' => ':attribute اور :other مختلف ہونے چاہییں۔',
    'digits' => ':attribute میں :digits ہندسے ہونے چاہییں۔',
    'digits_between' => ':attribute میں :min سے :max ہندسے ہونے چاہییں۔',
    'email' => ':attribute ایک درست ای میل پتہ ہونا چاہیے۔',
    'exists' => 'منتخب کردہ :attribute درست نہیں ہے۔',
    'file' => ':attribute ایک فائل ہونی چاہیے۔',
    'filled' => ':attribute کا خانہ لازمی پر ہونا چاہیے۔',
    'image' => ':attribute ایک تصویر ہونی چاہیے۔',
    'in' => 'منتخب کردہ :attribute درست نہیں ہے۔',
    'integer' => ':attribute ایک صحیح عدد ہونا چاہیے۔',
    'ip' => ':attribute ایک درست IP ایڈریس ہونا چاہیے۔',
    'json' => ':attribute ایک درست JSON سٹرنگ ہونی چاہیے۔',

    'max' => [
        'numeric' => ':attribute کی قیمت :max سے زیادہ نہیں ہو سکتی۔',
        'file' => ':attribute کا سائز :max کلو بائٹس سے زیادہ نہیں ہو سکتا۔',
        'string' => ':attribute :max حروف سے زیادہ نہیں ہو سکتا۔',
        'array' => ':attribute میں :max سے زیادہ آئٹمز نہیں ہو سکتے۔',
    ],

    'min' => [
        'numeric' => ':attribute کی قیمت کم از کم :min ہونی چاہیے۔',
        'file' => ':attribute کا سائز کم از کم :min کلو بائٹس ہونا چاہیے۔',
        'string' => ':attribute میں کم از کم :min حروف ہونے چاہییں۔',
        'array' => ':attribute میں کم از کم :min آئٹمز ہونے چاہییں۔',
    ],

    'not_in' => 'منتخب کردہ :attribute درست نہیں ہے۔',
    'numeric' => ':attribute ایک عدد ہونا چاہیے۔',
    'required' => ':attribute کا خانہ لازمی ہے۔',
    'same' => ':attribute اور :other ایک جیسے ہونے چاہییں۔',

    'size' => [
        'numeric' => ':attribute کی قیمت :size ہونی چاہیے۔',
        'file' => ':attribute کا سائز :size کلو بائٹس ہونا چاہیے۔',
        'string' => ':attribute میں :size حروف ہونے چاہییں۔',
        'array' => ':attribute میں :size آئٹمز ہونے چاہییں۔',
    ],

    'string' => ':attribute ایک سٹرنگ ہونی چاہیے۔',
    'unique' => ':attribute پہلے سے موجود ہے۔',
    'url' => ':attribute کا فارمیٹ غلط ہے۔',

    /*
  |--------------------------------------------------------------------------
  | حسب ضرورت فیلڈ نام
  |--------------------------------------------------------------------------
  |
  | یہاں آپ فیلڈ ناموں کے لیے صارف دوست ترجمے مہیا کر سکتے ہیں۔
  |
  */

    'attributes' => [],

];
