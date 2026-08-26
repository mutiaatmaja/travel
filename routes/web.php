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
