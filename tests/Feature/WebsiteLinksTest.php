<?php

namespace Tests\Feature;

use App\Mail\OtpVerificationMail;
use App\Mail\ProjectInvitationMail;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WebsiteLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_metadata_uses_the_current_installation_not_the_official_project_site(): void
    {
        $response = $this->get('https://campus.example/');

        $response->assertSee('<meta property="og:url" content="https://campus.example">', false)
            ->assertSee('<link rel="canonical" href="https://campus.example">', false);
    }

    public function test_home_distinguishes_the_official_project_and_company_links(): void
    {
        $response = $this->get('/');

        $response->assertSee('href="https://schoolshare.ethionext.com.et"', false)
            ->assertSeeText('SchoolShare website')
            ->assertSee('href="https://ethionext.com.et"', false)
            ->assertSeeText('EthioNext company website')
            ->assertSee('href="https://github.com/mz-mukhtar/School-Share/blob/main/docs/INSTALLATION_CPANEL.md"', false);
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    #[TestWith(['/about'])]
    #[TestWith(['/terms'])]
    public function test_public_pages_read_project_and_company_links_from_configuration(string $path): void
    {
        config()->set('schoolshare.branding.project_url', 'https://project.example');
        config()->set('schoolshare.branding.company_url', 'https://company.example');

        $response = $this->get($path);

        $response->assertSee('href="https://project.example"', false)
            ->assertSee('href="https://company.example"', false)
            ->assertDontSee('href="https://schoolshare.ethionext.com.et"', false)
            ->assertDontSee('href="https://ethionext.com.et"', false);
    }

    public function test_authenticated_footer_reads_project_and_company_links_from_configuration(): void
    {
        config()->set('schoolshare.branding.project_url', 'https://project.example');
        config()->set('schoolshare.branding.company_url', 'https://company.example');
        $user = User::factory()->create(['username' => 'student']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('href="https://project.example"', false)
            ->assertSee('href="https://company.example"', false)
            ->assertDontSee('href="https://schoolshare.ethionext.com.et"', false);
    }

    public function test_otp_email_links_to_the_sending_installation_and_the_company(): void
    {
        config()->set('app.url', 'https://campus.example');
        config()->set('schoolshare.branding.company_url', 'https://company.example');
        $mail = new OtpVerificationMail('123456', 'Student');

        $mail->assertSeeInHtml('href="https://campus.example"', false);
        $mail->assertSeeInHtml('href="https://company.example"', false);
        $mail->assertDontSeeInHtml('schoolshare.ethionext.com.et');
    }

    public function test_invitation_email_keeps_installation_links_separate_from_company_branding(): void
    {
        config()->set('app.url', 'https://campus.example');
        config()->set('schoolshare.branding.company_url', 'https://company.example');
        $this->app['url']->forceRootUrl('https://campus.example');
        $this->app['url']->forceScheme('https');
        $project = Project::factory()->make(['slug' => 'class-notes']);
        $mail = new ProjectInvitationMail($project, User::factory()->make(), User::factory()->make());

        $mail->assertSeeInHtml('href="https://campus.example"', false);
        $mail->assertSeeInHtml('href="https://campus.example/projects/class-notes"', false);
        $mail->assertSeeInHtml('href="https://company.example"', false);
        $mail->assertDontSeeInHtml('schoolshare.ethionext.com.et');
    }
}
