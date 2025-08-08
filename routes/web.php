<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;

Route::get('/pass',function(){

    $pass = \Illuminate\Support\Facades\Hash::make('nzx6098@pass');
    return $pass;
});
Route::get('/send-test-email', function () {
//    Mail::to('Balraj_s@hotmail.co.uk')->send(new TestEmail());
    Mail::to('talhabinasif365@gmail.com')->send(new TestEmail());
    Mail::to('talha.asif@appollondigitals.com')->send(new TestEmail());
//    Mail::to('Balraj_s@hotmail.co.uk')->send(new TestEmail());
    Mail::to('bsyed22@gmail.com')->send(new TestEmail());
    Mail::to('bsyed1986@gmail.com')->send(new TestEmail());
    Mail::to('muzammillalii@gmail.com')->send(new TestEmail());

    return 'Test email sent successfully!';
});

Route::get('/', function () {
    return view('welcome');
});

Route::get("load-settings",function(){
   $settings = new \App\Services\SettingsService();
   return $settings->getEffectiveSettings();

});

Route::get("all-settings",function(){
    $settings = new \App\Services\SettingsService();
   return $settings->getAllSettings();

});
