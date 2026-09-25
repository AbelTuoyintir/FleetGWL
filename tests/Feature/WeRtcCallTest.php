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
        $anotherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Two']);
        $anotherDriver = User::factory()->create(['role' => 'driver', 'name' => 'Driver Two']);
        $dispatcher = User::factory()->create(['role' => 'dispatcher', 'name' => 'Dispatcher One']);

        // For admin, we should get drivers (not other admins or dispatchers)
        $response = $this->actingAs($this->admin)
            ->getJson(route('calls.contacts'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['role' => 'driver', 'name' => 'Driver User'])
            ->assertJsonMissing(['name' => 'Admin Two'])
            ->assertJsonMissing(['name' => 'Dispatcher One']);

        // For driver, we should get admins and other drivers
        $response = $this->actingAs($this->driver)
            ->getJson(route('calls.contacts'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['role' => 'admin', 'name' => 'Admin User'])
            ->assertJsonFragment(['role' => 'driver', 'name' => 'Driver Two'])
            ->assertJsonMissing(['name' => 'Driver User']) // Excludes self
            ->assertJsonMissing(['name' => 'Dispatcher One']);

        // For dispatcher, finance, technician, auditor - empty contacts list
        $response = $this->actingAs($dispatcher)
            ->getJson(route('calls.contacts'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'contacts');
    }

    /**
     * Test allowed call relationship pairs: Admin->Driver, Driver->Admin, Driver->Driver.
     */
    public function test_allowed_call_relationships()
    {
        $driver2 = User::factory()->create(['role' => 'driver']);

        // 1. Admin -> Driver
        $response = $this->actingAs($this->admin)
            ->postJson(route('calls.start'), [
                'receiver_id' => $this->driver->id,
                'call_type' => 'audio',
            ]);
        $response->assertStatus(200)->assertJsonPath('success', true);

        // 2. Driver -> Admin
        $response = $this->actingAs($this->driver)
            ->postJson(route('calls.start'), [
                'receiver_id' => $this->admin->id,
                'call_type' => 'audio',
            ]);
        $response->assertStatus(200)->assertJsonPath('success', true);

        // 3. Driver -> Driver
        $response = $this->actingAs($this->driver)
            ->postJson(route('calls.start'), [
                'receiver_id' => $driver2->id,
                'call_type' => 'video',
            ]);
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    /**
     * Test disallowed call relationship pairs are rejected with 403 Forbidden.
     */
    public function test_disallowed_call_relationships_are_rejected()
    {
        $anotherAdmin = User::factory()->create(['role' => 'admin']);
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);
        $finance = User::factory()->create(['role' => 'finance']);
        $technician = User::factory()->create(['role' => 'technician']);
        $auditor = User::factory()->create(['role' => 'auditor']);

        $disallowedPairs = [
            [$this->admin, $anotherAdmin],     // Admin -> Admin
            [$dispatcher, $this->admin],       // Dispatcher -> Admin
            [$dispatcher, $this->driver],      // Dispatcher -> Driver
            [$dispatcher, $dispatcher],        // Dispatcher -> Dispatcher
            [$finance, $this->admin],          // Finance -> Admin
            [$finance, $this->driver],         // Finance -> Driver
            [$finance, $finance],              // Finance -> Finance
            [$technician, $this->admin],       // Technician -> Admin
            [$technician, $this->driver],      // Technician -> Driver
            [$technician, $technician],        // Technician -> Technician
            [$auditor, $this->admin],          // Auditor -> Admin
            [$auditor, $this->driver],         // Auditor -> Driver
            [$auditor, $auditor],              // Auditor -> Auditor
        ];

        foreach ($disallowedPairs as [$caller, $receiver]) {
            $response = $this->actingAs($caller)
                ->postJson(route('calls.start'), [
                    'receiver_id' => $receiver->id,
                    'call_type' => 'audio',
                ]);

            $response->assertStatus(403)
                ->assertJsonPath('success', false);
        }
    }

    /**
     * Test that unauthorized users or invalid call relationships cannot perform accept, reject, busy, end, or signals.
     */
    public function test_unauthorized_user_cannot_manipulate_call_or_signals()
    {
        $thirdUser = User::factory()->create(['role' => 'driver']);
        $anotherAdmin = User::factory()->create(['role' => 'admin']);

        // Valid call between Admin and Driver
        $validCall = Call::create([
            'caller_id' => $this->admin->id,
            'receiver_id' => $this->driver->id,
            'call_type' => 'video',
            'status' => 'calling',
        ]);

        // 1. Third party driver cannot accept, reject, or end valid call
        $this->actingAs($thirdUser)
            ->postJson(route('calls.accept'), ['call_id' => $validCall->id])
            ->assertStatus(403);

        $this->actingAs($thirdUser)
            ->postJson(route('calls.reject'), ['call_id' => $validCall->id])
            ->assertStatus(403);

        $this->actingAs($thirdUser)
            ->postJson(route('calls.end'), ['call_id' => $validCall->id])
            ->assertStatus(403);

        // 2. Caller (Admin) cannot accept the call (only receiver Driver can accept)
        $this->actingAs($this->admin)
            ->postJson(route('calls.accept'), ['call_id' => $validCall->id])
            ->assertStatus(403);

        // 3. Signal with spoofed recipient_id is rejected
        $this->actingAs($this->admin)
            ->postJson(route('signals.offer'), [
                'call_id' => $validCall->id,
                'offer' => 'sdp-offer',
                'recipient_id' => $thirdUser->id, // Incorrect recipient
            ])
            ->assertStatus(403);

        // 4. Call created directly in DB between disallowed roles (e.g. Admin->Admin) cannot be accepted/signaled
        $invalidCall = Call::create([
            'caller_id' => $this->admin->id,
            'receiver_id' => $anotherAdmin->id,
            'call_type' => 'audio',
            'status' => 'calling',
        ]);

        $this->actingAs($anotherAdmin)
            ->postJson(route('calls.accept'), ['call_id' => $invalidCall->id])
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->postJson(route('signals.offer'), [
                'call_id' => $invalidCall->id,
                'offer' => 'sdp-offer',
                'recipient_id' => $anotherAdmin->id,
            ])
            ->assertStatus(403);
    }
}
