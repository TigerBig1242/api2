<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//echo phpinfo();
$img = '';
$data = file_get_contents('php://input');
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://market.deltafood.me/api/webhook",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/x-www-form-urlencoded"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo $response;
}

exit();





//$url = "https://app.deltafood.me/ci/webhook/2"; //. $_GET['id'];
//
//
//$curl = curl_init();
//
//curl_setopt_array($curl, array(
//  CURLOPT_URL => "https://app.deltafood.me/ci/webhook/2",
//  CURLOPT_RETURNTRANSFER => true,
//  CURLOPT_ENCODING => "",
//  CURLOPT_MAXREDIRS => 10,
//    
//  CURLOPT_TIMEOUT => 30,
//  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//  CURLOPT_CUSTOMREQUEST => "POST",
//  CURLOPT_POSTFIELDS => "aaaaaaaaaaaa=",
//  CURLOPT_HTTPHEADER => array(
//    "cache-control: no-cache",
//    "content-type: application/x-www-form-urlencoded",
//    "postman-token: 6dded559-f58f-7771-8524-fc5deda74190"
//  ),
//));
//
//$response = curl_exec($curl);
//$err = curl_error($curl);
//
//curl_close($curl);
//
//if ($err) {
//  echo "cURL Error #:" . $err;
//} else {
//  echo $response;
//}
//$cmd="curl -X POST \
//  https://app.deltafood.me/ci/webhook/2 \
//  -H 'cache-control: no-cache' \
//  -H 'content-type: application/x-www-form-urlencoded' \
//  -H 'postman-token: 51fe56a9-e8d9-91e5-ebc4-1c3c243366cf' \
//  -d aaaaaaaaaaaa=";
//exec($cmd,$result);
//print_r($result);
//exit();
//$curl = curl_init();
//
//curl_setopt_array($curl, array(
//    CURLOPT_URL => "https://app.deltafood.me/ci/webhook/2",
//    CURLOPT_RETURNTRANSFER => true,
//    CURLOPT_ENCODING => "",
//    CURLOPT_MAXREDIRS => 10,
//    CURLOPT_TIMEOUT => 30,
//    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//    CURLOPT_CUSTOMREQUEST => "POST",
//    CURLOPT_POSTFIELDS => "aaaaaaaaaaaa=",
//    CURLOPT_HTTPHEADER => array(
//        "cache-control: no-cache",
//        "content-type: application/x-www-form-urlencoded",
//        "postman-token: a6187972-5c35-74c3-5358-0ac123692cd9"
//    ),
//));
//
//$response = curl_exec($curl);
//$err = curl_error($curl);
//
//curl_close($curl);
//
//if ($err) {
//    echo "cURL Error #:" . $err;
//} else {
//    echo $response;
//}
//exit();
//
//
//file_get_contents($url);
//$curlSession = curl_init();
//curl_setopt($curlSession, CURLOPT_URL, $url);
//curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
//curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
//
//$jsonData = json_decode(curl_exec($curlSession));
//curl_close($curlSession);
//print "curl response is:" . $response;
//curl_close($ch);
//exit();

$message = $data;
//        $token = "QDateHQlGFLv191hcTV2KYYAc63KHFCorrkKHpPLFvj";
//        define("LINE_API", 'https://notify-api.line.me/api/notify');
//        define("LINE_TOKEN", "QDateHQlGFLv191hcTV2KYYAc63KHFCorrkKHpPLFvj");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("Asia/Bangkok");
$sToken = "QDateHQlGFLv191hcTV2KYYAc63KHFCorrkKHpPLFvj";
if ($img != "") {
    $sMessage = $message . "&imageThumbnail=" . $img . "&imageFullsize=" . $img;
} else {
    $sMessage = $message;
}
$chOne = curl_init();
curl_setopt($chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
curl_setopt($chOne, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($chOne, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($chOne, CURLOPT_POST, 1);
curl_setopt($chOne, CURLOPT_POSTFIELDS, "message=" . $sMessage);
$headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $sToken . '',);
curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
curl_setopt($chOne, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($chOne);

if (curl_error($chOne)) {
    //    echo 'error:' . curl_error($chOne);
} else {
    $result_ = json_decode($result, true);
}
curl_close($chOne);
