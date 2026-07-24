<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Call;
use App\Events\IncomingCall;
use App\Events\CallAccepted;
use App\Events\CallRejected;
use App\Events\CallEnded;
use App\Events\OfferCreated;
use App\Events\AnswerCreated;
use App\Events\IceCandidate;
use App\Events\UserBusy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WeRtcCallTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin and driver users
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@gwc.com',
        ]);

        $this->driver = User::factory()->create([
            'role' => 'driver',
            'name' => 'Driver User',
            'email' => 'driver@gwc.com',
        ]);
    }

    /**
     * Test starting a call successfully.
     */
    public function test_user_can_start_call_and_dispatches_incoming_call_event()
    {
        Event::fake([IncomingCall::class]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('calls.start'), [
                'receiver_id' => $this->driver->id,
                'call_type' => 'video',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'call']);

        $this->assertDatabaseHas('calls', [
            'caller_id' => $this->admin->id,
            'receiver_id' => $this->driver->id,
            'call_type' => 'video',
            'status' => 'calling',
        ]);

        Event::assertDispatched(IncomingCall::class, function ($event) {
            return $event->recipientId === $this->driver->id && $event->call->call_type === 'video';
        });
    }

    /**
     * Test accepting a call successfully.
     */
    public function test_user_can_accept_call()
    {
        Event::fake([CallAccepted::class]);

        $call = Call::create([
            'caller_id' => $this->admin->id,
            'receiver_id' => $this->driver->id,
            'call_type' => 'audio',
            'status' => 'calling',
        ]);

        $response = $this->actingAs($this->driver)
            ->postJson(route('calls.accept'), [
                'call_id' => $call->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => 'connected',
        ]);

        Event::assertDispatched(CallAccepted::class, function ($event) {
            return $event->recipientId === $this->admin->id;
        });
    }

    /**
     * Test rejecting a call.
     */
    public function test_user_can_reject_call()
    {
        Event::fake([CallRejected::class]);

        $call = Call::create([
            'caller_id' => $this->admin->id,
            'receiver_id' => $this->driver->id,
            'call_type' => 'audio',
            'status' => 'calling',
        ]);

        $response = $this->actingAs($this->driver)
            ->postJson(route('calls.reject'), [
                'call_id' => $call->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => 'rejected',
        ]);

        Event::assertDispatched(CallRejected::class, function ($event) {
            return $event->recipientId === $this->admin->id;
        });
    }

    /**
     * Test busy call.
     */
    public function test_user_can_send_busy_signal()
    {
        Event::fake([UserBusy::class]);

        $call = Call::create([
            'caller_id' => $this->admin->id,
            'receiver_id' => $this->driver->id,
            'call_type' => 'audio',
            'status' => 'calling',
        ]);

        $response = $this->actingAs($this->driver)
            ->postJson(route('calls.busy'), [
                'call_id' => $call->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        Event::assertDispatched(UserBusy::class);
    }

    /**
     * Test ending a call and calculating duration.
     */
    public function test_user_can_end_call_and_calculates_duration()
    {
        Event::fake([CallEnded::class]);

        $startedAt = now()->subSeconds(45);
        $call = Call::create([
            'caller_id' => $this->admin->id,
            'receiver_id' => $this->driver->id,
            'call_type' => 'video',
            'status' => 'connected',
            'started_at' => $startedAt,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('calls.end'), [
                'call_id' => $call->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $freshCall = Call::findOrFail($call->id);
        $this->assertEquals('ended', $freshCall->status);
        $this->assertGreaterThanOrEqual(44, $freshCall->duration);

        Event::assertDispatched(CallEnded::class);
    }

    /**
     * Test WebRTC signal relay.
     */
    public function test_user_can_send_webrtc_signals()
    {
        Event::fake([OfferCreated::class, AnswerCreated::class, IceCandidate::class]);

        $call = Call::create([
            'caller_id' => $this->admin->id,
            'receiver_id' => $this->driver->id,
            'call_type' => 'video',
            'status' => 'connected',
        ]);

        // Offer
        $response = $this->actingAs($this->admin)
            ->postJson(route('signals.offer'), [
                'call_id' => $call->id,
                'offer' => 'sdp-offer-string',
                'recipient_id' => $this->driver->id,
            ]);
        $response->assertStatus(200);
        Event::assertDispatched(OfferCreated::class);

        // Answer
        $response = $this->actingAs($this->driver)
            ->postJson(route('signals.answer'), [
                'call_id' => $call->id,
                'answer' => 'sdp-answer-string',
                'recipient_id' => $this->admin->id,
            ]);
        $response->assertStatus(200);
        Event::assertDispatched(AnswerCreated::class);

        // ICE Candidate
        $response = $this->actingAs($this->admin)
            ->postJson(route('signals.ice-candidate'), [
                'call_id' => $call->id,
                'candidate' => 'ice-candidate-payload',
                'recipient_id' => $this->driver->id,
            ]);
        $response->assertStatus(200);
        Event::assertDispatched(IceCandidate::class);
    }

    /**
     * Test contacts fetching based on role.
     */
    public function test_contacts_endpoint_returns_correct_roles()
    {
        // For admin, we should get drivers
        $response = $this->actingAs($this->admin)
            ->getJson(route('calls.contacts'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['role' => 'driver', 'name' => 'Driver User']);

        // For driver, we should get admins
        $response = $this->actingAs($this->driver)
            ->getJson(route('calls.contacts'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['role' => 'admin', 'name' => 'Admin User']);
    }
}
