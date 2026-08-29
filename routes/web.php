<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');

Route::livewire('/login', 'pages::login')
    ->middleware('guest')
    ->name('login');

Route::livewire('/dashboard', 'pages::dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::livewire('/roles-permissions', 'pages::roles-permissions')
    ->middleware('auth')
    ->name('roles-permissions');

Route::livewire('/users', 'pages::users')
    ->middleware('auth')
    ->name('users');

Route::livewire('/cities', 'pages::cities')->middleware('auth')->name('cities');
Route::livewire('/outlets', 'pages::outlets')->middleware('auth')->name('outlets');
Route::livewire('/vehicles', 'pages::vehicles')->middleware('auth')->name('vehicles');
Route::livewire('/drivers', 'pages::drivers')->middleware('auth')->name('drivers');
Route::livewire('/routes', 'pages::routes')->middleware('auth')->name('routes');
Route::livewire('/trips', 'pages::trips')->middleware('auth')->name('trips');

Route::livewire('/packages/statistics', 'pages::package-statistics')
    ->middleware('auth')
    ->name('packages.statistics');

Route::livewire('/packages/settings', 'pages::package-settings')
    ->middleware('auth')
    ->name('packages.settings');

Route::livewire('/packages', 'pages::packages')
    ->middleware('auth')
    ->name('packages');
