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

        $consumerLoginQuery = DB::select('SELECT * FROM users WHERE email = ? AND account_status = ?', [$request->email, 'Approved']);
        queryChecker($consumerLoginQuery, 'Login query');
        if (count($consumerLoginQuery) === 1) {

            foreach ($consumerLoginQuery as $consumerLoginQueryResult) {
                $passwordFromDB = $consumerLoginQueryResult->cpassword;
            }

            if (Hash::check($request->password, $passwordFromDB)) {

                foreach ($consumerLoginQuery as $consumerLoginQueryResult) {

                    $validator = Validator::make($request->all(), [
                        'email' => 'required|email',
                        'password' => 'required|string|min:6',
                    ]);

                    if ($validator->fails()) {
                        return response()->json($validator->errors(), 422);
                    }

                    $user = User::query()->table('consumers')->where('email', '=', $request->email)->first();
                    if (!$token = JWTAuth::fromUser($user)) {
                        return response()->json(['error' => 'Unauthorized'], 401);
                    }

                    return createNewToken($token, $consumerLoginQueryResult);
                }
            }
        }
    }

    protected function register(Request $request)
    {

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
