<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class BookingTicketController extends Controller
{
    public function __invoke(Booking $booking): PdfBuilder
    {
        abort_unless($booking->status === 'confirmed', 404);

        $booking->load([
            'trip.travelRoute.originCity',
            'trip.travelRoute.destinationCity',
            'trip.vehicle',
            'originStop.outlet.city',
            'destinationStop.outlet.city',
            'seats.vehicleSeat',
        ]);

        return Pdf::view('pdf.booking-ticket', ['booking' => $booking])
            ->format('a5')
            ->inline($booking->booking_code.'.pdf');
    }
}
