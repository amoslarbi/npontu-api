<?php

namespace App\Http\Controllers\Consumers;

use Illuminate\Support\Facades\Config;
use App\Models\User;
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
use App\Http\Controllers\Consumers\Pagination;
use Dompdf\Helpers as DompdfHelpers;

class Consumers extends Controller
{

    protected function testing(Request $request)
    {

        // telegramCriticalFailuresBot("Hi! Admin\nEndpoint execution failed, find details:\nEndpoint: add_skip_user\nError: " . response()->json(['error' => $response]) . "\nTimestamp: " . date("F j, Y, g:i a"));
        telegramCriticalFailuresBot(urlencode("Hi! Admin\nEndpoint execution failed, find details:\nEndpoint: add_skip_user\nError: "  . "\nTimestamp: " . date("F j, Y, g:i a")), "-507050651");
    }

    protected function contactUs(Request $request)
    {

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
                    "email": "otobookghana@gmail.com",
                }
                ],
                "dynamic_template_data": {
                "message": "' . $request->message . '",
                },
                "subject": "' . $request->subject . '",
            }
            ],
            "from": {
                "email": "' . $request->email . '",
                "name": "' . $request->fullName . '"
            },
            "template_id": "d-b77fbe5a12ae4edc9639aef20d4d1cf1"
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
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' =>   'working',
            ]);
        }
    }

    protected function earlyAccess(Request $request)
    {

        $earlyAccess = DB::SELECT("SELECT * FROM early_access WHERE email = ? ", [$request->email]);
        queryChecker($earlyAccess, 'Check Early access');
        // dd(queryChecker($earlyAccess, 'Check Early access'));
        if (count($earlyAccess) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'already',
            ]);
        }

        $postEarlyAccess = DB::INSERT("INSERT INTO early_access (email, createdAt) VALUES (?, ?)", [$request->email, NOW()]);
        queryChecker($postEarlyAccess, 'Early access');

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
                    "email": "' . $request->email . '",
                }
                ],
                "dynamic_template_data": {
                "url": "https://www.surveymonkey.com/r/2B5RQFM",
                },
                "subject": "Big thank you for subscribing to out early access.",
            }
            ],
            "from": {
                "email": "' . Config::get('app.OTOBOOK_EMAIL') . '",
                "name": "' . Config::get('app.OTOBOOK_EMAIL_NAME') . '"
            },
            "template_id": "d-537fa7506e144320acedab68983ed37c"
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
            ]);
        } else {
            $countSubscribers = DB::SELECT("SELECT * FROM early_access");
            queryChecker($countSubscribers, 'Count early access subscribers');
            telegramComingSoonBot(urlencode("Hi Admin!\nYou have a new subscriber.\nYour total number of subscribers is now: " . count($countSubscribers) . "\nFind details of new subscriber:\n\nEmail: " . $request->email . "\nTimestamp: " . date("F j, Y, g:i a")), "-576962548");
            return response()->json([
                'success' => true,
                'message' => 'working',
            ]);
        }
    }

    protected function tracking(Request $request)
    {
        if ($request->trackingType == "otobook") {
            $tracking = DB::SELECT("SELECT * FROM orders WHERE orderId = ? GROUP BY orderId", [$request->orderId]);
            queryChecker($tracking, 'Tracking query');
            if (count($tracking) === 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'working',
                    'data' => $tracking,
                    'type' => 'otobook',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'not working',
                ]);
            }
        } else {

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api-eu.dhl.com/track/shipments?trackingNumber=$request->orderId&language=en&limit=5",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "DHL-API-Key: Tub8NNE0TQKNG5fV5qCZBuPLj53nhCyJ",
                    "Cache-Control: no-cache",
                    "accept: application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $getDHLCurlResponse = json_decode($response, true);

            if ($err) {
                return response()->json([
                    'success' => false,
                    'message' => 'not working',
                    'error' => $err,
                ]);
            }
            if (count($getDHLCurlResponse) === 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'not working',
                    'error' => $response
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'working',
                'data' => $response,
                'type' => 'dhl',
            ]);
        }
    }

    protected function updateProfile(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $updateProfile = DB::UPDATE("UPDATE consumers SET firstName = ?, otherNames = ?, dateOfBirth = ? WHERE uuid = ? ", [$request->firstName, $request->otherNames, $request->dateOfBirth, $user->uuid]);
        queryChecker($updateProfile, 'Update consumer profile');
        if ($updateProfile > 0) {

            $selectConsumer = DB::SELECT("SELECT * FROM consumers WHERE uuid = ? ", [$user->uuid]);
            queryChecker($selectConsumer, 'Select consumer details');
            if (count($selectConsumer) === 1) {

                $activityLog = DB::INSERT(
                    "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer updated their profile', NOW()]
                );
                queryChecker($activityLog, 'Activity Log');

                return response()->json([
                    'success' => true,
                    'message' => 'working',
                    'data' => $selectConsumer,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not connect to server',
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No changes were recorded',
            ]);
        }
    }

    protected function vin(Request $request)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            // CURLOPT_URL => "http://api.carmd.com/v3.0/decode?vin=1GNALDEK9FZ108495",
            CURLOPT_URL => "http://api.carmd.com/v3.0/decode?vin=$request->vinNumberInput",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                // "x-rapidapi-host: vindecoder.p.rapidapi.com",
                // "x-rapidapi-key: 27f3efea4emsh6668b91f444430ap11614ejsnff5d9fcaedbf"

                "content-type: application/json",
                "authorization: Basic MWMwOThjMTItZGI5NC00ODczLWI2MmMtMjc5NzBjODg4NjZi",
                "partner-token: d313efc56c954a288f5fe2dd04a352e2"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return response()->json([
                'success' => false,
                'message' =>   'Unable to send email',
            ]);
        } else {
            // echo $response;
            return response()->json([
                'success' => true,
                'message' =>   'working',
                'data' =>   $response,
            ]);
        }
    }

    protected function updatePrefrences(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $updateConsumers = DB::UPDATE("UPDATE `consumers` SET `address` = ?, `phoneNumber` = ?, `language` = ?, `ghanaPost` = ? WHERE `uuid` = ? ", [$request->editConsumerAddress, $request->editConsumerPhoneNumber, $request->editConsumerLanguage, $request->editGhanaPost, $user->uuid]);
        queryChecker($updateConsumers, 'Update consumer preferences query');
        if ($updateConsumers > 0) {

            $selectConsumer = DB::SELECT("SELECT * FROM consumers WHERE uuid = ? ", [$user->uuid]);
            queryChecker($selectConsumer, 'Select consumer details query');
            if (count($selectConsumer) === 1) {

                $activityLog = DB::INSERT(
                    "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer updated their preferences', NOW()]
                );
                queryChecker($activityLog, 'Activity Log');

                return response()->json([
                    'success' => true,
                    'message' => 'working',
                    'data' => $selectConsumer,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not connect to server',
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No changes recorded',
            ]);
        }
    }

    protected function updateCart(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $updateCart = DB::UPDATE("UPDATE cart SET quantity = ? WHERE cartId = ? ", [$request->quantity, $request->cartId]);
        queryChecker($updateCart, 'Update cart query');

        $selectCart = DB::SELECT("SELECT * FROM cart WHERE consumerUuid = ?", [$user->uuid]);
        queryChecker($selectCart, 'Select from cart query');

        $forSubtotal = '';
        $forShippingFee = '';
        $quantityKiev = '';

        foreach ($selectCart as $selectCartResult) {
            $subtotal = $selectCartResult->unitPrice;
            $shippingFee = $selectCartResult->shippingFee;
            $quantity = $selectCartResult->quantity;
            $quantityKiev += $quantity;
            $forSubtotal += $subtotal;
            $forShippingFee += $shippingFee;

            array_push($someArray, [
                'forSubtotal'   => $forSubtotal,
                'forShippingFee'   => $forShippingFee,
                'forQuantityKiev'   => $quantityKiev,
            ]);
        }

        $updateConsumerTable = DB::UPDATE("UPDATE consumers SET cartCount = ? WHERE uuid = ? ", [$quantityKiev, $user->uuid]);
        queryChecker($updateConsumerTable, 'Update consumers cart count query');
        if ($updateConsumerTable > 0) {

            $activityLog = DB::INSERT(
                "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer updated their cart', NOW()]
            );
            queryChecker($activityLog, 'Activity Log');

            $someJSON = json_encode($someArray);
            echo $someJSON;
        }
    }

    protected function twoFactorViaPhone(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $twoFactor = DB::UPDATE("UPDATE consumers SET twoFactorPhoneStatus = ? WHERE uuid = ? ", [$request->twoFactor, $user->uuid]);
        queryChecker($twoFactor, 'Activate email two factor query');

        if ($twoFactor > 0) {

            $getConsumers = DB::SELECT("SELECT * FROM consumers WHERE uuid = ? ", [$user->uuid]);
            queryChecker($getConsumers, 'Get sonsumers query');
            if (count($getConsumers) === 1) {

                $activityLog = DB::INSERT(
                    "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer opted for mobile two factor', NOW()]
                );
                queryChecker($activityLog, 'Activity Log');

                return response()->json([
                    'success' => true,
                    'message' => 'working',
                    'data'  => $getConsumers
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Could not connect to server',
            ]);
        }
    }

    protected function twoFactor(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $twoFactor = DB::UPDATE("UPDATE consumers SET twoFactorStatus = ? WHERE uuid = ? ", [$request->twoFactor, $user->uuid]);
        queryChecker($twoFactor, 'Activate email two factor query');

        if ($twoFactor > 0) {

            $getConsumers = DB::SELECT("SELECT * FROM consumers WHERE uuid = ? ", [$user->uuid]);
            queryChecker($getConsumers, 'Get sonsumers query');
            if (count($getConsumers) === 1) {

                $activityLog = DB::INSERT(
                    "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer opted for email two factor', NOW()]
                );
                queryChecker($activityLog, 'Activity Log');

                return response()->json([
                    'success' => true,
                    'message' => 'working',
                    'data'  => $getConsumers
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Could not connect to server',
            ]);
        }
    }

    protected function reviewsBack(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $totalReviews = 0;
        $allRatings = 0;
        $theRating = 0;
        $someArray = [];
        $output = '';

        $reviewsBack = DB::SELECT("SELECT reviews.*, consumers.* FROM reviews INNER JOIN consumers ON reviews.consumerUuid = consumers.uuid WHERE reviews.productId = ? ORDER BY reviews.id DESC", [$request->productId]);
        queryChecker($reviewsBack, 'Get reviews query');

        foreach ($reviewsBack as $reviewsBackresult) {
            $old_date_timestamp = strtotime($reviewsBackresult->createdAt);
            $new_date = date('l, F d yy h:i a', $old_date_timestamp);

            if ($user->uuid == $reviewsBackresult->consumerUuid) {
                $deleteRating = '<li class="review">&nbsp; &nbsp; • &nbsp; &nbsp;<li><em style="cursor: pointer;" data-id="' . $reviewsBackresult->reviewId . '" style="color: red;" class="deleteReview icon ni ni-trash-fill"></em></li> </li>';
            }

            $totalReviews = count($reviewsBack);
            $allRatings += $reviewsBackresult->rating;
            $theRating = $allRatings / $totalReviews;

            if ($reviewsBackresult->rating == 1) {
                $getRatingStars = '
                <div class="star_rating">
                    <ul>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        ' . $deleteRating . '
                    </ul>
                </div>';
            }

            if ($reviewsBackresult->rating == 2) {
                $getRatingStars = '
                <div class="star_rating">
                    <ul>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        ' . $deleteRating . '
                    </ul>
                </div>';
            }

            if ($reviewsBackresult->rating == 3) {
                $getRatingStars = '
                <div class="star_rating">
                    <ul>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        ' . $deleteRating . '
                    </ul>
                </div>';
            }

            if ($reviewsBackresult->rating == 4) {
                $getRatingStars = '
                <div class="star_rating">
                    <ul>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star"></em></li>
                        ' . $deleteRating . '
                    </ul>
                </div>';
            }

            if ($reviewsBackresult->rating == 5) {
                $getRatingStars = '
                <div class="star_rating">
                    <ul>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        <li><em style="color: #0337cb;" class="icon ni ni-star-fill"></em></li>
                        ' . $deleteRating . '
                    </ul>
                </div>';
            }

            $output .= '
        <div class="reviews_comment_box">
            <div class="comment_thmb">
                <img style="width: 95px; height: 95px; object-fit: contain;" src=' . $reviewsBackresult->avatar . ' alt="">
            </div>

            <div class="comment_text" style="border-top: 1px solid #0037cb">
                <div class="reviews_meta">
                        ' . $getRatingStars . '
                    <p><strong style="text-transform: capitalize;">' . $reviewsBackresult->firstName . ' ' . $reviewsBackresult->otherNames . '</strong> - ' . $new_date . '</p>
                    <span>' . $reviewsBackresult->reviews . '</span>
                </div>
            </div>
        </div>
        ';

            array_push($someArray, [
                'mainOutput'   => $output,
                'reviewCount'   => $theRating,
                'justCount'   => $totalReviews,
            ]);
        }

        $someJSON = json_encode($someArray);
        return response()->json([
            'success' => true,
            'message' => 'working',
            'data' => $someArray,
        ]);
        // echo $someJSON;

    }

    protected function reviews(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        if ($request->rating == 0) {
            $request->rating = 1;
        }

        $insertReview = DB::INSERT("INSERT INTO reviews (reviewId, productId, consumerUuid, rating, reviews, createdAt) VALUES (?, ?, ?, ?, ?, ?)", [$request->reviewId, $request->productId, $user->uuid, $request->rating, $request->review, NOW()]);
        queryChecker($insertReview, 'Insert review query');

        $activityLog = DB::INSERT(
            "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$request->email, $request->ip, $request->os, $request->browser, $request->device, 'Consumer added a review', NOW()]
        );
        queryChecker($activityLog, 'Activity Log');

        return response()->json([
            'success' => true,
            'message' =>   'working',
        ]);
    }

    protected function paystackVerify(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/$request->referenceId",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer sk_test_70a668db5d98abe38a79731557274efcefef3271",
                "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return response()->json([
                'success' => false,
                'message' =>   'Unable to send email',
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' =>   'working',
                'data' =>   $response,
            ]);
        }
        // echo $response;

    }

    protected function resendPhoneOTP(Request $request)
    {

        $selectConsumersPhoneOTP = DB::SELECT("SELECT * FROM consumers WHERE email = ? ", [$request->email]);
        queryChecker($selectConsumersPhoneOTP, 'Select consumers for resend phone OTP');
        if (count($selectConsumersPhoneOTP) === 1) {

            foreach ($selectConsumersPhoneOTP as $selectConsumersPhoneOTPResult) {
                $firstName = $selectConsumersPhoneOTPResult->firstName;
                $phoneNumber = $selectConsumersPhoneOTPResult->phoneNumber;

                function verificationCode()
                {
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

                $updateConsumers = DB::UPDATE("UPDATE consumers SET twoFactorPhoneCode = ? WHERE email = ? ", [$otpCode, $request->email]);
                queryChecker($updateConsumers, 'Update consumers');
                if ($updateConsumers > 0) {

                    $key = Config::get('app.MNOTIFY');  // Remember to put your own API Key here
                    $to = $phoneNumber;
                    //$to = "87867";

                    $msg = "A sign in attempt requires further verification. To complete the sign in, enter the verification code below in the OTP field on the sign in page. Verification code: $otpCode";
                    $sender_id = "otobook"; //11 Characters maximum
                    //$date_time = "2017-05-02 00:59:00";

                    //encode the message
                    $msg = urlencode($msg);

                    //prepare your url
                    $url = "https://apps.mnotify.net/smsapi?"
                        . "key=$key"
                        . "&to=$to"
                        . "&msg=$msg"
                        . "&sender_id=$sender_id";
                    //. "&date_time=$date_time";

                    $response = file_get_contents($url);

                    if ($response) {
                        $activityLog = DB::INSERT(
                            "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [$request->email, $request->ip, $request->os, $request->browser, $request->device, 'Consumer opted for a new mobile OTP', NOW()]
                        );
                        queryChecker($activityLog, 'Activity Log');

                        return response()->json([
                            'success' => true,
                            'message' =>   'two factor phone',
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' =>   'Unable to send email',
                        ]);
                    }
                } else {
                    echo json_encode("issue");
                }
            }
        }
    }

    protected function resendEmailOTP(Request $request)
    {

        $selectConsumers = DB::SELECT("SELECT * FROM consumers WHERE email = ? ", [$request->email]);
        queryChecker($selectConsumers, 'Select consumers');
        if (count($selectConsumers) === 1) {

            foreach ($selectConsumers as $selectConsumersResult) {
                $firstName = $selectConsumersResult->firstName;

                function verificationCode()
                {
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

                $updateConsumers = DB::UPDATE("UPDATE consumers SET twoFactorCode = ? WHERE email = ?", [$otpCode, $request->email]);
                queryChecker($updateConsumers, 'Update consumers');
                if ($updateConsumers > 0) {

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
                                "email": "' . $request->email . '",
                              }
                            ],
                            "dynamic_template_data": {
                              "firstName": "' . $firstName . '",
                              "otpCode": "' . $otpCode . '",
                            },
                            "subject": ""
                          }
                        ],
                        "from": {
                            "email": "' . Config::get('app.OTOBOOK_EMAIL') . '",
                            "name": "' . Config::get('app.OTOBOOK_EMAIL_NAME') . '"
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
                        ]);
                    } else {

                        $activityLog = DB::INSERT(
                            "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [$request->email, $request->ip, $request->os, $request->browser, $request->device, 'Consumer opted for a new email OTP', NOW()]
                        );
                        queryChecker($activityLog, 'Activity Log');

                        return response()->json([
                            'success' => true,
                            'message' =>   'two factor email',
                        ]);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' =>   'Unable to send email',
                    ]);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' =>   'Unable to send email',
            ]);
        }
    }

    protected function receipt()
    {

        $connect = new PDO("mysql:host=otobook.mysql.database.azure.com; dbname=otobook", "otobook@otobook", "D9dpermit"); //Establishing connection with our database
        function fetch_customer_data($connect)
        {

            $output .= '<!DOCTYPE html>
            <html>
            <head>
            
            <meta charset="utf-8">
            <meta http-equiv="x-ua-compatible" content="ie=edge">
            <title>Email Receipt</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style type="text/css">
            /**
             * Google webfonts. Recommended to include the .woff version for cross-client compatibility.
             */
            @media screen {
                @font-face {
                font-family: "Source Sans Pro";
                font-style: normal;
                font-weight: 400;
                src: local("Source Sans Pro Regular"), local("SourceSansPro-Regular"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format("woff");
                }
            
                @font-face {
                font-family: "Source Sans Pro";
                font-style: normal;
                font-weight: 700;
                src: local("Source Sans Pro Bold"), local("SourceSansPro-Bold"), url(https://fonts.gstatic.com/s/sourcesanspro/v10/toadOcfmlt9b38dHJxOBGFkQc6VGVFSmCnC_l7QZG60.woff) format("woff");
                }
            }
            
            /**
             * Avoid browser level font resizing.
             * 1. Windows Mobile
             * 2. iOS / OSX
             */
            body,
            table,
            td,
            a {
                -ms-text-size-adjust: 100%; /* 1 */
                -webkit-text-size-adjust: 100%; /* 2 */
            }
            
            /**
             * Remove extra space added to tables and cells in Outlook.
             */
            table,
            td {
                mso-table-rspace: 0pt;
                mso-table-lspace: 0pt;
            }
            
            /**
             * Better fluid images in Internet Explorer.
             */
            img {
                -ms-interpolation-mode: bicubic;
            }
            
            /**
             * Remove blue links for iOS devices.
             */
            a[x-apple-data-detectors] {
                font-family: inherit !important;
                font-size: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
                color: inherit !important;
                text-decoration: none !important;
            }
            
            /**
             * Fix centering issues in Android 4.4.
             */
            div[style*="margin: 16px 0;"] {
                margin: 0 !important;
            }
            
            body {
                width: 100% !important;
                height: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /**
             * Collapse table borders to avoid space between cells.
             */
            table {
                border-collapse: collapse !important;
            }
            
            a {
                color: #1a82e2;
            }
            
            img {
                height: auto;
                line-height: 100%;
                text-decoration: none;
                border: 0;
                outline: none;
            }
            </style>
            
            </head>
            <body style="background-color: #fff;">
            
            <!-- start preheader -->
            <!--<div class="preheader" style="display: none; max-width: 0; max-height: 0; overflow: hidden; font-size: 1px; line-height: 1px; color: #fff; opacity: 0;">-->
            <!--  A preheader is the short summary text that follows the subject line when an email is viewed in the inbox.-->
            <!--</div>-->
            <!-- end preheader -->
            
            <!-- start body -->
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
            
                <!-- start logo -->
                <tr>
                <td align="center" bgcolor="#fff">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td align="center" valign="top" style="padding: 6px 24px;">
                        <a href="[findme]" target="_blank" style="display: inline-block;">
                            <img src="https://otobook.azurewebsites.net/account/mainBlue.png" alt="Logo" border="0" style="display: block; width: 200px; max-width: 200px; min-width: 88px;">
                        </a>
                        </td>
                    </tr>
                    </table>
                </td>
                </tr>
                <!-- end logo -->
            
                <!-- start hero -->
                <tr>
                <td align="center" bgcolor="#fff">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                    <tr>
                        <td align="left" bgcolor="#ffffff" style="padding: 20px 24px 0; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; border-top: 3px solid #d4dadf;">
                        <h1 style="margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -1px; line-height: 48px;">Your order is confirmed!</h1>
                        </td>
                    </tr>
                    </table>
                </td>
                </tr>
                <!-- end hero -->
            
                <!-- start copy block -->
                <tr>
                <td align="center" bgcolor="#fff">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
            
                    <!-- start copy -->
                    <tr>
                        <td align="left" bgcolor="#ffffff" style="padding: 24px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                        <!--<p style="margin: 0;">Here is a summary of your recent order. If you have any questions or concerns about your order, please <a href="https://sendgrid.com">contact us</a>.</p>-->
                        <p style="font-weight: 700;">Hello [Name],</p>
                        <p>Thanks for using otóbook. This email is the receipt for your purchase. No payment is due.</p>
                        </td>
                    </tr>
                    <!-- end copy -->
                    
                    <tr>
                        <td align="left" bgcolor="#ffffff" style="padding: 24px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                            <td class="Font Font--caption Font--uppercase Font--mute Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #8898aa;font-size: 12px;line-height: 16px;white-space: nowrap;font-weight: bold;text-transform: uppercase;">
                                Amount paid
                            </td>
                            <td width="39%" class="Font Font--caption Font--uppercase Font--mute Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #8898aa;font-size: 12px;line-height: 16px;white-space: nowrap;font-weight: bold;text-transform: uppercase;">
                                Date paid
                            </td>
                                <td width="5%" class="Font Font--caption Font--uppercase Font--mute Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #8898aa;font-size: 12px;line-height: 16px;white-space: nowrap;font-weight: bold;text-transform: uppercase;">
                                Payment method
                                </td>
                            </tr>
                            <tr>
                            <td class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                    GHS5426
                            </td>
                            <td class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                    January 5, 2021
                            </td>
                            <td class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                <span>Mastercard</span>
                                <span> – 6500</span>
                            </td>
                            </tr>
                        </table>
                        </td>
                    </tr>
            
                    <!-- start receipt table -->
                    <tr>
                        <td align="left" bgcolor="#ffffff" style="padding: 24px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                            <td bgcolor="#0337cb" style="padding: 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><strong style="color: #fff;"> Item </strong></td>
                            <td bgcolor="#0337cb" width="47%" style="padding: 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><strong style="color: #fff;">Quantity</strong></td>
                            <td bgcolor="#0337cb" width="5%" style="padding: 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><strong style="color: #fff;">Amount</strong></td>
                            </tr>
                            <tr>
                            <td style="padding: 6px 12px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>Bumper - Toyota Camry Spider</span></td>
                            <td width="37%" style="padding: 6px 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>1</span></td>
                            <td width="5%" style="padding: 6px 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>GHS1590</span></td>
                            </tr>
                            <tr>
                            <td style="padding: 6px 12px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>Carburetors - Mercedes Benz Brake C300</span></td>
                            <td width="37%" style="padding: 6px 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>1</span></td>
                            <td width="5%" style="padding: 6px 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>GHS440</span></td>
                            </tr>
                            <tr>
                            <td style="padding: 6px 12px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>Gaskets - Lexus</span></td>
                            <td width="37%" style="padding: 6px 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>3</span></td>
                            <td width="5%" style="padding: 6px 12px;font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;"><span>GHS90</span></td>
                            </tr>
                            <tr>
                            <td style="padding: 12px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; border-top: 2px dashed #fff; border-bottom: 2px dashed #fff;"><strong>Total</strong></td>
                            <td width="37%" style="padding: 12px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; border-top: 2px dashed #fff; border-bottom: 2px dashed #fff;"><strong style="visibility: hidden;">$54.00</strong></td>
                            <td width="5%" style="padding: 12px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; border-top: 2px dashed #fff; border-bottom: 2px dashed #fff;"><strong>GHS2300</strong></td>
                            </tr>
                        </table>
                        </td>
                    </tr>
                    <!-- end reeipt table -->
            
                    </table>
                    <!--[if (gte mso 9)|(IE)]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
                </tr>
                <!-- end copy block -->
            
                <!-- start receipt address block -->
                <tr>
                <td align="center" bgcolor="#fff" valign="top" width="100%">
                    
                    <table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin-bottom: 20px;">
                    <tr>
                        <td align="left" bgcolor="#ffffff" style="padding: 24px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                            <td style="visibility: hidden;" class="Font Font--caption Font--uppercase Font--mute Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #8898aa;font-size: 12px;line-height: 16px;white-space: nowrap;font-weight: bold;text-transform: uppercase;">
                                Amount paid
                            </td>
                            <td style="visibility: hidden;" class="Font Font--caption Font--uppercase Font--mute Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #8898aa;font-size: 12px;line-height: 16px;white-space: nowrap;font-weight: bold;text-transform: uppercase;">
                                Date paid
                            </td>
                                <td style="font-weight:normal;padding: 0px 6px;vertical-align:top;" align="right">
                                Order Amount: <span style="font-weight:normal;padding: 0px 6px;vertical-align:top;" align="right"> GHS2300</span>
                                </td>
                            </tr>
                            <tr>
                            <td style="visibility: hidden;" class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                    GH₵5426
                            </td>
                            <td style="visibility: hidden;" class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                    January 5, 2021
                            </td>
                            <td style="font-weight:normal;padding: 0px 6px;vertical-align:top;" align="right">
                                Shipping Fee: <span style="font-weight:normal;padding: 0px 6px;vertical-align:top;" align="right"> GHS708</span>
                                </td>
                            </tr>
                            <tr>
                            <td style="visibility: hidden;" class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                    GHS5426
                            </td>
                            <td style="visibility: hidden;" class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                    January 5, 2021
                            </td>
                            <td style="font-weight:normal;padding: 0px 6px;vertical-align:top;" align="right">
                                VAT: <span style="font-weight:normal;padding: 0px 6px;vertical-align:top;" align="right"> GHS118</span>
                                </td>
                            </tr>
                            <tr>
                            <td style="visibility: hidden;" class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                    GHS5426
                            </td>
                            <td style="visibility: hidden;" class="Font Font--body Font--noWrap" style="border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #525f7f;font-size: 15px;line-height: 24px;white-space: nowrap;">
                                    January 5, 2021
                            </td>
                            <td style="font-weight:normal; padding: 0px 6px; vertical-align:top;" align="right">
                                <span style="font-weight: bold;">Order Total: </span> <span style="font-weight:normal;padding: 0px 6px;vertical-align:top;" align="right"><span style="font-weight:bold;">GHS5426</span>
                                </td>
                            </tr>
                        </table>
                        </td>
                    </tr>
                    </table>
                    
                </td>
                </tr>
                <!-- end receipt address block -->
            
                <!-- start footer -->
                <tr>
                <td align="center" bgcolor="#fff">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
            
                    <!-- start permission -->
                    <tr>
                        <td align="center" bgcolor="#ddd" style="padding: 24px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #666;">
                        <p style="margin: 0;">If you have any questions about this receipt, simply reply to this email or reach out to our support team for help.</p>
                        </td>
                    </tr>
                    <!-- end permission -->
            
                    <!-- start unsubscribe -->
                    <tr>
                            <td align="center">

                                
                                <table border="0" cellpadding="0" cellspacing="0" class="m_1457654061572036542show-for-mobile" width="100%" style="border-collapse:collapse;table-layout:fixed;display:none;max-width:600px">
                                <tbody>
                                    <tr>
                                    <td align="center" style="padding:25px 0 0">
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="80%" style="border-collapse:collapse;table-layout:fixed;min-width:100%">
                                        <tbody>
                                            <tr>
                                            <td align="center">
                                                <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;table-layout:fixed;min-width:100%">
                                                <tbody> </tbody>
                                                </table>
                                                <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;table-layout:fixed;min-width:100%">
                                                <tbody>
                                                    <tr>
                                                    <td align="center">
                                                        <table align="center" cellspacing="0" border="0" style="width:100%;max-width:320px">
                                                        <tbody>
                                                            <tr>
                                                            <td align="center" style="border-collapse:collapse!important;box-sizing:border-box;padding-right:5px;padding-top:10px"><a href="#m_1457654061572036542_"><img width="150" alt="" border="0" style="max-width:150px;height:auto;display:block;width:150px" src="https://ci6.googleusercontent.com/proxy/1p_PLo--h8aLyfiyyJMy10NhaEfu1dvKf_t3HJy4JLsADuxHza3S1EZgn71w4s41tOGeAo-ogqZflCKWqG2e4tB7gNATolu60-XMHPzG65DrzYsRSKGiEniCmhKB8eF9N-bknSejl5l76qBRalijlt1fHQLEHFX58k8oI9U8bXvHLLQoKzxDnswhGppVWVjvVxky6n8_z65eHW-nYLdoZZ1ttHMgroNcGy9p=s0-d-e1-ft#https://d15k2d11r6t6rl.cloudfront.net/public/users/Integrators/669d5713-9b6a-46bb-bd7e-c542cff6dd6a/8cb45ebcfb7c4c8189af4a5ff6ca1a98/Google_Play_EN.png" class="CToWUd"></a></td>
                                                            <td align="center" style="border-collapse:collapse!important;box-sizing:border-box;padding-top:10px"><a href="#m_1457654061572036542_"><img width="150" alt="" border="0" style="max-width:150px;height:auto;display:block;width:150px" src="https://ci3.googleusercontent.com/proxy/hh-aJmjQpdQV_wU7SLlMgbmt9aXIrCbmzBgUlBkdZmmf9WAFpH55edzd0oBLRU6vLtCer6GiEZTltnTZqIDLrIH7xTw6uabEdn4fEFT7-EN8X0PT3BiWpgQC5kNvo7hVg25Lsov3aSHuH5Anmsa6YfomxnbWucIEsFoF3zwDHROMrNfdivwhyDEUr79mtvsiFsw_kZA_dP0LjPiZ7lzjQkW1DoKAJHD23ixpLvv8lw=s0-d-e1-ft#https://d15k2d11r6t6rl.cloudfront.net/public/users/Integrators/669d5713-9b6a-46bb-bd7e-c542cff6dd6a/8cb45ebcfb7c4c8189af4a5ff6ca1a98/Apple_App_Store_EN.png" class="CToWUd"></a></td>
                                                            </tr>
                                                        </tbody>
                                                        </table>
                                                    </td>
                                                    </tr>
                                                </tbody>
                                                </table>
                                                <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;table-layout:fixed;min-width:100%">
                                                <tbody>
                                                    <tr>
                                                    <td align="center" style="border-bottom:1px solid #edf1f2;padding-top:30px"></td>
                                                    </tr>
                                                </tbody>
                                                </table>
                                            </td>
                                            </tr>
                                        </tbody>
                                        </table>
                                    </td>
                                    </tr>
                                </tbody>
                                </table>

                                
                                <table cellpadding="0" cellspacing="0" border="0" style="width:100%; background-color:#ffffff;max-width:600px" class="m_1457654061572036542mw100" width="600">
                                <tbody>

                                                    <tr>
                        <td align="left" bgcolor="#ffffff" style="padding: 24px; font-family: "Source Sans Pro", Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                            <td class="Font Font--caption Font--uppercase Font--mute Font--noWrap" style="visibility: hidden; border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #8898aa;font-size: 12px;line-height: 16px;white-space: nowrap;font-weight: bold;text-transform: uppercase;">
                                Amount paid
                            </td>
                                                            <td align="center" style="padding:20px 0 10px">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width:88%;background-color:#ffffff;max-width:528px">
                                        <tbody>
                                        
                                            <tr>
                                            <td style="color:#646f79;font-family:Helvetica,Arial,sans-serif!important;font-weight:normal;text-align:center;line-height:16px;font-size:10px;box-sizing:border-box;margin:0px;padding:0;padding-top:1px"><a style="text-decoration:underline;color:#646f79" href="https://u17873620.ct.sendgrid.net/ls/click?upn=jGfBD-2FnpnULWjnPUrN0WaaSX278iQnWj-2BGaujY-2FM0uKsFFvXdu2WA1pMfo5AlDbYWzep_GgM6PTsQRdhaIAB-2BFOj5BUqls-2FOe57D3zwHr8e7beAgrdEiEU1JSyLxPDNXQj7ECEc3fH6bzMTzZWIwexD-2B5IK1oWGvM42wErdQhRf-2BKtWqwTzGRMX5mP4AQYOUAmC461Yyg2tU580X44xRUO3aO1eUGcIIt21CmsE79-2F6nqHAYgpVa5xyikbh4ql4xcT7zxhESHuX4Zfc77G04jnbHqbdrzQBw0QuSmGIVLINyzPuM-3D" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u17873620.ct.sendgrid.net/ls/click?upn%3DjGfBD-2FnpnULWjnPUrN0WaaSX278iQnWj-2BGaujY-2FM0uKsFFvXdu2WA1pMfo5AlDbYWzep_GgM6PTsQRdhaIAB-2BFOj5BUqls-2FOe57D3zwHr8e7beAgrdEiEU1JSyLxPDNXQj7ECEc3fH6bzMTzZWIwexD-2B5IK1oWGvM42wErdQhRf-2BKtWqwTzGRMX5mP4AQYOUAmC461Yyg2tU580X44xRUO3aO1eUGcIIt21CmsE79-2F6nqHAYgpVa5xyikbh4ql4xcT7zxhESHuX4Zfc77G04jnbHqbdrzQBw0QuSmGIVLINyzPuM-3D&amp;source=gmail&amp;ust=1609692859200000&amp;usg=AFQjCNEdqTURh2ugiA-vpVeBpUihRFTznA">Terms &amp; Condition</a> • <a style="text-decoration:underline;color:#646f79" href="https://u17873620.ct.sendgrid.net/ls/click?upn=jGfBD-2FnpnULWjnPUrN0WaaSX278iQnWj-2BGaujY-2FM0uKsFFvXdu2WA1pMfo5AlDbYAVDB_GgM6PTsQRdhaIAB-2BFOj5BUqls-2FOe57D3zwHr8e7beAgrdEiEU1JSyLxPDNXQj7ECivMGja1bg1fd90vK38KszCIpKTu7TxjtIfU581hv3JxKBMwEfeHAelYSvAbOFV3ME8VAIALhYjc-2BpV0MRuNJOjbizmpBlvr8a2ZgMD-2FgyZwGc7AIam9YevLPu-2F4yrdQyPPw2CgKIi3-2BwK3czXwM3eNOFYy5keg5BLeSNgpNkSuM-3D" target="_blank" data-saferedirecturl="https://www.google.com/url?q=https://u17873620.ct.sendgrid.net/ls/click?upn%3DjGfBD-2FnpnULWjnPUrN0WaaSX278iQnWj-2BGaujY-2FM0uKsFFvXdu2WA1pMfo5AlDbYAVDB_GgM6PTsQRdhaIAB-2BFOj5BUqls-2FOe57D3zwHr8e7beAgrdEiEU1JSyLxPDNXQj7ECivMGja1bg1fd90vK38KszCIpKTu7TxjtIfU581hv3JxKBMwEfeHAelYSvAbOFV3ME8VAIALhYjc-2BpV0MRuNJOjbizmpBlvr8a2ZgMD-2FgyZwGc7AIam9YevLPu-2F4yrdQyPPw2CgKIi3-2BwK3czXwM3eNOFYy5keg5BLeSNgpNkSuM-3D&amp;source=gmail&amp;ust=1609692859200000&amp;usg=AFQjCNGDRcAuC_kJAhAd32iC6ehcCThWPQ">Privacy Policy</a></td>
                                            </tr>
                                        
                                        <tr>
                                            <td style="color:#646f79;font-family:Helvetica,Arial,sans-serif!important;font-weight:normal;text-align:center;line-height:16px;font-size:10px;box-sizing:border-box;margin:0px;padding:0;padding-top:1px">© Copyright 2020. All Rights Reserved.</td>
                                            </tr>
                                            
                                                                            
                                            <tr>
                                            <td style="color:#646f79;font-family:Helvetica,Arial,sans-serif!important;font-weight:normal;text-align:center;line-height:16px;font-size:10px;box-sizing:border-box;margin:0px;padding:0;padding-top:1px">525 Route 73 N, Marlton NJ 08053</td>
                                            </tr>
                                        </tbody>
                                        </table>
                                    </td>
                                <td width="30%" class="Font Font--caption Font--uppercase Font--mute Font--noWrap" style="visibility: hidden; border: 0;border-collapse: collapse;margin: 0;padding: 0;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;mso-line-height-rule: exactly;vertical-align: middle;color: #8898aa;font-size: 12px;line-height: 16px;white-space: nowrap;font-weight: bold;text-transform: uppercase;">
                                Payment method
                                </td>
                            </tr>
                        
                        </table>
                        </td>
                    </tr>
                                </tbody>
                                </table>


                                <table align="center" border="0" cellpadding="0" cellspacing="0" width="80%" style="border-collapse:collapse;table-layout:fixed;max-width:480px">
                                <tbody>
                                    <tr>

    <td style="line-height:1px;text-align:center" align="center"><span style="border:none">
    </span></td> 

                                    </tr>
                                </tbody>
                                </table>


                            </td>
                            </tr>
                    <!-- end unsubscribe -->
            
                    </table>
                </td>
                </tr>
                <!-- end footer -->
            
            </table>
            <!-- end body -->
            
            </body>
            </html>';

            return $output;
        }

        include('pdf.php');
        $file_name = md5(rand()) . '.pdf';
        $html_code = '<link rel="stylesheet" href="bootstrap.min.css">';
        $html_code .= fetch_customer_data($connect);
        $pdf = new Pdf();
        $pdf->load_html($html_code);
        $pdf->render();
        $file = $pdf->output();
        file_put_contents($file_name, $file);
        // unlink($file_name);

    }

    protected function returnAndCancellation(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $returnAndCancellation = DB::SELECT("SELECT * FROM return_and_cancellation WHERE orderNumber = ? ", [$request->orderNumber]);
        queryChecker($returnAndCancellation, 'Return And Cancellation');
        if (count($returnAndCancellation) === 1) {
            return response()->json([
                'success' => true,
                'message' => 'already',
            ]);
        } else {
            $checkOrderNumberQuery = DB::SELECT("SELECT * FROM orders WHERE orderId = ? ", [$request->orderNumber]);
            queryChecker($checkOrderNumberQuery, 'Check Order Number Query');
            if (count($checkOrderNumberQuery) === 1) {

                //Return And Cancellation ID generated start

                function guidv4($data)
                {
                    assert(strlen($data) == 16);

                    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
                    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

                    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
                }

                //Return And Cancellation ID generated end

                $returnAndCancellationId = guidv4(openssl_random_pseudo_bytes(16));

                $returnAndCancellationInsert = DB::INSERT(
                    "INSERT INTO return_and_cancellation (returnAndCancellationId, consumerUuid, consumerName, consumerEmail, consumerPhone, dateOfPurchase, orderNumber, returnType, detailedDescription, theYear, make, model, vinNumber, driveTrain, createdAT) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$returnAndCancellationId, $user->uuid, $request->consumerName, $request->consumerEmail, $request->consumerPhone, $request->purchaseDate, $request->orderNumber, $request->returnType, $request->detailedDescription, $request->year, $request->make, $request->model, $request->vinNumber, $request->driveTrain, NOW()]
                );
                queryChecker($returnAndCancellationInsert, 'Insert into return and cancellation Query');

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
                        "email": "' . $request->consumerEmail . '",
                    }
                    ],
                    "dynamic_template_data": {
                    "firstName": "' . $request->consumerName . '",
                    },
                    "subject": ""
                }
                ],
                "from": {
                    "email": "' . Config::get('app.OTOBOOK_EMAIL') . '",
                    "name": "' . Config::get('app.OTOBOOK_EMAIL_NAME') . '"
                },
                "template_id": "d-15b6ac699b844c5fa65b4ef94b46d5da"
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
                    ]);
                } else {

                    $activityLog = DB::INSERT(
                        "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer filed for cancellation', NOW()]
                    );
                    queryChecker($activityLog, 'Activity Log');

                    return response()->json([
                        'success' => true,
                        'message' =>   'working',
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' =>   'This order does not exist',
                ]);
            }
        }
    }

    protected function order(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $order = DB::INSERT(
            "INSERT INTO orders (
            orderId,
            productId, 
            consumerId, 
            quantity,
            consumerName, 
            consumerAddress, 
            consumerPhoneNumber, 
            consumerGhanaPost, 
            unitPrice,
            total, 
            numberOfItems, 
            deliveryMethod,
            orderStatus,
            orderPlaced,
            orderPlacedDate) 
            SELECT 
            ?,
            productId,
            ?,
            quantity,
            ?,
            ?,
            ?,
            ?,
            unitPrice,
            ?,
            ?,
            ?,
            'Order Placed',
            'ORDER PLACED',
            NOW()
            FROM cart WHERE consumerUuid = ? ",
            [
                $request->orderId,
                $user->uuid,
                $request->addressDetailsName,
                $request->addressDetailsAddress,
                $request->addressDetailsPhone,
                $request->addressghanaPost,
                $request->total,
                $request->numberOfItems,
                $request->getDeliveryMethod,
                $user->uuid
            ]
        );

        queryChecker($order, 'Place orders');

        $updateOrder = DB::UPDATE(
            "UPDATE orders SET paymentMethod = ?, payment_brand = ?, shippingFee = ? WHERE consumerId = ? AND orderId = ? ",
            [$request->getPaymentMethod, $request->getPaymentBrand, $request->getTotalShippingFee, $user->uuid, $request->orderId]
        );
        queryChecker($updateOrder, 'Update orders');
        if ($updateOrder > 0) {

            $selectOrder = DB::SELECT("SELECT products.*, tbl_images.*, orders.*, consumers.email, consumers.firstName FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN orders ON orders.productId = products.productId INNER JOIN consumers ON consumers.uuid = orders.consumerId WHERE orders.consumerId = ? AND orderId = ? GROUP BY orders.id DESC", [$user->uuid, $request->orderId]);
            queryChecker($selectOrder, 'Select orders');

            $subtotalOverall = 0;
            $shippingFeeOverall = 0;
            $totalOverall = 0;

            $data = array();
            foreach ($selectOrder as $selectOrderResult) {

                // $email = $selectOrderResult[52];
                // $firstName = $selectOrderResult[53];
                // $orderId = $selectOrderResult[23];
                // $consumerPhoneNumber = $selectOrderResult[29];

                // $quantity = str_replace(",","",$selectOrderResult[26]);
                // $unitPrice = str_replace(",","",$selectOrderResult[6]);
                // $initialShippingFee = str_replace(",","",$selectOrderResult[16]);

                $email = $selectOrderResult->email;
                $firstName = $selectOrderResult->firstName;
                $orderId = $selectOrderResult->orderId;
                $consumerPhoneNumber = $selectOrderResult->consumerPhoneNumber;

                $quantity = str_replace(",", "", $selectOrderResult->quantity);
                $unitPrice = str_replace(",", "", $selectOrderResult->unitPrice);
                $initialShippingFee = str_replace(",", "", $selectOrderResult->shippingFee);

                $thisHuy = $quantity * $unitPrice;
                $thisGuy = $quantity * $initialShippingFee;

                $subtotalOverall += $thisHuy;
                $subtotalOveralll = number_format($subtotalOverall);

                $shippingFeeOverall += $thisGuy;
                $shippingFeeOveralll = number_format($shippingFeeOverall);

                $totalOverall = $subtotalOverall + $shippingFeeOverall;
                $totalOveralll = number_format($totalOverall);
            }

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
                            "email": "' . $email . '",
                        }
                        ],
                        "dynamic_template_data": {
                        "firstName": "' . $firstName . '",
                        "orderNumber": "' . $orderId . '",
                        "subtotal": "' . $subtotalOveralll . '",
                        "shippingFee": "' . $shippingFeeOveralll . '",
                        "total": "' . $totalOveralll . '"
                        },
                        "subject": ""
                    }
                    ],
                    "from": {
                        "email": "' . Config::get('app.OTOBOOK_EMAIL') . '",
                        "name": "' . Config::get('app.OTOBOOK_EMAIL_NAME') . '"
                    },
                    "template_id": "d-6abc30b10f4f45cf913a06269f2b90c6"
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
                    'message' =>   'Unable to place order, try again',
                ]);
            } else {

                $key = Config::get('app.MNOTIFY');
                $to = $consumerPhoneNumber;

                $msg = "Hi $firstName, Your order has been placed successfully. Order Number: $orderId. Thank you for shopping on otóbook";
                $sender_id = "otóbook";

                $msg = urlencode($msg);

                $url = "https://apps.mnotify.net/smsapi?"
                    . "key=$key"
                    . "&to=$to"
                    . "&msg=$msg"
                    . "&sender_id=$sender_id";

                $response = file_get_contents($url);

                if ($response) {

                    $deleteFromCart = DB::DELETE("DELETE FROM cart WHERE consumerUuid = ? ", [$user->uuid]);
                    queryChecker($deleteFromCart, 'Delete from cart after order is placed');
                    if ($deleteFromCart > 0) {

                        $countCartAfterOrder = DB::SELECT("SELECT * FROM cart WHERE consumerUuid = ? ", [$user->uuid]);
                        queryChecker($countCartAfterOrder, 'Count cart after order is placed');

                        $quantityKievAfterOrder = 0;
                        foreach ($countCartAfterOrder as $countCartAfterOrderResult) {
                            $quantityAfterOrder = $countCartAfterOrderResult->quantity;
                            $quantityKievAfterOrder += $quantityAfterOrder;
                        }

                        $updateConsumerTable = DB::UPDATE("UPDATE consumers SET cartCount = ? WHERE uuid = ? ", [$quantityKievAfterOrder, $user->uuid]);
                        queryChecker($updateConsumerTable, 'Update cart count after order is placed');

                        $activityLog = DB::INSERT(
                            "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer placed an order', NOW()]
                        );
                        queryChecker($activityLog, 'Activity Log');

                        return response()->json([
                            'success' => true,
                            'message' => 'working',
                            'data' => $quantityKievAfterOrder,
                        ]);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to place order, try again',
                    ]);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unable to place order, try again',
            ]);
        }
    }

    protected function wishlist(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $products = DB::SELECT("SELECT products.*, tbl_images.*, wishlist.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN wishlist ON wishlist.productId = products.productId WHERE wishlist.consumerUuid = ? GROUP BY wishlist.productId DESC", [$user->uuid]);
        queryChecker($products, 'Get products query');
        $totalData = count($products);
        $totalFilter = $totalData;

        if (!empty($request['search']['value'])) {
            $searchItem = $request['search']['value'];
            $products =  DB::SELECT("SELECT products.*, tbl_images.*, wishlist.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN wishlist ON wishlist.productId = products.productId WHERE wishlist.consumerUuid = ? AND products.productTitle LIKE '%$searchItem%' OR products.productTitle LIKE '$searchItem%' OR products.productTitle LIKE '%$searchItem'", [$user->uuid]);
        }
        $totalData = count($products);
        $theAdd = '';

        $data = array();
        foreach ($products as $productsResult) {
            $firstOne = $productsResult->productId;
            $productQuantity = $productsResult->quantity;
            $productPrice = number_format($productsResult->productPrice);
            $theShippingFee = number_format($productsResult->shippingFee);

            if ($productQuantity < 1) {
                $theAdd = '<li><a style="cursor: default; padding-left: 10px!important; padding-right: 10px !important;" class="btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Out of Stock">
                <em class="icon ni ni-cross-round"></em></a>
                </li>';
            } else {
                $theAdd = '<li><a id="addToCartButton_' . $productsResult->productId . '" style="padding-left: 10px!important; padding-right: 10px !important;" data-id="' . $productsResult->productId . '" class="addToCart btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Add to Cart">
                <em style="color: #0337cb;" class="icon ni ni-cart"></em></a>
                </li>';
            }

            $subdata = array();

            $subdata[] = '<div class="user-card">
            <div class="user-avatar"><img style="margin-left: 1px !important; height: 40px !important; object-fit: contain;" class="avatar" src="' . $productsResult->path . '" alt=""></div>
            <div class="user-info">
            <span class="tb-lead">' . $productsResult->productTitle . '</span>
            </div>
            </div>';

            $subdata[] = '<span>GH₵ ' . '' . $productPrice . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . $theShippingFee . '</span>';

            $subdata[] = '<ul style="display: inline-flex; align-items: center; position: relative;">

            ' . $theAdd . '

            <li><a style="padding-left: 10px!important; padding-right: 10px !important;" href="' . Config::get('app.UI_BASE_URL') . 'shop/p?main=' . $productsResult->productId . '" data-id="' . $productsResult->productId . '" class="getConsumers btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="View Product">
                <em style="color: #0337cb;" style="color: #0337cb;" class="icon ni ni-eye"></em></a>
            </li>

            <li><a id="removeFromWishlistButton_' . $productsResult->productId . '" style="padding-left: 10px!important; padding-right: 10px !important;" href="#" data-id="' . $productsResult->wishlistId . '" class="deleteProduct btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Remove From wishlist">
                <em style="color: #0337cb;" style="color: #0337cb;" class="icon ni ni-trash"></em></a>
            </li>
            
            </ul>';

            $subdata[] = $productsResult->productTitle;
            $subdata[] = $productsResult->path;
            $subdata[] = $productsResult->productPrice;
            $subdata[] = $productsResult->shippingFee;
            $subdata[] = $productsResult->productId;
            $data[] = $subdata;
        }

        return response()->json([
            "draw"              =>  intval($request->draw),
            "recordsTotal"      =>  intval($totalData),
            "recordsFiltered"   =>  intval($totalFilter),
            'data'              =>  $data,
        ]);
    }

    protected function getSmallOrders(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $getSmallOrders = DB::SELECT("SELECT products.*, tbl_images.*, orders.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN orders ON orders.productId = products.productId WHERE orders.consumerId = ? AND orderId = ? GROUP BY orders.id DESC", [$user->uuid, $request->orderId]);
        queryChecker($getSmallOrders, 'Get small orders');
        $getSmallOrdersTotalData = count($getSmallOrders);
        $getSmallOrdersTotalFilter = $getSmallOrdersTotalData;

        //Search
        if (!empty($request['search']['value'])) {
            $searchItem = $request['search']['value'];
            $getSmallOrders =  DB::SELECT("SELECT products.*, tbl_images.*, orders.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN orders ON orders.productId = products.productId WHERE orders.consumerUuid = ? AND products.productTitle LIKE '%$searchItem%' OR products.productTitle LIKE '$searchItem%' OR products.productTitle LIKE '%$searchItem'", [$user->uuid]);
        }
        $getSmallOrdersTotalData = count($getSmallOrders);
        $subtotalOverall = 0;
        $shippingFeeOverall = 0;

        foreach ($getSmallOrders as $getSmallOrdersResult) {

            $quantity = str_replace(",", "", $getSmallOrdersResult->quantity);
            $unitPrice = str_replace(",", "", $getSmallOrdersResult->productPrice);
            $initialShippingFee = str_replace(",", "", $getSmallOrdersResult->shippingFee);

            $thisHuy = $quantity * $unitPrice;
            $thisGuy = $quantity * $initialShippingFee;

            $theSubtotal = number_format($thisHuy);
            $theShippingFee = number_format($thisGuy);

            $subtotalOverall += $thisHuy;
            $subtotalOveralll = number_format($subtotalOverall);

            $shippingFeeOverall += $thisGuy;
            $shippingFeeOveralll = number_format($shippingFeeOverall);

            $subdata = array();

            $subdata[] = '<div class="user-card">
            <div class="user-avatar"><img style="margin-left: 1px !important; height: 40px !important; object-fit: contain;" class="avatar" src="' . $getSmallOrdersResult->path . '" alt=""></div>
            <div style="margin-top: -8px !important;" class="user-info">
            <span class="tb-lead">' . $getSmallOrdersResult->productTitle . '</span>
            </div>
            </div>';

            $subdata[] = '<span>' . $getSmallOrdersResult->quantity . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . number_format($getSmallOrdersResult->productPrice) . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . $theSubtotal . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . $theShippingFee . '</span>';

            $subdata[] = '<ul style="display: inline-flex; align-items: center; position: relative;">
            <li><a id="removeFromordersButton_' . $getSmallOrdersResult->id . '" style="padding-left: 10px!important; padding-right: 10px !important;" href="#" data-id="' . $getSmallOrdersResult->productId . '" class="buyAgain btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Buy Again">
                <em style="color: #0337cb;" style="color: #0337cb;" class="icon ni ni-repeat"></em></a>
            </li>
            </ul>';

            $subdata[] = $getSmallOrdersResult->consumerName;
            $subdata[] = $getSmallOrdersResult->consumerAddress;
            $subdata[] = $getSmallOrdersResult->consumerPhoneNumber;
            $subdata[] = $getSmallOrdersResult->consumerGhanaPost;
            $subdata[] = $getSmallOrdersResult->paymentMethod;
            $subdata[] = 'GH₵ ' . '' . $subtotalOveralll . '';
            $subdata[] = 'GH₵ ' . '' . $shippingFeeOveralll . '';
            $subdata[] = $getSmallOrdersResult->productId;
            $subdata[] = $getSmallOrdersResult->productPrice;
            $subdata[] = $getSmallOrdersResult->shippingFee;
            $subdata[] = $getSmallOrdersResult->deliveryMethod;
            $subdata[] = $getSmallOrdersResult->orderId;

            $data[] = $subdata;
        }

        return response()->json([
            "draw"              =>  intval($request->draw),
            "recordsTotal"      =>  intval($getSmallOrdersTotalData),
            "recordsFiltered"   =>  intval($getSmallOrdersTotalFilter),
            'data'              =>  $data,
        ]);
    }

    protected function getOrders(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $getOrders = DB::SELECT("SELECT * FROM orders WHERE consumerId = ? GROUP BY orderId DESC", [$user->uuid]);
        queryChecker($getOrders, 'Get orders');
        $getOrdersTotalData = count($getOrders);
        $getOrdersTotalFilter = $getOrdersTotalData;

        //Search
        // $getOrders = DB::SELECT("SELECT * FROM orders WHERE consumerId = ? GROUP BY orderId DESC", [$user->uuid]);
        if (!empty($request['search']['value'])) {
            $searchItem = $request['search']['value'];
            // $getOrders =  DB::SELECT("SELECT * FROM orders WHERE consumerId = ? AND products.productTitle LIKE '%$searchItem%' OR products.productTitle LIKE '$searchItem%' OR products.productTitle LIKE '%$searchItem'", [$user->uuid]);
        }
        $getOrdersTotalData = count($getOrders);
        $data = array();
        $receiptPath = '';

        foreach ($getOrders as $getOrdersResult) {

            $old_date_timestamp = strtotime($getOrdersResult->orderPlacedDate);
            $new_date = date('F d, Y', $old_date_timestamp);

            if ($getOrdersResult->orderStatus == "Pending Confirmation") {
                $orderStatus = "Order Confirmed";
            }
            if ($getOrdersResult->orderStatus == "Waiting To Be Shipped") {
                $orderStatus = "Processing Item(s) for Transit";
            }
            if ($getOrdersResult->orderStatus == "Shipped") {
                $orderStatus = "In Transit";
            }
            if ($getOrdersResult->orderStatus == "Delivered") {
                $orderStatus = "Delivered";
            }
            if ($getOrdersResult->orderStatus == "Order Placed") {
                $orderStatus = "Pending Confirmation";
            }

            if ($getOrdersResult->receiptPath != "") {
                $receiptPath = '<li><a href="' . $getOrdersResult->receiptPath . '" style="padding-left: 10px!important; padding-right: 10px !important;" target="_blank" data-id="' . $getOrdersResult->receiptPath . '" class="btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Download Receipt">
                <em style="color: #0337cb;" style="color: #0337cb;" class="icon ni ni-download"></em></a>
                </li>';
            }

            $subdata = array();

            $subdata[] = '<span style="display:inline-block; white-space: nowrap; width: 85%; overflow: hidden; text-overflow: ellipsis;">' . $getOrdersResult->orderId . '</span> <a id="' . $getOrdersResult->orderId . '" target="_blank" data-id="' . $getOrdersResult->orderId . '" class="copyOrderId btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Copy Order Number">
            <em style="color: #0337cb;" class="icon ni ni-clipboad-check"></em></a>';
            $subdata[] = '<span>GH₵ ' . '' . number_format($getOrdersResult->total) . '</span>';
            $subdata[] = '<span>' . $new_date . '</span>';
            $subdata[] = '<span>' . $orderStatus . '</span>';

            $subdata[] = '<ul style="display: inline-flex; align-items: center; position: relative;">
            <li><a style="padding-left: 10px!important; padding-right: 10px !important;" target="_blank" data-id="' . $getOrdersResult->orderId . '" class="viewOrderDetails btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="View Order Details">
            <em style="color: #0337cb;" style="color: #0337cb;" class="icon ni ni-eye"></em></a>
            </li>
        
            ' . $receiptPath . '
        
            <!-- <li><a style="padding-left: 10px!important; padding-right: 10px !important;" target="_blank" data-id="' . $getOrdersResult->orderId . '" class="cancelOrder btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Cancel Order">
                <em style="color: #0337cb;" style="color: #0337cb;" class="icon ni ni-cross-round"></em></a>
            </li> -->
            
            </ul>';

            $data[] = $subdata;
        }

        return response()->json([
            "draw"              =>  intval($request->draw),
            "recordsTotal"      =>  intval($getOrdersTotalData),
            "recordsFiltered"   =>  intval($getOrdersTotalFilter),
            'data'              =>  $data,
        ]);
    }

    protected function flutterwaveVerify(Request $request)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/$request->transactionId/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer FLWSECK-15767dc5d8e08b256a97a67109ed0837-X"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // if ($err) {
        //     return response()->json([
        //         'success' => false,
        //         'message' =>   'Unable to send email',
        //     ]);
        // } else {
        //     return response()->json([
        //         'success' => true,
        //         'message' =>   'working',
        //         'data'     => $response
        //     ]);
        // }
        echo $response;
    }

    protected function giveFeedback(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        if ($request->rating == 0) {
            $request->rating = 1;
        }

        $giveFeedback = DB::INSERT("INSERT INTO feedback (feedbackId, consumerUuid, orderId, rating, feedbackText, createdAt) VALUES (?, ?, ?, ?, ?, NOW())", [$request->feedbackId, $user->uuid, $request->orderId, $request->rating, $request->feedback, NOW()]);
        queryChecker($giveFeedback, 'Give feedback');

        $activityLog = DB::INSERT(
            "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer gave feedback on an item', NOW()]
        );
        queryChecker($activityLog, 'Activity Log');

        return response()->json([
            'success' => true,
            'message' => 'working',
        ]);
    }

    protected function deleteReview(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $deleteReview = DB::DELETE("DELETE FROM reviews WHERE reviewId = ? ", [$request->reviewId]);
        queryChecker($deleteReview, 'Delete review');

        $activityLog = DB::INSERT(
            "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer deleted a review', NOW()]
        );
        queryChecker($activityLog, 'Activity Log');

        return response()->json([
            'success' => true,
            'message' => 'working',
        ]);
    }

    protected function deleteProductFromWishlist(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $deleteProductFromWishlist = DB::DELETE("DELETE FROM wishlist WHERE wishlistId = ? ", [$request->wishlistDeleteId]);
        queryChecker($deleteProductFromWishlist, 'Delete item from wishlist');
        if ($deleteProductFromWishlist > 0) {

            $countWishlistAfterDelete = DB::SELECT("SELECT * FROM wishlist WHERE consumerUuid = ? ", [$user->uuid]);
            queryChecker($countWishlistAfterDelete, 'Count wishlist after delete');

            $countWishlistAfterDeleteQuantityKiev = '';
            foreach ($countWishlistAfterDelete as $countWishlistAfterDeleteResult) {
                $quantity = $countWishlistAfterDeleteResult->quantity;
                $countWishlistAfterDeleteQuantityKiev += $quantity;
            }

            $updateWishlistAfterDelete = DB::UPDATE("UPDATE consumers SET wishlistCount = ? WHERE uuid = ? ", [$countWishlistAfterDeleteQuantityKiev, $user->uuid]);
            queryChecker($updateWishlistAfterDelete, 'Update wishlist after delete');

            $activityLog = DB::INSERT(
                "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer deleted item from wishlist', NOW()]
            );
            queryChecker($activityLog, 'Activity Log');

            return response()->json([
                'success' => true,
                'message' => 'working',
                'data' => count($countWishlistAfterDelete),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Could not connect to server',
        ]);
    }

    protected function deleteProductFromCart(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $deleteProductFromCart = DB::DELETE("DELETE FROM cart WHERE cartId = ? ", [$request->deleteCartId]);
        queryChecker($deleteProductFromCart, 'Delete item from cart');
        if ($deleteProductFromCart > 0) {

            $countCartAfterDelete = DB::SELECT("SELECT * FROM cart WHERE consumerUuid = ? ", [$user->uuid]);
            queryChecker($countCartAfterDelete, 'Count cart after delete');

            $countCartAfterDeleteQuantityKiev = '';
            foreach ($countCartAfterDelete as $countCartAfterDeleteResult) {
                $quantity = $countCartAfterDeleteResult->quantity;
                $countCartAfterDeleteQuantityKiev += $quantity;
            }

            $updateCartAfterDelete = DB::UPDATE("UPDATE consumers SET cartCount = ? WHERE uuid = ? ", [$countCartAfterDeleteQuantityKiev, $user->uuid]);
            queryChecker($updateCartAfterDelete, 'Update cart after delete');

            $activityLog = DB::INSERT(
                "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer deleted item from cart', NOW()]
            );
            queryChecker($activityLog, 'Activity Log');

            return response()->json([
                'success' => true,
                'message' => 'working',
                'data' => count($countCartAfterDelete),
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Could not connect to server',
        ]);
    }

    protected function clearWishlist(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $clearWishlist = DB::DELETE("DELETE FROM wishlist WHERE consumerUuid = ? ", [$user->uuid]);
        queryChecker($clearWishlist, 'Clear cart');
        if ($clearWishlist > 0) {

            $countWishlistAfterClear = DB::SELECT("SELECT * FROM wishlist WHERE consumerUuid = ? ", [$user->uuid]);
            queryChecker($countWishlistAfterClear, 'Count wishlist after clear');

            $countWishlistAfterClearQuantityKiev = '';
            foreach ($countWishlistAfterClear as $countWishlistAfterClearResult) {
                $quantity = $countWishlistAfterClearResult->quantity;
                $countWishlistAfterClearQuantityKiev += $quantity;
            }

            $updateWishlistAfterClear = DB::UPDATE("UPDATE consumers SET wishlistCount = ? WHERE uuid = ? ", [$countWishlistAfterClearQuantityKiev, $user->uuid]);
            queryChecker($updateWishlistAfterClear, 'Update cart after clear');

            $activityLog = DB::INSERT(
                "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer cleared wishlist', NOW()]
            );
            queryChecker($activityLog, 'Activity Log');

            return response()->json([
                'success' => true,
                'message' => 'working',
                'data' => count($countWishlistAfterClear),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unable to clear wishlist, try again',
            ]);
        }
    }

    protected function clearCart(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $clearCart = DB::DELETE("DELETE FROM cart WHERE consumerUuid = ? ", [$user->uuid]);
        queryChecker($clearCart, 'Clear cart');
        if ($clearCart > 0) {

            $countCartAfterClear = DB::SELECT("SELECT * FROM cart WHERE consumerUuid = ? ", [$user->uuid]);
            queryChecker($countCartAfterClear, 'Count cart after clear');

            $countCartAfterClearQuantityKiev = '';
            foreach ($countCartAfterClear as $countCartAfterClearResult) {
                $quantity = $countCartAfterClearResult->quantity;
                $countCartAfterClearQuantityKiev += $quantity;
            }

            $updateCartAfterClear = DB::UPDATE("UPDATE consumers SET cartCount = ? WHERE uuid = ? ", [$countCartAfterClearQuantityKiev, $user->uuid]);
            queryChecker($updateCartAfterClear, 'Update cart after clear');

            $activityLog = DB::INSERT(
                "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer cleared cart', NOW()]
            );
            queryChecker($activityLog, 'Activity Log');

            return response()->json([
                'success' => true,
                'message' => 'working',
                'data' => count($countCartAfterClear),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unable to clear cart, try again',
            ]);
        }
    }

    protected function checkout(Request $request)
    {

        $keyss = '';
        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $checkout = DB::SELECT("SELECT products.*, tbl_images.*, cart.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN cart ON cart.productId = products.productId WHERE cart.consumerUuid = ? GROUP BY cart.productId", [$user->uuid]);
        $checkoutTotalData = count($checkout);
        if ($checkoutTotalData <= 1) {
        } else {
            $keyss = header("Access-Control-Allow-Origin: *");
        }
        $checkoutTotalFilter = $checkoutTotalData;
        $keyss;

        $checkoutSearch = DB::SELECT("SELECT products.*, tbl_images.*, cart.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN cart ON cart.productId = products.productId WHERE cart.consumerUuid = ? GROUP BY cart.productId", [$user->uuid]);
        if (!empty($request['search']['value'])) {
            $searchItem = $request['search']['value'];
            $checkout =  DB::SELECT("SELECT products.*, tbl_images.*, cart.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN cart ON cart.productId = products.productId WHERE cart.consumerUuid = ? AND products.productTitle LIKE '%$searchItem%' OR products.productTitle LIKE '$searchItem%' OR products.productTitle LIKE '%$searchItem'", [$user->uuid]);
        }

        $checkoutTotalData = count($checkoutSearch);
        $quantityKiev = '';
        $data = array();

        foreach ($checkoutSearch as $checkoutSearchResult) {

            $forAvatar = $checkoutSearchResult->path;

            $quantity = str_replace(",", "", $checkoutSearchResult->quantity);
            $unitPrice = str_replace(",", "", $checkoutSearchResult->productPrice);
            $initialShippingFee = str_replace(",", "", $checkoutSearchResult->shippingFee);

            $thisHuy = $quantity * $unitPrice;
            $thisGuy = $quantity * $initialShippingFee;

            $theSubtotal = number_format($thisHuy);
            $theShippingFee = number_format($thisGuy);

            $subdata = array();

            $subdata[] = '<div class="user-card">
            <div class="user-avatar"><img class="avatar" src="' . $forAvatar . '" alt=""></div>
            <div style="margin-top: -8px !important;" class="user-info">
            <span class="tb-lead">' . $checkoutSearchResult->productTitle . '</span>
            </div>
            </div>';

            $subdata[] = '<span>' . $checkoutSearchResult->quantity . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . number_format($checkoutSearchResult->productPrice) . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . $theSubtotal . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . $theShippingFee . '</span>';

            $subdata[] = '<ul style="display: inline-flex; align-items: center; position: relative;">
            <li><a style="padding-left: 10px!important; padding-right: 10px !important;" target="_blank" href="' . Config::get('app.UI_BASE_URL') . 'shop/p?main=' . $checkoutSearchResult->productId . '" data-id="' . $checkoutSearchResult->productId . '" class="getConsumers btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="View Product">
            <em style="color: #0337cb;" class="icon ni ni-eye"></em></a>
            </li>
        
            <li><a id="removeFromCartButton_' . $checkoutSearchResult->productId . '" style="padding-left: 10px!important; padding-right: 10px !important;" href="#" data-id="' . $checkoutSearchResult->productId . '" class="deleteProduct btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Remove From Cart">
            <em style="color: #0337cb;" class="icon ni ni-trash"></em></a>
            </li>
            
            </ul>';

            $subdata[] = $checkoutSearchResult->productId;
            $subdata[] = $checkoutSearchResult->shippingFee;
            $subdata[] = $quantityKiev;
            $data[] = $subdata;
        }

        return response()->json([
            "draw"              =>  intval($request['draw']),
            "recordsTotal"      =>  intval($checkoutTotalFilter),
            "recordsFiltered"   =>  intval($checkoutTotalFilter),
            'data'              =>  $data,
        ]);
    }

    protected function checkPassword(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        if (Hash::check($request->oldPassword, $user->cpassword)) {
            return response()->json([
                'success' => true,
                'message' => 'working',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Could not connect to server',
            ]);
        }
    }

    protected function changePassword(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $changePasswordNewHashed = Hash::make($request->newPassword);
        if (Hash::check($request->oldPassword, $user->cpassword)) {

            $changePassword = DB::UPDATE("UPDATE consumers SET cpassword = ?, reset_password_status = ? WHERE uuid = ?", [$changePasswordNewHashed, 'Approved', $user->uuid]);
            queryChecker($changePassword, 'Consumer could not change password');
            if ($changePassword > 0) {

                $activityLog = DB::INSERT(
                    "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer changed password', NOW()]
                );
                queryChecker($activityLog, 'Activity Log');

                return response()->json([
                    'success' => true,
                    'message' => 'working',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes recorded',
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Your old password is incorrect',
            ]);
        }
    }

    protected function changeConsumerAvatar(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        function hashConsumerAvatar()
        {
            $alphabet = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
            $pass = array(); //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < 16; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
            return implode($pass); //turn the array into a string
        }

        $hashConsumerAvatar = hashConsumerAvatar();
        $accesskey = "xd8ENrE4Yb4N0glWreRxaUSrHzqSRUxyBauxvOQDx0WwXEgh9UeBbvW090gzyO+4Als1gpSuO/+nZrIBgKcDAA==";
        $storageAccount = 'otobookstorage';
        $filetoUpload = realpath($request->file('changeConsumerAvatar'));
        $containerName = 'stage-consumer-profile';
        $blobName = $hashConsumerAvatar . '.jpg';
        $destinationURL = "https://$storageAccount.blob.core.windows.net/$containerName/$blobName";

        // $file = $request->file('changeConsumerAvatar');
        azureBlob($filetoUpload, $storageAccount, $containerName, $blobName, $destinationURL, $accesskey);
        // if($file != ''){
        // $ext = $file->getClientOriginalExtension();
        // $name = $hashConsumerAvatar . '.' . $ext;
        // $location = 'uploads/images/' . $name;
        // //Move Uploaded File
        // $destinationPath = 'uploads/images';
        // $file->move($destinationPath, $name);
        // $finalLocation = Config::get("app.BASE_URL") . $location;
        // }

        $changeConsumerAvatar = DB::UPDATE("UPDATE consumers SET avatar = ? WHERE uuid = ?", [$destinationURL, $user->uuid]);
        queryChecker($changeConsumerAvatar, 'Change Consumer Avatar');
        if ($changeConsumerAvatar > 0) {

            $selectConsumer = DB::SELECT("SELECT * FROM consumers WHERE uuid = ? ", [$user->uuid]);
            queryChecker($selectConsumer, 'Select consumer details query');
            if (count($selectConsumer) === 1) {

                $activityLog = DB::INSERT(
                    "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer added to wishlist', NOW()]
                );
                queryChecker($activityLog, 'Activity Log');

                return response()->json([
                    'success' => true,
                    'message' => 'working',
                    'data' => $selectConsumer,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not connect to server',
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No changes recorded',
            ]);
        }
    }

    protected function addToWishlist(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $checkExistingWishlist = DB::SELECT("SELECT * FROM wishlist WHERE productId = ? AND consumerUuid = ? ", [$request->productId, $user->uuid]);
        queryChecker($checkExistingWishlist, 'Check existing wishlist');

        if (count($checkExistingWishlist) === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Item already added to wishlist',
            ]);
        } else {

            $insertWishlist = DB::SELECT("INSERT INTO wishlist (wishlistId, productId, createdAt, consumerUuid) VALUES (?, ?, ?, ?)", [$request->wishlistId, $request->productId, NOW(), $user->uuid]);
            queryChecker($insertWishlist, 'Insert new wishlist');

            $activityLog = DB::INSERT(
                "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer added to wishlist', NOW()]
            );

            $selectWishlist = DB::SELECT("SELECT * FROM wishlist WHERE consumerUuid = ? ", [$user->uuid]);
            $updateWishlistCount = DB::UPDATE("UPDATE consumers SET wishlistCount = ? WHERE uuid = ? ", [count($selectWishlist), $user->uuid]);
            if ($updateWishlistCount > 0) {

                return response()->json([
                    'success' => true,
                    'message' => 'working',
                    'data'    =>  count($selectWishlist),
                ]);
            }
        }
    }

    protected function addToCart(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $selectCart = DB::SELECT("SELECT * FROM cart WHERE productId = ? AND consumerUuid = ? ", [$request->productId, $user->uuid]);
        queryChecker($selectCart, 'Select from cart');

        if (count($selectCart) === 1) {

            $activityLog = DB::INSERT(
                "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer added to cart', NOW()]
            );

            $quantity = '';
            foreach ($selectCart as $selectCartResult) {
                $quantity = $selectCartResult->quantity;
                $quantity++;
            }

            $updateCartQuantity = DB::UPDATE("UPDATE cart SET quantity = ?, updatedAt = ? WHERE productId = ? AND consumerUuid = ? ", [$quantity, NOW(), $request->productId, $user->uuid]);
            queryChecker($updateCartQuantity, 'Update cart');

            if ($updateCartQuantity > 0) {

                $countCart = DB::SELECT("SELECT * FROM cart WHERE consumerUuid = ? ", [$user->uuid]);
                queryChecker($countCart, 'Count cart');

                $quantityKiev = 0;
                foreach ($countCart as $countCartResult) {
                    $quantity = $countCartResult->quantity;
                    $quantityKiev += $quantity;
                }

                $updateConsumerTable = DB::UPDATE("UPDATE consumers SET cartCount = ? WHERE uuid = ? ", [$quantityKiev, $user->uuid]);
                if ($updateConsumerTable > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => 'working',
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not connect to server',
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not connect to server',
                ]);
            }
        } else {

            $addToCartTwo = DB::INSERT("INSERT INTO cart (cartId, productId, quantity, unitPrice, shippingFee, consumerUuid, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)", [$request->cartId, $request->productId, $request->quantity, $request->unitPrice, $request->shippingFee, $user->uuid, NOW()]);
            queryChecker($addToCartTwo, 'Add to cart');

            $activityLog = DB::INSERT(
                "INSERT INTO activity_log (consumerUuid, ip, os, browser, device, activity, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$user->uuid, $request->ip, $request->os, $request->browser, $request->device, 'Consumer added to cart', NOW()]
            );

            $countCartTwo = DB::SELECT("SELECT * FROM cart WHERE consumerUuid = ? ", [$user->uuid]);
            queryChecker($countCartTwo, 'Count cart');

            $quantityKiev = 0;
            foreach ($countCartTwo as $countCartTwoResult) {
                $quantity = $countCartTwoResult->quantity;
                $quantityKiev += $quantity;
            }

            $updateConsumerTableTwo = DB::UPDATE("UPDATE consumers SET cartCount = ? WHERE uuid = ? ", [$quantityKiev, $user->uuid]);
            if ($updateConsumerTableTwo > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'working',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to add to cart, please try again',
                ]);
            }
        }
    }

    protected function getCategories()
    {

        $master_categories = [];
        $master_category_query = DB::SELECT("SELECT * FROM manage_browse_category");

        foreach ($master_category_query as $master_category_queryResult) {

            $master_category_ID = $master_category_queryResult->categoryId;
            $master_category_name = $master_category_queryResult->categoryName;
            $main_cats = [];

            $main_category_query = DB::SELECT("SELECT * FROM manage_main_browse_category WHERE browseCategoryId = '$master_category_ID'");
            foreach ($main_category_query as $main_category_queryResult) {

                $main_category_ID = $main_category_queryResult->mainCategoryId;
                $main_category_name = $main_category_queryResult->mainCategory;
                $sub_cats = [];

                $sub_category_query = DB::SELECT("SELECT * FROM manage_sub_category WHERE mainBrowseCategoryId = '$main_category_ID'");

                foreach ($sub_category_query as $sub_category_queryResult) {

                    $sub_category_ID = $sub_category_queryResult->newSubId;
                    $sub_category_name = $sub_category_queryResult->newSub;

                    array_push($sub_cats, [
                        'sub_category_name' => $sub_category_name,
                    ]);
                }

                array_push($main_cats, [
                    'main_category_name' => $main_category_name,
                    'main_category_ID' => $main_category_ID,
                    'sub_categories' => $sub_cats,
                ]);
            }

            array_push($master_categories, [
                'master_category_name' => $master_category_name,
                'main_categories' => $main_cats,
            ]);
        }

        $someJSON = json_encode($master_categories);
        return response()->json([
            "success"      =>  true,
            "message"   =>  "working",
            'data'              =>  $master_categories,
        ]);
        // echo $someJSON;

    }

    protected function getCart(Request $request)
    {

        if ($request->bearerToken() == null || $request->bearerToken() == "") {
            return checkJWT($request->bearerToken());
        }
        $user = checkJWT($request->bearerToken());

        $finalAPIURL = '';

        if (strpos($_SERVER['HTTP_HOST'], "127") !== false) {
            $finalAPIURL = "http://localhost/otobook-api/";
        } else {
            $finalUIURL = Config::get('app.APP_URL');
        }

        $finalUIURL = '';

        if (strpos($_SERVER['HTTP_HOST'], "127") !== false) {
            $finalUIURL = "http://localhost/otobook/";
        } else {
            $finalUIURL = Config::get('app.UI_BASE_URL');
        }

        $getCart = DB::SELECT("SELECT products.*, tbl_images.*, cart.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN cart ON cart.productId = products.productId WHERE cart.consumerUuid = ? GROUP BY cart.productId DESC", [$user->uuid]);
        $totalFilter = count($getCart);

        $getCart = DB::SELECT("SELECT products.*, tbl_images.*, cart.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN cart ON cart.productId = products.productId WHERE cart.consumerUuid = ? GROUP BY cart.productId DESC", [$user->uuid]);
        if (!empty($request['search']['value'])) {
            $searchItem = $request['search']['value'];
            $getCart =  DB::SELECT("SELECT products.*, tbl_images.*, cart.* FROM products INNER JOIN tbl_images ON tbl_images.productId = products.productId INNER JOIN cart ON cart.productId = products.productId WHERE cart.consumerUuid = ? AND products.productTitle LIKE '%$searchItem%' OR products.productTitle LIKE '$searchItem%' OR products.productTitle LIKE '%$searchItem'", [$user->uuid]);
        }

        $totalFilter = count($getCart);
        $selected = '';
        $data = array();

        foreach ($getCart as $getCartResult) {

            $thisHuy = $getCartResult->quantity * $getCartResult->productPrice;
            $thisGuy = $getCartResult->quantity * $getCartResult->shippingFee;
            $theUnitPrice = number_format($thisHuy);
            $theShippingFee = number_format($thisGuy);

            if ($getCartResult->quantity == 1) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option selected value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 2) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option selected value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 3) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option value="2">2</option>
                <option selected value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 4) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option selected value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 5) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option selected value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 6) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option selected value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 7) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option selected value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 8) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option selected value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 9) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option selected value="9">9</option>
                <option value="10">10</option>
                </select>
                </div>';
            }

            if ($getCartResult->quantity == 10) {
                $selected = '<div class="user-card">
                <select data-id=' . $getCartResult->quantity . ' class="quantitySelect btn btn-trigger btn-icon">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option selected value="10">10</option>
                </select>
                </div>';
            }

            $subdata = array();

            $subdata[] = '<div class="user-card">
            <div class="user-avatar"><img style="margin-left: 1px !important; height: 40px !important; object-fit: contain;"  class="avatar" src="' . $getCartResult->path . '" alt=""></div>
            <div style="margin-top: -8px !important;" class="user-info">
            <span class="tb-lead">' . $getCartResult->productTitle . '</span>
            </div>
            </div>';

            $subdata[] = $selected;

            $subdata[] = '<span>GH₵ ' . '' . number_format($getCartResult->productPrice) . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . $theUnitPrice . '</span>';
            $subdata[] = '<span>GH₵ ' . '' . $theShippingFee . '</span>';

            $subdata[] = '<ul style="display: inline-flex; align-items: center; position: relative;">
            <li><a style="padding-left: 10px!important; padding-right: 10px !important;" target="_blank" href="' . $finalUIURL . 'shop/p?main=' . $getCartResult->productId . '" data-id="' . $getCartResult->productId . '" class="getConsumers btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="View Product">
            <em style="color: #0337cb;" class="icon ni ni-eye"></em></a>
            </li>
        
            <li><a id="removeFromCartButton_' . $getCartResult->productId . '" style="padding-left: 10px!important; padding-right: 10px !important;" href="#" data-id="' . $getCartResult->cartId . '" class="deleteProduct btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Remove From Cart">
            <em style="color: #0337cb;" class="icon ni ni-trash"></em></a>
            </li>
            
            </ul>';
            $subdata[] = $getCartResult->productId;
            $subdata[] = $getCartResult->shippingFee;
            $subdata[] = $getCartResult->quantity;
            $subdata[] = $getCartResult->cartId;
            $data[] = $subdata;
        }

        return response()->json([
            "draw"              =>  intval($request->draw),
            "recordsTotal"      =>  intval($totalFilter),
            "recordsFiltered"   =>  intval($totalFilter),
            'data'              =>  $data,
        ]);
    }

    protected function shopAll()
    {

        $finalAPIURL = '';

        if (strpos($_SERVER['HTTP_HOST'], "127") !== false) {
            $finalAPIURL = "http://localhost/otobook-api/";
        } else {
            $finalAPIURL = Config::get('app.APP_URL');
        }

        $finalUIURL = '';

        if (strpos($_SERVER['HTTP_HOST'], "127") !== false) {
            $finalUIURL = "http://localhost/otobook/";
        } else {
            $finalUIURL = Config::get('app.UI_BASE_URL');
        }

        // Set some useful configuration
        $forPagination = '';
        $baseURL = 'shopAll.php';
        $offset = !empty($_POST['page']) ? $_POST['page'] : 0;
        $limit = 20;
        $output = '';
        $rowCount = 0;

        $getProductCount = DB::select('SELECT COUNT(*) as rowNum FROM `products` WHERE `status` = ? GROUP BY products.productId DESC', ['Publish']);
        // Initialize pagination class 
        $pagConfig = array(
            'baseURL' => $baseURL,
            'totalRows' => count($getProductCount),
            'perPage' => $limit,
            'currentPage' => $offset,
            'contentDiv' => 'postContent',
            'link_func' => 'searchFilter'
        );

        $pagination =  new Pagination($pagConfig);
        $getProduct = DB::select('SELECT products.*, tbl_images.* FROM products INNER JOIN tbl_images ON products.productId = tbl_images.productId WHERE products.status = ? GROUP BY products.productId DESC LIMIT ?, ?', ['Publish', $offset, $limit]);
        $theAdd = '';
        $checkOutOfStock = '';
        $checkOutOfStockText = '';

        foreach ($getProduct as $getProductResult) {

            $forAvatar = $getProductResult->path;
            $productOldPrice = $getProductResult->oldPrice;
            $firstOne = $getProductResult->productId;
            $productOldPriceKiev = '';
            $productQuantity = $getProductResult->quantity;
            $discount = $getProductResult->discount;
            $priceKievFirst = $getProductResult->productPrice;
            $priceKiev = number_format($priceKievFirst);
            $shippingFeeKievFirst = $getProductResult->shippingFee;
            $shippingFeeKiev = $shippingFeeKievFirst;

            // $countCart = DB::select('SELECT productId FROM wishlist WHERE productId = ? AND consumerUuid = ?', [".$firstOne.", ".$uuid."]);

            if ($productQuantity < 1) {
                $checkOutOfStock = '<a style="cursor: default;" class="btn btn-icon" data-toggle="tooltip" data-placement="top" title="Out of Stock">
                <em class="icon ni ni-cross-round"></em></a>';
                $checkOutOfStockText = '<span style="color: red;">Out of stock</span>';
            } else {
                $checkOutOfStock = '<a id="' . $firstOne . '' . ' ' . $priceKievFirst . '' . ' ' . $shippingFeeKiev . '" data-id="' . $firstOne . '" class="addToCart btn btn-icon" data-toggle="tooltip" data-placement="top" title="Add to Cart • ' . $productQuantity . ' in stock">
                <em style="color: #0337cb;" class="icon ni ni-cart"></em></a>';
                $checkOutOfStockText = 'Availabe: <span> ' . $productQuantity . '</span></p>';
            }

            // out of stock and cart end

            if ($productOldPrice != "") {
                $discountKiev = '<div style="margin-left: -0.5rem" class="label_product">
                    <span class="label_sale"><span class="label_sale" style="display: revert; text-decoration: line-through;">GH₵ ' . '' . $getProductResult->oldPrice . '</span>' . '• &nbsp;-' . '' . $getProductResult->discount . '' . '%</span>
                </div>';
            } else {
                $discountKiev = '';
            }

            $output .= '
        
            <div style="
            width: 13rem;
            background-color: #eee;
            float: none;
            margin: 0 0.25%;
            display: inline-block;
            margin-top: 12px;
            zoom: 1;">
        
            <div style="background: #fff; padding: 10px 18px 8px 18px;
            border-radius: 3px;
            margin: 1px;
            -moz-box-shadow: 0 0 3px #ccc;
            -webkit-box-shadow: 0 0 3px #ccc;
            box-shadow: 0 0 3px #ccc;">
        
            <div class="product_name">
            <h3><a href="' . $finalUIURL . 'shop/p?main=' . $getProductResult->productId . '">' . $getProductResult->productTitle . '</a></h3>
            <span class="manufacture_product"><a href="' . $finalUIURL . 'shop/p?main=' . $getProductResult->productId . '">' . $getProductResult->category . '</a></span>
        </div>
        <div class="product_thumb">
            <a class="primary_img" href="' . $finalUIURL . 'shop/p?main=' . $getProductResult->productId . '"><img style="object-fit: contain; width: 600px !important; height: 185px !important;" src="' . $forAvatar . '" alt=""></a>
        
            ' . $discountKiev . '
        
        </div>
        <div class="product_content">
            <div class="product_ratings">
            <span class="label_sale"><span class="label_sale">' . $getProductResult->brand . '</span>' . ' • ' . '' . $getProductResult->modelYear . '</span>
            </div>
            <div class="product_footer d-flex align-items-center">
                    <div class="product_footer d-flex align-items-center">
                        <div class="price_box" style="margin-bottom: 1px !important;">
                        <span id="subtotal" style="text-decoration: none; font-size: 1rem; font-weight: 600;" class="old_price">GH₵ ' . '' . $priceKiev . '</span>
                        </div><br />
                    </div>
                <div>
                    ' . $checkOutOfStock . '
                </div>
            </div>
        </div>
        
        
            </div>
            </div>
        
        ';
        }

        if ($rowCount > 20) {
            $forPagination = $pagination->createLinks();
        }

        $output .= $pagination->createLinks();
        $output .= $forPagination;
        return response()->json([
            'success' => true,
            'message' => 'working',
            'data' => $output,
        ]);
    }
}
