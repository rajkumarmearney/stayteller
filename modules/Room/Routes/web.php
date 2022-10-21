<?php
use Illuminate\Support\Facades\Route;

//Review
Route::group(['middleware' => ['auth']],function(){
    Route::get('/rooms',function (){ return redirect('/'); });
    Route::post('/rooms','RoomController@addReview')->name('review.store');
   

});
Route::group(['prefix'=>'room','middleware' => ['auth','verified']],function(){

    Route::get('room','RoomController@index')->name("user.room.index");
    Route::match(['get'],'/create','RoomController@createroom')->name('room.user.create');

});

