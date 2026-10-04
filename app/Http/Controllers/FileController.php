<?php

namespace App\Http\Controllers;

use App\Models\Checkpoint;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\FileVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Preview a file in the browser (or prompt download if not previewable).
     */
    public function show(Project $project, ProjectFile $file)
    {
        $this->authorizeView($project, $file);

        $versionId = request('version_id');
        $version = $versionId 
            ? $file->versions()->find($versionId) 
            : $file->latestVersion;

        if (!$version) abort(404, 'No file version found.');

        // For non-previewable files or large text files, just download
        if (!$file->isPreviewable()) {
            return $this->download($project, $file);
        }

        $content = null;
        if ($file->isText()) {
            if ($version->size_bytes <= 524288) {
                $content = Storage::disk('local')->get($version->storage_path);
            }
        }

        $file->load('versions.checkpoint');
        return view('projects.files.show', compact('project', 'file', 'content', 'version'));
    }

    public function download(Project $project, ProjectFile $file)
    {
        $this->authorizeView($project, $file);

        $versionId = request('version_id');
        $version = $versionId 
            ? $file->versions()->find($versionId) 
            : $file->latestVersion;

        if (!$version || !Storage::disk('local')->exists($version->storage_path)) {
            abort(404, 'File not found on disk.');
        }

        return Storage::disk('local')->download($version->storage_path, $file->original_name);
    }

    public function destroy(Project $project, ProjectFile $file)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403);
        }
        abort_if($file->project_id !== $project->id, 404);

        $user = auth()->user();

        foreach ($file->versions as $version) {
            $size = $version->size_bytes;
            Storage::disk('local')->delete($version->storage_path);
            
            // Reclaim quota
            $user->decrement('storage_used_bytes', min($size, $user->storage_used_bytes));

            // Update checkpoint size
            $checkpoint = Checkpoint::find($version->checkpoint_id);
            if ($checkpoint) {
                $checkpoint->decrement('total_size_bytes', min($size, $checkpoint->total_size_bytes));
            }
        }
        
        $file->versions()->delete();
        $file->delete();

        return back()->with('success', 'File deleted.');
    }

    public function diff(Project $project, ProjectFile $file)
    {
        $this->authorizeView($project, $file);

        $fromId = request('from');
        $toId = request('to');

        $from = $file->versions()->find($fromId);
        $to = $file->versions()->find($toId);

        if (!$from || !$to) {
            abort(404, 'Version not found.');
        }

        if ($from->id > $to->id) {
            $tmp = $from;
            $from = $to;
            $to = $tmp;
        }

        $fromText = $this->extractText($from);
        $toText = $this->extractText($to);

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

        $diff = new \Jfcherng\Diff\Diff(explode("\n", $fromText), explode("\n", $toText), $diffOptions);
        $renderer = \Jfcherng\Diff\Factory\RendererFactory::make('Inline', $rendererOptions);
        $htmlDiff = $renderer->renderArray($diff);

        return view('projects.files.diff', compact('project', 'file', 'from', 'to', 'htmlDiff'));
    }

    private function extractText(FileVersion $version): string
    {
        $path = Storage::disk('local')->path($version->storage_path);
        if (!file_exists($path)) return '';
        
        $ext = strtolower(pathinfo($version->projectFile->original_name, PATHINFO_EXTENSION));
        
        if (in_array($ext, ['txt', 'md', 'csv', 'json', 'js', 'css', 'html', 'php', 'py', 'sh'])) {
            return file_get_contents($path);
        }
        
        if (in_array($ext, ['doc', 'docx'])) {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        } elseif (method_exists($element, 'getElements')) {
                            foreach ($element->getElements() as $child) {
                                if (method_exists($child, 'getText')) {
                                    $text .= $child->getText() . "\n";
                                }
                            }
                        }
                    }
                }
                return $text;
            } catch (\Exception $e) {
                return 'Could not extract text from docx: ' . $e->getMessage();
            }
        }
        
        return 'Unsupported format for text diff.';
    }

    public function update(Request $request, Project $project, ProjectFile $file)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403);
        }
        abort_if($file->project_id !== $project->id, 404);

        $request->validate([
            'original_name' => ['required', 'string', 'max:255', 'regex:/^[\w\-\.]+$/'],
            'folder_id' => ['nullable', 'exists:project_folders,id'],
        ], [
            'original_name.regex' => 'The file name may only contain letters, numbers, dashes, underscores, and dots.'
        ]);

        $folderId = $request->input('folder_id');
        $newName = $request->input('original_name');

        // Check for duplicate name in the target folder
        $existing = ProjectFile::where('project_id', $project->id)
            ->where('folder_id', $folderId)
            ->where('original_name', $newName)
            ->where('id', '!=', $file->id)
            ->first();

        if ($existing) {
            return back()->with('error', 'A file with this name already exists in the target folder.');
        }

        $file->update([
            'original_name' => $newName,
            'folder_id' => $folderId,
        ]);

        return back()->with('success', 'File updated successfully.');
    }

    // -------------------------------------------------------------------------

    private function authorizeView(Project $project, ProjectFile $file): void
    {
        abort_if($file->project_id !== $project->id, 404);
        if ($project->visibility === 'private' && auth()->id() !== $project->user_id) {
            abort(403);
        }
    }
}
