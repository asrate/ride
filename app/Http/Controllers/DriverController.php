<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DriverController extends Controller
{
    //
    public function show( Request $request){
       // Retrieve the authenticated user 
$user = $request->user();
 // Load the 'driver' relationship for the user
$user->load('driver');
// Return the user object, including the driver information
return $user;
    }
    public function update( Request $request ){
$request->validate([
    'year' => 'required|numeric|between:2010,2024',
    'make' => 'required',
    'model' => 'required',
    'color' => 'required',
    'license_plate' => 'required',
    'name' => 'required'
]);
// Retrieve the authenticated user
$user=$request->user();
// Update the user's name with the value from the request
$user->update($request->only('name'));
$user->drive()->updateOrCreate($request->only([
    'year',
   'make',
   'model',
    'color',
    'license_plate'
]));
$user->load('driver');
return $user;
    }
}
