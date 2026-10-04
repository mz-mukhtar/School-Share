@extends('layouts.app')

@section('title', 'User Guide')
@section('meta_description', 'Learn how to use SchoolShare to manage your school projects, track versions, and collaborate.')

@section('content')
<div class="container py-4" style="max-width: 800px;">
    <div class="mb-5">
        <h1 class="fw-bold mb-2">SchoolShare Guide</h1>
        <p class="text-muted">Learn how to make the most of the GitHub for Students.</p>
    </div>

    <div class="ss-card mb-4 border-0" style="background:var(--ss-dark-2);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(37, 99, 235, 0.1); color: var(--ss-primary);">
                    <i class="bi bi-folder-plus fs-4"></i>
                </div>
                <h4 class="fw-bold mb-0">1. Creating a Project</h4>
            </div>
            <p class="text-muted">
                A project is like a folder for a specific assignment, essay, or class. 
                Click <strong>New Project</strong> on your dashboard, give it a name (e.g., "History Essay Phase 1"), and a brief description. You can choose to make it <strong>Public</strong> (anyone can see it) or <strong>Private</strong> (only you and invited collaborators can see it).
            </p>
        </div>
    </div>

    <div class="ss-card mb-4 border-0" style="background:var(--ss-dark-2);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); color: var(--ss-success);">
                    <i class="bi bi-cloud-upload fs-4"></i>
                </div>
                <h4 class="fw-bold mb-0">2. Uploading & Saving Checkpoints</h4>
            </div>
            <p class="text-muted mb-3">
                Instead of saving files as <code>essay_final_v2_really_final.docx</code>, SchoolShare uses <strong>Checkpoints</strong>.
            </p>
            <ul class="text-muted mb-0">
                <li class="mb-2">Open your project and drag-and-drop your files into the upload zone.</li>
                <li class="mb-2">Write a short note describing what you changed (e.g., "Added conclusion paragraph").</li>
                <li>Click <strong>Save Checkpoint</strong>. This saves a snapshot of your files exactly as they are right now.</li>
            </ul>
        </div>
    </div>

    <div class="ss-card mb-4 border-0" style="background:var(--ss-dark-2);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.1); color: var(--ss-warning);">
                    <i class="bi bi-arrow-counterclockwise fs-4"></i>
                </div>
                <h4 class="fw-bold mb-0">3. Restoring Past Versions</h4>
            </div>
            <p class="text-muted mb-3">
                Made a mistake? Accidentally deleted half your essay? You can always go back.
            </p>
            <ul class="text-muted mb-0">
                <li class="mb-2">Scroll down to the <strong>Checkpoint History</strong> in your project.</li>
                <li class="mb-2">Find the version you want to go back to.</li>
                <li>Click <strong>Restore Checkpoint</strong>. This will copy those old files and create a new checkpoint with them, safely restoring your old work without deleting anything!</li>
            </ul>
        </div>
    </div>

    <div class="ss-card mb-4 border-0" style="background:var(--ss-dark-2);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(6, 182, 212, 0.1); color: var(--ss-accent);">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
                <h4 class="fw-bold mb-0">4. In-Browser Editing</h4>
            </div>
            <p class="text-muted mb-0">
                You can click on any text or code file in your project to view it. If you click <strong>Edit File</strong>, you can make changes right in your browser! When you hit Save, SchoolShare automatically creates a new checkpoint for you.
                <br><br>
                <em>Note: You can view PDFs, Images, and Office Documents (Word, PowerPoint) in the browser, but editing is only available for plain text/code files right now.</em>
            </p>
        </div>
    </div>

    <div class="ss-card mb-4 border-0" style="background:var(--ss-dark-2);">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(168, 85, 247, 0.1); color: #c084fc;">
                    <i class="bi bi-people fs-4"></i>
                </div>
                <h4 class="fw-bold mb-0">5. Collaborating</h4>
            </div>
            <p class="text-muted mb-0">
                Working on a group project? Go to the <strong>Settings</strong> tab of your project and invite your classmates by their username. They will be able to upload files and save checkpoints to your project. You can see who did what in the Activity Feed!
            </p>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <a href="{{ route('dashboard') }}" class="btn btn-ss-primary px-4">Go to Dashboard</a>
    </div>
</div>
@endsection
