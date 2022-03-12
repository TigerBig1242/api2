<?php

//function pre($arr){
//    echo '<pre>';
//    print_r($arr);
//    echo '</pre>';
//}
function base_url(){
    
}
function photo(){

    return "https://market.deltafood.me/api2/";
}

// function photoTypeFood(){

//     return "https://market.deltafood.me/api/image/typeFood/";
// }
// function generateRandomString($length = 10) {
//     $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//     $charactersLength = strlen($characters);
//     $randomString = '';
//     for ($i = 0; $i < $length; $i++) {
//         $randomString .= $characters[rand(0, $charactersLength - 1)];
//     }
//     return $randomString;
// }

function sendOTP_sms($msisdn, $message, $ScheduledDelivery = "", $force = "corporate")
    {

        $body = [
            'msisdn' => $msisdn,
            'message' => $message,
            'sender' => 'Deltafood',
            //            'scheduled_delivery' => '',
            'force' => 'corporate'
        ];
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api-v2.thaibulksms.com/sms",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => http_build_query($body),
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                'Authorization: Basic ' . base64_encode("b594e895fb5f8003758bd0818cf58aea:2084b1f626fd1d02e2c7c907642b3e63"),
                "Content-Type: application/x-www-form-urlencoded"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
    }
    function delivery()
    {
        return "เดริเวอรี่";
    }
    function onMarket(){
        return "ทานที่ร้าน";
    }
    function pre($arr) {
        echo "<pre>";
        print_r($arr);
        echo "</pre>";
    } 