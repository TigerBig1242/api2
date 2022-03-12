<?php
defined('BASEPATH') or exit('No direct script access allowed');

class UserController extends CI_Controller
{
    private $dataEmpty = ['flag' => 0, 'ms' => "ไม่มีข้อมูล"];
    private $postData;
    private $m_id;
    public function __construct()
    {
        parent::__construct();
        // $token = $this->token();
        $this->load->model("User_model");
        $this->load->model("Rider_model");
        $this->load->model("Market_model");
        $this->load->model('line/Firebase_line');
        $this->load->model("line/Line_model");
        $this->load->model("line/Flex_user_model");
        $this->load->model("line/Flex_admin_model");
        $this->postData =  json_decode(file_get_contents("php://input"));
        // if ($token['check'] == true) {
        //     $m_id = $this->get_id($token['token']);
        // }
    }
    function get_id($token)
    {
        $m_id =  $this->Market_model->get_id($token);
        if ($m_id == 0) {
            echo json_encode(array('logout' => true));
            exit();
        } else {
            return $m_id;
        }
    }
    
    public function userProfile($m_id = 0)
    {
        $profile = $this->db->select('m_id,m_active,displayName,mobile,pictureUrl')
            ->where('m_id', $m_id)
            ->get('tb_member')->row();
        echo json_encode($profile);
    }

    public function searchFood() {
        if ($_GET) {
            $key = $_GET["search"];
            $key = urldecode($key);
            if ($key != "") {
                $food = $this->User_model->search_food($key);
                if (!empty($food)) {

                    echo json_encode(array("flag" => 1, "data" => $food));
                } else {
                    echo json_encode(array("flag" => 0, "ms" => "ไม่มีข้อมูล"));
                }
            } else {
                echo json_encode(array("flag" => 0, "ms" => "ไม่มีข้อมูล"));
            }
        } else {
            echo json_encode(array("flag" => 0, "ms" => "ไม่มีข้อมูล"));
        }
        //เรียกดูประเภทสินค้า
    }
    public function userType_store()
    {
        $typefood_name = $this->User_model->user_type_store();
        echo json_encode($typefood_name);
    }
    public function userGet_storeByType($typeStore_id = 0)
    {
        $store = $this->User_model->user_get_storeByType($typeStore_id);
        echo json_encode($store);
    }

    //ดูร้านทั้งหมด/ตามประเภท
    public function userStore_food($typefood_id = 0) //GET
    {
        $store = $this->User_model->user_store_food($typefood_id);
        echo json_encode($store);
    }
    public function userGet_store()
    {
        $store = $this->User_model->user_get_store();
        echo json_encode($store);
    }
    
    
    public function userGet_foodInStore($store_id = 0)
    {
        $food = $this->User_model->user_get_foodInStore($store_id);
        echo json_encode($food);
    }

    //เรียกดูอาหารในร้านที่เลือก
    public function userSelect_food($store_id = 0) //GET
    {
        $food = $this->User_model->user_select_food($store_id);
        echo json_encode($food);
    }
    public function userGet_food($food_id = 0)
    {
        $food = $this->User_model->user_get_food($food_id);
        echo json_encode($food);
    }

    //นำคำสั่งซื้อไปเก็บในorder and orderDetail
    public function userOrder_food() //json
    {
        $data = $this->postData;
//        pre($data);
        if (!empty($data)) {
            // if ($data->orderType==1) {
                $order = $this->User_model->user_order_food($data);
                # code...
            // }else if($data->orderType==2){
            //     $order = $this->User_model->user_order_foodOnMarket($data);
            // }
            echo json_encode($order);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    public function userGet_accountNum()
    {
        $account = $this->User_model->user_get_accountNum();
        echo json_encode($account);
    }
    public function userCancel_order()
    {
        $data = $this->postData;

        if (!empty($data)) {
            $order_id = $data->order_id;
            $cancel = $this->User_model->user_cancel_order($order_id);
            echo json_encode($cancel);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    //ดูรายการสั่ง
    public function userGet_list($m_id = 0)
    {
        $order = $this->User_model->user_get_list($m_id);
        echo json_encode($order);
    }

    //ดูประวัติการสั่ง
    public function userGet_history($m_id = 0, $page = 0)
    {
        $order = $this->User_model->user_get_history($m_id, $page);
        echo json_encode($order);
    }

    //ดูรายละเอียดคำสั่ง
    public function userOrder_detail($order_id = 0)
    {
        $order = $this->User_model->user_order_detail($order_id);
        echo json_encode($order);
    }

    public function getPhone($m_id = 0)
    {
        $phone = $this->db->select('mobile,displayName')
            ->where('m_id', $m_id)
            ->get('tb_member')->row();
        echo json_encode($phone);
    }

    public function getMapMarket()
    {
        $map =  $this->db->select('latitude,longitude')
            ->get('tb_market')->result();
        echo json_encode($map);
    }
    public function upload()
    {
        if (0 < $_FILES['image']['error']) {
            echo json_encode(array('flag' => 0));
        } else {
            $temp = explode(".", $_FILES["image"]["name"]);
            $name = time() . "_" . uniqid() . "." . end($temp);
            copy($_FILES['image']['tmp_name'], 'image/slip/' . $name);
            echo json_encode(array('flag' => 1, 'url' => 'image/slip/' . $name, 'name' => $_FILES["image"]["name"]));
        }
    }
    public function slipTryAgain()
    {
        $data = $this->postData;
        $slip = $this->User_model->slipTryAgain($data);
        echo json_encode($slip);
    }
    public function getDetailOrder($order_id = 0)
    {
        $order = $this->User_model->getDetailOrder($order_id);
        echo json_encode($order);
    }
    public function login()
    {
        // echo json_encode($_FILES['image']['tmp_name']);

        $data = $this->postData;
        if (isset($data->userId)) {
            $insert = $this->User_model->login($data);
            echo json_encode($insert);
        }
    }
    public function getDistance()
    {
        $distance = $this->User_model->getDistance();
        echo json_encode($distance);
    }

    public function receivePhone()
    {
        $data = json_decode(file_get_contents("php://input"));
        if (isset($data->mobile)) {
            $send = $this->Market_model->receivePhone($data);
            echo json_encode($send);
        }
    }
    public function receiveOTP()
    {
        $data = json_decode(file_get_contents("php://input"));
        $send = $this->Market_model->receiveOTP($data);
        echo json_encode($send);
    }

    function token()
    {
        $actual_link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

        if (strpos($actual_link, "UserController/login") || strpos($actual_link, "UserController/getDistance") || strpos($actual_link, "UserController/getMapMarket") || strpos($actual_link, "UserController/userStore_food") || strpos($actual_link, "UserController/userGet_storeByType") || strpos($actual_link, "UserController/userType_store") || strpos($actual_link, "UserController/upload")) {
            return ['check' => false];
        } else {
            $headers = apache_request_headers();
            if (isset($headers['authorization'])) {
                $authorization = str_replace("Bearer ", "", $headers['authorization']);
                $token = $authorization;
                $user = $this->db->get_where("tb_member", array('token' => $token));
                if ($user->num_rows() > 0) {
                    $this->session->set_userdata("user", $user->row());
                    $this->db->update("tb_member", array('last_active' => date("Y-m-d H:i:s")), array('m_id' => $user->row()->m_id));
                    return  ['check' => true, 'token' => $token];
                } else {
                    echo json_encode(array('logout' => true));
                    // echo "sa";

                    exit();
                }
            } else {

                echo json_encode(array('logout' => true));
                // echo "a";
                exit();
            }
        }
    }
}
