<?php

namespace App\Http\Controllers\Admin;

// use Config;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Namshi\JOSE\SimpleJWS;

class Admin extends Controller
{

  protected function getConsumersSmallProfile(Request $request)
  {

    $someArray = [];
    $getConsumersSmallProfile = DB::SELECT("SELECT * FROM users WHERE uuid = ? ", [$request->consumeruuid]);
    queryChecker($getConsumersSmallProfile, 'Select user small');

    foreach ($getConsumersSmallProfile as $getConsumersSmallProfileResult) {
      array_push($someArray, [
        'firstName'   => $getConsumersSmallProfileResult->firstName,
        'otherNames'   => $getConsumersSmallProfileResult->otherNames,
        'email'   => $getConsumersSmallProfileResult->email,
        'avatar'   => $getConsumersSmallProfileResult->avatar,
        'account_status' => $getConsumersSmallProfileResult->account_status,
      ]);
    }

    //looking for this
    return response()->json([
      'success' => true,
      'message' =>   'working',
      'data' =>   $someArray,
    ]);
  }

  protected function suspendConsumer(Request $request)
  {

    $suspendConsumer = DB::UPDATE("UPDATE consumers SET account_status = 'Suspended' WHERE uuid = ?", [$request->consumeruuid]);
    queryChecker($suspendConsumer, 'Suspend consumer');
    if ($suspendConsumer > 0) {

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
                "firstName": "' . $request->firstName . '",
                "emailDraft": "' . $request->extraComment . '",
                },
                "subject": ""
            }
            ],
            "from": {
              "email": "' . Config::get('app.OTOBOOK_EMAIL') . '",
              "name": "' . Config::get('app.OTOBOOK_EMAIL_NAME') . '"
          },
            "template_id": "d-abdc27c9669045cda7291bcb6bce653d"
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
    } else {
      return response()->json([
        'success' => false,
        'message' =>   'not working',
      ]);
    }
  }

  protected function deleteConsumer(Request $request)
  {

    $deleteConsumer = Db::DELETE("DELETE FROM consumers WHERE uuid = '" . $request->consumeruuid . "' ");
    queryChecker($deleteConsumer, 'Delete Consumer');
    if ($deleteConsumer > 0) {

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
                "firstName": "' . $request->firstName . '",
                "emailDraft": "' . $request->extraComment . '",
                },
                "subject": ""
            }
            ],
            "from": {
              "email": "' . Config::get('app.OTOBOOK_EMAIL') . '",
              "name": "' . Config::get('app.OTOBOOK_EMAIL_NAME') . '"
          },
            "template_id": "d-f85b758d007c4764aaeda14b2d99396d"
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
    } else {
      return response()->json([
        'success' => false,
        'message' =>   'Unable to delete consumer',
      ]);
    }
  }

  protected function consumers(Request $request)
  {

    //create column like table in database

    $getConsumers = DB::SELECT("SELECT * FROM users");
    queryChecker($getConsumers, 'Get users (admin)');
    $totalData = count($getConsumers);
    $totalFilter = $totalData;

    //Search
    // $sql = DB::SELECT("SELECT * FROM consumers WHERE 1=1");
    // if(!empty($request['search']['value'])){
    //     $sql.=" AND id Like '".$request['search']['value']."%' ";
    //     $sql.=" OR firstName Like '".$request['search']['value']."%' ";
    //     $sql.=" OR otherNames Like '".$request['search']['value']."%' ";
    //     $sql.=" OR signin_type Like '".$request['search']['value']."%' ";
    //     $sql.=" OR email Like '".$request['search']['value']."%' ";
    // }
    // $query = mysqli_query($conn,$sql);
    // $totalData = mysqli_num_rows($query);

    // Order
    // $getConsumers.=" ORDER BY ".$col[$request['order'][0]['column']]."   ".$request['order'][0]['dir']."  LIMIT ".
    //     $request['start']."  ,".$request['length']."  ";
    // queryChecker($getConsumers, 'Get consumers continuation (admin)');

    $data = array();
    foreach ($getConsumers as $getConsumersResult) {
      $account_status = $getConsumersResult->account_status;

      if ($account_status == "Approved") {
        $getItt = '<li><a href="#" data-id="' . $getConsumersResult->uuid . '" class="suspenConsumer btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Suspend Consumer">
                <em style="color: #0337cb;" class="icon ni ni-user-cross"></em></a>
                </li>';
      } else if ($account_status == "Suspended") {
        $getItt = '<li><a href="#" data-id="' . $getConsumersResult->uuid . '" class="activateConsumer btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Activate Consumer">
                <em style="color: #0337cb;" class="icon ni ni-user-check"></em></a>
                </li>';
      } else {
        $getItt = '<li><a href="#" data-id="' . $getConsumersResult->uuid . '" class="activateConsumer btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Activate Consumer">
                <em style="color: #0337cb;" class="icon ni ni-user-check"></em></a>
                </li>';
      }

      $subdata = array();

      $subdata[] = '<div class="user-card">
            <div class="user-avatar"><img class="avatar" src="' . $getConsumersResult->avatar . '" alt=""></div>
            <div class="user-info">
            <span class="tb-lead">' . $getConsumersResult->firstName . ' ' . $getConsumersResult->otherNames . '</span>
            <span>' . $getConsumersResult->email . '</span>
            </div>
            </div>';

      $subdata[] = '<span>' . $getConsumersResult->account_status . '</span>';

      $subdata[] = '<ul style="display: flex; align-items: center; position: relative;">
      <li><a href="#" data-id="' . $getConsumersResult->uuid . '" class="getConsumers btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="View Details">
      <em style="color: #0337cb;" class="icon ni ni-eye"></em></a>
      </li>

      ' . $getItt . '
      
      <li><a href="#" data-id="' . $getConsumersResult->uuid . '" class="deleteConsumer btn btn-trigger btn-icon" data-toggle="tooltip" data-placement="top" title="Delete Consumer">
      <em style="color: #0337cb;" class="icon ni ni-trash"></em></a>
      </li>
      
      </ul>';

      $data[] = $subdata;
    }

    return response()->json([
      "draw"              =>  intval($request->draw),
      "recordsTotal"      =>  intval($totalData),
      "recordsFiltered"   =>  intval($totalFilter),
      'data'              =>  $data,
    ]);

  }

  protected function activateConsumer(Request $request)
  {

    $activateConsumer = DB::UPDATE("UPDATE users SET account_status = ? WHERE uuid = ? ", ['Approved', $request->consumeruuid]);
    queryChecker($activateConsumer, 'Activate consumer account');
    if ($activateConsumer > 0) {

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
                    "firstName": "' . $request->firstName . '",
                    "emailDraft": "' . $request->extraComment . '",
                    },
                    "subject": ""
                }
                ],
                "from": {
                    "email": ' . Config::get('app.OTOBOOK_EMAIL') . ',
                    "name": ' . Config::get('app.OTOBOOK_EMAIL_NAME') . '
                },
                "template_id": "d-f9226f096f924602acb2ece64018f68c"
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
    } else {
      return response()->json([
        'success' => false,
        'message' =>   'now working',
      ]);
    }
  }

}
