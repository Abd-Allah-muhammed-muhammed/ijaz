<?php

namespace App\Actions\Provider;

use App\Models\Provider;
use Modules\Marketplace\Models\CategorySkill;
use Modules\Marketplace\Models\ProviderCategory;

final class SyncProviderCategoriesAndSkillsAction
{
    /**
     * @param  list<array{id: int|string, skills?: list<int|string>|null}>  $categoriesInput
     */
    public function handle(Provider $provider, array $categoriesInput): void
    {
        $categories = collect($categoriesInput)->keyBy('id');
        $old_skills = [];
        $provider->categorySkills->each(function (CategorySkill $item) use (&$old_skills) {
            $old_skills[$item->category_id][] = $item->skill_id;
        });
        $provider->providerCategories->each(function (ProviderCategory $providerCategory) use (&$categories, $old_skills, $provider) {
            $new = $categories->get($providerCategory->category_id);
            if (! $new) {
                $providerCategory->delete();
                $provider->categorySkills()
                    ->where('category_id', $providerCategory->category_id)
                    ->delete();

                return;
            }
            $skills = array_values(array_unique($new['skills'] ?? []));
            $old_s = $old_skills[$providerCategory->category_id] ?? [];
            $to_delete = array_diff($old_s, $skills);
            if (! empty($to_delete)) {
                $provider->categorySkills()
                    ->where('category_id', $providerCategory->category_id)
                    ->whereIn('skill_id', $to_delete)
                    ->delete();
            }
            $to_add = array_diff($skills, $old_s);
            if (! empty($to_add)) {
                $provider->categorySkills()->createMany(
                    array_map(static fn ($skill_id) => [
                        'category_id' => $providerCategory->category_id,
                        'skill_id' => $skill_id,
                    ], $to_add
                    ));
            }

            $categories = $categories->forget($providerCategory->category_id);
        });
        if ($categories->isNotEmpty()) {
            foreach ($categories as $cat_id => $item) {
                $provider->providerCategories()->create([
                    'category_id' => $cat_id,
                ]);
                $skills = array_unique($item['skills'] ?? []);
                if (! empty($skills)) {
                    $provider->categorySkills()->createMany(
                        array_map(static fn ($skill_id) => ['category_id' => $cat_id, 'skill_id' => $skill_id], $skills)
                    );
                }
            }
        }
    }
}
