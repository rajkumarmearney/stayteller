<?php
use Illuminate\Support\Facades\Route;

//Review
Route::group(['middleware' => ['auth']],function(){
    Route::get('/rooms',function (){ return redirect('/'); });
    Route::post('/rooms','RoomController@addReview')->name('review.store');
   

});

