<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'line/autoload.php';

class Line_model extends CI_Model
{

    private $bot;
    private $access_token = "z5x0eX294fz+az93iFrJRFtqB1dyVOR3RLw8du+Sy6x26iQ0PldFezCPQod7SdoQG2Z/ey0thHIk8B1/BjC7dcVrwYI7bE66fwKP60NsjTbzwIiFn3ysKFqfvj1zDfXWQtaABAl0dZrrKEzrP9llfgdB04t89/1O/w1cDnyilFU=";
    private $secert_key = '4883ca0e056faaecd2c40fd555414ccc';
    // private $access_token = "sCGjaB6jSJrpVG+loHyCLflN6VU8p7TrHsCNSNYbwHQmH5pgSG0TP3zMNwQB0wVk4w73tDJHKd7D8/Pq7/MKUxL++ztu9gMWUZIeSMZgw8XBtZKFDqw/TLvseeocJqJ1CtKsbIBmVIah+qpCGUZ6CwdB04t89/1O/w1cDnyilFU=";
    // private $secert_key = '6f78a533aa418f9a0ab9e97d0b63d236';

    #userId Admin
    private $userId_Admin;

    #Rich Menu Wed MAin
    // private $richMenuUser = "richmenu-ab92d587348031594384e28e8ebf3166"; //set
    // private $richMenuStore = "richmenu-5fa4b44e5fe7ad25172f74903251719a"; //set
    // private $richMenuRider = "richmenu-92c90a70797acb4c17d3db3f73884be8"; //set
    // private $richMenuAdmin = "richmenu-e918c53c20cb9f9b314e8a0e225b36ee"; //set
    #Rich Menu Wed Demo
    private $richMenuUser = "richmenu-6c739c196bb6983fd0af92d888cc34f6"; //set
    private $richMenuStore = "richmenu-db6ff54a4f743182cf683aa799e9dabd"; //set
    private $richMenuRider = "richmenu-c159b6c9439e8833290959bcafcedc1b"; //set
    private $richMenuAdmin = "richmenu-6a26f2cdb9bb92917b6c283298fb5ec7"; //set

    private $imagePathUser = "image/rich_menu/Rich_user.jpg";
    private $imagePathStore = "image/rich_menu/Rich_store.jpg";
    private $imagePathRider = "image/rich_menu/Rich_rider.jpg";
    private $imagePathAdmin = "image/rich_menu/Rich_admin.jpg";

