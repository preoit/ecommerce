<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_account_with_order_history_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        \Illuminate\Support\Facades\DB::table('orders')->insert([
            'order_number' => '#LOCKED', 'user_id' => $user->id, 'customer_name' => $user->name,
            'phone' => '01712345678', 'address' => 'Dhaka', 'city' => 'Dhaka', 'payment_method' => 'cod',
            'payment_status' => 'paid', 'status' => 'completed', 'subtotal' => 100, 'shipping_total' => 0,
            'total' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertStatus(422);
        $this->assertNotNull($user->fresh());
    }

    public function test_phone_otp_can_be_sent_and_verified(): void
    {
        \Illuminate\Support\Facades\Http::fake(['msg.mram.com.bd/*' => \Illuminate\Support\Facades\Http::response('12345', 200)]);
        config(['services.mram.api_key' => 'test-key']);
        $user = User::factory()->create(['is_admin' => false, 'phone' => '01712345678', 'phone_verified_at' => null]);
        $this->actingAs($user)->post(route('phone.verification.send'))->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertNotNull($user->phone_verification_code);
        $this->assertNotNull($user->phone_verification_expires_at);
        \Illuminate\Support\Facades\Http::assertSent(fn ($request) => $request['contacts'] === '8801712345678' && $request['label'] === 'transactional');
    }}
