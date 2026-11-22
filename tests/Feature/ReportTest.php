<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $overrides = []): array
    {
        return [...[
            'reporter_name' => 'Budi', 'reporter_email' => 'budi@example.com', 'reporter_whatsapp' => '0812 3456 7890',
            'category' => 'pengguna', 'wa_number' => '+62 813-1111-2222', 'title' => 'Penipu akun FF',
            'description' => 'Sudah transfer DP tapi akun tidak pernah dikirim, lalu nomor saya diblokir.',
            'evidence' => [UploadedFile::fake()->image('bukti.png')],
        ], ...$overrides];
    }

    public function test_a_visitor_files_a_report_that_waits_for_the_admin(): void
    {
        Storage::fake('public');
        $this->get('/laporan')->assertOk()->assertSee('Cari laporan berdasarkan ID game atau nomor WA')->assertSee('data-report-dialog', false)
            ->assertSee('Pengguna Bermasalah')->assertSee('ID Game Bermasalah')->assertSee('Belum ada laporan.');

        $this->post('/laporan', $this->form())->assertRedirect(route('reports'))->assertSessionHas('success');
        $report = Report::sole();
        $this->assertSame(['pending', '6281311112222', null, '6281234567890'], [$report->status, $report->wa_number, $report->game_id, $report->reporter_whatsapp]);
        Storage::disk('public')->assertExists($report->evidence[0]);

        // Not public until approved.
        $this->get('/laporan')->assertDontSee('Penipu akun FF');
        $this->get('/laporan/'.$report->id)->assertNotFound();
    }

    public function test_the_category_decides_which_suspect_field_is_required(): void
    {
        Storage::fake('public');
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->post('/laporan', $this->form(['wa_number' => '']))->assertSessionHasErrors(['wa_number' => 'Isi nomor WA pelaku.']);
        $this->post('/laporan', $this->form(['category' => 'id_game', 'wa_number' => '']))->assertSessionHasErrors(['game_id' => 'Isi ID game yang bermasalah.']);
        $this->post('/laporan', $this->form(['category' => 'id_game', 'game_id' => '1234 5678 (2001)', 'wa_number' => '']))->assertSessionHasNoErrors();
        $this->assertSame('12345678(2001)', Report::sole()->game_id);
        $this->post('/laporan', $this->form(['category' => 'akun_mafia', 'wa_number' => '']))->assertSessionHasErrors(['game_id' => 'Isi ID game yang bermasalah.']);
        $this->post('/laporan', $this->form(['category' => 'akun_mafia', 'game_id' => '55 66 77', 'wa_number' => '']))->assertSessionHasNoErrors();
        $mafia = Report::latest('id')->first();
        $this->assertSame(['556677', 'Akun Mafia'], [$mafia->game_id, $mafia->categoryLabel()]);
        $mafia->delete();

        $this->post('/laporan', $this->form(['evidence' => []]))->assertSessionHasErrors(['evidence' => 'Upload minimal 1 gambar bukti.']);
        $this->post('/laporan', $this->form(['evidence' => array_fill(0, 11, UploadedFile::fake()->image('b.png'))]))->assertSessionHasErrors('evidence');
        $this->post('/laporan', $this->form(['evidence' => [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')]]))->assertSessionHasErrors('evidence.0');
        $this->post('/laporan', $this->form(['reporter_email' => 'bukan-email', 'description' => 'pendek']))->assertSessionHasErrors(['reporter_email', 'description']);
        // Spam bots fill the hidden field: nothing is stored.
        $this->post('/laporan', $this->form(['website' => 'http://spam.example']))->assertRedirect(route('reports'));
        $this->assertSame(1, Report::count());
    }

    public function test_approved_reports_are_searchable_by_game_id_or_number_without_the_reporters_details(): void
    {
        Storage::fake('public');
        $this->post('/laporan', $this->form());
        $this->post('/laporan', $this->form(['category' => 'id_game', 'game_id' => '99887766', 'wa_number' => '', 'title' => 'Hackback akun ML', 'reporter_email' => 'rahasia@example.com']));
        $byNumber = Report::where('title', 'Penipu akun FF')->sole();
        $byId = Report::where('title', 'Hackback akun ML')->sole();

        $super = User::factory()->create();
        $this->actingAs($super)->get('/admin/reports')->assertOk()->assertSee('Penipu akun FF')->assertSee('budi@example.com')->assertSee('Menunggu (2)');
        $this->patch('/admin/reports/'.$byNumber->id, ['status' => 'approved'])->assertSessionHas('success');
        $this->patch('/admin/reports/'.$byId->id, ['status' => 'approved'])->assertSessionHas('success');
        $this->assertNotNull($byNumber->refresh()->approved_at);
        auth()->logout();

        $this->get('/laporan')->assertOk()->assertSee('Penipu akun FF')->assertSee('Hackback akun ML')->assertSeeInOrder(['2', 'Total laporan', '1', 'ID game', '1', 'No WA'])
            ->assertDontSee('budi@example.com')->assertDontSee('rahasia@example.com')->assertDontSee('6281234567890');
        // The number can be typed any way.
        foreach (['0813-1111-2222', '+62 813 1111 2222', '81311112222'] as $typed) {
            $this->get('/laporan?q='.urlencode($typed))->assertOk()->assertSee('Penipu akun FF')->assertDontSee('Hackback akun ML');
        }
        $this->get('/laporan?q=9988 7766')->assertOk()->assertSee('Hackback akun ML')->assertDontSee('Penipu akun FF');
        $this->get('/laporan?q=55555555')->assertOk()->assertSee('Tidak ada laporan untuk');
        $this->get('/laporan/'.$byNumber->id)->assertOk()->assertSee('0813-1111-2222')->assertSee('Foto bukti (1)')->assertDontSee('budi@example.com')->assertDontSee('Budi');
    }

    public function test_filing_reports_is_rate_limited(): void
    {
        Storage::fake('public');
        foreach (range(1, 6) as $attempt) {
            $this->post('/laporan', $this->form(['title' => 'Laporan '.$attempt]))->assertRedirect(route('reports'));
        }
        $this->post('/laporan', $this->form())->assertTooManyRequests();
        $this->assertSame(6, Report::count());
    }

    public function test_only_the_super_admin_moderates_reports(): void
    {
        Storage::fake('public');
        $this->post('/laporan', $this->form());
        $report = Report::sole();

        $this->get('/admin/reports')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->regularAdmin()->create());
        $this->get('/admin/reports')->assertForbidden();
        $this->patch('/admin/reports/'.$report->id, ['status' => 'approved'])->assertForbidden();
        $this->get('/admin')->assertDontSee('route("admin.reports")')->assertDontSee('>Laporan</span>', false);

        $this->actingAs(User::factory()->create());
        $this->get('/admin')->assertOk()->assertSee('1 laporan menunggu');
        $this->patch('/admin/reports/'.$report->id, ['status' => 'rejected'])->assertSessionHas('success');
        $this->get('/laporan')->assertDontSee('Penipu akun FF');
        $this->delete('/admin/reports/'.$report->id)->assertSessionHas('success');
        $this->assertModelMissing($report);
        Storage::disk('public')->assertMissing($report->evidence[0]);
    }
}
