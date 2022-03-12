<?php
defined('BASEPATH') or exit('No direct script access allowed');

class RiderController extends CI_Controller
{
    private $dataEmpty = ['flag' => 0, 'ms' => "ไม่มีข้อมูล"];
    private $rider_id;
    private $postData;

    public function __construct()
    {
        parent::__construct();
        // $token = $this->token();
        $this->load->model('Rider_model');
        $this->load->model('Market_model');
        $this->load->model('line/Line_model');
        $this->load->model('line/Firebase_line');
        $this->load->model('line/Flex_rider_model');
        $this->postData =  json_decode(file_get_contents("php://input"));
        // if ($token['check'] == true) {
            // $rider_id = $this->get_rider_id($token['token']);
        // }
    }
    function get_rider_id($token)
    {
        $rider_id =  $this->Market_model->get_rider_id($token);
        if ($rider_id == 0) {
            // echo json_encode(array('logout' => true));
            echo "คุณไม่ใช่ไรเดอร์";
            exit();
        } else {
            return $rider_id;
        }
    }
    public function riderOrder_in($rider_id = 0)
    {
        if ($rider_id != 'null') {
        $rider_id = $rider_id;
        $order = $this->Rider_model->rider_order_in($rider_id);
        echo json_encode($order);
        }
    }
    public function riderOrder_inDetail($rider_id = 0, $order_id = 0)
    {
        $order = $this->Rider_model->rider_order_inDetail($rider_id, $order_id);
        echo json_encode($order);
    }

    public function riderStatus_order()
    {
        $data = $this->postData;
        if (!empty($data)) {
            $status_id = $data->status_id;
            $order_id = $data->order_id;
            $rider_id = $data->rider_id;
            if ($status_id != 0 && $order_id != 0 && $rider_id != 0) {
                $status = $this->Rider_model->rider_status_order($status_id, $order_id, $rider_id);
                echo json_encode($status);
            } else {
                echo json_encode($this->dataEmpty);
            }
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    public function riderStatus_orderStore()
    {
        $data = $this->postData;
        if (!empty($data)) {
            $store_id = $data->store_id;
            $order_id = $data->order_id;
            if ($store_id != 0 && $order_id != 0) {
                $status = $this->Rider_model->rider_status_order_store($store_id, $order_id);
                echo json_encode($status);
            }
        } else {
            echo json_encode($this->dataEmpty);
        }
    }

    public function riderOn_off()
    {
        $data = $this->postData;
        if (!empty($data)) {
            $rider_status = $data->rider_status;
            $rider_id = $data->rider_id;
            $changeStatus = $this->Rider_model->rider_on_off($rider_id, $rider_status);
            echo json_encode($changeStatus);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    public function riderProfile($rider_id = 0)
    {
        $profile = $this->Rider_model->rider_profile($rider_id);
        echo json_encode($profile);
    }

    public function riderGet_history($rider_id = 0, $date = 0)
    {
        if ($rider_id != 0 && $date != 0) {
            $history = $this->Rider_model->rider_get_history($rider_id, $date);
            echo json_encode($history);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    public function riderGet_detail($order_id)
    {
        $detail = $this->Rider_model->rider_get_detail($order_id);
        echo json_encode($detail);
    }
    public function riderGet_status($rider_id = 0)
    {
        $status = $this->Rider_model->rider_get_status($rider_id);
        echo json_encode($status);
    }

    public function riderGetOrder_unreceived($type_id=0)
    {
        if ($type_id!=0) {
            $order = $this->Rider_model->rider_get_order_unreceived($type_id);
            echo json_encode($order);
        }
    }
    public function getAmountOrder_unreceived()
    {
        $amountOrder = $this->Rider_model->rider_get_amount_unreceived();
        echo json_encode($amountOrder);
    }
    public function receiveOrder()
    {
        $data = $this->postData;
        if ($data) {
            $order_id = $data->order_id;
            $rider_id = $data->rider_id;
            $m_idRider = $this->db->select('m_id')
                ->where('rider_id', $rider_id)
                ->get('tb_rider')->row()->m_id;
            $messaging = '';
            $userId = $this->Market_model->getUserId($m_idRider);
            $message = $this->Rider_model->resive_order($order_id, $m_idRider);
            if ($message['flag'] == 1) {
                $this->Line_model->sendMessage($userId, $message['ms']);
                $this->Line_model->sendFlex_toRider($userId, $order_id);
                $noti = ["flag" => 1, "ms" => $message['ms'], "order_id" => $order_id, 'store' => $message['store'], 'm_id' => $message['m_id']];
                echo json_encode($noti);
            } elseif ($message['flag'] == 0) {
                $noti = ["flag" => 0, "ms" => $message['ms']];
                echo json_encode($noti);
            } else {
                $messaging = 'มีข้อผิดพลาด';
                $noti = ["flag" => 0, "ms" => $messaging];
                echo json_encode($noti);
            }
        } else {
            echo json_encode($this->dataEmpty);
        }

        // $this->send_line_notify($messaging);
    }
    public function getDetailOrder($order_id = 0)
    {
        $order = $this->Rider_model->getDetailOrder($order_id);
        echo json_encode($order);
    }
    public function login()
    {
        $data = $this->postData;
        if ($data) {
            $insert = $this->Rider_model->login($data);
            echo json_encode($insert);
        }
    }

    public function riderSummary($rider_id,$day)
    {
        // echo "a";
        // exit;
        $sum = $this->Rider_model->rider_summary($rider_id, $day);
        echo json_encode($sum);
    }
    private function token()
    {
        $actual_link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        if (strpos($actual_link, "RiderController/login")) {
            return ['check' => false];
        } else {
            $headers = apache_request_headers();
            if (isset($headers['authorization'])) {
                $authorization = str_replace("Bearer ", "", $headers['authorization']);
                $token = $authorization;
                if ($token != 'null') {
                    $user = $this->db->get_where("tb_member", array('token' => $token));
                    // echo json_encode($user->row());
                    // exit;
                    if ($user->num_rows() > 0) {
                        $this->session->set_userdata("user", $user->row());
                        $this->db->update("tb_member", array('last_active' => date("Y-m-d H:i:s")), array('m_id' => $user->row()->m_id));
                        return  ['check' => true, 'token' => $token];
                    } else {
                        echo json_encode(['logout' => true, 'token' => $token]);
                        // echo "s";
                        exit();
                    }
                } else {
                    echo json_encode(['logout' => true]);
                    // echo "a";
                    exit();
                }
            } else {

                echo json_encode(['logout' => true]);
                // echo "ffff";
                exit();
            }
        }
    }
}
