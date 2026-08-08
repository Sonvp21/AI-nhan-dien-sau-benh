<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'agri-index')->name('agri.index');
Route::view('/auth', 'agri-auth')->name('agri.auth');
Route::view('/thong-bao', 'agri-notifications')->name('agri.notifications');
Route::view('/them-ruong', 'agri-add-field')->name('agri.add-field');
Route::view('/thu-vien-sau-benh', 'agri-library')->name('agri.library');
Route::view('/cong-dong', 'agri-community')->name('agri.community');