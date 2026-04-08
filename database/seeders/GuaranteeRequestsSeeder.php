<?php

namespace Database\Seeders;

use App\Enums\GuaranteeRequest\GuaranteeRequestStatusEnum;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GuaranteeRequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::pluck('id')->toArray();
        $providers = Provider::pluck('id')->toArray();

        if (empty($users) || empty($providers)) {
            $this->command->error('No users or providers found. Please seed users and providers first.');

            return;
        }

        $statuses = GuaranteeRequestStatusEnum::cases();

        $this->command->info('Creating 10,000 guarantee requests...');

        $chunkSize = 500;
        $total = 10000;

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        for ($i = 0; $i < $total; $i += $chunkSize) {
            $records = [];

            for ($j = 0; $j < $chunkSize && ($i + $j) < $total; $j++) {
                $amount = fake()->randomFloat(2, 100, 5000);
                $fees = fake()->randomFloat(2, 10, 500);

                $records[] = [
                    'user_type' => User::class,
                    'user_id' => fake()->randomElement($users),
                    'provider_type' => Provider::class,
                    'provider_id' => fake()->randomElement($providers),
                    'description' => fake()->paragraph(),
                    'status' => fake()->randomElement($statuses)->value,
                    'amount' => $amount,
                    'fees' => $fees,
                    'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
                    'updated_at' => now(),
                ];
            }

            DB::table('guarantee_requests')->insert($records);
            $bar->advance($chunkSize);
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Successfully created 10,000 guarantee requests!');
    }
}