    /*  */
    public function __construct()
    {
        parent::__construct();
        $this->load->model("line/Flex_user_model");
        $this->load->model("line/Flex_rider_model");
        $this->load->model("line/Flex_store_model");
        $this->load->model("line/Flex_admin_model");

        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->access_token);
        $this->bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $this->secert_key]);
        $this->userId_Admin = $this->getUserId_admin();
    }
    function getUserId_admin()
    {
        $userId = $this->db->select('userId')
            ->where('type_id', 1)
            ->where('m_active', 1)
            ->where('m_status', 1)
            ->get('tb_member')->result();
        return $userId;
    }
    public function setRichMenu($profile)
    {
        $userId = $profile->userId;
        $type = $profile->type_id;
        if ($type == 1) {
            $richMenuID = $this->richMenuAdmin;
            $imagePath = $this->imagePathAdmin;
        } elseif ($type == 2) {
            $richMenuID = $this->richMenuStore;
            $imagePath = $this->imagePathStore;
        } elseif ($type == 3 ||$type == 5) {
            $richMenuID = $this->richMenuRider;
            $imagePath = $this->imagePathRider;
        } elseif ($type == 4) {
            $richMenuID = $this->richMenuUser;
            $imagePath = $this->imagePathUser;
        }
        $contentType = 'image/jpeg';
        $this->bot->uploadRichMenuImage($richMenuID, $imagePath, $contentType);
        $this->bot->linkRichMenu($userId, $richMenuID);
    }

    public function createNewRichmenu()
    {
        $channelAccessToken = $this->access_token;
        $sh = <<< EOF
        curl -X POST \
        -H 'Authorization: Bearer $channelAccessToken' \
        -H 'Content-Type:application/json' \
        -d '{
            "size": {
            "width": 2500,
            "height": 843
            },
            "selected": true,
            "name": "Rich Menu 1",
            "chatBarText": "Bulletin",
            "areas": [
            {
                "bounds": {
                "x": 30,
                "y": 64,
                "width": 1885,
                "height": 728
                },
                "action": {
                "type": "uri",
                "uri": "https://market.deltafood.me/store"
                }
            },
            {
                "bounds": {
                "x": 1962,
                "y": 64,
                "width": 513,
                "height": 728
                },
                "action": {
                "type": "uri",
                "uri": "https://market.deltafood.me/user"
                }
            },
            {
                "bounds": {
                "x": 1136,
                "y": 844,
                "width": 127,
                "height": 5
                },
                "action": {
                "type": "message",
                "text": "Area 3"
                }
            }
            ]
        }' https://api.line.me/v2/bot/richmenu;
        EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        if (isset($result['richMenuId'])) {
            echo $result['richMenuId'];
        } else {
            echo $result['message'];
        }
    }

    public function flexMessage($data, $userId)
    {
        $accessToken = $this->access_token;
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
        // echo $message;
    }
    ####################################################################
    public function flex_user($m_id, $order_id)
    {
        $userId = $this->Market_model->getUserId($m_id);

        $json = $this->Flex_user_model->user_order($order_id);
        $data = json_decode($json, true);
        $this->flexMessage($data, $userId);
    }
    public function flex_store($userId, $order_id, $store_id)
    {
        $json = $this->Flex_store_model->store_order($order_id, $store_id);
        $data = json_decode($json, true);
        $this->flexMessage($data, $userId);
    }
    public function flex_rider($userId, $order_id)
    {
        $json = $this->Flex_rider_model->rider_order($order_id);
        $data = json_decode($json, true);
        $this->flexMessage($data, $userId);
    }
    public function flex_rider_orderIn($order_id)
    {
        // $where = "r.rider_status!='3' AND r.rider_status!='0'AND r.rider_status!='9'";
        $orderType = $this->db->where('order_id',$order_id)->get('tb_order')->row()->orderType;
        if ($orderType==1) {
            $type_id = 3;
        }elseif ($orderType==2){
            $type_id=5;
        }
        $m_id =  $this->db->select('r.m_id')
            ->join('tb_member as m', 'm.m_id=r.m_id')
            ->where('r.rider_status !=',0)
            ->where('r.r_active !=',0)
            ->where('m.m_active', 1)
            ->where('m.type_id', $type_id)
            ->get('tb_rider as r')->result();
        if ($m_id) {
            foreach ($m_id as $key => $value) {
                $userId = $this->Market_model->getUserId($value->m_id);

                if ($orderType==1) {
                    $json = $this->Flex_rider_model->rider_orderIn($value->m_id, $order_id);
                }elseif ($orderType==2){
                    $json = $this->Flex_rider_model->rider_orderInOnMarket($value->m_id, $order_id);
                }
                $data = json_decode($json, true);
                $this->flexMessage($data, $userId);
            }
        }else{
            $messaging = "มีคำสั่งซื้อจากลูกค้า แต่ไม่มีพนักงานส่งอาหาร";
            $this->sendMessageToAdmin($messaging);
        }


    }
    public function sendFlex_toRider($userId, $order_id)
    {

        $json = $this->Flex_rider_model->send_flex_toRider($order_id);
        $data = json_decode($json, true);
        $this->flexMessage($data, $userId);
    }
    public function resiveOrder($order_id, $userId)
    {
        $messaging = '';
        $m_idRider = $this->Market_model->getM_id_Rider($userId);
        $message = $this->Rider_model->resive_order($order_id, $m_idRider);
        if ($message['flag'] == 1) {
            $this->sendMessage($userId, $message['ms']);
            $this->sendFlex_toRider($userId, $order_id);
            return true;
            //serFirebase
        } elseif ($message['flag'] == 0) {
            $this->sendMessage($userId, $message['ms']);
            return false;
        } else {
            $messaging = 'มีข้อผิดพลาด';
            $this->sendMessage($userId, $messaging);
            return false;
        }

        // $this->send_line_notify($messaging);
    }
    public function flexAdmin_checkSlip($order_id)
    {
        $json = $this->Flex_admin_model->adminCheck_slip($order_id);
        $data = json_decode($json, true);
        foreach ($this->userId_Admin as $key => $value) {
            # code...
            $this->flexMessage($data, $value->userId);
        }
    }
    public function sendMessageToUser($m_id, $messaging)
    {
        $userId = $this->Market_model->getUserId($m_id);
        $this->sendMessage($userId, $messaging);
    }
    public function sendMessageToAdmin($messaging)
    {
        foreach ($this->userId_Admin as $key => $value) {

            $this->sendMessage($value->userId, $messaging);
        }
    }
    public function sendMessageToStore($store_id, $messaging)
    {
        $where = "store_id='$store_id' AND m_active = '1' AND m_status ='1'";
        $userId_emp = $this->db->select('userId')
            ->where($where)
            ->get('tb_member')->result();
        foreach ($userId_emp as $key => $value) {

            $this->sendMessage($value->userId, $messaging);
        }
    }
    public function sendMessageToRider($rider_id, $messaging)
    {
        $userId_rider = $this->db->join('tb_rider as r', 'r.m_id=m.m_id')
            ->where('r.rider_id', $rider_id)
            ->get('tb_member as m')->row()->userId;
        $this->sendMessage($userId_rider, $messaging);
    }
}
