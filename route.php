<?php

Route::get('/ogone/thanks', [
	'as'	=> 'ogone.thanks',
	'uses'	=> 'OgoneController@thanks'
]);

Route::get('/ogone/cancel', [
	'as'	=> 'ogone.cancel',
	'uses'	=> 'OgoneController@cancel'
]);

Route::post('/ogone/callback', [
	'as'	=> 'ogone.callback',
	'uses'	=> 'OgoneController@callback'
]);
