<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Market_model extends CI_Model
{
    #ทำให้คนขับ สถานะเป็นว่าง
    public function changeRiderToReady($rider_id)
    {
        $rider_status = $this->db->select('rider_status')
            ->where('rider_id', $rider_id)
            ->get('tb_rider');
        if ($rider_status->num_rows() > 0) {
            $rider_status = $rider_status->row()->rider_status;

            #ทำให้คนขับ สถานะเป็นว่าง แต่ถ้าคนขับปิดระบบ ไม่ต้องเปลี่ยนสถานะ
            if ($rider_status != 0) {
                // $where = "'status_id'!='4' AND 'status_id'!='5' AND 'status_id'!='7'AND 'status_id'!='8'AND 'status_id'!='9'AND 'status_id'!='1'";
                $status =   $this->db->select('status_id')
                    ->where('rider_id', $rider_id)
                    ->where('status_id', 1)
                    ->or_where('status_id', 2)
                    ->or_where('status_id', 3)
                    ->get('tb_order')->result();
                if (empty($status)) {
                    $this->db->where('rider_id', $rider_id)
                        ->set('rider_status', 1)
                        ->update('tb_rider');
                }
            }
        }
    }
    
    #ทำให้คนขับ สถานะเป็นไม่ว่าง
    public function changeRiderToNotReady($rider_id)
    {
        $rider_status = $this->db->select('rider_status')
            ->where('rider_id', $rider_id)
            ->get('tb_rider');
        if ($rider_status->num_rows() > 0) {
            $rider_status = $rider_status->row()->rider_status;

            if ($rider_status != 0) {
                $this->db->where('rider_id', $rider_id)
                    ->set('rider_status', 3)
                    ->update('tb_rider');
            }
        }
    }
    public function getMid_formOrder($order_id)
    {
        $m_id = $this->db->select('m_id')
            ->where('order_id', $order_id)
            ->get('tb_order');
        $m_id = $m_id->row()->m_id;
        return $m_id;
    }

    #เปลี่ยน status_id ใน order ให้เป็น 2 #กำลังทำ เพื่อแสดงให้ลูกค้าเห็น
    // public function changeStatusOrder($order_id){
    //     $status = $this->db->select('status_id')
    //     ->where('order_id',$order_id)
    //     ->get('tb_order');
    //     if($status->num_rows() > 0){
    //         $status = $status->row()->status_id;
    //         if($status==1){
    //             $this->db->set('status_id',2)
    //             ->where('order_id',$order_id)
    //             ->update('tb_order');
    //         }
    //     }
    // }

    public function random()
    {
        $rand = '';
        $key = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        for ($i = 0; $i < 1; $i++) {
            $rand .= $key[rand() % strlen($key)];
        }
        $rand .= "-";
        $key = "0123456789";
        for ($i = 0; $i < 3; $i++) {
            $rand .= $key[rand() % strlen($key)];
        }
        return $rand;
    }

    //เปลี่ยน m_id ให้เป็น userId
    public function getUserId($m_id)
    {
        $userId = $this->db->select('userId')
            ->where('m_id', $m_id)
            ->get('tb_member');
        if ($userId->num_rows() > 0) {
            $userId = $userId->row()->userId;
        }
        return $userId;
    }
    //เปลี่ยน userId ให้เป็น m_id
    public function getM_id($userId)
    {
        $m_id = $this->db->select('m_id')
            ->where('userId', $userId)
            ->get('tb_member');
        if ($m_id->num_rows() > 0) {
            $m_id = $m_id->row()->m_id;
        }
        return $m_id;
    }
    public function getM_id_Rider($userId)
    {
        $m_id = $this->db->select('m_id')
            // ->where('type_id', 3)
            ->where('userId', $userId)
            ->get('tb_member');
        if ($m_id->num_rows() > 0) {
            $m_id = $m_id->row()->m_id;
        }
        return $m_id;
    }


    #####################################################################################################
    public function randomRef()
    {
        $rand = '';
        // $key = "abcdefghijklmnopqrstuvwxyz";
        $key = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        for ($i = 0; $i < 4; $i++) {
            $rand .= $key[rand() % strlen($key)];
        }
        return $rand;
    }
    public function randomKey()
    {
        $rand = '';
        $key = "123456789";
        for ($i = 0; $i < 1; $i++) {
            $rand .= $key[rand() % strlen($key)];
        }
        $key = "0123456789";
        for ($i = 0; $i < 3; $i++) {
            $rand .= $key[rand() % strlen($key)];
        }
        return $rand;
    }
    public function get_client_ip()
    {
        // $ipaddress = '';
        // if (getenv('HTTP_CLIENT_IP'))
        //     $ipaddress = getenv('HTTP_CLIENT_IP');
        // else if (getenv('HTTP_X_FORWARDED_FOR'))
        //     $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        // else if (getenv('HTTP_X_FORWARDED'))
        //     $ipaddress = getenv('HTTP_X_FORWARDED');
        // else if (getenv('HTTP_FORWARDED_FOR'))
        //     $ipaddress = getenv('HTTP_FORWARDED_FOR');
        // else if (getenv('HTTP_FORWARDED'))
        //     $ipaddress = getenv('HTTP_FORWARDED');
        // else if (getenv('REMOTE_ADDR'))
        //     $ipaddress = getenv('REMOTE_ADDR');
        // else
        //     $ipaddress = 'UNKNOWN';

        $ipaddress = getenv('REMOTE_ADDR');

        return $ipaddress;
    }
    public function sendOTP($data)
    {
        if (!empty($data)) {
            $mobile = $data->mobile;
            $OTP_key = $data->OTP_key;
            $OTP_ref = $data->OTP_ref;
            $date =  strtotime(date('Y/m/d H:i:s'));
            $date =  date('Y/m/d H:i:s', $date + 60); //เพิ่มเวลา 1 นาที
            $day = substr($date, 0, 10);
            $time = substr($date, 11, 5);
            $message = "รหัส OTP ของคุณคือ : " . $OTP_key . " (Ref:" . $OTP_ref . ") ใช้งานได้ถึง " . $day . " เวลา " . $time;
            sendOTP_sms($mobile, $message);
            // echo json_encode(["mobile" => $mobile, "message" => $message]);
        }
    }
    public function receivePhone($data)
    {
        $data = json_decode(file_get_contents('php://input'));
        $ip = $this->get_client_ip();
        $mobile = $data->mobile;
        $checkMem = $this->db->where('mobile', $mobile)->get('tb_member'); //เช็คเบอร์ในฐานข้อมูล
        if ($mobile != null) {
            if ($checkMem->num_rows() > 0) {
                $noti = ['flag' => 0, 'ms' => "เบอร์มือถือนี้ถูกใช้งานกับบัญชีอื่นแล้ว"];
            } else {
                $OTP_ref = $this->randomRef();
                $OTP_key = $this->randomKey();

                $checkIp = $this->db->where('ip', $ip)
                    ->or_where('mobile', $mobile)
                    ->get('tb_otp');
                $data = (object)[
                    "OTP_key" => $OTP_key,
                    "OTP_ref" => $OTP_ref,
                    "mobile" => $mobile,
                    "ip" => $ip
                ];
                if ($checkIp->num_rows() < 3) {
                    $limit = false;
                } else {
                    $date = $checkIp->result();
                    $p = strtotime(date('Y-m-d H:i:s'));
                    $d = strtotime($date[count($date) - 1]->date);
                    $time = $p - $d;
                    if ($time > 180) {
                        $this->db->where('ip', $ip)
                            ->or_where('mobile', $mobile)
                            ->delete('tb_otp'); //ลบข้อมูลที่เป็น ip นี้
                        $limit = false;
                    } else {
                        $limit = true;
                        $noti = ['flag' => 2, 'ms' => "เรียกใช้งานเกินกำหนด โปรดทำรายการใหม่หลังจาก 3 นาที"];
                    }
                }
                if ($limit == false) {
                    $insert = $this->db->set('date', date('Y-m-d H:i:s'))->insert('tb_otp', $data);
                    if ($insert != 0) {
                        $this->sendOTP($data);
                        unset($data->OTP_key);
                        unset($data->ip);
                        unset($data->mobile);
                        $noti = ['flag' => 1, 'ms' => "โปรดกรอกรหัส OTP ที่ส่งไปยังเบอร์มือถือของคุณ", "data" => $data];
                    } else {
                        $noti = ['flag' => 0, 'ms' => "เกิดข้อผิดพลาด ไม่ทราบสาเหตุ"];
                    }
                }
            }
        }
        return $noti;
    }
    #####################################################################################################
    public function receiveOTP($data)
    {
        $data = json_decode(file_get_contents('php://input'));
        $OTP_key = $data->OTP_key;
        $OTP_ref = $data->OTP_ref;
        $mobile = $data->mobile;
        $ip = $this->get_client_ip();
        $m_id = $data->m_id;

        $check = $this->db->where('OTP_key', $OTP_key)
            ->where('OTP_ref', $OTP_ref)
            ->where('mobile', $mobile)
            ->where('ip', $ip)
            ->get('tb_otp');
        if ($check->num_rows() > 0) {
            $p = strtotime(date('Y-m-d H:i:s'));
            $d = strtotime($check->row()->date);
            $time = $p - $d;
            if ($time > 60) {
                $noti = ['flag' => 2, 'ms' => "รหัส OTP หมดเวลา"];
                $delete = $this->db->where('ip', $ip)
                    ->or_where('mobile', $mobile)
                    ->delete('tb_otp');
            } else {
                $this->db->set('mobile', $mobile)
                    ->where('m_id', $m_id)
                    ->update('tb_member');
                $delete = $this->db->where('ip', $ip)
                    ->or_where('mobile', $mobile)
                    ->delete('tb_otp');
                $noti = ['flag' => 3, 'ms' => "ยืนยันข้อมูลสำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "รหัส OTP ไม่ถูกต้อง"];
        }
        return $noti;
    }

    public function get_id($token = "")
    {
        if ($token != "") {
            $m_id = $this->db->select('m_id')
                ->where('token', $token)
                ->where('m_active', 1)
                ->get('tb_member');
            if ($m_id->num_rows() > 0) {
                $m_id = $m_id->row()->m_id;
            } else {
                $m_id = 0;
            }
        } else {
            $m_id = 0;
        }
        return $m_id;
    }
    public function get_store_id($token = "")
    {
        if ($token != "") {
            $store_id = $this->db->select('store_id')
                ->where('token', $token)
                ->where('m_active', 1)
                ->get('tb_member');
            if ($store_id->num_rows() > 0) {
                $store_id = $store_id->row()->store_id;
                if ($store_id == null || $store_id == "") {
                    $store_id = 0;
                }
            } else {
                $store_id = 0;
            }
        } else {
            $store_id = 0;
        }
        return $store_id;
    }
    public function get_rider_id($token = "")
    {
        if ($token != "") {
            $rider_id = $this->db->select('r.rider_id')
                ->join('tb_rider as r', 'r.m_id=m.m_id')
                ->where('m.m_active', 1)
                ->where('r.r_active', 1)
                ->where('m.token', $token)
                ->get('tb_member as m');
            if ($rider_id->num_rows() > 0) {
                $rider_id = $rider_id->row()->rider_id;
            } else {
                $rider_id = 0;
            }
        } else {
            $rider_id = 0;
        }
        return $rider_id;
    }
    public function get_admin($token = "")
    {
        if ($token != "") {
            $admin = $this->db->select('m_id')
                ->where('token', $token)
                ->where('type_id', 1)
                ->where('m_active', 1)
                ->get('tb_member');
            if ($admin->num_rows() > 0) {
                $admin = $admin->row()->m_id;
                if ($admin == null || $admin == "") {
                    $admin = 0;
                }
            } else {
                $admin = 0;
            }
        } else {
            $admin = 0;
        }
        return $admin;
    }
}
