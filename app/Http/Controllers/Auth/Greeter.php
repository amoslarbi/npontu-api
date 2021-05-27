<?php

namespace App\Http\Controllers\Auth;
// use Config;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Models\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use JWTAuth;
use JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Ap;
use Illuminate\Support\Facades\Auth;

class Greeter extends Controller
{

    protected function adminLogin(Request $request)
    {

        $adminLoginQuery = DB::select('SELECT * FROM `admin` WHERE `username` = ? AND `password` = ?', [$request->username, $request->password]);
        queryChecker($adminLoginQuery, 'Admin Login');
        if (count($adminLoginQuery) === 1) {
            return response()->json([
                'success' => true,
                'message' =>   'working',
                'data' =>   $adminLoginQuery
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' =>   'not working',
            ]);
        }

    }

    protected function login(Request $request)
    {

        $twoFactorFinalStatus = "";
        
        $consumerLoginQuery = DB::select('SELECT * FROM consumers WHERE email = ? AND account_status = ?', [$request->email, 'Approved']);
        queryChecker($consumerLoginQuery, 'Login query');
        if(count($consumerLoginQuery) === 1){

            foreach($consumerLoginQuery as $consumerLoginQueryResult){
                $passwordFromDB = $consumerLoginQueryResult->cpassword;
            }

            if(Hash::check($request->password, $passwordFromDB)){

            foreach ($consumerLoginQuery as $consumerLoginQueryResult) {

                if($consumerLoginQueryResult->twoFactorStatus == "yes"){
                    $twoFactorFinalStatus = "email";
                }
                if($consumerLoginQueryResult->twoFactorStatus == "yes"){
                    $twoFactorFinalStatus = "phone";
                }

                if($twoFactorFinalStatus == "email"){

                    function verificationCode() {
                        $alphabet = "0123456789";
                        $pass = array(); //remember to declare $pass as an array
                        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
                        for ($i = 0; $i < 6; $i++) {
                            $n = rand(0, $alphaLength);
                            $pass[] = $alphabet[$n];
                        }
                        return implode($pass); //turn the array into a string
                    }
                    
                    $otpCode = verificationCode();
                  
                    $updateOTPEmailQuery = DB::update('UPDATE consumers SET twoFactorCode = ? WHERE email = ?', [$otpCode, $consumerLoginQueryResult->email]);
                    queryChecker($updateOTPEmailQuery, 'Update consumers for two factor code');

                    if($updateOTPEmailQuery > 0 ){
                  
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                      CURLOPT_URL => "https://api.sendgrid.com/v3/mail/send",
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => "",
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => "POST",
                      CURLOPT_POSTFIELDS => '{
                        "personalizations": [
                          {
                            "to": [
                              {
                                "email": '.$consumerLoginQueryResult->email.',
                              }
                            ],
                            "dynamic_template_data": {
                              "firstName": '.$consumerLoginQueryResult->firstName.',
                              "otpCode": '.$consumerLoginQueryResult->twoFactorCode.',
                            },
                            "subject": ""
                          }
                        ],
                        "from": {
                            "email": "'.Config::get('app.OTOBOOK_EMAIL').'",
                            "name": "'.Config::get('app.OTOBOOK_EMAIL_NAME').'"
                        },
                        "template_id": "d-2babff30b945445f911cce53268853e2"
                      }',
                      CURLOPT_HTTPHEADER => array(
                        "authorization: Bearer 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",
                        "content-type: application/json"
                      ),
                    ));
                  
                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                  
                    curl_close($curl);
                  
                    if ($err) {
                      return response()->json([
                        'success' => false,
                        'message' =>   'Unable to send email',
                        'error' =>   $err,
                      ], 400);
                    } else {
                        return response()->json([
                            'success' => true,
                            'message' =>   'two factor email',
                        ], 200);
                    }
                  
                    }
                    else{
                        return response()->json([
                            'success' => false,
                            'message' =>   'Could not connect to server',
                        ], 400);
                    }
                  
                }
                else if($twoFactorFinalStatus == "phone"){
                        // send email code
                        // echo json_encode("two factor phone");
                
                    function verificationCode() {
                        $alphabet = "0123456789";
                        $pass = array(); //remember to declare $pass as an array
                        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
                        for ($i = 0; $i < 6; $i++) {
                            $n = rand(0, $alphaLength);
                            $pass[] = $alphabet[$n];
                        }
                        return implode($pass); //turn the array into a string
                    }
                    
                    $otpCode = verificationCode();
                    
                    $updateOTPPhoneQuery = DB::update('UPDATE consumers SET twoFactorCode = ? WHERE email = ?', [$otpCode, $consumerLoginQueryResult->email]);
                    queryChecker($updateOTPPhoneQuery, 'Update consumers for two factor code');

                    if($updateOTPPhoneQuery > 0 ){
                
                    $key = "2YfogeuUg5HVBYBxmY4GELu7x";
                    $to = $consumerLoginQueryResult->phoneNumber;
                
                    // $msg = "A sign in attempt requires further verification. To complete the sign in, enter the verification code below in the OTP field on the sign in page. Verification code: $otpCode";
                    $msg = "Verification code: $otpCode";
                    $sender_id = "otobook"; //11 Characters maximum
                    $msg = urlencode($msg);
                
                    //prepare your url
                    $url = "https://apps.mnotify.net/smsapi?"
                    . "key=$key"
                    . "&to=$to"
                    . "&msg=$msg"
                    . "&sender_id=$sender_id";
                    //. "&date_time=$date_time";
                
                    $response = file_get_contents($url) ;
                    if ($response) {
                        return response()->json([
                            'success' => true,
                            'message' =>   'two factor phone',
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' =>   'Could not connect to server',
                        ]);
                    }
                    
                    }
                    else{
                        return response()->json([
                            'success' => false,
                            'message' =>   'Could not connect to server',
                        ]);
                    }
                
                    }else{
                    if($consumerLoginQueryResult->account_type == "consumers"){

                        $validator = Validator::make($request->all(), [
                            'email' => 'required|email',
                            'password' => 'required|string|min:6',
                        ]);
                
                        if ($validator->fails()) {
                            return response()->json($validator->errors(), 422);
                        }

                        // $user = User::where('email','=',$request->email)->first();
                        $user = User::query()->table('consumers')->where('email','=',$request->email)->first();
                        if (!$token = JWTAuth::fromUser($user)) {
                        // if (! $token = JWTAuth::attempt($validator->validated())) {
                            return response()->json(['error' => 'Unauthorized'], 401);
                        }
                
                        return createNewToken($token, $consumerLoginQueryResult);

                    }else{
                        return response()->json([
                            'success' => false,
                            'message' =>   'Username or password does not exist',
                        ]);
                    }
                }

            }

