<?php

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\Specialization;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        $specializations = [
            [
                'en' => 'Programming & Development',
                'ar' => 'البرمجة والتطوير',
                'children' => [
                    ['en' => 'Web Development', 'ar' => 'تطوير الويب'],
                    ['en' => 'Mobile Development', 'ar' => 'تطوير تطبيقات الجوال'],
                    ['en' => 'Data Science', 'ar' => 'علم البيانات'],
                    ['en' => 'Cyber Security', 'ar' => 'الأمن السيبراني'],
                    ['en' => 'Game Development', 'ar' => 'تطوير الألعاب'],
                ],
            ],
            [
                'en' => 'Languages',
                'ar' => 'اللغات',
                'children' => [
                    ['en' => 'English Language', 'ar' => 'اللغة الإنجليزية'],
                    ['en' => 'Arabic Language', 'ar' => 'اللغة العربية'],
                    ['en' => 'French Language', 'ar' => 'اللغة الفرنسية'],
                    ['en' => 'Turkish Language', 'ar' => 'اللغة التركية'],
                    ['en' => 'German Language', 'ar' => 'اللغة الألمانية'],
                ],
            ],
            [
                'en' => 'Business & Management',
                'ar' => 'الأعمال والإدارة',
                'children' => [
                    ['en' => 'Project Management', 'ar' => 'إدارة المشاريع'],
                    ['en' => 'Human Resources', 'ar' => 'الموارد البشرية'],
                    ['en' => 'Accounting', 'ar' => 'المحاسبة'],
                    ['en' => 'Digital Marketing', 'ar' => 'التسويق الرقمي'],
                    ['en' => 'Entrepreneurship', 'ar' => 'ريادة الأعمال'],
                ],
            ],
            [
                'en' => 'Design & Multimedia',
                'ar' => 'التصميم والوسائط',
                'children' => [
                    ['en' => 'Graphic Design', 'ar' => 'التصميم الجرافيكي'],
                    ['en' => 'UI/UX Design', 'ar' => 'تصميم واجهات المستخدم'],
                    ['en' => 'Video Editing', 'ar' => 'مونتاج الفيديو'],
                    ['en' => 'Photography', 'ar' => 'التصوير الفوتوغرافي'],
                ],
            ],
            [
                'en' => 'Engineering',
                'ar' => 'الهندسة',
                'children' => [
                    ['en' => 'Civil Engineering', 'ar' => 'الهندسة المدنية'],
                    ['en' => 'Mechanical Engineering', 'ar' => 'الهندسة الميكانيكية'],
                    ['en' => 'Electrical Engineering', 'ar' => 'الهندسة الكهربائية'],
                    ['en' => 'Architecture', 'ar' => 'العمارة'],
                ],
            ],
            [
                'en' => 'Health & Medical',
                'ar' => 'الصحة والطب',
                'children' => [
                    ['en' => 'First Aid', 'ar' => 'الإسعافات الأولية'],
                    ['en' => 'Nursing', 'ar' => 'التمريض'],
                    ['en' => 'Nutrition', 'ar' => 'التغذية'],
                ],
            ],
            [
                'en' => 'Personal Development',
                'ar' => 'التنمية الذاتية',
                'children' => [
                    ['en' => 'Public Speaking', 'ar' => 'الخطابة'],
                    ['en' => 'Leadership', 'ar' => 'القيادة'],
                    ['en' => 'Time Management', 'ar' => 'إدارة الوقت'],
                ],
            ],
        ];

        foreach ($specializations as $specializationData) {
            $specialization = Specialization::query()->create([
                'parent_id' => null,
            ]);

            $specialization->translateOrNew('en')->title = $specializationData['en'];
            $specialization->translateOrNew('ar')->title = $specializationData['ar'];
            $specialization->save();

            foreach ($specializationData['children'] as $childData) {
                $child = Specialization::query()->create([
                    'parent_id' => $specialization->id,
                ]);

                $child->translateOrNew('en')->title = $childData['en'];
                $child->translateOrNew('ar')->title = $childData['ar'];
                $child->save();
            }
        }

        $this->command?->info('Created '.count($specializations).' root specializations with children.');
    }
}
