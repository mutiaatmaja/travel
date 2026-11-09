<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;
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

        $barcode = (new TypeCode128)->getBarcode($package->code);
        $barcodeSvg = (new SvgRenderer)->render($barcode, $barcode->getWidth() * 2, 60);

        return Pdf::view('pdf.package-receipt', [
            'package' => $package,
            'barcodeDataUri' => 'data:image/svg+xml;base64,'.base64_encode($barcodeSvg),
        ])
            ->format('a5')
            ->inline('Resi-'.$package->code.'.pdf');
    }
}
