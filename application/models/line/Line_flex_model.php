<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'line/autoload.php';

class Line_flex_model extends CI_Model
{

  private $bot;
    private $assess_token = "bNbjZAR8emm86xH7oW6WkgYmU75pCAiJupQ4SjBG05NVq9mO5EnnHppcljiopIGGQ1GBlR9ZxdzVQlw4qzA1xPBK7CGqh5Nyv4KWFXwO4gWijhrVVJMsexEwc+Uap1GC4Qx9ykcN7BOo474wnXzr4AdB04t89/1O/w1cDnyilFU=";
    private $serect_key = 'e91da921522c91af4b73a3b201274af7';
    /*  */
    public function __construct()
    {
        parent::__construct();
        $this->load->model("line/Line_flex_model");
        $this->load->model("line/Flex_user_model");
        $this->load->model("line/Flex_rider_model");

        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->assess_token);
        $this->bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $this->serect_key]);
    }

    public function flexMessage($data, $userId)
    {
        $accessToken = $this->assess_token;
        $arrayHeader = array();
        $arrayHeader[] = "Content-Type: application/json";
        $arrayHeader[] = "Authorization: Bearer {$accessToken}";
        $arrayPostData = array(
            'to' => $userId,
            'messages' => array(
                $data
            )
        );
        $strUrl = "https://api.line.me/v2/bot/message/push";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $strUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayPostData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
    }

    public function sendMessage($userId, $message)
    {
        $textMessageBuilder = new LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
        $response = $this->bot->pushMessage($userId, $textMessageBuilder);
        $response->getHTTPStatus();
        echo $message;
    }
    ####################################################################


    public function flex_user($userId, $order_id)
    {
        
        $json = $this->Flex_user_model->user_order($userId, $order_id);
        $data = json_decode($json, true);
        $this->flexMessage($data, $userId);
    }
    public function flex_rider($userId, $order_id)
    {
        $json = $this->Flex_rider_model->rider_order($order_id);
        $data = json_decode($json, true);
        $this->flexMessage($data, $userId);
    }

    public function flex_rider_orderIn(){
    $userId=  $this->db->select('userId')
      ->get('tb_rider')->result();

      foreach ($userId as $key => $value) {
        $json = $this->Flex_rider_model->rider_orderIn();
        $data = json_decode($json, true);
        $this->flexMessage($data, $value->userId);
      }
    }

  





    
}
