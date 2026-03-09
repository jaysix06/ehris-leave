<?php

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->sender = User::factory()->create(['active' => true]);
    $this->receiver = User::factory()->create(['active' => true]);
});

it('can send a message to another user', function () {
    $response = $this->actingAs($this->sender)
        ->postJson('/api/messages', [
            'receiver_id' => $this->receiver->getKey(),
            'body' => 'Hello there!',
        ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'body' => 'Hello there!',
            'mine' => true,
        ]);

    $this->assertDatabaseHas('messages', [
        'sender_id' => $this->sender->getKey(),
        'receiver_id' => $this->receiver->getKey(),
        'body' => 'Hello there!',
    ]);
});

it('cannot send a message to yourself', function () {
    $response = $this->actingAs($this->sender)
        ->postJson('/api/messages', [
            'receiver_id' => $this->sender->getKey(),
            'body' => 'Talking to myself',
        ]);

    $response->assertUnprocessable();
});

it('validates required fields when sending a message', function () {
    $response = $this->actingAs($this->sender)
        ->postJson('/api/messages', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['receiver_id', 'body']);
});

it('can load messages for a conversation', function () {
    Message::create([
        'sender_id' => $this->sender->getKey(),
        'receiver_id' => $this->receiver->getKey(),
        'body' => 'First message',
    ]);

    Message::create([
        'sender_id' => $this->receiver->getKey(),
        'receiver_id' => $this->sender->getKey(),
        'body' => 'Reply message',
    ]);

    $response = $this->actingAs($this->sender)
        ->getJson("/api/messages/{$this->receiver->getKey()}");

    $response->assertSuccessful()
        ->assertJsonCount(2);

    $messages = $response->json();
    expect($messages[0]['body'])->toBe('First message');
    expect($messages[0]['mine'])->toBeTrue();
    expect($messages[1]['body'])->toBe('Reply message');
    expect($messages[1]['mine'])->toBeFalse();
});

it('marks messages as read when loading a conversation', function () {
    Message::create([
        'sender_id' => $this->receiver->getKey(),
        'receiver_id' => $this->sender->getKey(),
        'body' => 'Unread message',
    ]);

    $this->assertDatabaseHas('messages', [
        'receiver_id' => $this->sender->getKey(),
        'read_at' => null,
    ]);

    $this->actingAs($this->sender)
        ->getJson("/api/messages/{$this->receiver->getKey()}")
        ->assertSuccessful();

    $this->assertDatabaseMissing('messages', [
        'sender_id' => $this->receiver->getKey(),
        'receiver_id' => $this->sender->getKey(),
        'read_at' => null,
    ]);
});

it('can list conversations with last message and unread count', function () {
    Message::create([
        'sender_id' => $this->sender->getKey(),
        'receiver_id' => $this->receiver->getKey(),
        'body' => 'Hello!',
    ]);

    Message::create([
        'sender_id' => $this->receiver->getKey(),
        'receiver_id' => $this->sender->getKey(),
        'body' => 'Hi back!',
    ]);

    $response = $this->actingAs($this->sender)
        ->getJson('/api/messages/conversations');

    $response->assertSuccessful();

    $conversations = $response->json();
    expect($conversations)->toHaveCount(1);
    expect($conversations[0]['contact_id'])->toBe($this->receiver->getKey());
    expect($conversations[0]['last_message']['body'])->toBe('Hi back!');
    expect($conversations[0]['unread_count'])->toBe(1);
});

it('can mark messages as read', function () {
    Message::create([
        'sender_id' => $this->receiver->getKey(),
        'receiver_id' => $this->sender->getKey(),
        'body' => 'Read me!',
    ]);

    $response = $this->actingAs($this->sender)
        ->patchJson("/api/messages/{$this->receiver->getKey()}/read");

    $response->assertSuccessful()
        ->assertJsonFragment(['marked' => 1]);

    $this->assertDatabaseMissing('messages', [
        'sender_id' => $this->receiver->getKey(),
        'receiver_id' => $this->sender->getKey(),
        'read_at' => null,
    ]);
});

it('requires authentication for all message endpoints', function () {
    $this->getJson('/api/messages/conversations')->assertUnauthorized();
    $this->getJson('/api/messages/1')->assertUnauthorized();
    $this->postJson('/api/messages', [])->assertUnauthorized();
    $this->patchJson('/api/messages/1/read')->assertUnauthorized();
});
