<?php

const API_URL = '/api/tasks';

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('tests index endpoint', function () {
    Task::factory()->count(15)->create();

    $response = $this->getJson(API_URL);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'name', 'description', 'user_id', 'created_at', 'updated_at']
        ],
        'meta',
        'links',
    ]);
});

it('tests store endpoint', function () {
    $user = User::factory()->create();

    $payload = [
        'name' => 'Test Task',
        'description' => 'Test Description',
        'user_id' => $user->id,
    ];

    $response = $this->postJson(API_URL, $payload);

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Test Task');
    $response->assertJsonPath('data.description', 'Test Description');
    $response->assertJsonPath('data.user_id', $user->id);
    $this->assertDatabaseHas('tasks', $payload);
});

it('tests show endpoint', function () {
    $task = Task::factory()->create();

    $response = $this->getJson(API_URL . '/' . $task->id);

    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $task->id);
});

it('tests update endpoint', function () {
    $task = Task::factory()->create();

    $payload = [
        'name' => 'Updated Task',
        'description' => 'Updated Description',
    ];

    $response = $this->putJson(API_URL . '/' . $task->id, $payload);

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'Updated Task');
    $response->assertJsonPath('data.description', 'Updated Description');
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'name' => 'Updated Task',
        'description' => 'Updated Description',
    ]);
});

it('tests destroy endpoint', function () {
    $task = Task::factory()->create();

    $response = $this->deleteJson(API_URL . '/' . $task->id);

    $response->assertStatus(204);
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

it('tests validation for store endpoint', function () {
    $response = $this->postJson(API_URL, []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'description']);
});

it('tests validation for update endpoint', function () {
    $task = Task::factory()->create();

    $response = $this->putJson(API_URL . '/' . $task->id, []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'description']);
});
