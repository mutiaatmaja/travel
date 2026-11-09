<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class PackageReceiptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Package $package): PdfBuilder
    {
        $package->load('packageSetting');

        return Pdf::view('pdf.package-receipt', ['package' => $package])
            ->format('a5')
            ->inline('Resi-'.$package->code.'.pdf');
    }
}
