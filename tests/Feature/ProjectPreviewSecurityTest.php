<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProjectPreviewSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['<img src="missing" onerror="window.readmeAudit=1">', 'img'])]
    #[TestWith(['<svg onload="window.readmeAudit=1"></svg>', 'svg'])]
    #[TestWith(['<iframe srcdoc="<script>window.readmeAudit=1</script>"></iframe>', 'iframe'])]
    #[TestWith(['<script>window.readmeAudit=1</script>', 'script'])]
    public function test_readme_renders_raw_html_as_text(string $markdown, string $tag): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['username' => 'readme-owner'])->refresh();
        $project = $user->projects()->create(['name' => 'Readme project', 'visibility' => 'private']);
        $this->createFile($project, 'README.md', 'text/markdown', $markdown);

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertOk();
        $html = $response->viewData('readmeHtml');
        $this->assertStringContainsString('&lt;'.$tag, $html);
        $this->assertStringNotContainsString('<'.$tag, $html);
        Storage::disk('local')->assertExists($project->files()->first()->latestVersion->storage_path);
    }

    #[TestWith(['[unsafe](javascript:alert%281%29)'])]
    #[TestWith(['[unsafe](<JaVaScRiPt:alert(1)>)'])]
    #[TestWith(['[unsafe](vbscript:msgbox%281%29)'])]
    #[TestWith(['[unsafe](file:///etc/passwd)'])]
    #[TestWith(['[unsafe](data:text/html;base64,PHNjcmlwdD4=)'])]
    #[TestWith(['![unsafe](data:image/svg+xml;base64,PHN2Zz4=)'])]
    public function test_readme_does_not_render_unsafe_link_or_image_destinations(string $markdown): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['username' => 'readme-owner'])->refresh();
        $project = $user->projects()->create(['name' => 'Readme project', 'visibility' => 'private']);
        $this->createFile($project, 'README.md', 'text/markdown', $markdown);

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertOk();
        $html = $response->viewData('readmeHtml');
        $this->assertDoesNotMatchRegularExpression('/(?:href|src)="(?:javascript|vbscript|file|data):/i', $html);
        $this->assertStringContainsString('unsafe', $html);
        Storage::disk('local')->assertExists($project->files()->first()->latestVersion->storage_path);
    }

    public function test_readme_preserves_markdown_formatting_safe_links_and_images(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['username' => 'readme-owner'])->refresh();
        $project = $user->projects()->create(['name' => 'Readme project', 'visibility' => 'private']);
        $markdown = "# Project guide\n\n**Important**\n\n[Guide](https://example.test/guide)\n\n![Diagram](https://example.test/diagram.png)\n\n```html\n<img onerror=alert(1)>\n```\n\n| Item | Status |\n| --- | --- |\n| Upload | Ready |";
        $this->createFile($project, 'README.md', 'text/markdown', $markdown);

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertSee('<h1>Project guide</h1>', false)
            ->assertSee('<strong>Important</strong>', false)
            ->assertSee('href="https://example.test/guide"', false)
            ->assertSee('src="https://example.test/diagram.png"', false)
            ->assertSee('&lt;img onerror=alert(1)&gt;', false)
            ->assertSee('<table>', false);
        Storage::disk('local')->assertExists($project->files()->first()->latestVersion->storage_path);
    }

    public function test_pdf_preview_loads_patched_matching_library_and_worker_with_evaluation_disabled(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['username' => 'pdf-owner'])->refresh();
        $project = $user->projects()->create(['name' => 'PDF project', 'visibility' => 'private']);
        $file = $this->createFile($project, 'notes.pdf', 'application/pdf', '%PDF-1.4');

        $response = $this->actingAs($user)->get(route('projects.files.show', [$project, $file]));

        $response->assertSee('pdfjs-dist@6.4.299/build/pdf.min.mjs', false)
            ->assertSee('pdfjs-dist@6.4.299/build/pdf.worker.min.mjs', false)
            ->assertSee('isEvalSupported: false', false)
            ->assertDontSee('pdf.js/3.11.174', false)
            ->assertSee('id="pdf-next"', false)
            ->assertSee('id="pdf-prev"', false);
        Storage::disk('local')->assertExists($file->latestVersion->storage_path);
    }

    private function createFile(Project $project, string $name, string $mimeType, string $content): ProjectFile
    {
        $checkpoint = $project->checkpoints()->create([
            'user_id' => $project->user_id,
            'title' => 'Initial upload',
            'total_size_bytes' => strlen($content),
        ]);
        $file = $project->files()->create([
            'original_name' => $name,
            'mime_type' => $mimeType,
            'version_count' => 1,
        ]);
        $path = 'preview-tests/'.$project->id.'/'.$file->id;
        Storage::disk('local')->put($path, $content);
        $version = $file->versions()->create([
            'checkpoint_id' => $checkpoint->id,
            'storage_path' => $path,
            'size_bytes' => strlen($content),
            'version_number' => 1,
        ]);
        $file->update(['latest_version_id' => $version->id]);

        return $file->refresh();
    }
}
