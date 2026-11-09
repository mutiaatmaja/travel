<?php

namespace Tests\Feature;

use App\Http\Controllers\PackageReceiptController;
use App\Models\Permission;
use App\Models\User;
use Database\Factories\PackageFactory;
use Database\Factories\PackageSettingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

class PackageReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::firstOrCreate(['name' => 'packages.manage'], ['display_name' => 'Kelola Paket']));
        $this->actingAs($user);
    }

    /** @param list<string> $texts */
    private function assertReceiptText(array $texts): void
    {
        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($texts): bool {
            $html = $pdf->getHtml();
            foreach ($texts as $text) {
                $this->assertStringContainsString($text, $html);
            }

            return true;
        });
    }

    public function test_receipt_requires_authentication_and_package_permission(): void
    {
        $package = PackageFactory::new()->create();

        $this->get(route('packages.receipt', $package))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('packages.receipt', $package))->assertForbidden();
    }

    public function test_receipt_is_a_real_inline_a5_pdf(): void
    {
        $this->signIn();
        config(['laravel-pdf.driver' => 'dompdf']);
        $package = PackageFactory::new()->create();

        $response = $this->get(route('packages.receipt', $package));

        $response->assertOk()->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="Resi-'.$package->code.'.pdf"');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+419\.5\d*\s+595\.2\d*\s*\]/', $response->getContent());
    }

    public function test_receipt_contains_shipping_details_cost_and_payment_disclaimer(): void
    {
        $this->signIn();
        Pdf::fake();
        $setting = PackageSettingFactory::new()->create(['name' => 'Tarif Reguler Contoh', 'rate_per_kg' => 15000, 'minimum_charge' => 60000]);
        $package = PackageFactory::new()->create([
            'package_setting_id' => $setting->id,
            'sender_phone' => '081234567890', 'recipient_phone' => '+6281398765432', 'sender_name' => 'Pengirim Resi', 'sender_address' => "Jalan Pengirim No. 1\nPontianak",
            'recipient_name' => 'Penerima Resi', 'recipient_address' => "Jalan Penerima No. 2\nSintang",
            'weight_kg' => 3.5, 'length_cm' => 30, 'width_cm' => 20, 'height_cm' => 10,
            'category' => 'Ongoing', 'financial_category' => 'Kredit', 'status' => 'in_transit', 'description' => 'Barang pecah belah',
        ]);

        $this->get(route('packages.receipt', $package))->assertOk();

        $this->assertReceiptText([$package->code, 'Pengirim Resi', 'Jalan Pengirim No. 1', 'Penerima Resi', 'Jalan Penerima No. 2', 'Ongoing', 'Kredit', 'Tarif Reguler Contoh', 'Dalam Perjalanan', 'Barang pecah belah', '60.000', '3.50', '30.00', '20.00', '10.00', 'Bukti pengiriman, bukan bukti pelunasan.']);
        Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->format === 'a5' && $pdf->downloadName === 'Resi-'.$package->code.'.pdf' && ! $pdf->isDownload());
    }

    #[TestWith(['PKG-DEMO-003'])]
    #[TestWith(['PKG-20261109-A1B2C3'])]
    public function test_receipt_embeds_a_barcode_for_the_requested_package_number(string $code): void
    {
        $this->signIn();
        Pdf::fake();
        $package = PackageFactory::new()->create(['code' => $code]);

        $this->get(route('packages.receipt', $package))->assertOk();

        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($code): bool {
            $html = $pdf->getHtml();
            $this->assertStringContainsString('alt="Barcode '.$code.'"', $html);
            $this->assertSame(1, preg_match('/src="data:image\/svg\+xml;base64,([A-Za-z0-9+\/=]+)"/', $html, $matches));
            $svg = base64_decode($matches[1], true);
            $this->assertNotFalse($svg);
            $this->assertStringContainsString('<desc>'.$code.'</desc>', $svg);
            $this->assertStringContainsString('<rect ', $svg);
            $this->assertStringNotContainsString('https://', $html);

            return true;
        });
    }

    #[TestWith(['pending', 'Pending'])]
    #[TestWith(['pickup', 'Pickup'])]
    #[TestWith(['delivered', 'Terkirim'])]
    #[TestWith(['cancelled', 'Dibatalkan'])]
    public function test_each_package_status_can_be_printed_without_claiming_payment(string $status, string $label): void
    {
        $this->signIn();
        Pdf::fake();
        $package = PackageFactory::new()->create(['status' => $status]);

        $this->get(route('packages.receipt', $package))->assertOk();

        $this->assertReceiptText([$label, 'Bukti pengiriman, bukan bukti pelunasan.']);
    }

    public function test_legacy_package_prints_incomplete_labels_without_guessing_parties(): void
    {
        $this->signIn();
        Pdf::fake();
        $package = PackageFactory::new()->create(['customer_name' => 'Pelanggan Lama', 'sender_name' => null, 'sender_address' => null, 'recipient_name' => null, 'recipient_address' => null]);

        $this->get(route('packages.receipt', $package))->assertOk();

        $this->assertReceiptText(['Belum dilengkapi', 'Data pelanggan lama:', 'Pelanggan Lama']);
    }

    public function test_long_addresses_can_be_rendered_to_a_real_pdf(): void
    {
        $this->signIn();
        config(['laravel-pdf.driver' => 'dompdf']);
        $package = PackageFactory::new()->create(['sender_name' => str_repeat('A', 255), 'sender_address' => str_repeat('Alamat asal lengkap. ', 100), 'recipient_address' => str_repeat('Alamat tujuan lengkap. ', 90)]);

        $response = $this->get(route('packages.receipt', $package));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_template_escapes_untrusted_addresses(): void
    {
        $package = PackageFactory::new()->create(['sender_address' => '<script>alert(1)</script>']);
        $package->load('packageSetting');

        $html = (new PackageReceiptController)($package)->getHtml();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_package_list_links_to_receipt_and_exposes_expandable_details(): void
    {
        $this->signIn();
        $package = PackageFactory::new()->create(['description' => 'Keterangan Paket Tertentu', 'sender_address' => 'Alamat Asal Tertentu', 'recipient_address' => 'Alamat Tujuan Tertentu']);

        Livewire::test('pages::packages.index')->assertSee(route('packages.receipt', $package))
            ->assertSee('Print')->assertSee('Edit')->assertSee('Hapus')->assertSee('Detail Paket')
            ->assertSee('Keterangan Paket Tertentu')->assertSee('Alamat Asal Tertentu')->assertSee('Alamat Tujuan Tertentu');
    }

    public function test_unknown_package_receipt_returns_not_found(): void
    {
        $this->signIn();

        $this->get(route('packages.receipt', ['package' => 999999]))->assertNotFound();
    }
}
