<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ExpenseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::firstOrCreate(['name' => 'expenses.manage'], ['display_name' => 'Kelola Pengeluaran']));
        $this->actingAs($user);

        return $user;
    }

    public function test_expense_routes_require_authentication_and_permission(): void
    {
        foreach (['expenses.categories' => [], 'expenses.index' => ['service' => 'travel']] as $route => $parameters) {
            $this->get(route($route, $parameters))->assertRedirect(route('login'));
        }
        $user = User::factory()->create();
        $user->givePermission(Permission::create(['name' => 'packages.manage', 'display_name' => 'Kelola Paket']));
        $this->actingAs($user);

        $this->get(route('expenses.categories'))->assertForbidden();
        $this->get(route('expenses.index', ['service' => 'travel']))->assertForbidden();
        $this->get(route('expenses.index', ['service' => 'package']))->assertForbidden();
    }

    public function test_sidebar_exposes_all_expense_submenus_and_expense_only_user_has_a_landing_page(): void
    {
        $user = $this->signIn();

        $this->get(route('expenses.categories'))->assertOk()->assertSee('Pengeluaran Travel')->assertSee('Pengeluaran Paket')
            ->assertSee(route('expenses.index', ['service' => 'travel']))->assertSee(route('expenses.index', ['service' => 'package']));
        $this->get(route('expenses.index', ['service' => 'travel']))->assertOk();
        $this->get(route('expenses.index', ['service' => 'package']))->assertOk();
        $this->assertSame('expenses.categories', $user->landingRouteName());
        $this->get('/expenses/unknown')->assertNotFound();
    }

    #[TestWith(['travel'])]
    #[TestWith(['package'])]
    public function test_category_can_be_created_edited_and_deleted(string $service): void
    {
        $this->signIn();

        $form = Livewire::test('pages::expenses.categories')->call('openCreate')->set('service', $service)
            ->set('name', '  Operasional  ')->set('description', 'Biaya rutin')->call('save')->assertHasNoErrors()->assertSet('modalOpen', false);

        $category = ExpenseCategory::sole();
        $this->assertSame('Operasional', $category->name);
        $form->call('openEdit', $category->id)->assertSet('service', $service)->set('name', 'Biaya Harian')
            ->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id, 'name' => 'Biaya Harian', 'description' => 'Biaya rutin']);
        $form->call('confirmDelete', $category->id)->call('delete')->assertHasNoErrors();
        $this->assertModelMissing($category);
    }

    public function test_used_category_cannot_be_deleted_and_renaming_preserves_expense_relationship(): void
    {
        $this->signIn();
        $category = ExpenseCategory::factory()->create();
        $expense = Expense::factory()->for($category, 'category')->create();

        $form = Livewire::test('pages::expenses.categories')->call('openEdit', $category->id)->set('name', 'Nama Baru')->call('save')->assertHasNoErrors();
        $this->assertSame('Nama Baru', $expense->fresh()->category->name);
        $form->call('confirmDelete', $category->id)->call('delete')
            ->assertHasErrors(['deleteId' => 'Kategori masih digunakan oleh pengeluaran dan tidak dapat dihapus.']);
        $this->assertModelExists($category);
        $this->assertModelExists($expense);
    }

    public function test_category_cannot_be_moved_between_services(): void
    {
        $this->signIn();
        $category = ExpenseCategory::factory()->create(['service' => 'travel']);

        Livewire::test('pages::expenses.categories')->call('openEdit', $category->id)->set('service', 'package')->call('save')
            ->assertHasErrors(['service' => 'Layanan kategori tidak dapat diubah.']);

        $this->assertSame('travel', $category->fresh()->service);
    }

    public function test_category_names_are_unique_per_service_but_allowed_across_services(): void
    {
        $this->signIn();
        ExpenseCategory::factory()->create(['name' => 'Operasional', 'service' => 'travel']);
        $form = Livewire::test('pages::expenses.categories')->call('openCreate')->set('name', 'Operasional');

        $form->call('save')->assertHasErrors(['name' => 'unique']);
        $form->set('service', 'package')->call('save')->assertHasNoErrors();

        $this->assertDatabaseCount('expense_categories', 2);
    }

    public function test_empty_name_and_unknown_service_are_rejected(): void
    {
        $this->signIn();

        Livewire::test('pages::expenses.categories')->call('openCreate')->set('name', '   ')->set('service', 'other')->call('save')
            ->assertHasErrors(['name' => 'required', 'service' => 'in']);

        $this->assertDatabaseCount('expense_categories', 0);
    }

    public function test_action_rechecks_permission_after_page_was_opened(): void
    {
        $user = $this->signIn();
        $form = Livewire::test('pages::expenses.categories')->call('openCreate')->set('name', 'Baru');
        $user->permissions()->detach();
        $user->unsetRelation('permissions');
        $this->actingAs($user->fresh());

        $form->call('save')->assertForbidden();

        $this->assertDatabaseCount('expense_categories', 0);
    }

    public function test_expense_seed_is_repeatable_and_does_not_create_financial_transactions(): void
    {
        $admin = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $otherPermission = Permission::create(['name' => 'custom.permission', 'display_name' => 'Custom']);
        $admin->permissions()->attach($otherPermission);
        $custom = ExpenseCategory::factory()->create(['name' => 'Custom']);

        $this->seed(ExpenseSeeder::class);
        $this->seed(ExpenseSeeder::class);

        $this->assertDatabaseCount('expense_categories', 9);
        $this->assertModelExists($custom);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('permissions', 2);
        $this->assertTrue($admin->fresh()->hasPermission('expenses.manage'));
        $this->assertTrue($admin->fresh()->hasPermission('custom.permission'));
    }
}
