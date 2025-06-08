<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use File;
use DB;
use Google\Client;
use Illuminate\Support\Facades\File as FacadesFile;

class Common extends Model
{
    public static function generateUniqueUserId()
    {
        $token =  rand(100000, 999999);

        $first = Common::generateRandomString(3);
        $first .= $token;
        $first .= Common::generateRandomString(3);
        $count = User::where('user_name', $first)->count();

        while ($count >= 1) {

            $token =  rand(100000, 999999);
            $first = Common::generateRandomString(3);
            $first .= $token;
            $first .= Common::generateRandomString(3);
            $count = Common::where('ads_number', $first)->count();
        }

        return $first;
    }

    public static function generateRandomString($length)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function send_push($topic, $title, $message, $plateform = "")
    {


        $client = new Client();
        $client->setAuthConfig('googleCredentials.json');
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->fetchAccessTokenWithAssertion();
        $accessToken = $client->getAccessToken();
        $accessToken = $accessToken['access_token'];

        $contents = FacadesFile::get(base_path('googleCredentials.json'));
        $json = json_decode(json: $contents, associative: true);

        // Log::info($accessToken);

        $url = 'https://fcm.googleapis.com/v1/projects/'.$json['project_id'].'/messages:send';
        $notificationArray = array('title' => $title, 'body' => $message);

        $device_token = $topic;

        $fields = array(
            'message'=> [
                'token'=> $device_token,
                'notification' => $notificationArray,
            ]
        );

        $headers = array(
            'Content-Type:application/json',
            'Authorization:Bearer ' . $accessToken
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        // print_r(json_encode($fields));
        $result = curl_exec($ch);
        // Log::debug($result);

        if ($result === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);

        // return $response;
    }
}
