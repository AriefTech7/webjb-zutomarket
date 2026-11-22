<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Images up to 10 MB are accepted everywhere (bigger ones are shrunk in the browser first). */
class ImageUploadLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_info_and_report_images_accept_up_to_ten_megabytes(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $account = fn (int $kb) => ['title' => 'Akun '.$kb, 'category_id' => $category->id, 'price' => 100000, 'description' => 'x', 'specifications' => 'y', 'status' => 'available',
            'main_image' => UploadedFile::fake()->image('main.png')->size($kb)];

        $this->post('/admin/accounts', $account(9 * 1024))->assertSessionHasNoErrors();
        $this->post('/admin/accounts', $account(11 * 1024))->assertSessionHasErrors('main_image');

        $this->post('/admin/infos', ['title' => 'Poster', 'image' => UploadedFile::fake()->image('p.png')->size(9 * 1024)])->assertSessionHasNoErrors();
        $this->post('/admin/infos', ['title' => 'Poster besar', 'image' => UploadedFile::fake()->image('p.png')->size(11 * 1024)])->assertSessionHasErrors('image');

        auth()->logout();
        $report = ['reporter_name' => 'Budi', 'reporter_email' => 'b@example.com', 'reporter_whatsapp' => '081234567890', 'category' => 'pengguna', 'wa_number' => '081311112222',
            'title' => 'Penipu', 'description' => 'Sudah transfer tapi akun tidak pernah dikirim sama sekali.'];
        $this->post('/laporan', $report + ['evidence' => [UploadedFile::fake()->image('b.png')->size(9 * 1024)]])->assertSessionHasNoErrors();
        $this->post('/laporan', $report + ['evidence' => [UploadedFile::fake()->image('b.png')->size(11 * 1024)]])->assertSessionHasErrors(['evidence.0' => 'Setiap gambar bukti maksimal 10 MB.']);
    }

    public function test_forms_tell_admins_and_visitors_that_big_images_are_compressed(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/accounts/create')->assertSee('Maksimal 10 MB per gambar; gambar besar dikompres otomatis.');
        $this->get('/admin/infos')->assertSee('Maksimal 10 MB; gambar besar dikompres otomatis.');
        $this->get('/admin/banner')->assertSee('maksimal 10 MB (gambar besar dikompres otomatis)');
        auth()->logout();
        $this->get('/laporan')->assertSee('tiap gambar maksimal 10 MB (gambar besar dikompres otomatis)');
    }
}
