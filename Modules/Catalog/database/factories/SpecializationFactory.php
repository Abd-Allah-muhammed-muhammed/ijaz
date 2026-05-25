<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Specialization;

/**
 * @extends Factory<Specialization>
 */
class SpecializationFactory extends Factory
{
    protected $model = Specialization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'icon' => null,
        ];
    }

    /**
     * Configure the model factory to create translations after model creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Specialization $specialization) {
            $specialization->translations()->createMany([
                [
                    'locale' => 'en',
                    'title' => fake()->unique()->randomElement([
                        'Web Development',
                        'Mobile Development',
                        'Data Science',
                        'Graphic Design',
                        'Digital Marketing',
                        'Project Management',
                        'English Language',
                        'Arabic Language',
                        'Accounting',
                        'Cyber Security',
                    ]).' '.fake()->unique()->numerify('##'),
                ],
                [
                    'locale' => 'ar',
                    'title' => fake()->unique()->randomElement([
                        'تطوير الويب',
                        'تطوير تطبيقات الجوال',
                        'علم البيانات',
                        'تصميم جرافيك',
                        'تسويق رقمي',
                        'إدارة المشاريع',
                        'اللغة الإنجليزية',
                        'اللغة العربية',
                        'محاسبة',
                        'الأمن السيبراني',
                    ]).' '.fake()->unique()->numerify('##'),
                ],
            ]);
        });
    }
}
