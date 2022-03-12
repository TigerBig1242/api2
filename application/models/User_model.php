<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{
    public function search_food($keywords)
    {
        //ค้นหาตามอาหาร
        $store = $this->db->select('s.store_id,s.store_name,s.store_image,s.store_status')
            ->join('tb_store as s', 's.store_id = f.store_id')
            ->where('f.f_active', 1)
            ->where('f.status !=', 9)
            ->like('f.food_name', $keywords, 'both')
            ->group_by('s.store_id')
            ->order_by('s.store_status', 'DESC')
            ->get('tb_food as f')->result();
        foreach ($store as $key => $value) {
            $food = $this->db->select('f.food_id,f.food_name,f.food_price,f.status,s.store_name,f.image')
                ->join('tb_store as s', 's.store_id = f.store_id')
                ->where('f.store_id', $value->store_id)
                ->where('f.f_active', 1)
                ->where('f.status !=', 9)
                ->like('f.food_name', $keywords, 'both')
                ->get('tb_food as f')->result();
            foreach ($food as $key => $value2) {
                $value2->image = photo() . $value2->image;
            }
            $value->food = $food;
        }

        //ค้นหาตามร้าน
        if (count($store) < 1) {
            $store = $this->db->select('store_id,store_name,store_image,store_status')
                ->like('store_name', $keywords, 'both')
                ->group_by('store_id')
                ->order_by('store_status', 'DESC')
                ->get('tb_store')->result();
            foreach ($store as $key => $value) {
                $food = $this->db->select('f.food_id,f.food_name,f.food_price,f.status,s.store_name,f.image')
                    ->join('tb_store as s', 's.store_id = f.store_id')
                    ->limit(5)
                    ->where('f.store_id', $value->store_id)
                    ->where('f.f_active', 1)
                    ->where('f.status !=', 9)
                    ->order_by('f.status', 'DESC')
                    ->get('tb_food as f')->result();
                foreach ($food as $key => $value2) {
                    $value2->image = photo() . $value2->image;
                }
                $value->food = $food;
            }
        }
        return $store;
    }
    public function user_type_store()
    {
        $typeStore = $this->db->select('ts.*')
            ->join('tb_typeStore as ts', 'ts.typeStore_id=tj.typeStore_id')
            ->where('tj.tj_active', 1)
            ->group_by('tj.typeStore_id')
            ->get('tb_typeJoinStore as tj')->result();
        foreach ($typeStore as $key => $value) {
            $value->tsImage = photo() . $value->tsImage;
        }
        return $typeStore;
    }
    public function user_get_storeByType($typeStore_id)
    {
        $store = $this->db->select('s.store_name,s.store_id,s.store_image,s.store_status,s.time_on,s.store_detail')
            ->join('tb_typeJoinStore as tj', 'tj.typeStore_id=ts.typeStore_id')
            ->join('tb_store as s', 's.store_id=tj.store_id')
            ->where('ts.typeStore_id', $typeStore_id)
            ->where('s.status_active', 1)
            ->where('ts.ts_active', 1)
            ->where('tj.tj_active', 1)
            ->order_by('s.store_status', 'DESC')
            ->get('tb_typeStore as ts')->result();
        if ($typeStore_id == 0) {
            $store = $this->db->select('s.store_name,s.store_id,s.store_image,s.store_status,s.time_on,s.store_detail')
                ->where('s.status_active', 1)
                ->group_by('s.store_id')
                ->order_by('s.store_status', 'DESC')
                ->get('tb_store as s')->result();
        }
        foreach ($store as $key => $value) {
            $value->store_image = photo() . $value->store_image;
        }
        $store = $this->pushTypeStore_name($store);
        return $store;
    }
    public function pushTypeStore_name($store)
    {
        foreach ($store as $key => $value) {
            $typeStore = $this->db->select('ts.typeStore_name')
                ->join('tb_typeStore as ts', 'ts.typeStore_id=tj.typeStore_id')
                ->where('tj.store_id', $value->store_id)
                ->where('tj.tj_active', 1)
                ->where('ts.ts_active', 1)
                ->get('tb_typeJoinStore as tj')->result();
            foreach ($typeStore as $key => $value2) {
                if ($value->store_detail != null) {
                    $value->store_detail = $value->store_detail . ',' . $value2->typeStore_name;
                } else {
                    $value->store_detail = $value2->typeStore_name;
                }
            }
        }
        return $store;
    }
    public function user_store_food($typefood_id)
    {
        if ($typefood_id != 0) {

            $store = $this->db->select('s.store_id,s.store_name,s.store_image,s.time_on,s.store_detail,s.store_status')
                ->group_by('s.store_id')
                ->join('tb_food as f', 'f.store_id = s.store_id')
                ->where('s.status_active', 1)
                ->where('f.typefood_id', $typefood_id)
                ->order_by('s.store_status', 'DESC')
                ->get('tb_store as s')->result();
            foreach ($store as $key => $value) {
                $image = $value->store_image;
                $value->store_image = photo() . $image;
            }
            return $store;
        } else {

            $store =  $this->db->select('s.store_id,s.store_name,s.store_image,s.time_on,,s.store_detail,s.store_status')
                // ->where('s.store_status', 1)
                ->order_by('s.store_status', 'DESC')
                ->where('s.status_active', 1)
                ->get('tb_store as s')->result();
            foreach ($store as $key => $value) {
                $image = $value->store_image;
                $value->store_image = photo() . $image;
            }
            return $store;
        }
    }
    public function user_get_store()
    {
        $store = $this->db->where('status_active', 1)
            ->where('store_status', 1)
            ->get('tb_store')->result();
        return $store;
    }
    public function user_get_foodInStore($store_id)
    {
        $typeFood = $this->db->select('tf.*')
            ->join('tb_food as f', 'f.typefood_id= tf.typefood_id')
            ->where('tf.tf_active', 1)
            ->where('f.f_active', 1)
            ->where('f.store_id', $store_id)
            ->group_by('f.typefood_id')
            ->get('tb_typefood as tf')->result();

        foreach ($typeFood as $key => $value) {
            $whereFood = "f_active = '1'AND status !='9' AND store_id = '$store_id' AND typefood_id = '$value->typefood_id'";
            $food = $this->db->where($whereFood)
                // ->order_by('food_price', 'ASC')
                ->order_by('status', 'DESC')
                ->get('tb_food')->result();
            foreach ($food as $key => $value2) {
                unset($value2->option);
                if ($value2->status == 1) {
                    $value2->f_status = true;
                } elseif ($value2->status == 0) {
                    $value2->f_status = false;
                }
                $value2->image = photo() . $value2->image;
            }
            $value->food = $food;
        }
        $typefood_null = $this->typeFood_null($store_id);
        if ($typefood_null != null) {
            $typeFood[count($typeFood)] = [
                "typefood_name" => "ไม่ระบุประเภท",
                "food" => $typefood_null
            ];
        }
        return $typeFood;
    }
    public function typeFood_null($store_id)
    {
        #อาหารไม่ระบุประเภท
        $whereFood = "f_active = '1'AND status !='9' AND store_id = '$store_id' AND typefood_id = '0'";
        $food = $this->db->where($whereFood)
            ->order_by('food_price', 'ASC')
            ->get('tb_food')->result();
        foreach ($food as $key => $value2) {
            unset($value2->option);
            if ($value2->status == 1) {
                $value2->f_status = true;
            } elseif ($value2->status == 0) {
                $value2->f_status = false;
            }
            $value2->image = photo() . $value2->image;
        }

        if (!empty($food)) {
            $other = ['อาหารไม่ระบุประเภท' => $food];
            return $food;
        } else {
            return null;
        }
    }
    public function user_select_food($store_id)
    {
        $where = "f.store_id='$store_id' AND f.f_active = '1' AND f.status='1'";
        $food_name = $this->db->select('f.food_id,f.food_name,f.image,f.food_price,tf.typefood_name,f.typefood_id,f.store_id')
            ->order_by('f.typefood_id', 'ASC')
            ->order_by('f.food_price', 'ASC')
            ->join('tb_food as f', 'f.typefood_id = tf.typefood_id')
            ->where($where)
            ->get('tb_typefood as tf')->result();
        foreach ($food_name as $key => $value) {
            $image = $value->image;
            $value->image = photo() . $image;
        }
        return $food_name;
    }
    public function user_get_food($food_id)
    {
        $food = $this->db->select('food_id,food_name,food_price,image,option')
            ->where('food_id', $food_id)
            ->where('f_active', 1)
            ->where('status', 1)
            ->get('tb_food')->result();
        foreach ($food as $key => $value) {
            if ($value->option != null) {
                $value->option = json_decode($value->option);
            }
            $value->image = photo() . $value->image;
        }
        return $food;
    }
    public function user_order_food($data)
    {
        $checkUser = $this->db->select('m_active')
            ->where('m_id', $data->m_id)
            ->get('tb_member')->row();

        if ($checkUser->m_active == 1) {

            $date = date("Y-m-d H:i:s");
            $orderdetail = $data->detail;
            $payment_id = $data->payment_id;
            $rann = $this->Market_model->random();
            $order_num =  $this->db->select('order_num')
                ->where('order_num', $rann)
                ->get('tb_order')->result();

            if (!empty($order_num)) {
                $this->user_order_food($data);
            } else {
                $check = $this->checkStatus_activeFood($orderdetail);
                if ($check == false) {
                    $chack = ['flag' => 0, 'ms' => "ไม่สามารถสั่งได้ เนื่องจากอาหารหมดหรือร้านอาหารได้ปิดwxแล้ว"];
                } else {
                    if ($data->slip_image != null) {
                        # code...
                        $data->slip_image = json_encode([
                            [
                                "slip" => $data->slip_image,
                                "date" => $date
                            ]
                        ]);
                    }
                    $insertOrder = $this->db->set('order_num', $rann)
                        ->set('date_time', $date)
                        ->insert('tb_order', $data);
                    if ($insertOrder == 1) {
                        $order_id = $this->db->insert_id();

                        foreach ($orderdetail as $key => $value) {
                            $value->orderDetail_date = $date;
                            $value->order_id = $order_id;
                            $value->option = json_encode($value->option);
                        }
                        $insertDetail = $this->db->insert_batch('tb_orderdetail', $orderdetail);
                        $orderDetail = $this->db->select('f.food_name,f.food_id,f.food_price')
                            ->join('tb_food as f', 'f.food_id= od.food_id')
                            ->where('od.order_id', $order_id)
                            ->get('tb_orderdetail as od')->result();

                        foreach ($orderDetail as $key => $value) {
                            $food_name = $value->food_name;
                            $food_price = $value->food_price;
                            $this->db->set('food_name', $food_name)
                                ->set('food_price', $food_price)
                                ->where('order_id', $order_id)
                                ->where('food_id', $value->food_id)
                                ->update('tb_orderdetail');
                        }
                        #ตรวจสอบข้อผิดพลาด ถ้าผิดพลาด ไม่ต้องส่งไปหาคนขับ
                        if ($insertDetail) {
                            if ($data->orderType == 1) {
                                $upMap =  $this->uploadMapImg_order($order_id, $data->map);
                            }elseif($data->orderType == 2) {
                                $upMap=true;
                            }
                            if ($upMap == false) {
                                $chack = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ข้อมูลแผนที่ไม่ถูกต้อง โปรดลองใหม่"];
                            } else {
                                $m_id = $data->m_id;
                                if ($payment_id == 1) { //ชำระผ่านเลข บช
                                    $status_id = 8;
                                    $this->db->set('status_id', $status_id)
                                        ->where('order_id', $order_id)
                                        ->update('tb_order');
                                    $this->Line_model->flexAdmin_checkSlip($order_id);
                                    // admin confrim 
                                    $chack = ['flag' => 1, 'ms' => "บันทึกสำเร็จ กำลังตรวจสอบการชำละเงิน", 'order_id' => $order_id];
                                } elseif ($payment_id == 2) { //ชำระปลายทาง
                                    $status_id = 9;
                                    $this->flexOrder($m_id, $order_id, $status_id);
                                    $chack = ['flag' => 1, 'ms' => "บันทึกสำเร็จ เรากำลังหาคนส่งอาหารให้คุณ", 'order_id' => $order_id];
                                }
                            }
                        } else {
                            $this->db->where('order_id', $order_id)
                                ->delete('tb_order');
                            $chack = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ข้อมูลไม่ถูกต้อง2"];
                        }
                    } else {
                        $chack = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ข้อมูลไม่ถูกต้อง1"];
                    }
                }
            }
        } else {
            $chack = ['flag' => 0, 'ms' => "คุณถูกระงับการใช้งาน"];
        }
        return $chack;
    }
    public function user_order_foodOnMarket($data)
    {
        $checkUser = $this->db->select('m_active')
            ->where('m_id', $data->m_id)
            ->get('tb_member')->row();
        if ($checkUser->m_active == 1) {
            $date = date("Y-m-d H:i:s");
            $orderdetail = $data->detail;
            $payment_id = $data->payment_id;
            $rann = $this->Market_model->random();
            $order_num =  $this->db->select('order_num')
                ->where('order_num', $rann)
                ->get('tb_order')->result();

            if (!empty($order_num)) {
                $this->user_order_food($data);
            } else {
                $check = $this->checkStatus_activeFood($orderdetail);
                if ($check == false) {
                    $chack = ['flag' => 0, 'ms' => "ไม่สามารถสั่งได้ เนื่องจากอาหารหมดหรือร้านอาหารได้ปิดแล้ว"];
                } else {
                    $data->slip_image = json_encode([
                        [
                            "slip" => $data->slip_image,
                            "date" => $date
                        ]
                    ]);
                    $insertOrder = $this->db->set('order_num', $rann)
                        ->set('date_time', $date)
                        ->insert('tb_order', $data);
                    if ($insertOrder == 1) {
                        $order_id = $this->db->insert_id();

                        foreach ($orderdetail as $key => $value) {
                            $value->orderDetail_date = $date;
                            $value->order_id = $order_id;
                            $value->option = json_encode($value->option);
                        }
                        $insertDetail = $this->db->insert_batch('tb_orderdetail', $orderdetail);
                        $orderDetail = $this->db->select('f.food_name,f.food_id,f.food_price')
                            ->join('tb_food as f', 'f.food_id= od.food_id')
                            ->where('od.order_id', $order_id)
                            ->get('tb_orderdetail as od')->result();

                        foreach ($orderDetail as $key => $value) {
                            $food_name = $value->food_name;
                            $food_price = $value->food_price;
                            $this->db->set('food_name', $food_name)
                                ->set('food_price', $food_price)
                                ->where('order_id', $order_id)
                                ->where('food_id', $value->food_id)
                                ->update('tb_orderdetail');
                        }
                        #ตรวจสอบข้อผิดพลาด ถ้าผิดพลาด ไม่ต้องส่งไปหาคนขับ
                        if ($insertDetail) {
                            $m_id = $data->m_id;
                            if ($payment_id == 1) { //ชำระผ่านเลข บช
                                $status_id = 8;
                                $this->db->set('status_id', $status_id)
                                    ->where('order_id', $order_id)
                                    ->update('tb_order');
                                $this->Line_model->flexAdmin_checkSlip($order_id);
                                // admin confrim 
                                $chack = ['flag' => 1, 'ms' => "บันทึกสำเร็จ กำลังตรวจสอบการชำละเงิน", 'order_id' => $order_id];
                            } elseif ($payment_id == 2) { //ชำระปลายทาง
                                $status_id = 9;
                                $this->flexOrder($m_id, $order_id, $status_id);
                                $chack = ['flag' => 1, 'ms' => "บันทึกสำเร็จ เรากำลังหาคนส่งอาหารให้คุณ", 'order_id' => $order_id];
                            }
                        } else {
                            $this->db->where('order_id', $order_id)
                                ->delete('tb_order');
                            $chack = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ข้อมูลไม่ถูกต้อง"];
                        }
                    } else {
                        $chack = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ข้อมูลไม่ถูกต้อง"];
                    }
                }
            }
        } else {
            $chack = ['flag' => 0, 'ms' => "คุณถูกระงับการใช้งาน"];
        }
        return $chack;
    }

    function checkStatus_activeFood($detail)
    {
        $check = true;
        foreach ($detail as $key => $value) {
            $food_id = $value->food_id;
            $food = $this->db->where('food_id', $food_id)
                ->join('tb_store as s', 's.store_id= f.store_id')
                ->get('tb_food as f')->row();
            $food_status = $food->status;
            $f_active = $food->f_active;
            $status_active = $food->status_active;
            $store_status = $food->store_status;
            if ($food_status != 1 || $f_active != 1 || $status_active != 1 || $store_status != 1) {
                $check = false;
            }
        }
        return $check;
    }
    public function flexOrder($m_id, $order_id, $status_id)
    {
        $this->db->set('status_id', $status_id)
            ->where('order_id', $order_id)
            ->update('tb_order');
        $this->Line_model->flex_user($m_id, $order_id); //ส่งlineflex ให้ลูกค้า
        $this->Line_model->flex_rider_orderIn($order_id); //ส่งlineflex ให้คนขับ
    }
    public function uploadMapImg_order($order_id, $map)
    {
        // $directory = 'image/mapOrder/412-Map-1631088532.png';
        $newName = $order_id . "-" . "Map" . "-" . time();
        $url = 'https://maps.googleapis.com/maps/api/staticmap?center=' . $map . '&zoom=15&size=400x400&&markers=color:red%7Clabel:S%7C' . $map . '&key=AIzaSyADQl79AootGyhKCW8MQ8xxz561gPFu0rA';
        $directory = 'image/mapOrder/' . $newName . '.png';
        $image = file_get_contents($url);
        $upMap =  file_put_contents($directory, $image);
        if ($upMap != 0) {
            $update = $this->db->set('image_map', $directory)
                ->where('order_id', $order_id)
                ->update('tb_order');
            if ($update != 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public function user_get_accountNum()
    {
        return  $this->db->select('account_num,account_name,account_type,promptpay_num,promptpay_name')
            ->where('admin_id', 1)
            ->get('tb_admin')->result();
    }
    public function user_cancel_order($order_id)
    {
        #statuss_id
        $status = $this->db->select('status_id')
            ->where('order_id', $order_id)
            ->get('tb_order');
        $status = $status->row()->status_id;

        #statuss_id Check
        # 1 รอคิวการทำอาหาร
        # 9 กำลังหาคนส่งอาหาร
        # 5 ยกเลิก
        if ($status == 1 || $status == 9 || $status == 8 || $status == 7) {
            $set_status_id = $this->db->set('status_id', 5)
                ->set('sendTime_done', date('Y-m-d H:i:s'))
                ->where('order_id', $order_id)
                ->update('tb_order');
            if ($set_status_id != 0) {
                $this->db->set('food_status', 0)
                    ->where('order_id', $order_id)
                    ->update('tb_orderdetail');

                #set rider = ว่าง
                $rider_id = $this->db->select('rider_id')
                    ->where('order_id', $order_id)
                    ->get('tb_order');
                if ($rider_id->num_rows() > 0) {
                    $rider_id = $rider_id->row()->rider_id;
                    if ($rider_id != 0) {
                        $this->Market_model->changeRiderToReady($rider_id);
                    }
                }
                $data = $this->db->select('order_id,status_id,rider_id')
                    ->where('order_id', $order_id)
                    ->get('tb_order')->row();
                $store = $this->db->select('f.store_id')
                    ->join('tb_food as f', 'f.food_id = od.food_id')
                    ->where('od.order_id', $order_id)
                    ->where('od.food_status', 0)
                    ->group_by('f.store_id')
                    ->get('tb_orderdetail as od')->result();
                $chack = ['flag' => 1, 'ms' => "ยกเลิกรายการสำเร็จ", 'data' => $data, 'store' => $store];
                $this->Firebase_line->removeFirebase($order_id);
            } else {
                $chack = ['flag' => 0, 'ms' => "ไม่สามารถยกเลิกได้ โปรดลองใหม่อีกครั้ง"];
            }
        } else {
            $chack = ['flag' => 0, 'ms' => "ไม่สามารถยกเลิกได้ เนื่องจากรายการของคุณถูกดำเนินการแล้ว"];
        }
        return $chack;
    }
    public function user_get_list($m_id)
    {
        $where = "o.status_id!='4' AND o.status_id!='5'";
        $order = $this->db->select('o.order_pay,o.m_id,o.order_id,o.date_time,o.order_num,o.order_price,o.status_id,st.status_name,o.del_cost')
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->where('o.m_id', $m_id)
            ->where($where)
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->result();
        $order = $this->phusDetail_to_order($order);
        $order = $this->pushStore_to_order($order);
        return $order;
    }
    public function user_get_history($m_id, $page)
    {
        $where = "o.status_id!='1' AND o.status_id!='2'AND o.status_id!='3'AND o.status_id!='7' AND o.status_id!='8'AND o.status_id!='9'";
        $count = $this->db->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->where('o.m_id', $m_id)
            ->where($where)
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->num_rows();

        $order = $this->db->select('o.order_pay,o.order_id,o.date_time,o.order_num,o.status_id,st.status_name,o.order_price,o.m_id,o.del_cost')
            ->limit(7, $page * 7)
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->where('o.m_id', $m_id)
            ->where($where)
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->result();

        $order = $this->phusDetail_to_order($order);
        $order = $this->pushStore_to_order($order);
        $data = array("order" => $order, "count" => $count);
        return $data;
    }
    public function user_order_detail($order_id)
    {
        $order = $this->db->select('o.orderType,o.member_name,o.order_pay,o.m_id,o.order_id,o.date_time,o.order_num,o.order_price,o.status_id,st.status_name,o.payment_id,pm.payment,o.address,o.mark,o.del_cost')
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->join('tb_payment as pm', 'pm.payment_id=o.payment_id')
            ->where('o.order_id', $order_id)
            ->get('tb_order as o')->row();
        if ($order->orderType==1) {
           $order->orderType_name = delivery();
        }elseif($order->orderType==2){

        $order->orderType_name =onMarket();
        }
        $amount = $this->db->select_sum('amount')
            ->where('order_id', $order->order_id)
            // ->where('food_status', 1)
            ->get('tb_orderdetail');
        if ($amount->num_rows() > 0) {
            $order->amount = $amount->row()->amount;
        }
        $order->store = $this->db->select('s.store_name,s.store_id')
            ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
            ->join('tb_store as s', 's.store_id = f.store_id')
            ->where('od.order_id', $order->order_id)
            ->group_by('s.store_id')
            ->get('tb_food as f')->result();
        foreach ($order->store as $key => $value2) {
            //สถานะคำสั่งซื้อในร้าน
            $status =  $this->Rider_model->getStatusOrStore($order->order_id, $value2->store_id);
            if ($status->num_rows() > 0) {
                $statusOrStore_id = $status->row()->food_status;
                $statusOrStore_name = $status->row()->statusOrStore_name;
                $value2->statusOrStore_name = $statusOrStore_name;
                $value2->statusOrStore_id = $statusOrStore_id;
            }
            $food = $this->db->select('od.food_name,od.amount,od.sum_price,od.food_price,od.food_status,od.amount,od.food_detail,od.option')
                ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
                ->where('od.order_id', $order->order_id)
                ->where('f.store_id', $value2->store_id)
                ->get('tb_food as f')->result();
            foreach ($food as $key => $f) {
                if ($f->option) {
                    $f->option = json_decode($f->option);
                }
            }
            $value2->food = $food;
        }
        return $order;
    }
    function pushStore_to_order($order)
    {
        $order = $order;
        foreach ($order as $key => $value) {
            foreach ($value->store as $key => $value2) {
                //สถานะคำสั่งซื้อในร้าน
                $status =  $this->Rider_model->getStatusOrStore($value->order_id, $value2->store_id);
                if ($status->num_rows() > 0) {
                    $statusOrStore_id = $status->row()->food_status;
                    $statusOrStore_name = $status->row()->statusOrStore_name;
                    $value2->statusOrStore_name = $statusOrStore_name;
                    $value2->statusOrStore_id = $statusOrStore_id;
                }
                $value2->food = $this->db->select('od.food_name,od.amount,od.sum_price,od.food_price,od.food_status,od.amount,od.food_detail')
                    ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
                    ->where('od.order_id', $value->order_id)
                    ->where('f.store_id', $value2->store_id)
                    ->get('tb_food as f')->result();
            }
        }

        return $order;
    }
    function phusDetail_to_order($order)
    {
        foreach ($order as $key => $value) {
            $amount = $this->db->select_sum('amount')
                ->where('order_id', $value->order_id)
                ->get('tb_orderdetail');
            if ($amount->num_rows() > 0) {
                $value->amount = $amount->row()->amount;
            }
            $value->store = $this->db->select('s.store_name,s.store_id')
                ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
                ->join('tb_store as s', 's.store_id = f.store_id')
                ->where('od.order_id', $value->order_id)
                ->group_by('s.store_id')
                ->get('tb_food as f')->result();
        }
        return $order;
    }
    public function slipTryAgain($data)
    {
        $upload =  $this->uploadSlip($data);
        if ($upload["flag"] == 0) {
            $noti = $upload;
        } else {
            $slip = $upload["directory"];

            $getSlip = $this->db->where('order_id', $data->order_id)->get('tb_order')->row()->slip_image;
            $getSlip = json_decode($getSlip);

            $newSlip = [
                "slip" => $slip,
                "date" => date('Y-m-d H:i:s')
            ];
            array_push($getSlip, $newSlip);

            $data->slip_image = json_encode($getSlip);
            $update =  $this->db->set('slip_image', $data->slip_image)
                ->where('order_id', $data->order_id)
                ->update('tb_order');
            if ($update != 0) {
                $setStatus =  $this->db->set('status_id', 8)
                    ->where('order_id', $data->order_id)
                    ->update('tb_order');
                if ($setStatus != 0) {
                    $this->Line_model->flexAdmin_checkSlip($data->order_id);
                    $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ โปรดทำรายการอีกครั้ง"];
                }
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ โปรดทำรายการอีกครั้ง"];
            }
        }
        return $noti;
    }
    function uploadSlip($data)
    {
        $newName = time() . "_" . uniqid();
        $imgPath = explode(',', $data->img)[1];
        $imgDecode = base64_decode($imgPath);
        $directory = 'image/slip/' . $newName . '.png';
        if ($data->img) {
            $upload = file_put_contents($directory, $imgDecode);
            if ($upload == 0) {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ไฟล์ภาพมีปัญหา"];
            } else {
                $noti = ['flag' => 1, 'directory' => $directory];
            }
            return $noti;
        }
    }
    public function getDetailOrder($order_id)
    {
        $order = $this->db->select('o.order_id, o.order_num,o.sendTime_done,os.*')
            ->join('tb_orderstatus as os', 'os.status_id=o.status_id')
            ->where('o.order_id', $order_id)
            ->get('tb_order as o')->row();
        return $order;
    }
    public function login($data)
    {
        $headers = apache_request_headers();
        if (isset($headers['authorization'])) {
            $token = str_replace("Bearer ", "", $headers['authorization']);
            if ($token != 'null') {
                $in = 0;
                $up = 0;
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
                    $profile = $this->db->select('m_id,m_active,type_id,userId,mobile')
                        ->where('m_id', $m_id)
                        ->get('tb_member')->row();
                    $this->Line_model->setRichMenu($profile); //set Rich Menu
                    unset($profile->userId);
                    $noti = ['flag' => 1, 'ms' => "เข้าสู่ระบบสำเร็จ", 'data' => $profile];
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
    public function getDistance()
    {
        $distance = $this->db->select('distance,del_cost')
            ->get('tb_market')->row();
        return $distance;
    }
}
