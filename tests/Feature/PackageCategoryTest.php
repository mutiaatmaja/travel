<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageSetting;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PackageCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function signInWithPackagePermission(): void
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::create(['name' => 'packages.manage', 'display_name' => 'Kelola Paket']));
        $this->actingAs($user);
    }

    public function test_category_page_requires_authentication_and_permission(): void
    {
        $this->get(route('packages.categories'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('packages.categories'))->assertForbidden();
    }

    public function test_authorized_user_sees_category_page_and_sidebar_link(): void
    {
        $this->signInWithPackagePermission();

        $this->get(route('packages.categories'))->assertOk()
            ->assertSee('Ongoing')->assertSee('Ingoing')->assertSee('Cash')->assertSee('Kredit')
            ->assertSee(route('packages.categories'));
    }

    #[TestWith(['Ongoing', 'Cash'])]
    #[TestWith(['Ingoing', 'Kredit'])]
    public function test_package_categories_are_saved_and_can_be_edited(string $category, string $financialCategory): void
    {
        $this->signInWithPackagePermission();
        $setting = PackageSetting::create(['name' => 'Reguler', 'pricing_type' => 'weight', 'rate_per_kg' => 15000]);

        $form = Livewire::test('pages::packages.index')->call('openCreate')
            ->set('packageSettingId', $setting->id)->set('customerName', 'Pelanggan')
            ->set('category', $category)->set('financialCategory', $financialCategory)
            ->call('save')->assertHasNoErrors()->assertSet('category', '')->assertSet('financialCategory', '');

        $package = Package::sole();
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'category' => $category, 'financial_category' => $financialCategory]);
        $form->call('openEdit', $package->id)->assertSet('category', $category)->assertSet('financialCategory', $financialCategory)
            ->set('category', 'Ingoing')->set('financialCategory', 'Cash')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'category' => 'Ingoing', 'financial_category' => 'Cash']);
    }

    #[TestWith(['', '', 'required'])]
    #[TestWith(['volume', 'Transfer', 'exists'])]
    public function test_missing_or_unknown_categories_are_rejected(string $category, string $financialCategory, string $rule): void
    {
        $this->signInWithPackagePermission();
        $setting = PackageSetting::create(['name' => 'Reguler', 'pricing_type' => 'weight']);

        Livewire::test('pages::packages.index')->set('packageSettingId', $setting->id)->set('customerName', 'Pelanggan')
            ->set('category', $category)->set('financialCategory', $financialCategory)->call('save')
            ->assertHasErrors(['category' => $rule, 'financialCategory' => $rule]);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_existing_uncategorized_package_can_be_opened_and_classified(): void
    {
        $this->signInWithPackagePermission();
        $setting = PackageSetting::create(['name' => 'Reguler', 'pricing_type' => 'weight']);
        $package = Package::create(['package_setting_id' => $setting->id, 'customer_name' => 'Pelanggan Lama']);

        Livewire::test('pages::packages.index')->assertSee('Belum dikategorikan')
            ->call('openEdit', $package->id)->assertSet('category', '')->assertSet('financialCategory', '')
            ->set('category', 'Ongoing')->set('financialCategory', 'Kredit')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('packages', ['id' => $package->id, 'category' => 'Ongoing', 'financial_category' => 'Kredit']);
    }
}
