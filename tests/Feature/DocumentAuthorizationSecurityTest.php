<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = User::factory()->create([
            'role' => 'driver',
        ]);
    }

    /**
     * Test that a driver cannot access the create document page.
     */
    public function test_driver_cannot_access_create_page()
    {
        $response = $this->actingAs($this->driver)->get(route('documents.create'));

        $response->assertStatus(403);
    }

    /**
     * Test that a driver cannot access document statistics.
     */
    public function test_driver_cannot_access_statistics()
    {
        $response = $this->actingAs($this->driver)->get(route('documents.statistics'));

        $response->assertStatus(403);
    }

    /**
     * Test that a driver cannot access expiring documents.
     */
    public function test_driver_cannot_access_expiring_soon()
    {
        $response = $this->actingAs($this->driver)->get(route('documents.expiring'));

        $response->assertStatus(403);
    }

    /**
     * Test that a driver cannot access bulk actions.
     */
    public function test_driver_cannot_access_bulk_action()
    {
        $document = Document::create([
            'title' => 'Public Doc',
            'slug' => 'public-doc',
            'file_path' => 'test.pdf',
            'file_name' => 'test.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf',
            'is_public' => true,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->driver)->post(route('documents.bulk-action'), [
            'action' => 'archive',
            'documents' => [$document->id]
        ]);

        $response->assertStatus(403);
        $this->assertEquals('active', $document->fresh()->status);
    }

    /**
     * Test that a driver cannot edit a public document.
     */
    public function test_driver_cannot_edit_public_document()
    {
        $document = Document::create([
            'title' => 'Public Doc',
            'slug' => 'public-doc',
            'file_path' => 'test.pdf',
            'file_name' => 'test.pdf',
            'file_type' => 'application/pdf',
            'extension' => 'pdf',
            'is_public' => true
        ]);

        $response = $this->actingAs($this->driver)->get(route('documents.edit', $document));

        $response->assertStatus(403);
    }
}
