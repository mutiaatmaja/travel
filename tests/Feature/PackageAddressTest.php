<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Permission;
use App\Models\User;
use Database\Factories\PackageFactory;
use Database\Factories\PackageSettingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PackageAddressTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::firstOrCreate(['name' => 'packages.manage'], ['display_name' => 'Kelola Paket']));
        $this->actingAs($user);

        return $user;
    }

    private function packageForm(): Testable
    {
        $setting = PackageSettingFactory::new()->create();

        return Livewire::test('pages::packages.index')->call('openCreate')
            ->set('packageSettingId', $setting->id)->set('category', 'Ongoing')->set('financialCategory', 'Cash')
            ->set('senderPhone', '081234567890')->set('recipientPhone', '+6281398765432')->set('senderName', 'Pengirim Contoh')->set('senderAddress', "Jl. Asal No. 1\nPontianak 78111")
            ->set('recipientName', 'Penerima Contoh')->set('recipientAddress', "Jl. Tujuan No. 2\nSintang 78611");
    }

    public function test_new_package_stores_sender_recipient_and_addresses_and_generates_number(): void
    {
        $this->signIn();
        $form = $this->packageForm()->set('senderPhone', '081234567890')->set('recipientPhone', '+6281398765432')->set('senderName', '  Pengirim Contoh  ');
        $form->assertSee('Nama Pengirim')->assertSee('Nama Penerima')->assertDontSee('wire:model="code"', false);

        $form->call('save')->assertHasNoErrors()->assertSet('modalOpen', false);

        $package = Package::sole();
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'sender_phone' => '081234567890', 'recipient_phone' => '+6281398765432', 'sender_name' => 'Pengirim Contoh', 'sender_address' => "Jl. Asal No. 1\nPontianak 78111", 'recipient_name' => 'Penerima Contoh', 'recipient_address' => "Jl. Tujuan No. 2\nSintang 78611"]);
        $this->assertStringStartsWith('PKG-', $package->code);
        $form->assertSee($package->code)->assertSee('Pengirim Contoh')->assertSee('Penerima Contoh');
        $form->call('openCreate')->assertSet('senderName', '')->assertSet('senderAddress', '')->assertSet('recipientName', '')->assertSet('recipientAddress', '');
    }

    public function test_existing_package_contacts_can_be_edited_without_changing_number(): void
    {
        $this->signIn();
        $package = PackageFactory::new()->create(['sender_name' => 'Pengirim Awal', 'sender_address' => 'Alamat Asal', 'recipient_name' => 'Penerima Awal', 'recipient_address' => 'Alamat Tujuan']);

        Livewire::test('pages::packages.index')->call('openEdit', $package->id)
            ->assertSet('senderName', 'Pengirim Awal')->assertSet('senderAddress', 'Alamat Asal')
            ->assertSet('recipientName', 'Penerima Awal')->assertSet('recipientAddress', 'Alamat Tujuan')
            ->set('senderPhone', '081234567890')->set('recipientPhone', '+6281398765432')->set('senderName', 'Pengirim Baru')->set('senderAddress', 'Asal Baru')->set('recipientName', 'Penerima Baru')->set('recipientAddress', 'Tujuan Baru')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('packages', ['id' => $package->id, 'code' => $package->code, 'sender_name' => 'Pengirim Baru', 'sender_address' => 'Asal Baru', 'recipient_name' => 'Penerima Baru', 'recipient_address' => 'Tujuan Baru']);
        $this->assertSame($package->customer_name, $package->fresh()->customer_name);
    }

    public function test_all_contact_fields_are_required_and_preserved_after_validation_failure(): void
    {
        $this->signIn();

        $form = $this->packageForm()->set('senderPhone', '081234567890')->set('recipientPhone', '+6281398765432')->set('senderName', '  ')->set('senderAddress', '')->set('recipientName', '')->set('recipientAddress', '')
            ->call('save')->assertHasErrors(['senderName' => 'required', 'senderAddress' => 'required', 'recipientName' => 'required', 'recipientAddress' => 'required'])
            ->assertSet('modalOpen', true)->assertSet('category', 'Ongoing');
        $this->assertDatabaseCount('packages', 0);
        $form->set('senderPhone', '081234567890')->set('recipientPhone', '+6281398765432')->set('senderName', 'Pengirim')->set('senderAddress', 'Alamat Asal')->set('recipientName', 'Penerima')->set('recipientAddress', 'Alamat Tujuan')
            ->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('packages', 1);
    }

    #[TestWith(['senderPhone', ''])]
    #[TestWith(['recipientPhone', ''])]
    #[TestWith(['senderPhone', 'nomor-telepon'])]
    #[TestWith(['recipientPhone', '<script>12345678</script>'])]
    public function test_phone_numbers_reject_empty_or_invalid_input(string $field, string $value): void
    {
        $this->signIn();

        $this->packageForm()->set($field, $value)->call('save')
            ->assertHasErrors([$field])->assertSet('modalOpen', true);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_edit_loads_and_updates_phone_numbers_and_create_resets_them(): void
    {
        $this->signIn();
        $package = PackageFactory::new()->create(['sender_phone' => null, 'recipient_phone' => null]);

        $form = Livewire::test('pages::packages.index')->call('openEdit', $package->id)
            ->assertSet('senderPhone', '')->assertSet('recipientPhone', '')
            ->set('senderPhone', ' 081234567890 ')->set('recipientPhone', '+6281398765432')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('packages', ['id' => $package->id, 'sender_phone' => '081234567890', 'recipient_phone' => '+6281398765432']);
        $form->call('openEdit', $package->id)->assertSet('senderPhone', '081234567890')->assertSet('recipientPhone', '+6281398765432')
            ->call('openCreate')->assertSet('senderPhone', '')->assertSet('recipientPhone', '');
    }

    #[TestWith(['senderPhone', 31])]
    #[TestWith(['recipientPhone', 31])]
    #[TestWith(['senderName', 256])]
    #[TestWith(['recipientName', 256])]
    #[TestWith(['senderAddress', 2001])]
    #[TestWith(['recipientAddress', 2001])]
    public function test_contact_lengths_are_validated(string $field, int $length): void
    {
        $this->signIn();

        $this->packageForm()->set($field, str_repeat('A', $length))->call('save')->assertHasErrors([$field => 'max']);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_legacy_customer_is_not_assumed_to_be_sender_or_recipient(): void
    {
        $this->signIn();
        $package = PackageFactory::new()->create(['customer_name' => 'Pelanggan Lama', 'sender_name' => null, 'sender_address' => null, 'recipient_name' => null, 'recipient_address' => null]);

        $form = Livewire::test('pages::packages.index')->call('openEdit', $package->id)->assertSee('Data pelanggan lama:')->assertSee('Pelanggan Lama')
            ->assertSet('senderName', '')->assertSet('senderAddress', '')->assertSet('recipientName', '')->assertSet('recipientAddress', '')
            ->call('save')->assertHasErrors(['senderName', 'senderAddress', 'recipientName', 'recipientAddress']);
        $this->assertNull($package->fresh()->sender_name);
        $form->set('senderPhone', '081234567890')->set('recipientPhone', '+6281398765432')->set('senderName', 'Pengirim Terverifikasi')->set('senderAddress', 'Alamat Asal')->set('recipientName', 'Penerima Terverifikasi')->set('recipientAddress', 'Alamat Tujuan')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Pelanggan Lama', $package->fresh()->customer_name);
    }

    #[TestWith(['Nama Pengirim Unik'])]
    #[TestWith(['Nama Penerima Unik'])]
    #[TestWith(['Pelanggan Lama Unik'])]
    public function test_package_list_and_tracing_can_search_new_names_and_legacy_customer(string $search): void
    {
        $this->signIn();
        $package = PackageFactory::new()->create(['sender_name' => 'Nama Pengirim Unik', 'recipient_name' => 'Nama Penerima Unik', 'customer_name' => 'Pelanggan Lama Unik']);
        PackageFactory::new()->create(['sender_name' => 'Lain', 'recipient_name' => 'Lain', 'customer_name' => 'Lain']);

        Livewire::test('pages::packages.index')->set('search', $search)
            ->assertViewHas('packages', fn ($packages): bool => $packages->total() === 1 && $packages->first()->id === $package->id);
        Livewire::test('pages::packages.tracing')->set('search', $search)
            ->assertViewHas('packages', fn ($packages): bool => $packages->count() === 1 && $packages->first()->id === $package->id);
    }

    public function test_finance_detail_displays_contacts_and_escapes_addresses(): void
    {
        $user = $this->signIn();
        $user->givePermission(Permission::create(['name' => 'expenses.manage', 'display_name' => 'Kelola Pengeluaran']));
        $package = PackageFactory::new()->create(['sender_name' => 'Pengirim Keuangan', 'recipient_name' => 'Penerima Keuangan', 'sender_address' => '<script>alert(1)</script>', 'recipient_address' => 'Alamat Tujuan Keuangan']);
        $date = $package->created_at->toDateString();

        Livewire::withQueryParams(['startDate' => $date, 'endDate' => $date])->test('pages::packages.finance-detail')
            ->assertSee('Pengirim Keuangan')->assertSee('Penerima Keuangan')->assertSee('Alamat Tujuan Keuangan')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_public_tracking_does_not_expose_addresses(): void
    {
        $package = PackageFactory::new()->create(['sender_address' => 'Alamat Rahasia Pengirim', 'recipient_address' => 'Alamat Rahasia Penerima', 'sender_phone' => '081211112222', 'recipient_phone' => '+628133334444']);

        $form = Livewire::test('pages::public.home')->set('trackingNumber', $package->code)->call('trackPackage')->assertHasNoErrors();

        $this->assertArrayNotHasKey('sender_phone', $form->get('trackingResult'));
        $this->assertArrayNotHasKey('recipient_phone', $form->get('trackingResult'));
        $form->assertDontSee('081211112222')->assertDontSee('+628133334444');
        $this->assertArrayNotHasKey('sender_address', $form->get('trackingResult'));
        $this->assertArrayNotHasKey('recipient_address', $form->get('trackingResult'));
        $this->assertSame($package->sender_name, $form->get('trackingResult')['customer']);
        $form->assertDontSee('Alamat Rahasia Pengirim')->assertDontSee('Alamat Rahasia Penerima');
    }

    public function test_save_rechecks_permission_before_accepting_addresses(): void
    {
        $user = $this->signIn();
        $form = $this->packageForm();
        $user->permissions()->detach();
        $this->actingAs($user->fresh());

        $form->call('save')->assertForbidden();

        $this->assertDatabaseCount('packages', 0);
    }
}
