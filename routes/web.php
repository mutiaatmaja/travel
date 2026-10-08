<?php

use App\Http\Controllers\BookingTicketController;
use App\Http\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::public.home')->name('home');

Route::livewire('/login', 'pages::public.login')
    ->middleware('guest')
    ->name('login');

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::livewire('/dashboard', 'pages::dashboard.index')
    ->middleware(['auth', 'permission:dashboard.view'])
    ->name('dashboard');

Route::livewire('/roles-permissions', 'pages::master-data.roles-permissions')
    ->middleware(['auth', 'permission:roles-permissions.manage'])
    ->name('roles-permissions');

Route::livewire('/users', 'pages::master-data.users')
    ->middleware(['auth', 'permission:users.manage'])
    ->name('users');

Route::livewire('/cities', 'pages::master-data.cities')->middleware(['auth', 'permission:master-data.manage'])->name('cities');
Route::livewire('/outlets', 'pages::master-data.outlets')->middleware(['auth', 'permission:master-data.manage'])->name('outlets');
Route::livewire('/vehicles', 'pages::master-data.vehicles')->middleware(['auth', 'permission:master-data.manage'])->name('vehicles');
Route::livewire('/drivers', 'pages::master-data.drivers')->middleware(['auth', 'permission:master-data.manage'])->name('drivers');
Route::livewire('/routes', 'pages::master-data.routes')->middleware(['auth', 'permission:master-data.manage'])->name('routes');
Route::livewire('/trips', 'pages::master-data.trips')->middleware(['auth', 'permission:master-data.manage'])->name('trips');
Route::livewire('/route-fares', 'pages::master-data.route-fares')->middleware(['auth', 'permission:route-fare.manage'])->name('route-fares');

Route::livewire('/packages/statistics', 'pages::packages.statistics')
    ->middleware(['auth', 'permission:packages.manage'])
    ->name('packages.statistics');

Route::livewire('/packages/settings', 'pages::packages.settings')
    ->middleware(['auth', 'permission:packages.manage'])
    ->name('packages.settings');

Route::livewire('/packages', 'pages::packages.index')
    ->middleware(['auth', 'permission:packages.manage'])
    ->name('packages');

Route::livewire('/packages/tracing', 'pages::packages.tracing')
    ->middleware(['auth', 'permission:packages.manage'])
    ->name('packages.tracing');

Route::livewire('/booking/settings', 'pages::booking.settings')
    ->middleware(['auth', 'permission:booking.settings.manage'])
    ->name('booking.settings');

Route::livewire('/booking', 'pages::booking.booking')
    ->middleware(['auth', 'permission:booking.view'])
    ->name('booking');

Route::livewire('/booking/status', 'pages::booking.status')
    ->middleware(['auth', 'permission:booking.view'])
    ->name('booking.status');

Route::livewire('/booking/fleet-condition', 'pages::booking.fleet-condition')
    ->middleware('auth')
    ->name('booking.fleet-condition');

Route::livewire('/booking/trips', 'pages::booking.trip-overview')
    ->middleware(['auth', 'permission:trip.view'])
    ->name('booking.trips');

Route::get('/booking/{booking}/ticket.pdf', BookingTicketController::class)
    ->middleware(['auth', 'permission:booking.view'])
    ->name('booking.ticket');
