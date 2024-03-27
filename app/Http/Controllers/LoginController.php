<?php

namespace App\Http\Controllers;

use App\Notifications\LoginNeedVerifications;
use Illuminate\Http\Request;
use App\Models\User;
// use App\Http\Controllers\User;

class LoginController extends Controller
{
    public function submit(Request $request){
       
        // validate the phone number
        // return env('AFRO_TOKEN');
        $request->validate([
            'phone' => 'required|regex:/^\d{10}$/'
        ]);
        // return "Phone number irets valid";
        try { 
            $loginCode = rand(111111, 999999);
            $user = User::firstOrCreate([
                
                'phone' => $request->phone,],[

                'login_code' =>$loginCode,
               
                // $notifiable->update(['login_code' => $loginCode]);
            ]);
        } catch (\Throwable $th) {
            return $th;
        }
        //  find or create the user a model
        
        
        if(!$user){
            return response()->json([
             'status' => 'error',
             'message' => 'phone number not found'
            ], 401);
        }
        
        // send the user a one-time use code
        $this->afroMessage($user);
                

        return response()->json(['message' =>'Text message notification sent']);

        
        // retunr back a response 
    }
    public function verify(Request $request){

        // validate the incoming request
        $request->validate([
            'phone' => 'required|numeric|min:10',
            'login_code'=> 'required|numeric|between:111111,999999'
        ]);
    
        // find the user
        $user =User::where('phone',$request->phone)
            ->where('login_code',$request->login_code)
            ->first();
    
        // if user is found, create and return the token along with the user details
        if($user){
            try {
                $token = $user->createToken('login_code')->plainTextToken;
                return response()->json(['user' => $user, 'token' => $token], 200);
            } catch (Exception $e) {
                // Log the exception
                Log::error('Token creation failed: ' . $e->getMessage());
                // Return an error response or handle the error as needed
                return response()->json(['error' => 'Token creation failed'], 500);
            }
        }
        
        // if user is not found, return an error message
        return response()->json([
            'status' => 'error',
            'message' => 'invalid login code'
        ], 401);
    }
    
    //  public function logout(Request $request){
    //     $request->user()->tokens()->delete();
    //     return response()->json(['message' => 'Successfully logged out']);
    // }
    public function afroMessage($user){

        // $user =User::where('phone',$request->phone)
        // $loginCode = rand(111111, 999999);
        // $notifiable->update(['login_code' => $loginCode]);
        /** We use php cURL for the samples **/
        // return $notifiable;
    $ch = curl_init();
    // base url
	$url = 'https://api.afromessage.com/api/send';
	$token = env('AFRO_TOKEN');
	$to = $user->phone;
    $from = env('YOUR_IDENTIFIER_ID');
    $sender = env('YOUR_SENDER_NAME');
    // message should be URL encoded
	$message = $user->login_code;
	

    /** set request options **/
	curl_setopt($ch, CURLOPT_URL, $url . '?from=' . $from . '&sender=' .$sender . '&to=' . $to . '&message=' . $message );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    /** Add headers **/
	$headers = array();
	$headers[] = 'Authorization: Bearer '.$token;
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    /** Execute request **/
	$result = curl_exec($ch);

    /** Handle response **/
	if (curl_errno($ch)) {
        // general http error
		echo 'Error:' . curl_error($ch);
    } else {	
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		switch ($http_code) {
	    case 200:
                /** response object ... inspect the `acknowledge` node and act accordingly **/
				$data = json_decode($result,true);
				if ($data['acknowledge'] == 'success') {
					echo "Api success";
                }else{
					echo "Api failure";
                }
				break;
	    default:
          /** most probably authorization error ... inspect response**/
	      echo 'Other HTTP Code: ', $http_code;
        }
    }
    /** finish call **/
	curl_close ($ch);
    }

   

}
