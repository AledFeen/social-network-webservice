<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Complaint;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ComplaintControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_complaint(): void
    {
        $user = User::factory()->create(['role' => 1]);
        $user1 = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user1->id
        ]);

        $complaint = Complaint::factory()->create([
            'sender_id' => $user1->id,
            'post_id' => $post->id
        ]);

        $this->assertDatabaseCount('complaints', 1);

        $response = $this->actingAs($user)->get("/api/complaint?complaint_id={$complaint->id}");

        $expectedData = [
            'data' => [
                'id' => $complaint->id,
                'sender_id' => $user1->id,
                'post_id' => $post->id,
            ]
        ];

        $response->assertStatus(200)
            ->assertJson($expectedData);
    }

    public function test_get_complaints(): void
    {
        $user = User::factory()->create(['role' => 1]);
        $user1 = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user1->id
        ]);

        $complaint = Complaint::factory()->create([
            'sender_id' => $user1->id,
            'post_id' => $post->id,
            'created_at' => '2024-10-16',
        ]);

        $complaint1 = Complaint::factory()->create([
            'sender_id' => $user1->id,
            'post_id' => $post->id,
            'created_at' => '2024-10-16',
        ]);

        $expectedData = [
            [
                'id' => $complaint->id,
                'sender_id' => $user1->id,
                'post_id' => $post->id,
            ],
            [
                'id' => $complaint1->id,
                'sender_id' => $user1->id,
                'post_id' => $post->id,
            ]
        ];

        $response = $this->actingAs($user)->get("/api/complaints?type=post&date=2024-10-16&status=non-checked&page_id=1");
        $response->assertStatus(200)
            ->assertJsonFragment($expectedData[0])
            ->assertJsonFragment($expectedData[1])
            ->assertJsonFragment(['current_page' => 1])
            ->assertJsonFragment(['last_page' => 1])
            ->assertJsonFragment(['total' => 2]);
        //dump($response->getContent());
    }

    public function test_create_complaint(): void
    {
        $user = User::factory()->create(['role' => 0]);
        $user1 = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user1->id
        ]);

        $response = $this->actingAs($user)->post('/api/complaint', ['post_id' => $post->id, 'text' => 'abrabraabrabra']);
        $response->assertStatus(201);
    }

    public function test_update_complaint(): void
    {
        $user = User::factory()->create(['role' => 1]);
        $user1 = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user1->id
        ]);

        $complaint = Complaint::factory()->create([
            'sender_id' => $user1->id,
            'post_id' => $post->id
        ]);

        $response = $this->actingAs($user)->put('/api/complaint',
            [
                'complaint_id' => $complaint->id,
                'measure_status' => 'accepted',
                'measure' => 'text'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('complaints', ['measure' => 'text']);
    }

    public function test_update_complaint_no_rights(): void
    {
        User::factory()->create(['role' => 1]);
        $user1 = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user1->id
        ]);

        $complaint = Complaint::factory()->create([
            'sender_id' => $user1->id,
            'post_id' => $post->id
        ]);

        $response = $this->actingAs($user1)->put('/api/complaint',
            [
                'complaint_id' => $complaint->id,
                'measure_status' => 'accepted',
                'measure' => 'text'
            ]);

        $response->assertStatus(400);
    }

}
