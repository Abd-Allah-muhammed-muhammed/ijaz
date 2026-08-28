<?php

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\Bank;

class BanksSeeder extends Seeder
{
    /**
     * @var list<array{
     *     en: string,
     *     ar: string,
     *     hi: string,
     *     ur: string
     * }>
     */
    private const array BANKS = [
        [
            'en' => 'Saudi National Bank',
            'ar' => 'البنك الأهلي السعودي',
            'hi' => 'सऊदी नेशनल बैंक',
            'ur' => 'سعودی نیشنل بینک',
        ],
        [
            'en' => 'Al Rajhi Bank',
            'ar' => 'مصرف الراجحي',
            'hi' => 'अल राजही बैंक',
            'ur' => 'الراجحی بینک',
        ],
        [
            'en' => 'Riyad Bank',
            'ar' => 'بنك الرياض',
            'hi' => 'रियाद बैंक',
            'ur' => 'ریاض بینک',
        ],
        [
            'en' => 'Banque Saudi Fransi',
            'ar' => 'البنك السعودي الفرنسي',
            'hi' => 'बैंक सऊदी फ्रांसी',
            'ur' => 'سعودی فرانسی بینک',
        ],
        [
            'en' => 'Saudi British Bank (SABB)',
            'ar' => 'البنك السعودي البريطاني',
            'hi' => 'सऊदी ब्रिटिश बैंक (SABB)',
            'ur' => 'سعودی برطانوی بینک (SABB)',
        ],
        [
            'en' => 'Arab National Bank',
            'ar' => 'البنك العربي الوطني',
            'hi' => 'अरब नेशनल बैंक',
            'ur' => 'عرب قومی بینک',
        ],
        [
            'en' => 'Bank Aljazira',
            'ar' => 'بنك الجزيرة',
            'hi' => 'बैंक अलजज़ीरा',
            'ur' => 'بینک الجزیرہ',
        ],
        [
            'en' => 'Alinma Bank',
            'ar' => 'مصرف الإنماء',
            'hi' => 'अलिनमा बैंक',
            'ur' => 'الإنماء بینک',
        ],
        [
            'en' => 'Bank Albilad',
            'ar' => 'بنك البلاد',
            'hi' => 'बैंक अलबिलाद',
            'ur' => 'بینک البلاد',
        ],
        [
            'en' => 'Saudi Investment Bank',
            'ar' => 'البنك السعودي للاستثمار',
            'hi' => 'सऊदी इन्वेस्टमेंट बैंक',
            'ur' => 'سعودی انvestment بینک',
        ],
        [
            'en' => 'Gulf International Bank Saudi Arabia',
            'ar' => 'بنك الخليج الدولي - السعودية',
            'hi' => 'गल्फ इंटरनेशनल बैंक सऊदी अरब',
            'ur' => 'خلیجی بین الاقوامی بینک - سعودیہ',
        ],
        [
            'en' => 'STC Bank',
            'ar' => 'بنك إس تي سي',
            'hi' => 'STC बैंक',
            'ur' => 'STC بینک',
        ],
        [
            'en' => 'D360 Bank',
            'ar' => 'بنك دال 360',
            'hi' => 'D360 बैंक',
            'ur' => 'D360 بینک',
        ],
        [
            'en' => 'Vision Bank',
            'ar' => 'بنك فيجن',
            'hi' => 'विज़न बैंक',
            'ur' => 'ویژن بینک',
        ],
    ];

    public function run(): void
    {
        foreach (self::BANKS as $bank) {
            if (Bank::query()->whereTranslation('name', $bank['ar'], 'ar')->exists()) {
                continue;
            }

            Bank::query()->create([
                'is_active' => true,
                'translations' => [
                    'en' => ['name' => $bank['en']],
                    'ar' => ['name' => $bank['ar']],
                    'hi' => ['name' => $bank['hi']],
                    'ur' => ['name' => $bank['ur']],
                ],
            ]);
        }
    }
}
