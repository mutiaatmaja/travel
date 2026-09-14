<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::public.home')->name('home');

Route::livewire('/login', 'pages::public.login')
    ->middleware('guest')
    ->name('login');

Route::livewire('/dashboard', 'pages::dashboard.index')
    ->middleware('auth')
    ->name('dashboard');

Route::livewire('/roles-permissions', 'pages::master-data.roles-permissions')
    ->middleware('auth')
    ->name('roles-permissions');

Route::livewire('/users', 'pages::master-data.users')
    ->middleware('auth')
    ->name('users');

Route::livewire('/cities', 'pages::master-data.cities')->middleware('auth')->name('cities');
Route::livewire('/outlets', 'pages::master-data.outlets')->middleware('auth')->name('outlets');
Route::livewire('/vehicles', 'pages::master-data.vehicles')->middleware('auth')->name('vehicles');
Route::livewire('/drivers', 'pages::master-data.drivers')->middleware('auth')->name('drivers');
Route::livewire('/routes', 'pages::master-data.routes')->middleware('auth')->name('routes');
Route::livewire('/trips', 'pages::master-data.trips')->middleware('auth')->name('trips');

Route::livewire('/packages/statistics', 'pages::packages.statistics')
    ->middleware('auth')
    ->name('packages.statistics');

Route::livewire('/packages/settings', 'pages::packages.settings')
    ->middleware('auth')
    ->name('packages.settings');

Route::livewire('/packages', 'pages::packages.index')
    ->middleware('auth')
    ->name('packages');

Route::livewire('/packages/tracing', 'pages::packages.tracing')
    ->middleware('auth')
    ->name('packages.tracing');

Route::livewire('/booking/settings', 'pages::booking.settings')
    ->middleware('auth')
    ->name('booking.settings');

Route::livewire('/booking', 'pages::booking.booking')
    ->middleware('auth')
    ->name('booking');

Route::livewire('/booking/status', 'pages::booking.status')
    ->middleware('auth')
    ->name('booking.status');

Route::livewire('/booking/fleet-condition', 'pages::booking.fleet-condition')
    ->middleware('auth')
    ->name('booking.fleet-condition');

Route::livewire('/booking/trips', 'pages::booking.trip-overview')
    ->middleware('auth')
    ->name('booking.trips');
