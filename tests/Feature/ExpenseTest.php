<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::firstOrCreate(['name' => 'expenses.manage'], ['display_name' => 'Kelola Pengeluaran']));
        $this->actingAs($user);

        return $user;
    }

    #[TestWith(['travel'])]
    #[TestWith(['package'])]
    public function test_expense_can_be_created_edited_and_deleted_in_its_service(string $service): void
    {
        $actor = $this->signIn();
        $category = ExpenseCategory::factory()->create(['service' => $service]);
        $form = Livewire::test('pages::expenses.index', ['service' => $service])->call('openCreate');

        $form->set('expenseCategoryId', $category->id)->set('expenseDate', '2026-10-01')->set('amount', '150000')
            ->set('description', 'Pembayaran operasional')->call('save')->assertHasNoErrors()->assertSet('modalOpen', false);

        $expense = Expense::sole();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'expense_category_id' => $category->id, 'amount' => 150000, 'created_by' => $actor->id]);
        $form->call('openEdit', $expense->id)->assertSet('expenseDate', '2026-10-01')->set('amount', '200000')->call('save')->assertHasNoErrors();
        $this->assertSame(200000, $expense->fresh()->amount);
        $this->assertSame($actor->id, $expense->fresh()->created_by);
        $form->call('confirmDelete', $expense->id)->assertSet('confirmDeleteOpen', true)->call('delete')->assertHasNoErrors();
        $this->assertModelMissing($expense);
    }

    public function test_editing_preserves_original_recorder(): void
    {
        $actor = $this->signIn();
        $expense = Expense::factory()->create();

        Livewire::test('pages::expenses.index', ['service' => 'travel'])->call('openEdit', $expense->id)->set('amount', '60000')->call('save')->assertHasNoErrors();

        $this->assertNotSame($actor->id, $expense->created_by);
        $this->assertSame($expense->created_by, $expense->fresh()->created_by);
    }

    #[TestWith(['0'])]
    #[TestWith(['-1'])]
    #[TestWith(['12.5'])]
    #[TestWith(['1000000000000'])]
    public function test_invalid_amount_is_rejected(string $amount): void
    {
        $this->signIn();
        $category = ExpenseCategory::factory()->create();

        Livewire::test('pages::expenses.index', ['service' => 'travel'])->call('openCreate')->set('expenseCategoryId', $category->id)
            ->set('expenseDate', '2026-10-01')->set('amount', $amount)->call('save')->assertHasErrors(['amount']);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_required_fields_and_invalid_date_are_rejected(): void
    {
        $this->signIn();

        Livewire::test('pages::expenses.index', ['service' => 'travel'])->call('openCreate')->set('expenseDate', '2026-02-30')
            ->call('save')->assertHasErrors(['expenseCategoryId' => 'required', 'amount' => 'required', 'expenseDate' => 'date_format']);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_category_from_other_service_is_rejected_during_creation_and_editing(): void
    {
        $this->signIn();
        $travel = Expense::factory()->create();
        $packageCategory = ExpenseCategory::factory()->create(['service' => 'package']);

        $form = Livewire::test('pages::expenses.index', ['service' => 'travel'])->call('openCreate')
            ->set('expenseCategoryId', $packageCategory->id)->set('amount', '50000')->call('save')->assertHasErrors(['expenseCategoryId' => 'exists']);
        $form->call('openEdit', $travel->id)->set('expenseCategoryId', $packageCategory->id)->call('save')->assertHasErrors(['expenseCategoryId' => 'exists']);

        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame($travel->expense_category_id, $travel->fresh()->expense_category_id);
    }

    public function test_transactions_cannot_be_opened_or_deleted_from_other_service(): void
    {
        $this->signIn();
        $category = ExpenseCategory::factory()->create(['service' => 'package']);
        $expense = Expense::factory()->for($category, 'category')->create();

        Livewire::test('pages::expenses.index', ['service' => 'travel'])->call('openEdit', $expense->id)->assertNotFound();
        Livewire::test('pages::expenses.index', ['service' => 'travel'])->call('confirmDelete', $expense->id)->assertNotFound();

        $this->assertModelExists($expense);
    }

    public function test_service_lists_and_totals_are_separate(): void
    {
        $this->signIn();
        $travelCategory = ExpenseCategory::factory()->create(['name' => 'BBM Travel']);
        $packageCategory = ExpenseCategory::factory()->create(['service' => 'package', 'name' => 'Pengemasan Paket']);
        Expense::factory()->for($travelCategory, 'category')->create(['amount' => 120000]);
        Expense::factory()->for($packageCategory, 'category')->create(['amount' => 35000]);

        Livewire::test('pages::expenses.index', ['service' => 'travel'])->assertViewHas('totalAmount', 120000)->assertViewHas('expenseCount', 1)->assertDontSee('Pengemasan Paket');
        Livewire::test('pages::expenses.index', ['service' => 'package'])->assertViewHas('totalAmount', 35000)->assertViewHas('expenseCount', 1)->assertDontSee('BBM Travel');
    }

    public function test_filters_apply_to_both_total_and_rows_and_can_be_cleared(): void
    {
        $this->signIn();
        $selectedCategory = ExpenseCategory::factory()->create();
        Expense::factory()->for($selectedCategory, 'category')->create(['expense_date' => '2026-10-01', 'amount' => 20000, 'description' => 'Biaya rutin']);
        Expense::factory()->for($selectedCategory, 'category')->create(['expense_date' => '2026-10-10', 'amount' => 30000, 'description' => 'Biaya rutin']);
        Expense::factory()->for($selectedCategory, 'category')->create(['expense_date' => '2026-10-10', 'amount' => 40000, 'description' => 'Biaya khusus']);
        Expense::factory()->create(['expense_date' => '2026-10-10', 'amount' => 50000, 'description' => 'Biaya rutin']);

        $form = Livewire::test('pages::expenses.index', ['service' => 'travel'])->set('categoryFilter', $selectedCategory->id)
            ->set('startDate', '2026-10-10')->set('endDate', '2026-10-10')->set('search', 'rutin')->call('applyFilters')
            ->assertHasNoErrors()->assertViewHas('totalAmount', 30000)->assertViewHas('expenseCount', 1);
        $form->call('clearFilters')->assertViewHas('totalAmount', 140000)->assertViewHas('expenseCount', 4);
    }

    public function test_category_search_does_not_leak_matching_categories_from_other_service(): void
    {
        $this->signIn();
        $travel = ExpenseCategory::factory()->create(['name' => 'Operasional', 'service' => 'travel']);
        $package = ExpenseCategory::factory()->create(['name' => 'Operasional', 'service' => 'package']);
        Expense::factory()->for($travel, 'category')->create(['amount' => 10000]);
        Expense::factory()->for($package, 'category')->create(['amount' => 99000]);

        Livewire::test('pages::expenses.index', ['service' => 'travel'])->set('search', 'Operasional')->call('applyFilters')
            ->assertHasNoErrors()->assertViewHas('totalAmount', 10000)->assertViewHas('expenseCount', 1);
    }

    public function test_total_includes_all_pages_and_sorting_uses_expense_date(): void
    {
        $this->signIn();
        $category = ExpenseCategory::factory()->create();
        Expense::factory()->count(11)->for($category, 'category')->create(['amount' => 1000, 'expense_date' => '2026-10-01']);
        $newest = Expense::factory()->for($category, 'category')->create(['amount' => 2000, 'expense_date' => '2026-10-12']);

        $form = Livewire::test('pages::expenses.index', ['service' => 'travel'])->assertViewHas('totalAmount', 13000)
            ->assertViewHas('expenses', fn ($expenses): bool => $expenses->count() === 10 && $expenses->total() === 12 && $expenses->first()->id === $newest->id);
        $form->call('toggleDateSort')->assertSet('sortDirection', 'asc')->assertViewHas('expenses', fn ($expenses): bool => $expenses->first()->expense_date->format('Y-m-d') === '2026-10-01');
        $form->call('gotoPage', 2)->assertViewHas('expenses', fn ($expenses): bool => $expenses->count() === 2)->assertViewHas('totalAmount', 13000);
    }

    public function test_invalid_filters_are_rejected_without_replacing_applied_filters(): void
    {
        $this->signIn();
        $packageCategory = ExpenseCategory::factory()->create(['service' => 'package']);

        Livewire::test('pages::expenses.index', ['service' => 'travel'])->set('categoryFilter', $packageCategory->id)
            ->set('startDate', '2026-10-10')->set('endDate', '2026-10-01')->call('applyFilters')
            ->assertHasErrors(['categoryFilter' => 'exists', 'endDate' => 'after_or_equal'])->assertViewHas('expenseCount', 0);
    }

    public function test_end_date_filter_can_be_used_without_start_date(): void
    {
        $this->signIn();
        Expense::factory()->create(['expense_date' => '2026-10-01', 'amount' => 1000]);
        Expense::factory()->create(['expense_date' => '2026-10-10', 'amount' => 2000]);

        Livewire::test('pages::expenses.index', ['service' => 'travel'])->set('endDate', '2026-10-01')->call('applyFilters')
            ->assertHasNoErrors()->assertViewHas('totalAmount', 1000);
    }

    public function test_replaying_same_submission_does_not_duplicate_expense(): void
    {
        $user = $this->signIn();
        $category = ExpenseCategory::factory()->create();
        $form = Livewire::test('pages::expenses.index', ['service' => 'travel'])->call('openCreate')
            ->set('expenseCategoryId', $category->id)->set('amount', '50000');
        Expense::factory()->for($category, 'category')->create(['submission_token' => $form->get('submissionToken'), 'created_by' => $user->id]);

        $form->call('save')->assertHasNoErrors()->call('save')->assertHasNoErrors();

        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_permission_is_rechecked_when_saving(): void
    {
        $user = $this->signIn();
        $category = ExpenseCategory::factory()->create();
        $form = Livewire::test('pages::expenses.index', ['service' => 'travel'])->call('openCreate')->set('expenseCategoryId', $category->id)->set('amount', '50000');
        $user->permissions()->detach();
        $this->actingAs($user->fresh());

        $form->call('save')->assertForbidden();

        $this->assertDatabaseCount('expenses', 0);
    }
}
