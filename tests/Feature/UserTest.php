<?php


use App\Models\PrivacySettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    public function test_user_observer_created()
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('privacy_settings', ['user_id' => $user->id])
        ->assertDatabaseHas('accounts', ['user_id' => $user->id]);
    }

    public function test_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_role() : void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->role == 'user');
    }

    public function test_admin_role() : void
    {
        $user = User::factory()->create(['role' => 1]);

        $this->assertTrue($user->role == 'admin');
    }

    public function test_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@test.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertStatus(302);
        $this->assertGuest();
    }

    /*
    / it sends email confirm letter
    /
    public function test_registration():void
    {
        $response = $this->post('/register',
            [
               'name' => 'test',
               'email' => 'test@gmail.com',
               'password' => '12344321',
               'password_confirmation' => '12344321'
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'name' => 'test',
            'email' => 'test@gmail.com',
        ]);

        $user = User::where('name', 'test')->first();

        $this->assertAuthenticatedAs($user)
            ->assertDatabaseHas('privacy_settings', ['user_id' => $user->id])
            ->assertDatabaseHas('accounts', ['user_id' => $user->id]);
    }
    */

}
