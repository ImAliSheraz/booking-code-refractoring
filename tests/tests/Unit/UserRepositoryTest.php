<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Repository\UserRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    public function testCreateOrUpdateCreatesNewUser()
    {
        // Arrange
        $userData = [
            'role' => config('app.user.customer_role'),
            'name' => 'Ali Sheraz',
            'company_id' => '',
            'department_id' => '',
            'email' => 'ali.sheraz@yopmail.com',
            'phone' => '+92312333434',
            'mobile' => '+92312333434',
            'password' => 'Demo@123',
            'consumer_type' => 'paid',
            'customer_type' => 'individual',
            'username' => 'ali_sheraz'
        ];

        $user = $this->repository->createOrUpdate(null, $userData);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name']
        ]);
    }
}
