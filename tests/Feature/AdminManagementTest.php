<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_super_admin_edits_another_admin_and_sets_their_password(): void
    {
        $super = User::factory()->create(['username' => 'razan', 'password' => 'rahasia123']);
        $staff = User::factory()->regularAdmin()->create(['username' => 'staf', 'email' => 'staf@example.com', 'password' => 'lama12345']);
        User::factory()->buyer()->create(['email' => 'pembeli@example.com']);
        $this->actingAs($super)->get('/admin/admins')->assertOk()->assertSee('action="'.route('admin.admins.update', $staff).'"', false)
            ->assertSee('action="'.route('admin.admins.password', $staff).'"', false);

        $edit = ['name' => 'Staf Baru', 'username' => ' StafBaru ', 'email' => 'BARU@example.com'];
        $this->put('/admin/admins/'.$staff->id, $edit + ['current_password' => 'salah'])->assertSessionHasErrorsIn('admin'.$staff->id, 'current_password');
        $this->put('/admin/admins/'.$staff->id, [...$edit, 'email' => 'pembeli@example.com', 'username' => 'razan', 'current_password' => 'rahasia123'])->assertSessionHasErrorsIn('admin'.$staff->id, ['email', 'username']);
        $this->put('/admin/admins/'.$staff->id, $edit + ['current_password' => 'rahasia123'])->assertSessionHas('success');
        $this->assertSame(['Staf Baru', 'stafbaru', 'baru@example.com'], [$staff->refresh()->name, $staff->username, $staff->email]);
        $this->assertFalse($staff->is_super_admin);

        $this->put('/admin/admins/'.$staff->id.'/password', ['new_password' => 'baru12345', 'new_password_confirmation' => 'beda', 'current_password' => 'rahasia123'])->assertSessionHasErrorsIn('admin'.$staff->id, 'new_password');
        $this->put('/admin/admins/'.$staff->id.'/password', ['new_password' => 'baru12345', 'new_password_confirmation' => 'baru12345', 'current_password' => 'rahasia123'])->assertSessionHas('success');
        $this->assertTrue(Hash::check('baru12345', $staff->refresh()->password));
        $this->put('/admin/admins/'.$super->id.'/password', ['new_password' => 'xyz12345', 'new_password_confirmation' => 'xyz12345', 'current_password' => 'rahasia123'])->assertSessionHasErrorsIn('admin'.$super->id, 'current_password');

        $this->post('/admin/logout');
        $this->post('/admin/login', ['username' => 'stafbaru', 'password' => 'baru12345'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_admin_sets_a_profile_photo_shown_in_the_live_chat(): void
    {
        Storage::fake('public');
        $razan = User::factory()->create(['name' => 'Razan']);
        $this->actingAs($razan)->get('/admin/password')->assertOk()->assertSee('Foto profil')->assertSee('data-avatar-input', false)->assertDontSee('Hapus foto');
        $this->get('/admin')->assertSee(route('admin.password'), false)->assertSee('Profil &amp; password', false);

        $this->post('/admin/profil/foto', [])->assertSessionHasErrors(['avatar' => 'Pilih foto dulu.']);
        $this->post('/admin/profil/foto', ['avatar' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])->assertSessionHasErrors('avatar');
        $this->post('/admin/profil/foto', ['avatar' => UploadedFile::fake()->image('a.png')->size(3000)])->assertSessionHasErrors(['avatar' => 'Foto maksimal 2 MB.']);
        $this->post('/admin/profil/foto', ['avatar' => UploadedFile::fake()->image('a.png', 256, 256)])->assertRedirect(route('admin.password'))->assertSessionHas('success');
        $first = $razan->refresh()->avatar_path;
        Storage::disk('public')->assertExists($first);
        $this->get('/admin')->assertSee($razan->avatarUrl(), false);

        // Replacing deletes the old file; visitors see the photo while the admin is online.
        $this->post('/admin/profil/foto', ['avatar' => UploadedFile::fake()->image('b.png', 256, 256)]);
        Storage::disk('public')->assertMissing($first);
        auth()->logout();
        $this->get('/')->assertSee('1 Admin Online')->assertSee('src="'.$razan->refresh()->avatarUrl().'"', false);

        $this->actingAs($razan)->delete('/admin/profil/foto')->assertSessionHas('success');
        Storage::disk('public')->assertMissing($razan->getOriginal('avatar_path'));
        $this->assertNull($razan->refresh()->avatar_path);
        auth()->logout();
        $this->get('/')->assertSee('title="Razan"', false)->assertSee('>R</span>', false);
        $this->post('/admin/profil/foto', ['avatar' => UploadedFile::fake()->image('c.png')])->assertRedirect('/admin/login');
    }

    public function test_every_admin_changes_their_own_password_but_only_the_super_admin_manages_others(): void
    {
        $super = User::factory()->create();
        $staff = User::factory()->regularAdmin()->create(['password' => 'lama12345']);
        $this->actingAs($staff);
        $this->put('/admin/admins/'.$super->id, ['name' => 'x', 'username' => 'xx1', 'email' => 'x@example.com', 'current_password' => 'lama12345'])->assertForbidden();
        $this->put('/admin/admins/'.$super->id.'/password', ['new_password' => 'baru12345', 'new_password_confirmation' => 'baru12345', 'current_password' => 'lama12345'])->assertForbidden();

        $this->get('/admin')->assertSee(route('admin.password'), false);
        $this->get('/admin/password')->assertOk()->assertSee('Password saat ini');
        $this->put('/admin/password', ['current_password' => 'salah', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])->assertSessionHasErrors('current_password');
        $this->put('/admin/password', ['current_password' => 'lama12345', 'password' => 'lama12345', 'password_confirmation' => 'lama12345'])->assertSessionHasErrors('password');
        $this->put('/admin/password', ['current_password' => 'lama12345', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])->assertRedirect(route('admin.password'))->assertSessionHas('success');
        $this->assertTrue(Hash::check('baru12345', $staff->refresh()->password));
        $this->assertAuthenticatedAs($staff);

        $this->post('/admin/logout');
        $this->get('/admin/password')->assertRedirect('/admin/login');
    }
}
