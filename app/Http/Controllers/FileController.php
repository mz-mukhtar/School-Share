<?php

namespace App\Http\Controllers;

use App\ArchivePath;
use App\CheckpointSnapshotService;
use App\Models\Project;
use App\Models\ProjectFile;
use App\StorageLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\Factory\RendererFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Preview a file in the browser (or prompt download if not previewable).
     */
    public function show(Project $project, ProjectFile $file, StorageLifecycle $storage): View|StreamedResponse
    {
        $this->authorizeView($project, $file);

        $versionId = request('version_id');
        $version = $versionId
            ? $file->versions()->find($versionId)
            : $file->latestVersion;

        if (! $version) {
            abort(404, 'No file version found.');
        }

        // For non-previewable files or large text files, just download
        if (! $file->isPreviewable($version->mime_type)) {
            return $this->download($project, $file);
        }

        $content = null;
        if ($file->isText($version->mime_type)) {
            $content = $storage->readText($version, config('schoolshare.operations.max_editor_bytes'));
        }

        $file->load('versions.checkpoint');

        return view('projects.files.show', compact('project', 'file', 'content', 'version'));
    }

    public function download(Project $project, ProjectFile $file): StreamedResponse
    {
        $this->authorizeView($project, $file);

        $versionId = request('version_id');
        $version = $versionId
            ? $file->versions()->find($versionId)
            : $file->latestVersion;

        if (! $version || ! Storage::disk('local')->exists($version->storage_path)) {
            abort(404, 'File not found on disk.');
        }

        return Storage::disk('local')->download($version->storage_path, $file->original_name);
    }

    public function destroy(Project $project, ProjectFile $file, StorageLifecycle $storage): RedirectResponse
    {
        if (! $project->canEdit(auth()->user())) {
            abort(403);
        }
        abort_if($file->project_id !== $project->id, 404);

        $storage->run(fn () => $file->delete());

        return back()->with('success', 'File deleted.');
    }

    public function diff(Project $project, ProjectFile $file, StorageLifecycle $storage): View
    {
        $this->authorizeView($project, $file);

        $fromId = request('from');
        $toId = request('to');

        $from = $file->versions()->find($fromId);
        $to = $file->versions()->find($toId);

        if (! $from || ! $to) {
            abort(404, 'Version not found.');
        }

        if ($from->id > $to->id) {
            $tmp = $from;
            $from = $to;
            $to = $tmp;
        }

        abort_unless($from->isText() && $to->isText(), 422, 'Only plain text/code files support synchronous diff. Download Office files for comparison.');
        $fromText = $storage->readText($from, config('schoolshare.operations.max_diff_bytes'));
        $toText = $storage->readText($to, config('schoolshare.operations.max_diff_bytes'));
        abort_if($fromText === null || $toText === null, 422, 'The diff inputs are missing or too large. Download them for comparison.');
        abort_if(substr_count($fromText, "\n") + 1 > config('schoolshare.operations.max_diff_lines') || substr_count($toText, "\n") + 1 > config('schoolshare.operations.max_diff_lines'), 422, 'The diff has too many lines. Download the files for comparison.');

        $diffOptions = [
            'context' => 3,
            'ignoreCase' => false,
            'ignoreWhitespace' => false,
        ];

        $rendererOptions = [
            'detailLevel' => 'line',
            'language' => 'eng',
            'resultForIdenticals' => 'Files are identical.',
        ];

        $diff = new Differ(explode("\n", $fromText), explode("\n", $toText), $diffOptions);
        $renderer = RendererFactory::make('Inline', $rendererOptions);
        $htmlDiff = $renderer->render($diff);

        return view('projects.files.diff', compact('project', 'file', 'from', 'to', 'htmlDiff'));
    }

    public function update(Request $request, Project $project, ProjectFile $file, StorageLifecycle $storage, CheckpointSnapshotService $snapshots): JsonResponse|RedirectResponse
    {
        if (! $project->canEdit(auth()->user())) {
            abort(403);
        }
        abort_if($file->project_id !== $project->id, 404);

        $request->validate([
            'original_name' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[\w\-\.]+$/'],
            'folder_id' => ['nullable', 'integer', Rule::exists('project_folders', 'id')->where('project_id', $project->id)],
            'content' => ['sometimes', 'string', 'nullable', 'max:'.config('schoolshare.operations.max_editor_bytes')],
        ], [
            'original_name.regex' => 'The file name may only contain letters, numbers, dashes, underscores, and dots.',
        ]);

        if ($request->filled('original_name')) {
            ArchivePath::validate($request->input('original_name'));
        }

        if ($request->has('content')) {
            abort_unless($file->isText(), 422, 'Only text/code files may be edited in the browser.');
            $user = auth()->user();
            $content = $request->input('content') ?? '';
            $size = strlen($content);

            abort_if($size > config('schoolshare.operations.max_editor_bytes'), 413, 'The editor content is too large.');
            $storage->run(function (StorageLifecycle $storage) use ($project, $file, $user, $size, $content, $snapshots) {
                $storage->checkQuota($user, $size);
                $storage->checkProjectCapacity($project, 1);
                $blob = $storage->create($user, $project, $size, fn (string $path) => Storage::disk('local')->put($path, $content));
                DB::transaction(function () use ($project, $file, $user, $size, $blob, $snapshots) {
                    $file->refresh();
                    $checkpoint = $project->checkpoints()->create([
                        'user_id' => $user->id,
                        'title' => 'Updated '.$file->original_name,
                        'message' => 'Edited via in-browser editor',
                        'total_size_bytes' => $size,
                    ]);

                    $version = $file->versions()->create([
                        'checkpoint_id' => $checkpoint->id,
                        'storage_path' => $blob->storage_path,
                        'size_bytes' => $size,
                        'mime_type' => $file->mime_type,
                        'version_number' => $file->version_count + 1,
                    ]);

                    $file->latest_version_id = $version->id;
                    $file->version_count += 1;
                    $file->save();
                    $snapshots->capture($checkpoint);

                });
            });

            return response()->json(['success' => true]);
        }

        if ($request->has('original_name') || $request->has('folder_id')) {
            $folderId = $request->has('folder_id') ? $request->input('folder_id') : $file->folder_id;
            $newName = $request->input('original_name', $file->original_name);

            // Check for duplicate name in the target folder
            $existing = ProjectFile::where('project_id', $project->id)
                ->where('folder_id', $folderId)
                ->where('original_name', $newName)
                ->where('id', '!=', $file->id)
                ->first();

            if ($existing) {
                return back()->with('error', 'A file with this name already exists in the target folder.');
            }

            $storage->run(fn () => $file->update([
                'original_name' => $newName,
                'folder_id' => $folderId,
            ]));
        }

        return back()->with('success', 'File updated successfully.');
    }

    // -------------------------------------------------------------------------

    private function authorizeView(Project $project, ProjectFile $file): void
    {
        abort_if($file->project_id !== $project->id, 404);
        if (! $project->hasAccess(auth()->user())) {
            abort(403);
        }
    }
}
