<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Webhook extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('line/Line_model');
        $this->load->model('Rider_model');
        $this->load->model('Market_model');
        $this->load->model('User_model');
        $this->load->model('line/Flex_rider_model');
        $this->load->model('line/Flex_admin_model');
        $this->load->model('line/Flex_user_model');
        $this->load->model('line/Firebase_line');
    }
    public function index()
    {
        $flexdata = file_get_contents('php://input');
        $datas = json_decode($flexdata);
        //        $deCode = (array) json_decode(json_decode($datas, false));
        //        file_put_contents('log.json', $datas, FILE_APPEND);
        $flexType = $datas->events[0]->type;
        if ($flexType == "postback") {
            $data = $datas->events[0]->postback->data;
            $sub = strpos($data, ',');
            $order_id = substr($data, 0, $sub);
            $type = substr($data, $sub + 1);
            $userId = $datas->events[0]->source->userId;

            $status_idOr = $this->db->select('status_id')
                ->where('order_id', $order_id)
                ->get('tb_order');
            $status_idOr = $status_idOr->row()->status_id;

            if ($type == "RiderConfirm") {
                //ส่งไลน์flex ให้คนขับ
              $receive=  $this->Line_model->resiveOrder($order_id, $userId);
              if($receive==true){
                  $this->Firebase_line->firebaseRider_receive($order_id);
              }
            } elseif ($type == "SlipTrue") {
                //admin confirm slip True
                $this->adminSlipTrue($status_idOr, $order_id);
            } else if ($type == "SlipFalse") {
                //admin confirm slip False
                $this->adminSlipFalse($status_idOr, $order_id);
            } elseif ($type == "SlipTryAgain") {
                //admin tryAgain slip
                $this->adminSlipTryAgain($status_idOr, $order_id);
            } else {

                // $this->send_line_notify($type);
                $this->send_line_notify($flexdata);
            }
        } elseif ($flexType == "message") {
            // $this->send_line_notify($datas->events[0]->message->text);

            $userId = $datas->events[0]->source->userId;
            $message = "ตอนนี้ระบบแชทยังไม่สามารถใช้งานได้ โปรดติดต่อผู้ดูแลที่หมายเลข ";
            $m_id= $this->Market_model->getM_id($userId);
            $this->Line_model->sendMessageToUser($m_id,$message);
        }
        //  else {

        //     // $this->send_line_notify($type);
        //     // $this->send_line_notify($flexdata);
        // }
    }
    #ยืนยันคำสั่งซื้อ
    public function adminSlipTrue($status_idOr, $order_id)
    {
        if ($status_idOr == 8 || $status_idOr == 7) {
            //admin confirm slip True
            //ยืนยันการชำระ set order_pay = จ่ายแล้ว
            $this->db->set('order_pay', 1)
                ->where('order_id', $order_id)
                ->update('tb_order');

            $m_id = $this->Market_model->getMid_formOrder($order_id);
            $status_id = 9;
            $this->User_model->flexOrder($m_id, $order_id, $status_id);
            $messaging = "ยืนยันสลิปเรียบร้อย";
            $this->Line_model->sendMessageToAdmin($messaging);
            $this->Firebase_line->adminConfirm_order($order_id,true);
        } elseif ($status_idOr == 5) {
            $messaging = "คำสั่งซื้อนี้ถูกยกเลิกแล้ว";
            $this->Line_model->sendMessageToAdmin($messaging);
        } elseif ($status_idOr != 5 && $status_idOr != 8) {
            $messaging = "คำสั่งซื้อนี้ถูกยืนยันแล้ว ไม่สามารถยืนยันได้อีก";
            $this->Line_model->sendMessageToAdmin($messaging);
        }
    }

    #แจ้งแนบสลิปอีกครั้ง
    public function adminSlipTryAgain($status_idOr, $order_id)
    {
        if ($status_idOr == 8) {

            $m_id = $this->Market_model->getMid_formOrder($order_id);
            $this->db->set('status_id', 7)
                ->where('order_id', $order_id)
                ->update('tb_order');
            $messaging = "การชำระเงินไม่ถูกต้อง แนบสลิปอีกครั้ง";
            $this->Line_model->sendMessageToUser($m_id, $messaging);
            $messaging = "แจ้งโอนสลิปอีกครั้งเรียบร้อย";
            $this->Line_model->sendMessageToAdmin($messaging);
            $this->Firebase_line->adminConfirm_order($order_id,false);
        } elseif ($status_idOr == 5) {
            $messaging = "คำสั่งซื้อนี้ถูกยกเลิกแล้ว";
            $this->Line_model->sendMessageToAdmin($messaging);
        } elseif ($status_idOr == 7) {
            $messaging = "กำลังรอการยืนยันการชำระอีกครั้ง";
            $this->Line_model->sendMessageToAdmin($messaging);
        } elseif ($status_idOr != 5 && $status_idOr != 8 && $status_idOr != 7) {
            $messaging = "คำสั่งซื้อนี้ถูกยืนยันแล้ว ไม่สามารถยกเลิกได้";
            $this->Line_model->sendMessageToAdmin($messaging);
        }
    }

    #ยกเลิกคำสั่งซื้อ
    public function adminSlipFalse($status_idOr, $order_id)
    {
        if ($status_idOr == 8 || $status_idOr == 7) {
            $this->db->set('status_id', 5)
                ->where('order_id', $order_id)
                ->update('tb_order');
            $m_id = $this->Market_model->getMid_formOrder($order_id);
            $messaging = "คำสั่งซื้อของคุณถูกยกเลิก เนื่องจากสลิปการโอนของคุณไม่ถูกต้อง โปรดตรวจสอบหรือติดต่อเราหากมีปัญหา";
            $this->Line_model->sendMessageToUser($m_id, $messaging);

            $messaging = "ยกเลิกเรียบร้อย";
            $this->Line_model->sendMessageToAdmin($messaging);
            $this->Firebase_line->adminConfirm_order($order_id,false);
            $this->Firebase_line->removeFirebase($order_id); //remove FireBase
        } elseif ($status_idOr == 5) {
            $messaging = "คำสั่งซื้อนี้ถูกยกเลิกแล้ว";
            $this->Line_model->sendMessageToAdmin($messaging);
        } 
         
        elseif ($status_idOr != 5 && $status_idOr != 8 && $status_idOr != 7) {
            $messaging = "คำสั่งซื้อนี้ถูกยืนยันแล้ว ไม่สามารถยกเลิกได้";
            $this->Line_model->sendMessageToAdmin($messaging);
        }
    }

    public function send_line_notify($message = "", $img = "")
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        date_default_timezone_set("Asia/Bangkok");
        $sToken = "5uuCIIUvPWH94Rj1DA823rfQ8eOVok3wJ8zS9OYH03I";
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
    }
    
}
