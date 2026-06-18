<?php

Route::livewire('/', 'pages::auth.login')->name('login');
Route::livewire('/admin/dashboard', 'pages::admin.idx')->name('admin');
Route::livewire('/admin/client','pages::admin.client.idx')->name('admin.client');
Route::livewire('/client','pages::client.idx')->name('client');
