<?php

use App\Enums\Advisements\AdvisementStatusEnum;
use App\Http\Controllers\Dashboard\CarAdvisementController as DashboardCarAdvisementController;
use App\Models\Admin;
use App\Models\CarAdvisement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

test('admin can update car advisement status', function () {
    if (! Schema::hasTable('media')) {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->uuidMorphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestamps();
        });
    }

    $admin = Admin::query()->create([
        'name' => 'Test Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $carAdvisement = CarAdvisement::factory()->pending()->create();

    $this
        ->actingAs($admin, 'admin')
        ->put(action([DashboardCarAdvisementController::class, 'update'], $carAdvisement), [
            'status' => AdvisementStatusEnum::PUBLISHED->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($carAdvisement->refresh()->status)->toBe(AdvisementStatusEnum::PUBLISHED);
});
