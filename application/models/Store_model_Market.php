<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Store_model extends CI_Model
{
    //ร้านดูอาหาร status 9 = remove
    public function store_get_food($store_id = 0)
    {
        $food = $this->db->select('*')
            ->where("store_id", $store_id)
            ->where('f_active', 1)
            ->where('status !=', 9)
            ->order_by('typefood_id', 'ASC')
            ->get("tb_food")->result();
        $food = $this->getIamgeFood($food);
        return $food;
        
    }
    public function store_get_listFood($store_id)
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
                ->order_by('food_price', 'ASC')
                ->get('tb_food')->result();
            foreach ($food as $key => $value2) {
                if ($value2->status == 1) {
                    $value2->f_status = true;
                } elseif ($value2->status == 0) {
                    $value2->f_status = false;
                }
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
        if (!empty($food)) {
            $other = ['อาหารไม่ระบุประเภท' => $food];
            return $food;
        } else {
            return null;
        }
    }
    public function store_get_food_inBin($store_id = 0)
    {
        $food = $this->db->select('*')
            ->where("store_id", $store_id)
            ->where('status', 9)
            ->where('f_active', 1)
            ->order_by('typefood_id', 'ASC')
            ->get("tb_food")->result();
        $food = $this->getIamgeFood($food);
        return $food;
    }
    public function store_get_foodId($store_id, $food_id)
    {
        $food = $this->db->select('f.*')
            ->join('tb_store as s', 's.store_id=f.store_id')
            ->where("s.store_id", $store_id)
            ->where('f_active', 1)
            ->where('status !=', 9)
            ->where('f.food_id', $food_id)
            ->get("tb_food as f")->result();
        foreach ($food as $key => $value) {
            if ($value->typefood_id == 0) {
                $value->typefood_name = "ไม่ระบุ";
            } else {
                $typeFood = $this->db->select('typefood_name')
                    ->where('typefood_id', $value->typefood_id)
                    ->get('tb_typefood');
                if ($typeFood->num_rows() > 0) {
                    $value->typefood_name = $typeFood->row()->typefood_name;
                }
            }
            if ($value->option != null) {
                # code...
                $value->option = json_decode($value->option);
            }
        }
        if (!empty($food)) {
            $food = $this->getIamgeFood($food);
        }
        return $food;
    }
    public function store_remove_typeFood($store_id, $typefood_id)
    {
        $remove = $this->db->set('tf_active', 0)
            ->where('store_id', $store_id)
            ->update('tb_typefood');
        if ($remove != 0) {
            $noti = ['flag' => 1, 'ms' => "ลบประเภทอาหารสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "ลบไม่สำเร็จ ลองใหม่อีกครั้ง"];
        }
        return $noti;
    }
    public function store_get_typeFood($store_id)
    {
        $typeFood = $this->db->where('store_id', $store_id)
            ->where('tf_active', 1)
            ->get('tb_typefood')->result();
        return $typeFood;
    }
    //ร้านเพิ่มอาหาร
    public function store_add_food($data)
    {
        $upload = $this->upload($data);
        if ($upload["flag"] == 0) {
            $noti = $upload;
        } else {
            $img = $upload["image"];
            // $data->image = $img;
            $date = date("Y-m-d H:i:s");
            $addFood = $this->db->set('date_time', $date)
                ->set("image", $img)
                ->insert("tb_food", $data);
            if ($addFood != 0) {
                $noti = ['flag' => 1, 'ms' => "เพิ่มอาหารสำเร็จ"];
            } else {
                $noti = ['flag' => 0, 'ms' => "เพิ่มอาหารไม่สำเร็จ เนื่องจากข้อมูลไม่ถูกต้อง"];
            }
        }
        return $noti;
    }

    public function store_add_typeFood($data)
    {
        $insertTypeFood = $this->db->insert('tb_typefood', $data);
        if ($insertTypeFood != 0) {
            $noti = ['flag' => 1, 'ms' => "เพิ่มประเภทอาหารสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "เพิ่มประเภทอาหารไม่สำเร็จ กรุณาลองใหม่"];
        }
        return $noti;
    }

    public function store_edit_typeFood($typefood_id, $typefood_name)
    {
        $update =  $this->db->set('typefood_name', $typefood_name)
            ->where('typefood_id', $typefood_id)
            ->update('tb_typefood');
        if ($update != 0) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    public function store_profile($store_id)
    {
        $profile = $this->db->where('store_id', $store_id)
            ->where('status_active', 1)
            ->get('tb_store')->row();
        $image = $profile->store_image;
        $profile->store_image = photo() . $image;

        $typeStore = $this->db->select('ts.typeStore_name')
            ->join('tb_typeStore as ts', 'ts.typeStore_id=tj.typeStore_id')
            ->where('tj.store_id', $profile->store_id)
            ->where('tj.tj_active', 1)
            ->where('ts.ts_active', 1)
            ->get('tb_typeJoinStore as tj')->result();
        foreach ($typeStore as $key => $value2) {
            if ($profile->store_detail != null) {
                $profile->store_detail = $profile->store_detail . ',' . $value2->typeStore_name;
            } else {
                $profile->store_detail = $value2->typeStore_name;
            }
        }
        return $profile;
    }
    public function store_get_employee($store_id)
    {
        $employee = $this->db->where('store_id', $store_id)
            ->where('m_active', 1)
            ->where('type_id', 2)
            ->get('tb_member')->result();
        return $employee;
    }
    public function employee_on_off($m_id, $m_status)
    {
        $store_id = $this->db->select('store_id')
            ->where('m_active', 1)
            ->where('m_id', $m_id)
            ->get('tb_member');
        if ($store_id->num_rows() > 0) {
            $store_id = $store_id->row()->store_id;
            $status = $this->db->set('m_status', $m_status)
                ->where('m_id', $m_id)
                ->update('tb_member');
            $emp_status = $this->db->where('store_id', $store_id)
                ->where('m_active', 1)
                ->where('m_status ', 1)
                ->get('tb_member')->num_rows();
            if ($emp_status <= 0) {
                $this->db->set('store_status', 0)
                    ->where('store_id', $store_id)
                    ->update('tb_store');
                $noti = ['flag' => 1, 'ms' => "บันทึกข้อมูลสำเร็จ ระบบของคุณถูกปิดอัตโนมัติ เนื่องจากไม่มีพนักงานให้บริการ"];
            } else {
                $noti = ['flag' => 1, 'ms' => "บันทึกข้อมูลสำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "คุณไม่ได้เป็นผู้ดูแลร้าน หรือ บัญชีของคุณถูกระงับการใช้งาน"];
        }
        return $noti;
    }
    //ร้านแก้ไขอาหาร
    public function store_edit_food($food_id, $data)
    {
        if ($data->img != null) {
            $upload = $this->upload($data);
            if ($upload["flag"] == 0) {
                $noti = $upload;
                echo json_encode($noti);
                exit;
            } else {
                $data->image = $upload["image"];
            }
        }
        unset($data->img);
        $data->option = json_encode($data->option);
        $edit = $this->db->where("food_id", $food_id)
            ->update("tb_food", $data);
        if ($edit != 0) {
            $noti = ['flag' => 1, 'ms' => "บันทึกข้อมูลสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ข้อมูลไม่ถูกต้อง"];
        }
        return $noti;
    }

    //ร้านเปลี่ยนสถานะอาหาร เปิด/ปิด
    public function sotre_status_food($food_id, $status)
    {
        $statusFood = $this->db->select('status')
            ->where('f_active', 1)
            ->where('food_id', $food_id)
            ->get('tb_food');
        if ($statusFood->num_rows() > 0) {
            if ($statusFood->row()->status == 9) {
                $noti = ['flag' => 0, 'ms' => "อาหารถูกลบ"];
            } else {
                $changeStatus = $this->db->set('status', $status)
                    ->where('food_id', $food_id)
                    ->update('tb_food');
                if ($changeStatus != 0) {
                    $noti = ['flag' => 1, 'ms' => "บันทึกข้อมูลสำเร็จ"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "บันทึกข้อมูลไม่สำเร็จ"];
                }
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "อาหารถูกลบ"];
        }
        return $noti;
    }
    //ร้านลบอาหาร
    public function store_remove_food($food_id)
    {
        $remove = $this->db->set('status', 9)
            ->where("food_id", $food_id)
            ->update("tb_food");
        if ($remove != 0) {
            $noti = ['flag' => 1, 'ms' => "ลบอาหารสำเร็จ คุณสามารถกู้คืนรายการอาหารของคุณได้ โดยไปที่ถังขยะ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "ลบอาหารไม่สำเร็จสำเร็จ อาจมีข้อมูลไม่ถูกต้อง หรือคุณสามารถแจ้งผู้ดูแลระบบเพื่อช่วยการลบรายการอาหารได้"];
        }
        return $noti;
    }
    public function store_restore_food($food_id)
    {
        $restore = $this->db->set('status', 0)
            ->where('status!=', 8)
            ->where('food_id', $food_id)
            ->update('tb_food');
        if ($restore != 0) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }

    /* 
    order_status 
    1 = ยืนยันแล้ว/มีคนส่ง
    2 = แม่ครัวเริ่มทำอาหาร / ยกเลิกไม่ได้
    3 = คนขับกำลังนำส่งอาหาร
    4 = ได้รับอาหารแล้ว
    5 = ยกเลิกคำสั่งซื้อ 
    */
    //คำสั่งซื้อของลูกค้า
    public function store_order_in($store_id, $page)
    {
        $where = "o.status_id!='3' AND o.status_id!='4' AND o.status_id!='5'AND o.status_id!='7' AND o.status_id!='8' AND o.status_id!='9'";
        $whereFood_status = "od.food_status!='0'";
        $count = $this->db->select('o.date_time,o.order_num,o.order_id,o.status_id')
            ->join('tb_orderdetail as od', 'od.order_id = o.order_id')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->where($whereFood_status)
            ->where('f.store_id', $store_id)
            ->where($where)
            ->order_by('o.order_id', 'ASC')
            ->group_by('o.order_id')
            ->get('tb_order as o')->num_rows();

        $order = $this->db->select('o.date_time,o.order_num,o.order_id,o.status_id')
            ->limit(7, $page * 7)
            ->join('tb_orderdetail as od', 'od.order_id = o.order_id')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->where($whereFood_status)
            ->where('f.store_id', $store_id)
            ->where($where)
            ->order_by('o.order_id', 'ASC')
            ->group_by('o.order_id')
            ->get('tb_order as o')->result();
        if (!empty($order)) {
            foreach ($order as $key => $value) {
                //getStatusOrSotre
                $statusOr = $this->getStatusOr_store($value->order_id, $store_id);
                if ($statusOr->num_rows() > 0) {
                    $statusOrStore_id = $statusOr->row()->food_status;
                    $statusOrStore_name = $statusOr->row()->statusOrStore_name;
                    $value->statusOrStore_name = $statusOrStore_name;
                    $value->statusOrStore_id = $statusOrStore_id;
                    if ($statusOrStore_id == 3) {
                        $value->finished = 3;
                    } elseif ($statusOrStore_id == 2) {
                        $value->finished = 2;
                    } else {
                        $value->finished = 1;
                    }
                }
                $amount = $this->getAmount($store_id, $value->order_id);
                $value->amount = $amount;

                #รวมราคาอาหาร
                $order_price = $this->getOrder_price_to_orderIn($value->order_id, $store_id);
                if ($order_price->num_rows() > 0) {
                    $value->order_price = $order_price->row()->sum_price;
                }
                #รายละเอียดคำสั่งซื้อ
                $detail = $this->pushFood_to_orderIn($value->order_id, $store_id);
                if (!empty($detail)) {
                    $value->detail = $detail;
                }
            }
        }
        $data = array("order" => $order, "count" => $count);
        return $data;
    }
    //รายละเอียด คำสั่งซื้อของลูกค้า
    public function store_order_inDetail($store_id, $order_id)
    {

        // $where = "o.status_id!='3' AND o.status_id!='4'AND o.status_id!='7' AND o.status_id!='8' AND o.status_id!='9'";
        $order = $this->db->select('o.date_time,o.order_num,o.order_id,o.status_id,o.phone,o.m_id,o.member_name,o.rider_id,o.orderType,
        or.food_price, or.sum_price')
        ->join('tb_orderdetail as or', 'o.order_id = or.order_id', 'left')
            // ->where($where)
            ->where('o.order_id', $order_id)
            ->get('tb_order as o')->row();
        if ($order->orderType == 1) {
            $order->orderType_name = delivery();
        } else if ($order->orderType == 2) {
            $order->orderType_name = onMarket();
            # code...
        }
        $statusOr = $this->getStatusOr_store($order->order_id, $store_id);
        if ($statusOr->num_rows() > 0) {
            $statusOrStore_id = $statusOr->row()->food_status;
            $statusOrStore_name = $statusOr->row()->statusOrStore_name;
            $order->statusOrStore_name = $statusOrStore_name;
            $order->statusOrStore_id = $statusOrStore_id;
        }

        $amount = $this->getAmount($store_id, $order->order_id);
        $order->amount = $amount;

        #รวมราคาอาหาร
        $order_price = $this->getOrder_price_to_orderIn($order_id, $store_id);
        if ($order_price->num_rows() > 0) {
            $order->order_price = $order_price->row()->sum_price;
        }
        #ชื่อคนขับ
        $rider = $this->getRider_name($order->order_id);
        $order->rider_name = $rider;
        #รายละเอียดคำสั่งซื้อ
        $food = $this->pushFood_to_orderIn($order_id, $store_id);
        if (!empty($food)) {
            $order->food = $food;
        }

        return $order;
    }

    //รายละเอียด คำสั่งซื้อของลูกค้า copy
    // public function store_order_inDetail_copy($store_id, $order_id)
    // {

    //     // $where = "o.status_id!='3' AND o.status_id!='4'AND o.status_id!='7' AND o.status_id!='8' AND o.status_id!='9'";
    //     $order = $this->db->select('o.date_time,o.order_num,o.order_id,o.status_id,o.phone,o.m_id,o.member_name,o.rider_id,o.orderType, or.orderDetail_id')
    //         // ->where($where)
            
    //         ->where('o.order_id', $order_id)
    //         ->get('tb_order as o')->row();
    //     if ($order->orderType == 1) {
    //         $order->orderType_name = delivery();
    //     } else if ($order->orderType == 2) {
    //         $order->orderType_name = onMarket();
    //         # code...
    //     }
    //     $statusOr = $this->getStatusOr_store($order->order_id, $store_id);
    //     if ($statusOr->num_rows() > 0) {
    //         $statusOrStore_id = $statusOr->row()->food_status;
    //         $statusOrStore_name = $statusOr->row()->statusOrStore_name;
    //         $order->statusOrStore_name = $statusOrStore_name;
    //         $order->statusOrStore_id = $statusOrStore_id;
    //     }

    //     $amount = $this->getAmount($store_id, $order->order_id);
    //     $order->amount = $amount;

    //     #รวมราคาอาหาร
    //     $order_price = $this->getOrder_price_to_orderIn($order_id, $store_id);
    //     if ($order_price->num_rows() > 0) {
    //         $order->order_price = $order_price->row()->sum_price;
    //     }
    //     #ชื่อคนขับ
    //     $rider = $this->getRider_name($order->order_id);
    //     $order->rider_name = $rider;
    //     #รายละเอียดคำสั่งซื้อ
    //     $food = $this->pushFood_to_orderIn($order_id, $store_id);
    //     if (!empty($food)) {
    //         $order->food = $food;
    //     }

    //     return $order;
    // }

    //Function detail order ใหม่
    public function store_order_inDetail_New($data)
      {   
        $result = (object)[];
        // $result = array();
        $select_order = $this->db->select('*')
            ->where('orderdetail.order_id', $data->order_id)
            ->get('tb_orderdetail as orderdetail');

        // foreach($query as $querys) {
        //     if($query->num_rows() > 0) {
        //         $food_id = $food_id;
        //         $amount = $this->db->set('amount', $amount) 
        //             ->where('food_id', $food_id)
        //             ->update('tb_orderdetail');
        //         $amount = $this->db->where('order_id', $order_id)
        //             ->get('tb_orderdetail')->row();   
        //     }
        // }
            if ($select_order->num_rows() > 0) { 
            $food_id = $data->food_id;
            foreach ($select_order as $amount) {
               if($amount->$data->amount) { 
            $amount = $this->db->set('amount', $data->amount)
                ->where('food_id', $food_id)
                ->update('tb_orderdetail');
            $sum_price = $this->db->set('sum_price', $data->sum_price)
                ->where('food_id', $food_id)
                ->update('tb_orderdetail');
            $amount = $this->db->where('order_id', $data->order_id)
                ->get('tb_orderdetail')->row(); 
               }
               $result->sl_order=$select_order;
            //    $result = $select_order->result();
            }
            // $query->sl_order=$select_order;
        }
        return $result;
    }
        // save edit list menu to temporder
        public function orderTempDetail($tempOrder)
        {
            foreach($tempOrder->food_id as $temp) {
                $temp->order_id=$tempOrder->order_id;
                $temp->food_price=$tempOrder->food_price;
                pre($tempOrder);              
                $temp_order = $this->db->insert('tb_temporder', $temp);
            }
            return $temp_order;
        }


    //ร้านเปลี่ยนสถานะคำสั่งซื้อ เป็นเริ่มทำ เพื่อไม่ให้ลูกค้ากดยกเลิก
    public function store_order_status($store_id, $order_id, $statusOrStore_id)
    {
        $food_id = $this->db->select('food_id')
            ->where('store_id', $store_id)
            ->get('tb_food')->result();
        $status = 0;
        foreach ($food_id as $key => $value) {
            $status =  $this->db->set('food_status', $statusOrStore_id)
                ->where('order_id', $order_id)
                ->where('food_id', $value->food_id)
                ->update('tb_orderdetail');
        }
        $getFood_status = $this->db->select('food_status')
            ->join('tb_food as f', 'f.food_id= od.food_id')
            ->where('f.store_id', $store_id)
            ->where('od.order_id', $order_id)
            ->group_by('od.order_id')
            ->get('tb_orderdetail as od');
        if ($getFood_status->num_rows() > 0) {
            $getFood_status = $getFood_status->row()->food_status;
            if ($getFood_status == 2) {
                //เปลี่ยน status_id ใน order ให้เป็น 2
                $status = $this->db->select('status_id,order_id,m_id,rider_id')
                    ->where('order_id', $order_id)
                    ->get('tb_order');
                if ($status->num_rows() > 0) {
                    $statusOr = $status->row()->status_id;
                    if ($statusOr == 1) {
                        $this->db->set('status_id', 2)
                            ->where('order_id', $order_id)
                            ->update('tb_order');
                    }
                }
                $noti = ['flag' => 1, 'ms' => "บันทึกข้อมูล", 'data' => $status->row()];
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกข้อมูลไม่สำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกข้อมูลไม่สำเร็จ"];
        }
        return $noti;
    }
    //ร้านยกเลิกคำสั่งซื้อ
    public function store_cancel_order($order_id, $store_id)
    {
        $food_id = $this->db->select('od.food_id')
            ->join('tb_food as f', 'f.food_id= od.food_id')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            ->get('tb_orderdetail as od')->result();

        foreach ($food_id as $key => $value) {
            $update =  $this->db->set('food_status', 0)
                ->where('order_id', $order_id)
                ->where('food_id', $value->food_id)
                ->update('tb_orderdetail');
        }
        $checkFood_status = $this->db->join('tb_food as f', 'f.food_id = od.food_id')
            ->where('od.food_status', 0)
            ->where('f.store_id', $store_id)
            ->group_by('f.store_id')
            ->get('tb_orderdetail as od')->num_rows();

        if ($checkFood_status > 0) {
            $cancel =  $this->db->where('food_status !=', 0)
                ->where('order_id', $order_id)
                ->get('tb_orderdetail')->num_rows();
            if ($cancel <= 0) {
                //set status order = cancel
                $this->db->set('status_id', 5)
                    ->set('sendTime_done', date('Y-m-d H:i:s'))
                    ->where('order_id', $order_id)
                    ->update('tb_order');

                //set rider = ว่าง
                $member = $this->db->select('rider_id,m_id,order_num,order_id,status_id')
                    ->where('order_id', $order_id)
                    ->get('tb_order');
                if ($member->num_rows() > 0) {
                    $rider_id = $member->row()->rider_id;
                    $m_id = $member->row()->m_id;
                    $this->Market_model->changeRiderToReady($rider_id);
                    $messaging = "คำสั่งซื้อ " . $member->row()->order_num . " ของคุณถูกยกเลิก ขออภัยในความไม่สะดวก";
                    $this->Line_model->sendMessageToUser($m_id, $messaging);
                }
                $this->Firebase_line->removeFirebase($order_id); //remove FireBase

                $can = true;
            } else {
                $member = $this->db->select('rider_id,m_id,order_num,order_id,status_id')
                    ->where('order_id', $order_id)
                    ->get('tb_order');
                $food = $this->db->select('food_name')
                    ->Where('order_id', $order_id)
                    ->where('food_status', 0)
                    ->get('tb_orderdetail')->result();
                $foodName = '';
                foreach ($food as $key => $value) {
                    $foodName .= $value->food_name . " ";
                }
                if ($member->num_rows() > 0) {
                    $rider_id = $member->row()->rider_id;
                    $m_id = $member->row()->m_id;
                    $messaging = "เมนูอาหาร " . $foodName . "ในคำสั่งซื้อ " . $member->row()->order_num . " ของคุณถูกยกเลิก ";
                    $this->Line_model->sendMessageToUser($m_id, $messaging);
                }
                $can = false;
            }
            $noti = ['flag' => 1, 'ms' => "ยกเลิกรายการสำเร็จ", 'data' => $member->row(), 'cancel' => $can];
        } else {
            $noti = ['flag' => 0, 'ms' => "ยกเลิกรายการไม่สำเร็จ"];
        }
        return $noti;
    }


    //ร้าน เปิด/ปิด ระบบ
    public function store_change_status($store_id, $store_status)
    {
        $emp_status = $this->db->select('m_status')
            ->where('store_id', $store_id)
            ->where('type_id', 2)
            ->where('m_status !=', '0')
            ->get('tb_member')->result();
        if ($store_status == 1) {
            if (!empty($emp_status)) {
                $check_active = $this->db->where('status_active', 1)
                    ->where('store_id', $store_id)
                    ->get('tb_store');
                if ($check_active->num_rows() > 0) {
                    $status = $this->db->set('store_status', $store_status)
                        ->where('store_id', $store_id)
                        ->update('tb_store');
                    if ($status != 0) {
                        $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                    } else {
                        $noti = ['flag' => 0, 'ms' => "ไม่สามารถ เปิด/ปิด ระบบได้ โปรดติดต่อผู้ดูแล"];
                    }
                } else {
                    $noti = ['flag' => 0, 'ms' => "ร้านอาหารของคุณถูกระงับการใช้งาน"];
                }
            } else {
                $noti = ['flag' => 0, 'ms' => "พนักงานไม่พร้อมทำงาน"];
            }
        } elseif ($store_status == 0) {
            $status = $this->db->set('store_status', $store_status)
                ->where('store_id', $store_id)
                ->update('tb_store');
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "สถานะไม่ถูกต้อง"];
        }
        return $noti;
    }
    //สถานะร้าน
    public function store_get_status($store_id)
    {
        $status = $this->db->select('store_status')
            ->where('store_id', $store_id)
            ->get('tb_store')->result();
        return $status;
    }

    //ร้านดูประวัติรายการ (ตามวันที่)
    public function store_order_history($store_id, $date)
    {
        $where = "o.status_id!='1'AND o.status_id!='7' AND o.status_id!='8' AND o.status_id!='2'AND o.status_id!='9'";

        $order = $this->db->select('o.order_id, o.order_num,o.date_time,o.status_id,st.status_name,o.member_name,f.store_id')
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->join('tb_orderdetail as od', 'od.order_id=o.order_id')
            ->join('tb_food as f', 'f.food_id= od.food_id')
            ->like('o.date_time', $date)
            ->where($where)
            ->or_where('od.food_status', 0)
            ->where('f.store_id', $store_id)
            ->group_by('o.order_id')
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            # code...
            $amount = $this->getAmount($store_id, $value->order_id);
            $value->amount = $amount;
            $order_price = $this->getOrder_price_to_history($value->order_id, $store_id);
            if ($order_price->num_rows() > 0) {
                $value->order_price = $order_price->row()->sum_price;
            }
            $rider = $this->getRider_name($value->order_id);
            $value->rider_name = $rider;

            $detail = $this->pushFood_to_history($value->order_id, $store_id);
            if (!empty($detail)) {
                $value->detail = $detail;
            }
        }

        return $order;
    }
    //ร้านดูรายละเอียดประวัติรายการ
    public function store_get_detail($store_id, $order_id)
    {
        // $where = " o.status_id!='7' AND o.status_id!='8'AND o.status_id!='9'";
        $order = $this->db->select('o.order_id, o.order_num,o.date_time,o.status_id,st.status_name,o.phone,o.member_name,o.orderType')
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->join('tb_orderdetail as od', 'od.order_id=o.order_id')
            ->join('tb_food as f', 'f.food_id= od.food_id')
            ->where('f.store_id', $store_id)
            ->where('o.order_id', $order_id)
            // ->where($where)
            ->group_by('o.order_id')
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            if ($value->orderType == 1) {
                $value->orderType_name = delivery();
            } elseif ($value->orderType == 2) {
                $value->orderType_name = onMarket();
            }
            $amount = $this->getAmount($store_id, $order_id);
            $value->amount = $amount;


            $order_price = $this->getOrder_price_to_history($order_id, $store_id);
            if ($order_price->num_rows() > 0) {
                $value->order_price = $order_price->row()->sum_price;
            }
            $rider = $this->getRider_name($value->order_id);
            $value->rider_name = $rider;
            $food = $this->pushFood_to_history($order_id, $store_id);
            if (!empty($food)) {
                $value->food = $food;
            }
        }
        $order = $this->updateStatusStore($order, $store_id);

        return $order;
    }

    //ร้านดูผลสรุปรายได้ในแต่ละ วัน/เดือน/ปี ของแต่ละเมนู
    public function store_sales_summary($store_id, $date)
    {
        // $where = ("od.food_status != '0'");
        $sales = $this->db->select('f.food_name,od.amount,od.sum_price,od.food_id')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->join('tb_store as s', 's.store_id = f.store_id')
            ->select_sum('od.amount')
            ->select_sum('od.sum_price')
            ->group_by('od.food_id')
            ->where('f.store_id', $store_id)
            ->where('od.food_status !=', 0)
            ->like('od.orderDetail_date', $date)
            ->get('tb_orderdetail as od')->result();
        return $sales;
    }

    function getIamgeFood($food)
    {
        foreach ($food as $key => $valueFood) {
            $image = $this->db->select('image')
                ->where('food_id', $valueFood->food_id)
                ->get("tb_food");
            if ($image->num_rows() > 0) {
                if ($image->row()->image != null) {
                    $valueFood->imageUrl = photo() . $image->row()->image;
                }
            }
        }
        return $food;
    }
    function getAmount($store_id, $order_id)
    {
        $amount = $this->db->select_sum('od.amount')
            ->join('tb_orderdetail as od', 'od.food_id = f.food_id')
            ->where('f.store_id', $store_id)
            ->where('od.order_id', $order_id)
            ->get('tb_food as f');
        if ($amount->num_rows() > 0) {
            $amount = $amount->row()->amount;
            return $amount;
        }
    }

    function getStatusOr_store($order_id, $store_id)
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
    function getOrder_price_to_orderIn($order_id, $store_id)
    {
        $order_price = $this->db->select_sum('od.sum_price')
            ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            // ->where_not_in('od.food_status', 0)
            ->get('tb_food as f');
        if ($order_price->num_rows() > 0) {
            return $order_price;
        }
    }
    function getOrder_price_to_history($order_id, $store_id)
    {
        $order_price = $this->db->select_sum('od.sum_price')
            ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            ->get('tb_food as f');
        return $order_price;
    }
    function getRider_name($order_id)
    {
        $rider = $this->db->select('m.name')
            ->join('tb_rider as r', 'r.rider_id=o.rider_id')
            ->join('tb_member as m', 'm.m_id=r.m_id')
            ->where('o.order_id', $order_id)
            ->get('tb_order as o');
        if ($rider->num_rows() > 0) {
            $rider = $rider->row()->name;
            return $rider;
        }
    }
    function pushFood_to_orderIn($order_id, $store_id)
    {
        $food = $this->db->select('od.food_name,od.amount,od.sum_price,od.food_detail,f.store_id,f.food_id,od.food_status,od.option')
            ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            // ->where('od.food_status !=', 0)
            ->get('tb_food as f')->result();
        foreach ($food as $key => $value) {
            $value->option = json_decode($value->option);
        }
        return $food;
    }
    function pushFood_to_history($order_id, $store_id)
    {
        $food = $this->db->select('od.food_id,od.food_status,od.amount,od.food_detail,od.sum_price,f.food_name,od.food_detail,od.option')
            ->join('tb_orderdetail as od', 'od.food_id = f.food_id')
            ->where('f.store_id', $store_id)
            ->where('od.order_id', $order_id)
            ->get('tb_food as f')->result();
        foreach ($food as $key => $value) {
            $value->option = json_decode($value->option);
        }
        return $food;
    }

    function upload($data)
    {
        $newName = time() . "_" . uniqid();
        $imgPath = explode(',', $data->img->data)[1];
        $imgDecode = base64_decode($imgPath);
        $directory = 'image/food/' . $newName . '.png';
        if ($data->img->data) {
            $upload = file_put_contents($directory, $imgDecode);
            if ($upload == 0) {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ไฟล์ภาพมีปัญหา"];
            } else {
                $noti = ['flag' => 1, 'image' => $directory];
            }
            return $noti;
        }
    }
    function updateStatusStore($order, $store_id)
    {
        foreach ($order as $key => $value) {
            $store = $this->db->select('od.food_status')
                ->join('tb_food as f', 'f.food_id=od.food_id')
                ->where('od.food_status', 0)
                ->where('f.store_id', $store_id)
                ->where('od.order_id', $value->order_id)
                ->group_by('f.store_id')
                ->get('tb_orderdetail as od')->result();
            if (!empty($store)) {
                $value->status_id = 5;
                $value->status_name = "ยกเลิก";
            }
        }
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
                    $profile = $this->db->select('m.m_id,m.m_active,s.store_id,m.type_id,m.userId')
                        ->join('tb_store as s', 's.store_id=m.store_id')
                        ->where('m.type_id', 2)
                        ->where('m.m_id', $m_id)
                        ->get('tb_member as m');
                    if ($profile->num_rows() > 0) {
                        $profile = $profile->row();
                        $this->Line_model->setRichMenu($profile); //set Rich Menu
                        unset($profile->userId);
                        $noti = ['flag' => 1, 'ms' => "เข้าสู่ระบบสำเร็จ", 'data' => $profile];
                    } else {
                        $profile = $this->db->select('m_id,m_active,type_id,userId')
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
    
    //login staff kitchen// 
    public function userLogin($data) {
        //$data = [];
        //       $data = $this->db->select('emp_id, emp_username, em_password')
//        ->where(['emp_id'])
//        ->get('tb_emp')->row();
        $check = $this->db->where('emp_username', $data->username)
                ->where('em_password', md5($data->password))
                ->get('tb_emp');
        //echo $this->db->last_query();
        if ($check->num_rows() > 0) {            
           $noti = ['flag' => 1, 'data' => $check->row()];
           //$noti = (object)['flag' => 1, 'data' => $check->row()];// platern 1
           unset($noti['data']-> em_password);
           unset($noti['data']-> emp_username);
           //echo json_encode($noti->data -> em_password); // right
           //echo json_encode($noti['em_password']); exit; //wrong
        } else { 
            $noti = ['flag' => 0, 'ms' => "เข้าสู่ระบบไม่สำเร็จ"];
        }
        return $noti;      
    }
    
    //display datastore and staff kitchen
    public function showDataStore($store_id) {
        $query = (object)[];
        $sl_Employee = $this->db->select('*')
        ->join('tb_store as s', 'e.store_id = s.store_id', 'left')
        ->where('e.store_id', $store_id)
        //->where('e.em_password', md5($data->password))
        ->get('tb_emp as e')->result();
        $query->get_Employee=$sl_Employee;
        //unset($query['get_Employee']->em_password);
        foreach($sl_Employee as $staff) {
            if($staff->em_password && $staff->emp_username) {
                unset($staff->em_password);
                unset($staff->emp_username);
            }
        }
        $sl_Store = $this->db->select('*')
        ->where('store_id', $store_id)
        //->where('e.em_password', md5($data->password))
        ->get('tb_store')->row();
        $query->get_Store=$sl_Store;
         return $query;
    }
    
    public function store_get_staff($store_id) {
        // $query = (object)[];
        $employee = $this->db->select('*')
        ->where('store_id', $store_id)
        ->get('tb_emp')->result();
        foreach($employee as $employer){
            if($employer->em_password && $employer->emp_username) {
                unset($employer->em_password);
                unset($employer->emp_username);
            }
        }
        // $query->staff=$employee;
        return $employee;
    }

    public function employee_Switch_On_Off($emp_id, $emp_status) {    
        $emp_id = $this->db->select('emp_id')
            ->where('emp_id', $emp_id)
            ->get('tb_emp');
        if ($emp_id->num_rows() > 0) {
            $emp_id = $emp_id->row()->emp_id;
            $status = $this->db->set('emp_status', $emp_status)
                ->where('emp_id', $emp_id)
                ->update('tb_emp');
            $emp_status = $this->db->where('emp_id', $emp_id)
                ->get('tb_emp')->num_rows();
            if ($emp_status <= 0) {
                $this->db->set('emp_status', 0)
                    ->where('emp_id', $emp_id)
                    ->update('tb_emp');
                $noti = ['flag' => 1, 'ms' => "บันทึกข้อมูลสำเร็จ ระบบของคุณถูกปิดอัตโนมัติ เนื่องจากไม่มีพนักงานให้บริการ"];
            } else {
                $noti = ['flag' => 1, 'ms' => "บันทึกข้อมูลสำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "คุณไม่ได้เป็นผู้ดูแลร้าน หรือ บัญชีของคุณถูกระงับการใช้งาน"];
        }
        return $noti;
    }

}
