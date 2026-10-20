<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\User;
use Database\Factories\PackageFactory;
use Database\Factories\PackageSettingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PackageFinanceTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(array $permissions = ['packages.manage', 'expenses.manage']): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $name) {
            $user->givePermission(Permission::firstOrCreate(['name' => $name], ['display_name' => $name]));
        }
        $this->actingAs($user);

        return $user;
    }

    private function period(): array
    {
        return ['startDate' => '2026-10-01', 'endDate' => '2026-10-20'];
    }

    public function test_routes_require_both_permissions(): void
    {
        $this->get(route('packages.finance'))->assertRedirect(route('login'));
        $this->get(route('packages.finance.detail'))->assertRedirect(route('login'));
        $this->signIn(['packages.manage']);
        $this->get(route('packages.finance'))->assertForbidden();
        $this->get(route('packages.finance.detail'))->assertForbidden();
        $this->signIn(['expenses.manage']);
        $this->get(route('packages.finance'))->assertForbidden();
        $this->get(route('packages.finance.detail'))->assertForbidden();
        $this->signIn();
        $this->get(route('packages.finance'))->assertOk()->assertSee('Keuangan Paket')->assertSee(route('packages.finance'));
    }

    public function test_summary_uses_real_package_costs_and_only_package_expenses(): void
    {
        $this->signIn();
        PackageFactory::new()->create(['created_at' => '2026-10-20 23:59:59', 'weight_kg' => 3]);
        PackageFactory::new()->create(['created_at' => '2026-10-01 00:00:00', 'weight_kg' => 2]);
        PackageFactory::new()->create(['created_at' => '2026-10-21', 'weight_kg' => 99]);
        PackageFactory::new()->create(['created_at' => '2026-09-30', 'weight_kg' => 99]);
        PackageFactory::new()->create(['created_at' => '2026-10-10', 'status' => 'cancelled', 'weight_kg' => 7]);
        $category = ExpenseCategory::factory()->create(['service' => 'package']);
        Expense::factory()->for($category, 'category')->create(['expense_date' => '2026-10-20', 'amount' => 12000]);
        Expense::factory()->for($category, 'category')->create(['expense_date' => '2026-10-21', 'amount' => 90000]);
        Expense::factory()->create(['expense_date' => '2026-10-10', 'amount' => 90000]);

        Livewire::withQueryParams($this->period())->test('pages::packages.finance')
            ->assertViewHas('summary', fn ($summary): bool => $summary['amount'] === 50000 && $summary['count'] === 2)
            ->assertViewHas('expenseAmount', 12000)->assertViewHas('expenseCount', 1)->assertViewHas('balance', 38000)
            ->assertSee('di luar total utama')->assertSee('belum menunjukkan pembayaran diterima');
    }

    public function test_breakdowns_preserve_literal_names_and_links_include_period_and_group(): void
    {
        $this->signIn();
        PackageFactory::new()->create(['created_at' => '2026-10-10', 'category' => 'Cash', 'financial_category' => 'Kredit', 'status' => 'delivered']);
        $parameters = [...$this->period(), 'group' => 'category', 'value' => 'Cash', 'missing' => 0];

        $response = $this->get(route('packages.finance', $this->period()));

        $response->assertOk()->assertSee(route('packages.finance.detail', $parameters));
        $response->assertSee(route('expenses.index', ['service' => 'package', ...$this->period()]));
        Livewire::withQueryParams($this->period())->test('pages::packages.finance')
            ->assertViewHas('summary', fn ($summary): bool => $summary['groups']['category']['"Cash"']['amount'] === 20000
                && $summary['groups']['financial_category']['"Kredit"']['count'] === 1
                && $summary['groups']['pricing_type']['"weight"']['amount'] === 20000);
    }

    #[TestWith(['category', 'Ongoing'])]
    #[TestWith(['financial_category', 'Cash'])]
    #[TestWith(['pricing_type', 'weight'])]
    #[TestWith(['status', 'delivered'])]
    public function test_detail_filters_each_supported_group(string $group, string $value): void
    {
        $this->signIn();
        $target = PackageFactory::new()->create(['created_at' => '2026-10-10', 'status' => 'delivered']);
        $volume = PackageSettingFactory::new()->create(['pricing_type' => 'volume']);
        PackageFactory::new()->create(['package_setting_id' => $volume->id, 'created_at' => '2026-10-10', 'category' => 'Ingoing', 'financial_category' => 'Kredit', 'status' => 'pickup']);

        Livewire::withQueryParams([...$this->period(), 'group' => $group, 'value' => $value])->test('pages::packages.finance-detail')
            ->assertViewHas('totals', fn ($totals): bool => $totals['amount'] === 20000 && $totals['count'] === 1)
            ->assertViewHas('packages', fn ($packages): bool => $packages->total() === 1 && $packages->first()->id === $target->id);
    }

    public function test_uncategorized_detail_is_distinct_from_a_category_with_the_same_display_name(): void
    {
        $this->signIn();
        $uncategorized = PackageFactory::new()->create(['created_at' => '2026-10-10', 'category' => null]);
        PackageFactory::new()->create(['created_at' => '2026-10-10', 'category' => 'Belum dikategorikan']);

        Livewire::withQueryParams([...$this->period(), 'group' => 'category', 'missing' => 1])->test('pages::packages.finance-detail')
            ->assertSee('Belum dikategorikan')->assertViewHas('packages', fn ($packages): bool => $packages->total() === 1 && $packages->first()->id === $uncategorized->id);
    }

    public function test_cancelled_detail_is_available_while_all_packages_detail_excludes_cancelled(): void
    {
        $this->signIn();
        $cancelled = PackageFactory::new()->create(['created_at' => '2026-10-10', 'status' => 'cancelled']);
        $active = PackageFactory::new()->create(['created_at' => '2026-10-10']);

        Livewire::withQueryParams([...$this->period(), 'group' => 'status', 'value' => 'cancelled'])->test('pages::packages.finance-detail')
            ->assertViewHas('packages', fn ($packages): bool => $packages->total() === 1 && $packages->first()->id === $cancelled->id);
        Livewire::withQueryParams($this->period())->test('pages::packages.finance-detail')
            ->assertViewHas('packages', fn ($packages): bool => $packages->total() === 1 && $packages->first()->id === $active->id);
    }

    public function test_detail_total_includes_all_pages_and_date_sort_is_stable(): void
    {
        $this->signIn();
        PackageFactory::new()->count(11)->create(['created_at' => '2026-10-01']);
        $latest = PackageFactory::new()->create(['created_at' => '2026-10-20']);

        $form = Livewire::withQueryParams($this->period())->test('pages::packages.finance-detail')->assertViewHas('totals', fn ($totals): bool => $totals['amount'] === 240000)
            ->assertViewHas('packages', fn ($packages): bool => $packages->count() === 10 && $packages->total() === 12 && $packages->first()->id === $latest->id);
        $form->call('toggleDateSort')->assertSet('sortDirection', 'asc')
            ->assertViewHas('packages', fn ($packages): bool => $packages->first()->created_at->format('Y-m-d') === '2026-10-01');
        $form->call('gotoPage', 2)->assertViewHas('packages', fn ($packages): bool => $packages->count() === 2)
            ->assertViewHas('totals', fn ($totals): bool => $totals['amount'] === 240000);
    }

    public function test_empty_period_and_negative_balance_are_supported(): void
    {
        $this->signIn();
        $category = ExpenseCategory::factory()->create(['service' => 'package']);
        Expense::factory()->for($category, 'category')->create(['expense_date' => '2026-10-10', 'amount' => 50000]);

        Livewire::withQueryParams($this->period())->test('pages::packages.finance')->assertViewHas('balance', -50000)
            ->assertViewHas('summary', fn ($summary): bool => $summary['amount'] === 0)->assertSee('Tidak ada paket aktif');
        Livewire::withQueryParams($this->period())->test('pages::packages.finance-detail')->assertSee('Tidak ada paket sesuai periode');
    }

    public function test_changing_period_recomputes_totals_and_invalid_period_preserves_previous_results(): void
    {
        $this->signIn();
        PackageFactory::new()->create(['created_at' => '2026-09-10']);
        $form = Livewire::withQueryParams($this->period())->test('pages::packages.finance')->assertViewHas('summary', fn ($summary): bool => $summary['count'] === 0);

        $form->set('startDate', '2026-09-01')->set('endDate', '2026-09-30')->call('applyPeriod')->assertHasNoErrors()
            ->assertViewHas('summary', fn ($summary): bool => $summary['amount'] === 20000);
        $form->set('endDate', '2026-08-31')->call('applyPeriod')->assertHasErrors(['endDate' => 'after_or_equal'])
            ->assertViewHas('summary', fn ($summary): bool => $summary['amount'] === 20000);
    }

    public function test_expense_link_applies_period_on_arrival(): void
    {
        $this->signIn();
        $category = ExpenseCategory::factory()->create(['service' => 'package']);
        Expense::factory()->for($category, 'category')->create(['expense_date' => '2026-10-20', 'amount' => 40000]);
        Expense::factory()->for($category, 'category')->create(['expense_date' => '2026-09-01', 'amount' => 90000]);

        Livewire::withQueryParams($this->period())->test('pages::expenses.index', ['service' => 'package'])
            ->assertSet('startDate', '2026-10-01')->assertSet('endDate', '2026-10-20')
            ->assertViewHas('totalAmount', 40000)->assertViewHas('expenseCount', 1);
    }

    public function test_unknown_group_is_rejected(): void
    {
        $this->signIn();

        $this->get(route('packages.finance.detail', [...$this->period(), 'group' => 'customer_name', 'value' => 'Someone']))
            ->assertStatus(422);
    }

    public function test_volume_minimum_charge_and_current_rate_are_used(): void
    {
        $this->signIn();
        $setting = PackageSettingFactory::new()->create(['pricing_type' => 'volume', 'rate_per_kg' => 12000, 'minimum_charge' => 30000]);
        PackageFactory::new()->create(['package_setting_id' => $setting->id, 'created_at' => '2026-10-10', 'length_cm' => 30, 'width_cm' => 20, 'height_cm' => 10]);
        $form = Livewire::withQueryParams($this->period())->test('pages::packages.finance');

        $form->assertViewHas('summary', fn ($summary): bool => $summary['amount'] === 30000 && $summary['groups']['pricing_type']['"volume"']['count'] === 1);
        $setting->update(['rate_per_kg' => 40000]);
        $form->call('applyPeriod')->assertViewHas('summary', fn ($summary): bool => $summary['amount'] === 40000);
    }

    #[TestWith(['2026-02-30', '2026-10-20'])]
    #[TestWith(['2026-10-20', '2026-10-01'])]
    public function test_invalid_link_period_is_rejected(string $startDate, string $endDate): void
    {
        $this->signIn();
        $period = compact('startDate', 'endDate');

        $this->get(route('packages.finance', $period))->assertStatus(422);
        $this->get(route('packages.finance.detail', $period))->assertStatus(422);
        $this->get(route('expenses.index', ['service' => 'package', ...$period]))->assertStatus(422);
    }

    public function test_access_is_rechecked_after_permission_is_removed(): void
    {
        $user = $this->signIn();
        $form = Livewire::withQueryParams($this->period())->test('pages::packages.finance');
        $user->permissions()->detach();
        $this->actingAs($user->fresh());

        $form->call('applyPeriod')->assertForbidden();
    }
}
