<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_are_stored_securely()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->create('sensitive_info.pdf', 100);

        $response = $this->actingAs($admin)->post(route('vehicles.documents.store'), [
            'title' => 'Secret Document',
            'document_type' => 'insurance',
            'file' => $file,
            'is_public' => false,
        ]);

        $document = Document::first();
        $this->assertNotNull($document);
        $this->assertTrue(Storage::disk('public')->exists($document->file_path));
    }

    public function test_disallowed_file_types_are_rejected()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        // Attempt to upload an HTML file (potentially malicious)
        $file = UploadedFile::fake()->create('malicious.html', 10, 'text/html');

        $response = $this->actingAs($admin)->post(route('vehicles.documents.store'), [
            'title' => 'Malicious Document',
            'document_type' => 'insurance',
            'file' => $file,
        ]);

        // When validation fails, it redirects back with errors.
        // If it passes validation but fails later, it redirects back with 'error' in session.
        $response->assertStatus(302);

        // If it was a validation error from $request->validate, it should be in session 'errors'
        // If it was caught by our try-catch, it's in session 'error'
        $hasErrors = session()->has('errors') || session()->has('error');
        $this->assertTrue($hasErrors, 'Session should have errors or an error message');

        $this->assertEquals(0, Document::count());
    }

    public function test_unauthorized_user_cannot_access_document_via_controller()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create(['role' => 'driver']);

        $file = UploadedFile::fake()->create('secret.pdf', 100);
        $this->actingAs($admin)->post(route('vehicles.documents.store'), [
            'title' => 'Secret Doc',
            'document_type' => 'insurance',
            'file' => $file,
            'is_public' => false,
        ]);

        $document = Document::first();

        $response = $this->actingAs($driver)->get(route('vehicles.documents.show', $document));
        $response->assertStatus(403);

        $response = $this->actingAs($driver)->get(route('vehicles.documents.download', $document));
        $response->assertStatus(403);
    }
}
