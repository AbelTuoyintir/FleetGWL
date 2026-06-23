<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Support\SqlDate;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        // Auto-expire documents that have passed their expiry date
        Document::where('is_expired', false)
                ->where('expiry_date', '<', now())
                ->update(['is_expired' => true, 'status' => 'expired']);

        $query = Document::with(['vehicle', 'acknowledgedBy'])
                        ->where('status', '!=', 'deleted')
                        ->latest();

        // Apply filters
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('document_type', $request->type);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('expired')) {
            $query->where('is_expired', filter_var($request->expired, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        // Filter by vehicle if specified
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Show public documents or documents for user's vehicle if not admin
        if (!auth()->user()->isAdmin()) {
            $userVehicleId = auth()->user()->driver?->vehicle?->id;

            $query->where(function($q) use ($userVehicleId) {
                $q->where('is_public', true);
                if ($userVehicleId) {
                    $q->orWhere('vehicle_id', $userVehicleId);
                }
            });
        }

        $documents = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->paginate(20);

        $documentTypes = Document::getDocumentTypes();
        $statuses = ['active', 'expired', 'archived', 'draft'];
        $vehicles = Vehicle::where('status', '!=', 'deleted')->orderBy('registration_number')->get();

        return view('documents.index', compact('documents', 'documentTypes', 'statuses', 'vehicles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vehicles = Vehicle::where('status', 'active')->get();
        $documentTypes = Document::getDocumentTypes();

        return view('documents.create', compact('vehicles', 'documentTypes'));
    }

    /**
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file' => 'required_without:files|file|max:10240', // 10MB max
                'files' => 'required_without:file|array',
                'files.*' => 'file|max:10240',
                'document_type' => 'required|string|in:' . implode(',', Document::getDocumentTypes()),
                'reference_number' => 'nullable|string|max:100',
                'vehicle_id' => 'nullable|exists:vehicles,id',
                'issue_date' => 'nullable|date',
                'expiry_date' => 'nullable|date|after_or_equal:issue_date',
                'reminder_date' => 'nullable|date|before_or_equal:expiry_date',
                'is_public' => 'boolean',
                'requires_acknowledgement' => 'boolean',
                'tags' => 'nullable|string',
            ]);

            $documents = [];

            if ($request->hasFile('files')) {
                $files = $request->file('files');
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $index => $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    
                    // If multiple files, append original filename to the title
                    $documentTitle = count($files) > 1 
                        ? $request->title . ' - ' . $originalName 
                        : $request->title;

                    $fileName = time() . '_' . $index . '_' . \Illuminate\Support\Str::slug($documentTitle) . '.' . $file->getClientOriginalExtension();
                    $filePath = $file->storeAs('documents', $fileName, 'public');

                    $data = $validated;
                    unset($data['file']);
                    unset($data['files']);

                    $data['title'] = $documentTitle;
                    $data['file_path'] = $filePath;
                    $data['file_name'] = $file->getClientOriginalName();
                    $data['file_type'] = $file->getMimeType();
                    $data['extension'] = $file->getClientOriginalExtension();
                    $data['file_size'] = $file->getSize();
                    $data['slug'] = \Illuminate\Support\Str::slug($documentTitle) . '-' . \Illuminate\Support\Str::random(6);

                    if ($request->has('expiry_date')) {
                        $data['is_expired'] = now()->greaterThan($request->expiry_date);
                    }

                    $documents[] = Document::create($data);
                }
            } elseif ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . \Illuminate\Support\Str::slug($request->title) . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('documents', $fileName, 'public');

                $data = $validated;
                unset($data['file']);
                unset($data['files']);

                $data['file_path'] = $filePath;
                $data['file_name'] = $file->getClientOriginalName();
                $data['file_type'] = $file->getMimeType();
                $data['extension'] = $file->getClientOriginalExtension();
                $data['file_size'] = $file->getSize();
                $data['slug'] = \Illuminate\Support\Str::slug($request->title) . '-' . \Illuminate\Support\Str::random(6);

                if ($request->has('expiry_date')) {
                    $data['is_expired'] = now()->greaterThan($request->expiry_date);
                }

                $documents[] = Document::create($data);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Documents uploaded successfully.',
                    'documents' => $documents,
                    'document' => count($documents) > 0 ? $documents[0] : null
                ]);
            }

            $firstDocument = count($documents) > 0 ? $documents[0] : null;
            return redirect()->route('vehicles.documents.show', $firstDocument)
                            ->with('success', 'Documents uploaded successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload failed: ' . $e->getMessage()
                ], 500);
            }
            return back()->withInput()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        // Check authorization
        $this->authorizeDocumentAccess($document);

        $document->load(['vehicle', 'acknowledgedBy']);

        return view('documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document)
    {
        // Check authorization
        $this->authorizeDocumentAccess($document);

        $vehicles = Vehicle::where('status', 'active')->get();
        $documentTypes = Document::getDocumentTypes();

        return view('documents.edit', compact('document', 'vehicles', 'documentTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document)
    {
        // Check authorization
        $this->authorizeDocumentAccess($document);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
            'document_type' => 'required|string|in:' . implode(',', Document::getDocumentTypes()),
            'reference_number' => 'nullable|string|max:100',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'reminder_date' => 'nullable|date|before_or_equal:expiry_date',
            'status' => 'required|string|in:active,expired,archived,draft',
            'is_public' => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'tags' => 'nullable|string',
        ]);

        // Handle new file upload
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . Str::slug($request->title) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('documents', $fileName, 'public');

            $validated['file_path'] = $filePath;
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_type'] = $file->getMimeType();
            $validated['extension'] = $file->getClientOriginalExtension();
            $validated['file_size'] = $file->getSize();

            // Create new version
            $validated['version'] = $document->version + 1;
            $validated['previous_version_id'] = $document->id;
        }

        // Update slug if title changed
        if ($document->title !== $request->title) {
            $validated['slug'] = Str::slug($request->title) . '-' . Str::random(6);
        }

        // Update expiry status
        if ($request->has('expiry_date')) {
            $validated['is_expired'] = now()->greaterThan($request->expiry_date);
        }

        // Reset acknowledgement if required and not acknowledged yet
        if ($request->requires_acknowledgement && !$document->acknowledged_at) {
            $validated['acknowledged_at'] = null;
            $validated['acknowledged_by'] = null;
            $this->sendAcknowledgementNotification($document);
        }

        $document->update($validated);

        return redirect()->route('vehicles.documents.show', $document)
                        ->with('success', 'Document updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        // Check authorization
        $this->authorizeDocumentAccess($document);

        // Custom soft delete (sets status to 'deleted' and tracks user)
        $document->softDelete();

        return redirect()->route('vehicles.documents.index')
                        ->with('success', 'Document deleted successfully.');
    }

    /**
     * Force delete a document (for admins)
     */
    public function forceDestroy($id)
    {
        $document = Document::where('status', 'deleted')->findOrFail($id);

        // Only admins can force delete
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        // Delete file from storage
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete(); // This performs the actual DB deletion

        return redirect()->route('vehicles.documents.trashed')
                        ->with('success', 'Document permanently deleted.');
    }

    /**
     * Restore a soft-deleted document
     */
    public function restore($id)
    {
        $document = Document::where('status', 'deleted')->findOrFail($id);

        // Check authorization
        $this->authorizeDocumentAccess($document);

        $document->update(['status' => 'active']);

        return redirect()->route('vehicles.documents.show', $document)
                        ->with('success', 'Document restored successfully.');
    }

    /**
     * Display trashed documents
     */
    public function trashed()
    {
        // Only admins can view trashed documents
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $documents = Document::where('status', 'deleted')
                            ->with(['vehicle'])
                            ->latest()
                            ->paginate(20);

        return view('documents.trashed', compact('documents'));
    }

    /**
     * Download a document
     */
    public function download(Document $document)
    {
        // Check authorization
        $this->authorizeDocumentAccess($document);

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        // Log download activity
        $document->increment('download_count');
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->log('downloaded');

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    /**
     * Preview a document (for images and PDFs)
     */
    public function preview(Document $document)
    {
        // Check authorization
        $this->authorizeDocumentAccess($document);

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        $file = Storage::disk('public')->get($document->file_path);
        $mimeType = Storage::disk('public')->mimeType($document->file_path);

        return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $document->file_name . '"');
    }

    /**
     * Acknowledge a document
     */
    public function acknowledge(Document $document)
    {
        if (!$document->requires_acknowledgement) {
            return redirect()->back()->with('error', 'This document does not require acknowledgement.');
        }

        $document->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => auth()->id(),
        ]);

        // Log acknowledgement
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->log('acknowledged');

        return redirect()->back()->with('success', 'Document acknowledged successfully.');
    }

    /**
     * Bulk actions on documents
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,archive,restore,download',
            'documents' => 'required|array',
            'documents.*' => 'exists:documents,id',
        ]);

        $documents = Document::whereIn('id', $request->documents)->get();

        foreach ($documents as $document) {
            // Check authorization for each document
            try {
                $this->authorizeDocumentAccess($document);
            } catch (\Exception $e) {
                continue; // Skip unauthorized documents
            }

            switch ($request->action) {
                case 'delete':
                    $document->delete();
                    break;

                case 'archive':
                    $document->update(['status' => 'archived']);
                    break;

                case 'restore':
                    $document->restore();
                    break;

                case 'download':
                    // Handle bulk download (could create a zip file)
                    break;
            }
        }

        return redirect()->back()->with('success', count($documents) . ' documents updated.');
    }

    /**
     * Get documents expiring soon
     */
    public function expiringSoon()
    {
        $days = request('days', 30); // Default: 30 days
        $date = now()->addDays($days);

        $documents = Document::where('expiry_date', '<=', $date)
                            ->where('is_expired', false)
                            ->where('status', 'active')
                            ->with(['vehicle'])
                            ->orderBy('expiry_date')
                            ->paginate(20);

        return view('documents.expiring', compact('documents', 'days'));
    }

    /**
     * Get document statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Document::count(),
            'active' => Document::where('status', 'active')->count(),
            'expired' => Document::where('is_expired', true)->count(),
            'by_type' => Document::groupBy('document_type')
                                ->selectRaw('document_type, count(*) as count')
                                ->pluck('count', 'document_type'),
            'by_month' => Document::whereYear('created_at', date('Y'))
                                ->groupBy(DB::raw(SqlDate::month('created_at')))
                                ->selectRaw(SqlDate::month('created_at') . ' as month, count(*) as count')
                                ->pluck('count', 'month'),
        ];

        return view('documents.statistics', compact('stats'));
    }

    /**
     * Send acknowledgement notification
     */
    private function sendAcknowledgementNotification(Document $document)
    {
        // Implement your notification logic here
        // This could send emails, notifications, etc.

        // Example:
        // Notification::send($document->uploadedBy, new DocumentRequiresAcknowledgement($document));

        return true;
    }

    /**
     * Authorize document access
     */
    private function authorizeDocumentAccess(Document $document)
    {
        $user = auth()->user();

        // Must be logged in
        if (!$user) {
            abort(403, 'You must be logged in to access this document.');
        }

        // Admins have full access
        if ($user->isAdmin()) {
            return true;
        }

        // Public documents are accessible to all authenticated users
        if ($document->is_public) {
            return true;
        }

        abort(403, 'You are not authorized to access this document.');
    }
}
