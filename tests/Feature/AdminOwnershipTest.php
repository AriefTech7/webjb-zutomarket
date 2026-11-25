<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function ownedAccount(User $owner, array $attributes = []): GameAccount
    {
        $account = GameAccount::factory()->create($attributes);
        $account->forceFill(['owner_id' => $owner->id])->save();

        return $account;
    }

    private function formData(GameAccount $account, array $overrides = []): array
    {
        return [...['title' => $account->title, 'slug' => $account->slug, 'category_id' => $account->category_id, 'price' => 100000, 'description' => 'Deskripsi', 'specifications' => 'Rank', 'status' => 'available'], ...$overrides];
    }

    public function test_a_regular_admin_only_sees_and_changes_their_own_accounts(): void
    {
        $alice = User::factory()->regularAdmin()->create(['username' => 'alice']);
        $bob = User::factory()->regularAdmin()->create(['username' => 'bob']);
        $mine = $this->ownedAccount($alice, ['title' => 'Akun Alice']);
        $theirs = $this->ownedAccount($bob, ['title' => 'Akun Bob']);
        $theirsBooked = $this->ownedAccount($bob, ['title' => 'Akun Bob DP', 'status' => 'dp']);
        $this->actingAs($alice);

        $this->get('/admin/accounts')->assertOk()->assertSee('Akun Alice')->assertDontSee('Akun Bob');
        $this->get('/admin')->assertOk()->assertSee('Akun Alice')->assertDontSee('Akun Bob')->assertSee('Total akun');
        $this->get('/admin/accounts/'.$mine->id.'/edit')->assertOk()->assertDontSee('name="owner_id"', false);

        $this->get('/admin/accounts/'.$theirs->id.'/edit')->assertForbidden();
        $this->put('/admin/accounts/'.$theirs->id, $this->formData($theirs, ['title' => 'Diambil']))->assertForbidden();
        $this->delete('/admin/accounts/'.$theirs->id)->assertForbidden();
        $this->delete('/admin/accounts', ['ids' => [$mine->id, $theirs->id]])->assertForbidden();
        $this->assertModelExists($mine);
        $this->assertSame('Akun Bob', $theirs->refresh()->title);
        $this->assertNull($theirsBooked->refresh()->dp_percent);

        // Their own account can be edited, but not handed to someone else.
        $this->put('/admin/accounts/'.$mine->id, $this->formData($mine, ['title' => 'Akun Alice Baru', 'owner_id' => $bob->id]))->assertSessionHasNoErrors();
        $this->assertSame(['Akun Alice Baru', $alice->id], [$mine->refresh()->title, $mine->owner_id]);

        // New accounts belong to whoever adds them.
        $category = Category::factory()->create();
        $this->post('/admin/accounts', ['title' => 'Akun Baru Alice', 'category_id' => $category->id, 'price' => 50000, 'description' => 'x', 'specifications' => 'y', 'status' => 'available',
            'main_image' => UploadedFile::fake()->image('a.png')])->assertSessionHasNoErrors();
        $this->assertSame($alice->id, GameAccount::where('title', 'Akun Baru Alice')->value('owner_id'));
    }

    public function test_store_wide_menus_are_for_the_super_admin_only(): void
    {
        $alice = User::factory()->regularAdmin()->create();
        $this->actingAs($alice);

        foreach (['/admin/settings', '/admin/categories', '/admin/admins', '/admin/sales', '/admin/buyers', '/admin/banner', '/admin/infos', '/admin/contacts'] as $page) {
            $this->get($page)->assertForbidden();
        }
        $this->put('/admin/settings', ['store_name' => 'Dibajak', 'whatsapp_number' => '6281234567890', 'whatsapp_default_message' => 'x'])->assertForbidden();
        foreach (['/admin', '/admin/accounts', '/admin/rentals', '/admin/messages'] as $page) {
            $this->get($page)->assertOk();
        }
        $this->get('/admin')->assertDontSee('Pengaturan WhatsApp')->assertDontSee('Pesanan website')->assertSee('Akun game');
    }

    public function test_the_super_admin_manages_every_account_and_the_admin_roles(): void
    {
        $owner = User::factory()->create(['username' => 'razan', 'password' => 'rahasia123']);
        $bob = User::factory()->regularAdmin()->create(['username' => 'bob']);
        $alice = User::factory()->regularAdmin()->create(['username' => 'alice']);
        $bobs = $this->ownedAccount($bob, ['title' => 'Akun Bob']);
        $this->actingAs($owner);

        $this->get('/admin/accounts')->assertOk()->assertSee('Akun Bob')->assertSee('milik bob');
        $this->get('/admin/accounts/'.$bobs->id.'/edit')->assertOk()->assertSee('Pemilik akun')->assertSee('<option value="'.$bob->id.'" selected', false);
        $this->put('/admin/accounts/'.$bobs->id, $this->formData($bobs, ['owner_id' => $alice->id]))->assertSessionHasNoErrors();
        $this->assertSame($alice->id, $bobs->refresh()->owner_id);
        $this->put('/admin/accounts/'.$bobs->id, $this->formData($bobs, ['owner_id' => User::factory()->buyer()->create()->id]))->assertSessionHasErrors('owner_id');

        // Roles: promote and demote others, never yourself.
        $this->get('/admin/admins')->assertOk()->assertSee('Super admin')->assertSee('Jadikan super admin');
        $this->patch('/admin/admins/'.$alice->id.'/role', ['current_password' => 'salah'])->assertSessionHasErrors('current_password');
        $this->patch('/admin/admins/'.$alice->id.'/role', ['current_password' => 'rahasia123'])->assertSessionHas('success');
        $this->assertTrue($alice->refresh()->is_super_admin);
        $this->patch('/admin/admins/'.$owner->id.'/role', ['current_password' => 'rahasia123'])->assertSessionHasErrors('current_password');
        $this->assertTrue($owner->refresh()->is_super_admin);

        // Removing an admin keeps their accounts in the store, now owned by the super admin who removed them.
        $this->ownedAccount($bob, ['title' => 'Akun Bob Lain']);
        $this->delete('/admin/admins/'.$bob->id, ['current_password' => 'rahasia123'])->assertSessionHas('success');
        $this->assertSame($owner->id, GameAccount::where('title', 'Akun Bob Lain')->value('owner_id'));
    }

    public function test_create_admin_command_makes_a_working_admin_and_the_first_one_super(): void
    {
        $this->artisan('app:create-admin')->expectsQuestion('Nama admin', 'Pemilik')->expectsQuestion('Username', 'pemilik')->expectsQuestion('Email', 'pemilik@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'password-panjang-1')->assertSuccessful();
        $this->artisan('app:create-admin')->expectsQuestion('Nama admin', 'Staf')->expectsQuestion('Username', 'staf')->expectsQuestion('Email', 'staf@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'password-panjang-2')->assertSuccessful();

        $this->assertSame([true, true], [User::where('username', 'pemilik')->value('is_admin'), User::where('username', 'pemilik')->value('is_super_admin')]);
        $this->assertSame([true, false], [User::where('username', 'staf')->value('is_admin'), User::where('username', 'staf')->value('is_super_admin')]);
        $this->post('/admin/login', ['username' => 'staf', 'password' => 'password-panjang-2'])->assertRedirect(route('admin.dashboard'));
    }
}
