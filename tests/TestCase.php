<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $legacyNames = [
            'BankTest',
            'BranchTest',
            'MaterialTest',
            'PropertyTest',
            'PropertyTypeTest',
            'RentalTest',
            'SupplierTest',
            'UnitTest',
            'WorkerCategoryTest',
        ];

        foreach ($legacyNames as $name) {
            if (str_ends_with(static::class, $name)) {
                $user = User::factory()->create([
                    'id' => 1,
                    'name' => 'Owner Admin',
                    'email' => 'owner@deiveni.com',
                    'role' => UserRole::Owner,
                ]);
                $this->actingAs($user);
                break;
            }
        }
    }
}
