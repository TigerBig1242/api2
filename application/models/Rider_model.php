<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rider_model extends CI_Model
{
    public function rider_order_in($rider_id)
    {
        $where = "o.status_id!='9'AND o.status_id!='7' AND o.status_id!='8' AND o.status_id!='4'AND o.status_id!='5'";
        $order = $this->db->select('o.order_id,o.order_num,o.date_time,st.status_name,o.order_price,o.phone,pm.payment,o.mark,o.map,o.address,o.status_id,o.rider_id,o.del_cost')
            ->join('tb_order as o', 'o.status_id = st.status_id')
            ->join('tb_payment as pm', 'pm.payment_id=o.payment_id')
            ->where('o.rider_id', $rider_id)
            ->where($where)
            ->order_by('order_id', 'DESC')
            ->get('tb_orderstatus as st')->result();

        foreach ($order as $key => $value) {

            $amount = $this->getAmount($value->order_id);
            $value->amount = $amount;

            $store = $this->pushStore_to_orderIn($value->order_id);
            if (!empty($store)) {
                $value->store = $store;
            }
        }
        if (!empty($order)) {

            $order = $this->pushDetail_to_orderIn($order);
        }
        return $order;
    }
    public function rider_order_inDetail($rider_id, $order_id)
    {
        $where = "o.status_id!='9'AND o.status_id!='7' AND o.status_id!='8' AND o.status_id!='4'";
        $order = $this->db->select('o.orderType,o.order_id,o.order_num,o.date_time,st.status_name,o.order_price,o.phone,pm.payment,o.mark,o.map,o.address,o.status_id,o.rider_id,o.member_name,o.del_cost')
            ->join('tb_order as o', 'o.status_id = st.status_id')
            ->join('tb_payment as pm', 'pm.payment_id=o.payment_id')
            ->where('o.order_id', $order_id)
            ->where('o.rider_id', $rider_id)
            ->where($where)
            ->get('tb_orderstatus as st')->result();

        foreach ($order as $key => $value) {
            $amount = $this->getAmount($value->order_id);
            $value->amount = $amount;
            $store = $this->pushStore_to_orderIn($value->order_id);
            if (!empty($store)) {
                $value->store = $store;
            }
        }
        $order = $this->pushDetail_to_orderIn($order);
        return $order;
    }

    public function rider_status_order($status_id, $order_id, $rider_id)
    {
        $food_status = $this->db->select('food_status')
            ->where('food_status', 1)
            ->where('order_id', $order_id)
            ->get('tb_orderdetail')->result();

        if (empty($food_status)) {
            $this->db->set('status_id', $status_id)
                ->where('order_id', $order_id)
                ->update('tb_order');
            if ($status_id == 4) {
                $date = date("Y-m-d H:i:s");
                $finish =  $this->db->set('order_pay', 1)
                    ->set('sendTime_done', $date)
                    ->where('order_id', $order_id)
                    ->update('tb_order');
                if ($finish != 0) {
                    $this->Market_model->changeRiderToReady($rider_id);

                    $data = $this->db->select('order_num,sendTime_done,order_id,status_id,m_id')
                        ->where('order_id', $order_id)
                        ->get('tb_order')->row();
                    $data->status = "ส่งสำเร็จ";
                    $this->Firebase_line->firebaseRider_receive($order_id);
                    $this->Firebase_line->removeFirebase($order_id); //remove FireBase
                    $chack = ['flag' => 1, 'ms' => "ส่งอาหารสำเร็จ", 'data' => $data];
                } else {
                    $chack = ['flag' => 0, 'ms' => "ร้านอาหารยังไม่ทำอาหาร"];
                }
            } elseif ($status_id == 3) {
                $data = $this->db->select('order_num,sendTime_done,order_id,status_id,m_id')
                    ->where('order_id', $order_id)
                    ->get('tb_order')->row();
                $this->Market_model->changeRiderToNotReady($rider_id);
                $chack = ['flag' => 1, 'ms' => "รับอาหารแล้ว", 'data' => $data];
            } else {
                $chack = ['flag' => 0, 'ms' => "ร้านอาหารยังไม่ทำอาหาร"];
            }
        } else {
            $chack = ['flag' => 0, 'ms' => "ร้านอาหารยังไม่ทำอาหาร"];
        }
        return $chack;
    }
    public function rider_status_order_store($store_id, $order_id)
    {
        $food = $this->db->select('od.food_id,od.food_status')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->where('f.store_id', $store_id)
            ->where('od.order_id', $order_id)
            ->get('tb_orderdetail as od')->result();
        $status = true;
        foreach ($food as $key => $value) {
            if ($value->food_status == 1) {
                $status = false;
            }
        }
        if ($status == true) {
            foreach ($food as $key => $value) {
                $status = $this->db->set('food_status', 3)
                    ->where('order_id', $order_id)
                    ->where('food_id', $value->food_id)
                    ->update('tb_orderdetail');
            }
            $where = "f.store_id = '$store_id' AND od.food_status !='3' AND od.order_id = '$order_id'";
            $checkStatus = $this->db->select('count(*) as count')
                ->join('tb_food as f', 'f.food_id = od.food_id')
                ->where($where)
                ->get('tb_orderdetail as od')->row()->count;
            if ($checkStatus > 0) {
                $chack = ['flag' => 0, 'ms' => "กรุณาลองใหม่"];
            } else {
                $chack = ['flag' => 1, 'ms' => "รับอาหารสำเร็จ"];
            }
        } else {
            $chack = ['flag' => 0, 'ms' => "ร้านอาหารยังไม่ทำอาหาร"];
        }
        return $chack;
    }
    public function rider_on_off($rider_id, $rider_status)
    {

        $status_rider = $this->db->select('m.m_active')
            ->join('tb_member as m', 'm.m_id=r.m_id')
            ->where('r.rider_id', $rider_id)
            ->get('tb_rider as r');
        if ($status_rider->num_rows() > 0) {
            $m_active = $status_rider->row()->m_active;
            if ($m_active == 1) {
                if ($rider_status == 0) {
                    $changeStatus = $this->db->set('rider_status', $rider_status)
                        ->where('rider_id', $rider_id)
                        ->update('tb_rider');
                } else {
                    // $where = "'status_id'='4' AND 'status_id'!='5' AND 'status_id'!='7'AND 'status_id'!='8'AND 'status_id'!='9'AND 'status_id'!='1'";
                    $status =   $this->db->select('status_id')
                        ->where('rider_id', $rider_id)
                        ->where('status_id', 1)
                        ->or_where('status_id', 2)
                        ->or_where('status_id', 3)
                        ->get('tb_order')->result();
                    if (empty($status)) {
                        $changeStatus = $this->db->where('rider_id', $rider_id)
                            ->set('rider_status',1)
                            ->update('tb_rider');
                    } else {
                        $s = false;
                        foreach ($status as $key => $value) {
                            if ($value->status_id == 3) {
                                $s = true;
                            }
                        }
                        if ($s == true) {
                            # code...
                            $changeStatus = $this->db->where('rider_id', $rider_id)
                                ->set('rider_status', 3)
                                ->update('tb_rider');
                        } else {
                            $changeStatus = $this->db->where('rider_id', $rider_id)
                                ->set('rider_status', 2)
                                ->update('tb_rider');
                        }
                    }
                }
                if ($changeStatus != 0) {
                    $noti = ['flag' => 1, 'ms' => "บันทึกเรียบร้อย"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "ลองอีกครั้ง"];
                }
            } else {
                $noti = ['flag' => 2, 'ms' => "บัญชีของคุณถูกระงับการใช้งาน"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "ไม่พบบัญชีผู้ใช้งาน"];
        }
        return $noti;
    }
    function setStatus($rider_id, $rider_status)
    {
        $this->db->set('rider_status', $rider_status)
            ->where('rider_id', $rider_id)
            ->update('tb_rider');
    }

    public function rider_profile($rider_id)
    {
        $profile = $this->db->select('rs.rider_statusname,m.name,m.mobile,m.age,m_active,m.email,m.pictureUrl,m.gender,m.address')
            ->join('tb_rider as r', 'r.m_id=m.m_id')
            ->join('tb_riderstatus as rs', 'rs.rider_status=r.rider_status')
            ->where('r.rider_id', $rider_id)
            // ->where('m.type_id', 3)
            ->get('tb_member as m')->row();
        if ($profile->m_active == 1) {
            $profile->active_name = "เปิดใช้งาน";
            $profile->active = true;
        } elseif ($profile->m_active == 0) {
            $profile->active_name = "ระงับการใช้งาน";
            $profile->active = false;
        }
        return $profile;
    }
    public function resive_order($order_id, $m_idRider)
    {
        $status_id = $this->db->select('status_id')
            ->where('order_id', $order_id)
            // ->where('status_id !=', null)
            ->get('tb_order');

        $rider_id = $this->db->select('r.rider_id')
            ->join('tb_member as m', 'm.m_id=r.m_id')
            ->where('r.m_id', $m_idRider)
            ->where('m.m_active', 1)
            ->get('tb_rider as r');
        if ($rider_id->num_rows() > 0) {
            $rider_id = $rider_id->row()->rider_id;

            if ($status_id->num_rows() > 0) {
                $status_id = $status_id->row()->status_id;

                if ($status_id == 9) {
                    $this->db->set('status_id', 1)
                        ->set('rider_id', $rider_id)
                        ->where('order_id', $order_id)
                        ->update('tb_order');
                    $this->db->set('rider_status', 2)
                        ->where('rider_id', $rider_id)
                        ->update('tb_rider');
                    $m_id = $this->db->select('m_id')
                        ->where('order_id', $order_id)
                        ->get('tb_order')->row()->m_id;

                    $store = $this->db->select('f.store_id')
                        ->join('tb_food as f', 'f.food_id=od.food_id')
                        ->join('tb_store as s', 's.store_id=f.store_id')
                        ->where('od.order_id', $order_id)
                        ->group_by('s.store_id')
                        ->get('tb_orderdetail as od')->result();
                    foreach ($store as $key => $value) {
                        $where = "m_active = '1' AND m_status ='1' AND store_id = '$value->store_id' AND type_id='2'";
                        $userId = $this->db->select('userId')
                            ->where($where)
                            ->get('tb_member')->result();
                        foreach ($userId as $key => $value2) {
                            # code...
                            $this->Line_model->flex_store($value2->userId, $order_id, $value->store_id);
                        }
                    }
                    $message = ['flag' => 1, 'ms' => "คำสั่งซื้อนี้เป็นของคุณแล้ว กดเพื่อดูรายละเอียด", 'store' => $store, 'm_id' => $m_id];
                } elseif ($status_id == 5) {
                    $message = ['flag' => 0, 'ms' => "order นี้ ถูกยกเลิกแล้ว"];
                } else {
                    $a = $this->db->select('rider_id')
                        ->where('order_id', $order_id)
                        ->get('tb_order');
                    if ($a->num_rows() > 0) {
                        $rider = $a->row()->rider_id;
                    } else {
                        $rider = 0;
                    }
                    if ($rider == $rider_id) {
                        $message = ['flag' => 0, 'ms' => "order นี้ เป็นของคุณ"];
                    } else {
                        $message = ['flag' => 0, 'ms' => "มีคนรับ order นี้แล้ว"];
                    }
                }
            } else {
                $message = ['flag' => 0, 'ms' => "คำสั่งซื้อนี้มีข้อผิดพลาด"];
            }
        } else {
            $message = ['flag' => 0, 'ms' => "คุณถูกระงับการใช้งาน"];
        }
        return $message;
    }

    public function rider_get_history($rider_id, $date)
    {
        $where = "o.status_id!='9'AND o.status_id!='7' AND o.status_id!='8' AND o.status_id!='1'AND o.status_id!='2'AND o.status_id!='3'";
        $order =  $this->db->select('o.order_num,o.date_time,o.order_price,o.order_id,os.status_name,o.status_id,o.del_cost,o.payment_id,pm.payment')
            ->join('tb_orderstatus as os', 'os.status_id=o.status_id')
            ->join('tb_payment as pm', 'pm.payment_id=o.payment_id')
            ->like('o.date_time', $date)
            ->where('o.rider_id', $rider_id)
            ->where($where)
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            $amount = $this->getAmount($value->order_id);
            $value->amount = $amount;
            $store = $this->pushStore_to_orderIn($value->order_id);
            $value->store = $store;
        }
        $order = $this->pushDetail_to_orderIn($order);
        return $order;
    }
    public function rider_get_detail($order_id)
    {
        $where = "o.status_id!='9'AND o.status_id!='7' AND o.status_id!='8' AND o.status_id!='1'AND o.status_id!='2'AND o.status_id!='3'";
        $order =  $this->db->select('o.order_num,o.date_time,o.order_price,o.order_id,os.status_name,o.status_id,pm.payment,o.del_cost,o.orderType')
            ->join('tb_orderstatus as os', 'os.status_id=o.status_id')
            ->join('tb_payment as pm', 'pm.payment_id=o.payment_id')
            ->where('o.order_id', $order_id)
            ->where($where)
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            $amount = $this->getAmount($value->order_id);
            $value->amount = $amount;
            $store = $this->pushStore_to_orderIn($order_id);
            $value->store = $store;
        }
        $order = $this->pushDetail_to_orderIn($order);
        return $order;
    }
    public function rider_get_status($rider_id)
    {
        $status = $this->db->select('r.rider_id,r.rider_status,m.m_active')
            ->join('tb_member as m', 'm.m_id=r.m_id')
            // ->where('m.type_id', 3)
            ->where('r.rider_id', $rider_id)
            ->get('tb_rider as r')->row();
        if ($status->m_active == 1) {
            $status->active = true;
        } else {
            $status->active = false;
        }

        return $status;
    }

    //ดูจำนวนสินค้าในรายการนั้นๆ
    function getAmount($order_id)
    {
        $amount = $this->db->select_sum('amount')
            ->where('order_id', $order_id)
            ->get('tb_orderdetail');
        if ($amount->num_rows() > 0) {
            $amount = $amount->row()->amount;
            return $amount;
        }
    }

    function getStatusOrStore($order_id, $store_id)
    {
        $status =  $this->db->select('od.food_status,soi.statusOrStore_name')
            ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
            ->join('tb_statusOrderInStore as soi', 'soi.food_status = od.food_status')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            ->group_by('od.order_id')
            ->get('tb_food as f');
        return $status;
    }
    function pushStore_to_orderIn($order_id)
    {
        $where2 = "od.food_status !=0";
        $store = $this->db->select('s.store_name,s.store_id')
            ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
            ->join('tb_store as s', 's.store_id = f.store_id')
            ->where('od.order_id', $order_id)
            // ->where($where2)
            ->group_by('s.store_id')
            ->get('tb_food as f')->result();
        return $store;
    }
    function pushDetail_to_orderIn($order)
    {
        for ($i = 0; $i < count($order); $i++) {
            foreach ($order[$i]->store as $key => $value2) {
                //สถานะคำสั่งซื้อในร้าน
                $status =  $this->getStatusOrStore($order[$i]->order_id, $value2->store_id);
                if ($status->num_rows() > 0) {
                    $statusOrStore_id = $status->row()->food_status;
                    $statusOrStore_name = $status->row()->statusOrStore_name;
                    $value2->statusOrStore_name = $statusOrStore_name;
                    $value2->statusOrStore_id = $statusOrStore_id;
                }
                $food = $this->pushFood_to_order($order[$i]->order_id, $value2->store_id);
                if (!empty($food)) {
                    $value2->food = $food;
                }
            }
        }
        return $order;
    }
    function pushFood_to_order($order_id, $store_id)
    {
        $food = $this->db->select('od.food_name,od.amount,od.sum_price')
            ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            // ->where($where2)
            ->get('tb_food as f')->result();
        return $food;
    }
    public function rider_get_order_unreceived($type_id)
    {
        if ($type_id==3) {
            $orderType=1;
        }elseif ($type_id==5){
            $orderType=2;
        }else{
            echo "no date";
            exit();
        }
        $order = $this->db->select('o.order_id,o.status_id,o.date_time,o.image_map,o.address,o.orderType')
            ->where('o.rider_id', null)
            ->where('o.status_id', 9)
            ->where('o.orderType', $orderType)
            ->order_by('o.order_id', 'ASC')
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            $value->image_map = photo() . $value->image_map;
        }

        $data = array("order" => $order);
        return $data;
    }
    public function rider_get_amount_unreceived()
    {
        $amount =  $this->db->select('count(*) as count')
            ->where('rider_id', null)
            ->order_by('order_id', 'ASC')
            ->get('tb_order')->row()->count;
        return $amount;
    }
    public function getDetailOrder($order_id)
    {
        $order = $this->db->select('o.order_id, o.order_num,o.del_cost,os.*,o.sendTime_done,o.orderType')
            ->join('tb_orderstatus as os', 'os.status_id=o.status_id')
            ->where('o.order_id', $order_id)
            ->get('tb_order as o')->row();
        return $order;
    }
    public function rider_summary($rider_id, $day)
    {
        $pp = 0;
        $pd = 0;
        $tt = 0;
        $summary = $this->db->select('*')
            ->select_sum('od.sum_price')
            ->join('tb_order as o', 'o.order_id=od.order_id')
            ->like('od.orderDetail_date', $day, 'both')
            ->where('o.status_id', 4)
            ->where('od.food_status', 3)
            ->where('o.rider_id', $rider_id)
            ->group_by('od.order_id')
            ->get('tb_orderdetail as od')->result();
        foreach ($summary as $key => $value) {
            $price = $value->sum_price + $value->del_cost; //ราคาอาหารที่ไม่ถูกยกเลิก + ค่าจัดส่ง
            if ($value->payment_id == 1) {
                $pp += $price;
            }
            if ($value->payment_id == 2) {
                $pd += $price;
            }
            $tt += $price;
        }
        $data = ["total" => $tt, "pp" => $pp, "pd" => $pd, "count" => count($summary)];
        return $data;
    }
    public function login($data)
    {
        $headers = apache_request_headers();
        if (isset($headers['authorization'])) {
            $token = str_replace("Bearer ", "", $headers['authorization']);
            if ($token != 'null') {
                $in = 0;
                $up = 0;
                // echo $token;
                // exit;
                unset($data->statusMessage);
                $check = $this->db->where('userId', $data->userId)
                    ->get('tb_member');
                if ($check->num_rows() > 0) {
                    $m_id = $check->row()->m_id;
                    $update = $this->db->set('token', $token)
                        ->set('last_active', date('Y-m-d H:i:s'))
                        ->where('m_id', $m_id)
                        ->update('tb_member', $data);
                    $up = $update;
                } else {
                    $insert = $this->db->set('token', $token)
                        ->set('date_time', date('Y-m-d H:i:s'))
                        ->set('last_active', date('Y-m-d H:i:s'))
                        ->insert('tb_member', $data);
                    $m_id = $this->db->insert_id();
                    $in = $insert;
                }
                if ($in != 0 || $up != 0) {
                    $profile = $this->db->select('m.m_active,r.rider_id,m.type_id,m.userId')
                        ->join('tb_rider as r', 'r.m_id=m.m_id')
                        // ->where('m.type_id', 3)
                        // ->or_where('m.type_id', 5)
                        ->where('r.r_active', 1)
                        ->where('m.m_id', $m_id)
                        ->get('tb_member as m');
                    if ($profile->num_rows() > 0) {
                        $profile = $profile->row();
                        $this->Line_model->setRichMenu($profile); //set Rich Menu
                        unset($profile->userId);
                        $noti = ['flag' => 1, 'ms' => "เข้าสู่ระบบสำเร็จ", 'data' => $profile];
                    } else {
                        $profile = $this->db->select('m_active,m_id,type_id,userId')
                            ->where('m_id', $m_id)
                            ->get('tb_member');
                        $profile = $profile->row();
                        $this->Line_model->setRichMenu($profile); //set Rich Menu
                        unset($profile->userId);
                        $noti = ['flag' => 2, 'ms' => "คุณไม่มีสิทธิ์ระบบนี้", 'data' => $profile];
                    }
                } else {
                    $noti = ['flag' => 0, 'ms' => "เข้าสู่ระบบไม่สำเร็จ"];
                }
            } else {
                $noti = ['flag' => 0, 'ms' => "เข้าสู่ระบบไม่สำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "เข้าสู่ระบบไม่สำเร็จ"];
        }
        return $noti;
    }
}

/// ระบบสั่งอาหารเดริเวอรี่ของตลาด