            DB::insert('INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)', [$consumerLoginQueryResult->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer logged in', NOW()]);

        }
        else{
            return response()->json([
                'success' => false,
                'message' =>   'Username or password does not exist',
            ]);
        }

    }
    else{
        return response()->json([
            'success' => false,
            'message' =>   'Username or password does not exist',
        ]);
    }

    }

    protected function register(Request $request)
    {

    $finalUIURL = '';

    if(strpos($_SERVER['HTTP_HOST'], "127") !== false){
        $finalUIURL = "http://localhost/npontu-api/";
    }
    else{
        $finalUIURL = Config::get('app.UI_BASE_URL');
    }

        $checkDuplicateUser = DB::select('SELECT * FROM users WHERE email = ? AND (account_status = ? OR account_status = ?)', [$request->email, 'Pending', 'Approved']);
        queryChecker($checkDuplicateUser, 'Select consumers');

        if(count($checkDuplicateUser) > 0){
            return response()->json([
                'success' => true,
                'message' => 'already',
            ]);
        }

        //uuid generated start
        function guidv4($data)
        {
            assert(strlen($data) == 16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
        //uuid generated end

        $uuid = guidv4(openssl_random_pseudo_bytes(16));

        $insertNewUser = DB::insert('INSERT INTO users (uuid, firstName, otherNames, email, cpassword, account_status, createdAT) 
        VALUES (?, ?, ?, ?, ?, ?, ?)', [$uuid, $request->firstName, $request->otherNames, $request->email, Hash::make($request->password), $request->account_status, NOW()]);
        queryChecker($insertNewUser, 'Insert into users');

        return response()->json([
            'success' => true,
            'message' =>   'working',
         ]);

            // //Email start
            // $curl = curl_init();
            // curl_setopt_array($curl, array(
            //     CURLOPT_URL => "https://api.sendgrid.com/v3/mail/send",
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => "",
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 30,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => "POST",
            //     CURLOPT_POSTFIELDS => '{
            //         "personalizations": [
            //         {
            //             "to": [
            //             {
            //                 "email": '.$request->email.',
            //             }
            //             ],
            //             "dynamic_template_data": {
            //             "firstName": '.$request->firstName.',
            //             },
            //             "subject": ""
            //         }
            //         ],
            //         "from": {
            //         "email": "'.Config::get('app.OTOBOOK_EMAIL').'",
            //         "name": "'.Config::get('app.OTOBOOK_EMAIL_NAME').'"
            //         },
            //         "template_id": "d-f9226f096f924602acb2ece64018f68c"
            //     }',
            //     CURLOPT_HTTPHEADER => array(
            //         "authorization: Bearer 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",
            //         "content-type: application/json"
            //     ),
            //     ));
                
            //     $response = curl_exec($curl);
            //     $err = curl_error($curl);
                
            //     curl_close($curl);
                
            //     if ($err) {
            //         return response()->json([
            //             'success' => false,
            //             'message' =>   'Unable to send email',
            //         ]);
            //     } else {
            //     return response()->json([
            //         'success' => true,
            //         'message' =>   'working',
            //     ]);
            //     }
            //     //Email end

    }

}
