<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSetting;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PackageCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::create(['name' => 'packages.manage', 'display_name' => 'Kelola Paket']));
        $this->actingAs($user);
    }

    #[TestWith(['package'])]
    #[TestWith(['financial'])]
    public function test_category_can_be_created_and_deleted(string $type): void
    {
        $this->signIn();

        $form = Livewire::test('pages::packages.categories')->call('openCreate')->assertSet('modalOpen', true)
            ->set('type', $type)->set('name', '  Kategori Baru  ')->call('save')->assertHasNoErrors()->assertSet('modalOpen', false);

        $category = PackageCategory::where('type', $type)->where('name', 'Kategori Baru')->sole();
        Livewire::test('pages::packages.index')->call('openCreate')->assertSee('Kategori Baru');
        $form->call('confirmDelete', $category->id)->assertSet('confirmDeleteOpen', true)->call('delete')->assertHasNoErrors();
        $this->assertModelMissing($category);
    }

    #[TestWith(['package', 'category'])]
    #[TestWith(['financial', 'financial_category'])]
    public function test_renaming_category_updates_existing_packages_and_used_category_cannot_be_deleted(string $type, string $column): void
    {
        $this->signIn();
        $category = PackageCategory::factory()->create(['type' => $type]);
        $setting = PackageSetting::create(['name' => 'Reguler', 'pricing_type' => 'weight']);
        $package = Package::create(['package_setting_id' => $setting->id, 'customer_name' => 'Pelanggan', $column => $category->name]);

        $form = Livewire::test('pages::packages.categories')->call('openEdit', $category->id)
            ->assertSet('name', $category->name)->assertSet('type', $type)->set('name', 'Nama Baru')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('package_categories', ['id' => $category->id, 'name' => 'Nama Baru']);
        $this->assertDatabaseHas('packages', ['id' => $package->id, $column => 'Nama Baru']);
        $form->call('confirmDelete', $category->id)->call('delete')
            ->assertHasErrors(['deleteId' => 'Kategori masih digunakan oleh paket dan tidak dapat dihapus.']);
        $this->assertModelExists($category);
    }

    #[TestWith(['', 'package', 'name', 'required'])]
    #[TestWith(['Ongoing', 'package', 'name', 'unique'])]
    #[TestWith(['Baru', 'unknown', 'type', 'in'])]
    public function test_invalid_category_is_rejected(string $name, string $type, string $field, string $rule): void
    {
        $this->signIn();

        Livewire::test('pages::packages.categories')->call('openCreate')->set('name', $name)->set('type', $type)
            ->call('save')->assertHasErrors([$field => $rule]);

        $this->assertDatabaseCount('package_categories', 4);
    }

    public function test_same_name_is_allowed_in_different_category_types(): void
    {
        $this->signIn();

        Livewire::test('pages::packages.categories')->call('openCreate')->set('name', 'Ongoing')->set('type', 'financial')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('package_categories', ['name' => 'Ongoing', 'type' => 'financial']);
    }

    public function test_editing_category_cannot_change_its_type(): void
    {
        $this->signIn();
        $category = PackageCategory::factory()->create(['type' => 'package']);

        Livewire::test('pages::packages.categories')->call('openEdit', $category->id)->set('type', 'financial')
            ->call('save')->assertHasErrors(['type' => 'Jenis kategori tidak dapat diubah.']);

        $this->assertDatabaseHas('package_categories', ['id' => $category->id, 'type' => 'package']);
    }

    public function test_duplicate_name_is_rejected_during_edit(): void
    {
        $this->signIn();
        $category = PackageCategory::where('name', 'Ingoing')->sole();

        Livewire::test('pages::packages.categories')->call('openEdit', $category->id)->set('name', 'Ongoing')
            ->call('save')->assertHasErrors(['name' => 'unique']);

        $this->assertSame('Ingoing', $category->fresh()->name);
    }

    public function test_unchanged_name_can_be_saved(): void
    {
        $this->signIn();
        $category = PackageCategory::where('name', 'Ingoing')->sole();

        Livewire::test('pages::packages.categories')->call('openEdit', $category->id)->call('save')->assertHasNoErrors();

        $this->assertSame('Ingoing', $category->fresh()->name);
    }

    public function test_category_actions_require_permission(): void
    {
        $this->actingAs(User::factory()->create());
        $category = PackageCategory::where('name', 'Ingoing')->sole();

        Livewire::test('pages::packages.categories')->call('openCreate')->assertForbidden();
        Livewire::test('pages::packages.categories')->call('openEdit', $category->id)->assertForbidden();
        Livewire::test('pages::packages.categories')->set('name', 'Baru')->call('save')->assertForbidden();
        Livewire::test('pages::packages.categories')->call('confirmDelete', $category->id)->assertForbidden();
        Livewire::test('pages::packages.categories')->call('delete')->assertForbidden();

        $this->assertDatabaseCount('package_categories', 4);
    }

    public function test_package_rejects_categories_from_the_wrong_type(): void
    {
        $this->signIn();
        $setting = PackageSetting::create(['name' => 'Reguler', 'pricing_type' => 'weight']);

        Livewire::test('pages::packages.index')->set('packageSettingId', $setting->id)->set('customerName', 'Pelanggan')
            ->set('category', 'Cash')->set('financialCategory', 'Ongoing')->call('save')
            ->assertHasErrors(['category' => 'exists', 'financialCategory' => 'exists']);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_package_can_use_custom_categories(): void
    {
        $this->signIn();
        PackageCategory::factory()->create(['name' => 'Antar Cabang', 'type' => 'package']);
        PackageCategory::factory()->create(['name' => 'Transfer', 'type' => 'financial']);
        $setting = PackageSetting::create(['name' => 'Reguler', 'pricing_type' => 'weight']);

        Livewire::test('pages::packages.index')->set('packageSettingId', $setting->id)->set('customerName', 'Pelanggan')
            ->set('category', 'Antar Cabang')->set('financialCategory', 'Transfer')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('packages', ['category' => 'Antar Cabang', 'financial_category' => 'Transfer']);
    }
}
