<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
test('admin is blocked from vendor modules while vendor can access them', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $vendor = User::factory()->create(['role' => 'vendor']);
    foreach (['products', 'categories', 'coupons'] as $entity) {
        $this->actingAs($admin)->get('/manage/'.$entity)->assertForbidden();
        $this->get('/manage/'.$entity.'/create')->assertForbidden();
        $this->post('/manage/'.$entity, [])->assertForbidden();
        $this->actingAs($vendor)->get('/manage/'.$entity)->assertOk();
        $this->get('/manage/'.$entity.'/create')->assertOk();
    }
    $this->actingAs($admin)->get('/orders')->assertForbidden();
    $this->get('/manage/users')->assertOk();
    $this->get('/settings')->assertOk();
    $token = $admin->createToken('test', ['commerce'])->plainTextToken;
    $this->withToken($token)->getJson('/api/v1/orders')->assertForbidden();
    $this->actingAs($vendor)->get('/orders')->assertOk();
    $this->get('/manage/users')->assertForbidden();
});
