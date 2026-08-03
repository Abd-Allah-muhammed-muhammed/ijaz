<?php

namespace App\Console\Commands;

use App\DTOs\Admin\CreateAdminAccountDTO;
use App\Models\Admin;
use App\Services\Admin\AdminManagementService;
use App\Services\Admin\RoleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('admin:create')]
#[Description('Interactively create an admin account (root or role-assigned) without storing passwords in seeders')]
class CreateAdminCommand extends Command
{
    public function handle(
        AdminManagementService $adminManagementService,
        RoleService $roleService,
    ): int {
        $name = text(
            label: 'Name',
            required: 'Name is required.',
        );

        $email = text(
            label: 'Email',
            required: 'Email is required.',
            validate: function (string $value): ?string {
                $validator = Validator::make(
                    ['email' => $value],
                    ['email' => ['email']],
                );

                if ($validator->fails()) {
                    return 'Please enter a valid email address.';
                }

                return null;
            },
        );

        $phone = text(
            label: 'Phone',
            required: 'Phone is required.',
        );

        $password = password(
            label: 'Password',
            required: 'Password is required.',
        );

        $passwordConfirmation = password(
            label: 'Confirm password',
            required: 'Password confirmation is required.',
        );

        if ($password !== $passwordConfirmation) {
            $this->error('Password confirmation does not match.');

            return self::FAILURE;
        }

        if ($adminManagementService->emailExists($email)) {
            $this->error('An admin with this email already exists.');

            return self::FAILURE;
        }

        if ($adminManagementService->phoneExists($phone)) {
            $this->error('An admin with this phone already exists.');

            return self::FAILURE;
        }

        $isRoot = confirm(
            label: 'Is this a root account?',
            default: false,
        );

        $roleName = null;

        if (! $isRoot) {
            $roles = $roleService->getAllForDropdown()
                ->sortBy('name')
                ->pluck('name')
                ->values()
                ->all();

            if ($roles === []) {
                $this->error('No admin-guard roles found.');
                $this->line('Run RolePermissionSeeder first: php artisan db:seed --class=RolePermissionSeeder');

                return self::FAILURE;
            }

            $roleName = select(
                label: 'Select a role',
                options: $roles,
            );
        }

        try {
            $admin = $adminManagementService->createAccount(new CreateAdminAccountDTO(
                name: $name,
                email: $email,
                phone: $phone,
                password: $password,
                isRoot: $isRoot,
                roleName: $roleName,
            ));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($this->successMessage($admin, $isRoot, $roleName));
        $this->warn(
            'Password was set interactively and is not recoverable from the application — use the password you just entered.'
        );

        return self::SUCCESS;
    }

    private function successMessage(Admin $admin, bool $isRoot, ?string $roleName): string
    {
        $roleLabel = $isRoot
            ? 'root'.($admin->hasRole('super-admin') ? ' (super-admin)' : '')
            : ($roleName ?? 'none');

        return "Admin [{$admin->name}] <{$admin->email}> created with role: {$roleLabel}.";
    }
}
