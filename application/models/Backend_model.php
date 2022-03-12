<?php
class Backend_model extends CI_Model
{
    public function load_list_admin()
    {
        $admin = $this->db->select('*')
            ->where('m.type_id', 1)
            ->get('tb_member as m')->result();
        foreach ($admin as $key => $value) {
            if ($value->m_status == 1) {
                $value->checked = true;
            } else {
                $value->checked = false;
            }
        }
        $count = count($admin);
        $data = array("data" => $admin, "count" => $count);
        return $data;
    }
    public function load_list_restaurant()
    {
        $store = $this->db->where('status_active', 1)
            ->get('tb_store')->result();
        foreach ($store as $key => $value) {

            $typeStore = $this->db->select('ts.typeStore_name,ts.typeStore_id,tj.typeJoin_id')
                ->join('tb_typeStore as ts', 'ts.typeStore_id=tj.typeStore_id')
                ->where('tj.tj_active', 1)
                ->where('tj.store_id', $value->store_id)
                ->get('tb_typeJoinStore as tj')->result();
            $value->typeStore = $typeStore;

            if ($value->store_status == 1) {
                $value->checked = true;
            } else {
                $value->checked = false;
            }
            $employee =  $this->db->where('store_id', $value->store_id)
                ->where('type_id', 2)
                ->get('tb_member')->num_rows();
            $value->amount = $employee;
        }
        $count = count($store);
        $data = array("data" => $store, "count" => $count);
        return $data;
    }
    public function set_restaurant_open($store_id, $store_status)
    {
        $emp_status = $this->db->select('m_status')
            ->where('store_id', $store_id)
            ->where('type_id', 2)
            ->where('m_status !=', '0')
            ->get('tb_member')->result();
        if ($store_status == 1) {
            if (!empty($emp_status)) {
                $checkStatus_active = $this->db->where('status_active', 1)
                    ->where('store_id', $store_id)
                    ->get('tb_store')->num_rows();
                if ($checkStatus_active > 0) {
                    $status = $this->db->set('store_status', $store_status)
                        ->where('store_id', $store_id)
                        ->update('tb_store');

                    $messaging = "ร้านอาหารของคุณถูกปิดโดยผู้ดูแล";
                    $this->Line_model->sendMessageToStore($store_id, $messaging);
                    $this->Firebase_line->setStore_open($store_id, $store_status);
                    $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "ร้านอาหารถูกระงับการใช้งาน"];
                }
            } else {
                $noti = ['flag' => 0, 'ms' => "พนักงานไม่พร้อมทำงาน"];
            }
        } elseif ($store_status == 0) {
            $status = $this->db->set('store_status', $store_status)
                ->where('store_id', $store_id)
                ->update('tb_store');
            $messaging = "ร้านอาหารของคุณถูกเปิดโดยผู้ดูแล";
            $this->Line_model->sendMessageToStore($store_id, $messaging);
            $this->Firebase_line->setStore_open($store_id, $store_status);
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "สถานะไม่ถูกต้อง"];
        }
        return $noti;
    }
    public function delete_restaurant($store_id)
    {
        $delete = $this->db->set('status_active', 0)
            ->set('store_status', 0)
            ->where('store_id', $store_id)
            ->update('tb_store');
        if ($delete == 1) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    public function save_restaurant($data)
    {
        if ($data->img->type) {
            $imgPath = explode(',', $data->img->url)[1];
            $imgDecode = base64_decode($imgPath);
            $directory = 'image/store/store' . rand(1, 999) . '.' . $data->img->type[1];
            $data->store_image = $directory;
            $upload = file_put_contents($directory, $imgDecode);
            $insert = $this->db->insert('tb_store', $data);
            $store_id = $this->db->insert_id();
            if ($insert == 1) {
                if (!empty($data->store_type)) {
                    foreach ($data->store_type as $key => $value) {
                        $insertType = $this->db->set('typeStore_id', $value->typeStore_id)
                            ->set('store_id', $store_id)
                            ->insert('tb_typeJoinStore');
                    }
                }
                $this->Firebase_line->setStore_open($store_id, 0); //set Firebase store Open
                $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ข้อมูลรูปภาพไม่ถูกต้อง"];
        }
        return $noti;
    }
    public function update_restaurant($data)
    {
        if ($data->img->type) {
            $imgPath = explode(',', $data->img->url)[1];
            $imgDecode = base64_decode($imgPath);
            $directory = 'image/store/store' . rand(1, 999) . '.' . $data->img->type[1];
            $data->store_image = $directory;
            $upload = file_put_contents($directory, $imgDecode);
        }
        $store_id = $data->store_id;
        $update = $this->db->where('store_id', $store_id)
            ->update('tb_store', $data);
        $setTj_active = $this->db->set('tj_active', 0)
            ->where('store_id', $store_id)
            ->update('tb_typeJoinStore');
        if (!empty($data->store_type)) {
            foreach ($data->store_type as $key => $value) {
                if ($value->typeJoin_id != 0) {
                    $updateType = $this->db->set('tj_active', 1)
                        ->where('typeJoin_id', $value->typeJoin_id)
                        ->update('tb_typeJoinStore');
                } else {
                    $getCheck = $this->db->where('typeStore_id', $value->typeStore_id)
                        ->where('store_id', $store_id)
                        ->get('tb_typeJoinStore')->num_rows();
                    if ($getCheck > 0) {
                        $updateType = $this->db->set('tj_active', 1)
                            ->where('typeStore_id', $value->typeStore_id)
                            ->where('store_id', $store_id)
                            ->update('tb_typeJoinStore');
                    } else {
                        $insertType = $this->db->set('store_id', $store_id)
                            ->set('typeStore_id', $value->typeStore_id)
                            ->insert('tb_typeJoinStore');
                    }
                }
            }
        }
        if ($update == 1) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    public function loadListCustomer()
    {
        $member = $this->db->join('tb_typeuser as ts', 'ts.type_id = m.type_id')
            ->order_by('ts.rank', 'ASC')
            ->get('tb_member as m')->result();
        foreach ($member as $key => $value) {
            if ($value->m_active == 1) {
                $value->checked = true;
            } else {
                $value->checked = false;
            }
        }

        $count = count($member);
        $data = array("data" => $member, "count" => $count);
        return $data;
    }
    public function grant_customerUse($m_id, $m_active)
    {

        $status = $this->db->set('m_active', $m_active)
            ->where('m_id', $m_id)
            ->update('tb_member');
        $getUser = $this->db->where('m_id', $m_id)
            ->get('tb_member')->row();
        if ($m_active == 0) {
            if ($getUser->type_id == 2) {
                $updateEmp = $this->db->set('store_id', null)
                    ->set('m_status', 0)
                    ->where('m_id', $m_id)
                    ->update('tb_member');
                $this->checkEmp_store($getUser->store_id);
            } elseif ($getUser->type_id == 3 || $getUser->type_id == 5) {
                $block = $this->db->set('rider_status', 0)
                    ->set('r_active', 0)
                    ->where('m_id', $m_id)
                    ->update('tb_rider');
            }
        } else {
            if ($getUser->type_id == 3 || $getUser->type_id == 5) {
                $block = $this->db->set('r_active', 1)
                    ->where('m_id', $m_id)
                    ->update('tb_rider');
            }
        }
        if ($status == 1) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        if ($getUser->type_id == 1) {
            $checkAdmin = $this->db->where('type_id', 1)
                ->where('m_active', 1)
                ->get('tb_member')->result();
            if (empty($checkAdmin)) {
                $status = $this->db->set('m_active', 1)
                    ->set('m_status', 1)
                    ->where('m_id', $m_id)
                    ->update('tb_member');
                $noti = ['flag' => 0, 'ms' => "ไม่สามารถปิดใช้งานผู้ดูแลระบบได้"];
            } else {
                $status = $this->db->set('m_status', 0)
                    ->where('m_id', $m_id)
                    ->update('tb_member');
            }
        }
        return $noti;
    }
    public function load_list_admin_store($store_id)
    {
        $employee =  $this->db->where('store_id', $store_id)
            ->where('type_id', 2)
            ->get('tb_member')->result();
        foreach ($employee as  $key => $value) {
            if ($value->m_active == 1) {
                $value->checkedActive = true;
            } else {
                $value->checkedActive = false;
            }
            if ($value->m_status == 1) {
                $value->statusRedy = true;
            } else {
                $value->statusRedy = false;
            }
        }
        $count = count($employee);
        $data = array("data" => $employee, "count" => $count);
        return $data;
    }
    public function load_listFood_byRes($store_id)
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
                $value2->image = photo() . $value2->image;
                if ($value2->option != null) {
                    $value2->option = json_decode($value2->option);
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
        foreach ($food as $key => $value) {
            $value->image = photo() . $value->image;
            if ($value->option != null) {
                $value->option = json_decode($value->option);
            }
        }
        if (!empty($food)) {
            $food = $food;
        } else {
            $food = null;
        }
        return $food;
    }
    public function load_restaurant_profileByID($store_id)
    {
        $store = $this->db->where('store_id', $store_id)
            ->get('tb_store')->row();

        $amount_order = $this->db->join('tb_order as o', 'o.order_id = od.order_id')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->where('o.status_id', 4)
            ->where('od.food_status', 3)
            ->where('f.store_id', $store_id)
            ->group_by('o.order_id')
            ->get('tb_orderdetail as od')->num_rows();


        $whereFood = "f_active = '1'AND status !='9' AND store_id = '$store_id'";
        $amount_food = $this->db->select('count(*) as count')
            ->where($whereFood)
            ->get('tb_food')->row()->count;


        $store->amount_order = $amount_order;
        $store->amount_food = $amount_food;

        return $store;
    }
    public function loadLastorder_byResID($store_id)
    {

        $order = $this->db->select('os.status_name,o.*')
            ->limit(6)
            ->join('tb_orderdetail as od', 'od.order_id = o.order_id')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->join('tb_orderstatus as os', 'os.status_id = o.status_id')
            ->where('f.store_id', $store_id)
            ->order_by('o.order_id', 'DESC')
            ->group_by('o.order_id')
            ->get('tb_order as o')->result();
        $order = $this->updateStatusStore($order, $store_id);
        return $order;
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
            if ($value->orderType == 1) {
                $value->orderType_name = delivery();
            } else {
                $value->orderType_name = onMarket();
            }
        }
        return $order;
    }
    public function loadAdmin_byResID($store_id)
    {
        $where = "store_id = '$store_id' AND type_id = '2' AND m_active = '1'";
        $employee =  $this->db->where($where)
            ->get('tb_member')->result();
        return $employee;
    }
    public function loadTypeMenu_byResID($store_id)
    {
        $typeFood = $this->db->where('tf_active', 1)
            ->order_by('typefood_id', 'DESC')
            ->where('store_id', $store_id)
            ->get('tb_typefood')->result();
        return $typeFood;
    }
    public function updateTypeMenu_byResID($typefood_id, $typefood_name)
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
    public function deteleTypeMenu_byResID($typefood_id)
    {
        $delete = $this->db->set('tf_active', 0)
            ->where('typefood_id', $typefood_id)
            ->update('tb_typefood');
        if ($delete != 0) {
            $setFoodType = $this->db->set('typefood_id', 0)
                ->where('typefood_id', $typefood_id)
                ->update('tb_food');
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    public function insertMenu_byResID($data)
    {
        $data->option = json_encode($data->option);
        $upload = $this->upload($data);
        if ($upload["flag"] == 0) {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ไฟล์ภาพมีปัญหา"];
        } else {
            $date = date("Y-m-d H:i:s");
            $insert = $this->db->set('image', $upload["image"])
                ->set('date_time', $date)
                ->insert('tb_food', $data);
            if ($insert != 0) {
                $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ กรุณาลองใหม่"];
            }
        }
        return $noti;
    }
    public function updateMenu_byResID($data)
    {
        if ($data->option != null) {
            $data->option = json_encode($data->option);
        }
        if ($data->img->status == 1) {
            $upload = $this->upload($data);
            if ($upload["flag"] == 0) {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ไฟล์ภาพมีปัญหา"];
            } else {
                $update = $this->db->set('image', $upload["image"])
                    ->where('food_id', $data->food_id)
                    ->update('tb_food', $data);
                if ($update != 0) {
                    $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ กรุณาลองใหม่"];
                }
            }
        } else {
            $update = $this->db->where('food_id', $data->food_id)
                ->update('tb_food', $data);
            if ($update != 0) {
                $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ กรุณาลองใหม่"];
            }
        }
        return $noti;
    }
    public function deleteMenu_byResID($food_id)
    {
        $delete = $this->db->set('f_active', 0)
            ->set('status', 0)
            ->where('food_id', $food_id)
            ->update('tb_food');
        if ($delete != 0) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ กรุณาลองใหม่"];
        }
        return $noti;
    }
    function upload($data)
    {
        $newName = time() . "_" . uniqid();
        $imgPath = explode(',', $data->img->data)[1];
        $imgDecode = base64_decode($imgPath);
        $directory = 'image/food/' . $newName . '.png';
        if ($data->img->data != null) {
            $upload = file_put_contents($directory, $imgDecode);
            if ($upload == 0) {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ไฟล์ภาพมีปัญหา"];
            } else {
                $noti = ['flag' => 1, 'image' => $directory];
            }
            return $noti;
        }
    }
    public function loadListHistoryOrder_restaurantByID($store_id, $page)
    {
        $count = $this->db->join('tb_orderstatus as os', 'os.status_id=o.status_id')
            ->join('tb_orderdetail as od', 'od.order_id = o.order_id')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->where('f.store_id', $store_id)
            ->order_by('o.order_id', 'DESC')
            ->group_by('o.order_id')
            ->get('tb_order as o')->num_rows();


        $order = $this->db->select('o.order_id,o.date_time,o.order_num,o.order_id,o.status_id,os.status_id,os.status_name,o.orderType')
            ->limit(10, $page * 10)
            ->join('tb_orderstatus as os', 'os.status_id=o.status_id')
            ->join('tb_orderdetail as od', 'od.order_id = o.order_id')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->where('f.store_id', $store_id)
            ->order_by('o.order_id', 'DESC')
            ->group_by('o.order_id')
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            $value->class_bg = '';

            $status =  $this->db->select('od.food_status,soi.statusOrStore_name')
                ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
                ->join('tb_statusOrderInStore as soi', 'soi.food_status = od.food_status')
                ->where('od.order_id', $value->order_id)
                ->where('f.store_id', $store_id)
                ->group_by('od.order_id')
                ->get('tb_food as f')->row();
            if ($status->food_status == 0) {
                $value->status_id = "9";
                $value->status_name = $status->statusOrStore_name;
            } elseif ($status->food_status == 1) {
                $value->status_id = "1";
                $value->status_name = $status->statusOrStore_name;
            } elseif ($status->food_status == 2) {
                $value->status_id = "2";
                $value->status_name = $status->statusOrStore_name;
            }
            if ($value->orderType == 1) {
                $value->orderType_name = delivery();
            } else {
                $value->orderType_name = onMarket();
            }
        }
        $data = array("data" => $order, "count" => $count);

        return $data;
    }
    public function moreDetailOrder_byID($store_id, $order_id)
    {
        $order = $this->db->join('tb_payment as pm', 'pm.payment_id = o.payment_id')
            ->where('o.order_id', $order_id)
            ->get('tb_order as o')->row();
        if ($order->orderType == 1) {
            $order->orderType_name = delivery();
        } else {
            $order->orderType_name = onMarket();
        }
        if ($order->order_pay == 0) {
            $order->order_payName = "ยังไม่ชำระ";
        } elseif ($order->order_pay == 1) {
            $order->order_payName = "ชำระแล้ว";
        }
        if ($order->rider_id != null) {
            $rider = $this->db->select('m.name,m.mobile')
                ->join('tb_rider as r', 'r.rider_id=o.rider_id')
                ->join('tb_member as m', 'm.m_id=r.m_id')
                ->where('o.order_id', $order_id)
                ->get('tb_order as o');
            if ($rider->num_rows() > 0) {
                $rider_name = $rider->row()->name;
                $rider_mobile = $rider->row()->mobile;
                $order->rider_name = $rider_name;
                $order->rider_phone = $rider_mobile;
            } else {
                $order->rider_name = null;
                $order->rider_phone = null;
            }
        } else {
            $order->rider_name = null;
            $order->rider_phone = null;
        }
        $orderDetail = $this->db->select('od.food_name,od.amount,od.sum_price,od.food_detail,f.store_id,f.food_id,od.food_status,od.option,od.food_price')
            ->join('tb_orderdetail as od', 'od.food_id = f.food_id')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            ->get('tb_food as f')->result();
        $price = 0;
        foreach ($orderDetail as $key => $value) {
            $price += $value->sum_price;

            if ($value->option != null) {
                $value->option = json_decode($value->option);
            } else {
                $value->option = null;
            }
        }

        $status =  $this->db->select('od.food_status,soi.statusOrStore_name')
            ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
            ->join('tb_statusOrderInStore as soi', 'soi.food_status = od.food_status')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            ->group_by('od.order_id')
            ->get('tb_food as f')->row();
        if ($status->food_status == 0) {
            $order->status_id = "9";
            $order->status_name = $status->statusOrStore_name;
        } elseif ($status->food_status == 1) {
            $order->status_id = "1";
            $order->status_name = $status->statusOrStore_name;
        } elseif ($status->food_status == 2) {
            $order->status_id = "2";
            $order->status_name = $status->statusOrStore_name;
        }
        $order->order_price = $price;
        $order->detail = $orderDetail;


        $displayName = $this->db->select('displayName')
            ->where('m_id', $order->m_id)
            ->get('tb_member')->row();
        $order->displayName = $displayName->displayName;
        return $order;
    }
    public function loadOrder_customerByID($m_id, $page)
    {
        $count = $this->db->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->where('o.m_id', $m_id)
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->num_rows();
        $order = $this->db->select('o.order_id,o.order_num,o.date_time,st.*,o.orderType')
            ->limit(10, $page * 10)
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->where('o.m_id', $m_id)
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            if ($value->orderType == 1) {
                $value->orderType_name = delivery();
            } else {
                $value->orderType_name = onMarket();
            }
        }

        $data = array("data" => $order, "count" => $count);

        return $data;
    }
    public function moreDetailOrder_cusByID($order_id)
    {
        $order = $this->db->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->join('tb_payment as pm', 'pm.payment_id=o.payment_id')
            ->where('o.order_id', $order_id)
            ->get('tb_order as o')->row();
        if ($order->rider_id != null) {
            $rider = $this->db->select('m.name,m.mobile')
                ->join('tb_rider as r', 'r.rider_id=o.rider_id')
                ->join('tb_member as m', 'm.m_id=r.m_id')
                ->where('o.order_id', $order_id)
                ->get('tb_order as o');
            if ($rider->num_rows() > 0) {
                $rider_name = $rider->row()->name;
                $rider_mobile = $rider->row()->mobile;
                $order->rider_name = $rider_name;
                $order->rider_phone = $rider_mobile;
            } else {
                $order->rider_name = null;
                $order->rider_phone = null;
            }
        } else {
            $order->rider_name = null;
            $order->rider_phone = null;
        }
        if ($order->orderType == 1) {
            $order->orderType_name = delivery();
        } else {
            $order->orderType_name = onMarket();
        }


        $order = $this->pushDetail_to_order($order);
        $order = $this->pushStore_to_order($order);
        return $order;
    }
    function pushStore_to_order($order)
    {
        $order = $order;

        $order_price = 0;
        // for ($i = 0; $i < count($order); $i++) {
        foreach ($order->store as $key => $value) {
            //สถานะคำสั่งซื้อในร้าน
            $status =  $this->Rider_model->getStatusOrStore($order->order_id, $value->store_id);
            if ($status->num_rows() > 0) {
                $statusOrStore_id = $status->row()->food_status;
                $statusOrStore_name = $status->row()->statusOrStore_name;
                $value->statusOrStore_name = $statusOrStore_name;
                $value->statusOrStore_id = $statusOrStore_id;
            }
            $food = $this->db->select('od.food_name,od.amount,od.sum_price,od.food_price,od.food_status,od.amount,od.food_detail,od.option')
                ->join('tb_orderdetail as od', 'f.food_id = od.food_id')
                ->where('od.order_id', $order->order_id)
                ->where('f.store_id', $value->store_id)
                ->get('tb_food as f')->result();
            $value->food = $food;
            $store_price = 0;
            $priceFood = 0;
            foreach ($food as $key => $value2) {
                # code...
                if ($value2->food_status != 0) {
                    $priceFood += $value2->sum_price;
                }
                $store_price += $value2->sum_price;

                if ($value2->option != null) {
                    $value2->option = json_decode($value2->option);
                }
            }
            $value->store_price = $store_price;
            $order_price += $priceFood;
        }
        $order->order_price = $order_price;
        // }
        return $order;
    }
    function pushDetail_to_order($order)
    {
        $order = $order;
        $amount = $this->db->select_sum('amount')
            ->where('order_id', $order->order_id)
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

        return $order;
    }
    public function load_list_rider()
    {
        $rider =  $this->db->select('m.m_id,m.name,m.mobile,m.m_active,m.pictureUrl,r.rider_id,rs.*,ts.*,r.rider_status')
            ->join('tb_rider as r', 'r.m_id=m.m_id')
            ->join('tb_riderstatus as rs', 'rs.rider_status=r.rider_status')
            ->join('tb_typeuser as ts', 'ts.type_id=m.type_id')
            ->where('m.type_id', 3)
            ->or_where('m.type_id', 5)
            ->order_by('m.type_id', 'ASC')
            ->get('tb_member as m')->result();
        foreach ($rider as $key => $value) {
            if ($value->m_active == 1) {
                $value->checked = true;
            } else {
                $value->checked = false;
            }
            if ($value->rider_status == 0) {
                $value->checkedOn = false;
            } else {
                $value->checkedOn = true;
            }
        }
        $count = count($rider);
        $data = array("data" => $rider, "count" => $count);
        return $data;
    }
    public function loadRiderProfile_byID($rider_id)
    {
        $rider = $this->db->select('m.*,r.*,rs.*')
            ->join('tb_member as m', 'm.m_id=r.m_id')
            ->join('tb_riderstatus as rs', 'r.rider_status= rs.rider_status')
            ->where('r.rider_id', $rider_id)
            ->get('tb_rider as r')->row();
        return $rider;
    }
    public function loadRiderOrderLast_byID($rider_id)
    {
        $order = $this->db->select('o.order_id,o.order_num,o.date_time,st.status_id,st.status_name,o.orderType')
            ->limit(10)
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->where('o.rider_id', $rider_id)
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            if ($value->orderType == 1) {
                $value->orderType_name = delivery();
            } else {
                $value->orderType_name = onMarket();
            }
        }
        return $order;
    }
    public function moreDetailOrderRider_byID($order_id, $rider_id)
    {
        $order = $this->db->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->join('tb_payment as pm', 'pm.payment_id=o.payment_id')
            ->where('o.order_id', $order_id)
            ->where('o.rider_id', $rider_id)
            ->get('tb_order as o')->row();
        if ($order->orderType == 1) {
            $order->orderType_name = delivery();
        } else {
            $order->orderType_name = onMarket();
        }
        $order = $this->pushDetail_to_order($order);
        $order = $this->pushStore_to_order($order);
        return $order;
    }
    public function loadListHistoryOrderRider_byID($rider_id, $page)
    {
        $count = $this->db->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->where('o.rider_id', $rider_id)
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->num_rows();
        $order = $this->db->select('o.order_id,o.order_num,o.date_time,st.*,o.orderType')
            ->limit(10, $page * 10)
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->where('o.rider_id', $rider_id)
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->result();
        foreach ($order as $key => $value) {
            if ($value->orderType == 1) {
                $value->orderType_name = delivery();
            } else {
                $value->orderType_name = onMarket();
            }
        }

        $data = array("data" => $order, "count" => $count);

        return $data;
    }
    public function loadOrder_All($page)
    {
        $count = $this->db->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->num_rows();

        $order = $this->db->select('o.order_id,o.order_num,o.date_time,o.order_price,st.*,o.m_id,o.rider_id,o.del_cost,o.orderType')
            ->limit(20, $page * 20)
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->result();

        foreach ($order as $key => $value) {
            $member = $this->db->select('displayName,mobile')
                ->where('m_id', $value->m_id)
                ->get('tb_member');
            if ($member->num_rows() > 0) {
                $value->m_name = $member->row()->displayName;
                $value->m_phone = $member->row()->mobile;
            }

            $rider = $this->db->select('m.name,m.mobile')
                ->join('tb_member as m', 'm.m_id=r.m_id')
                ->where('r.rider_id', $value->rider_id)
                ->get('tb_rider as r');
            if ($rider->num_rows() > 0) {
                $value->r_name = $rider->row()->name;
                $value->r_phone = $rider->row()->mobile;
            } else {
                $value->r_name = null;
                $value->r_phone = null;
            }
            if ($value->orderType == 1) {
                $value->orderType_name = delivery();
            } else {
                $value->orderType_name = onMarket();
            }
        }
        $data = array("data" => $order, "count" => $count);
        return $data;
    }
    public function moreDetailOrderAll_byID($order_id)
    {
        $order = $this->db->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->join('tb_payment as pm', 'pm.payment_id=o.payment_id')
            ->where('o.order_id', $order_id)
            ->get('tb_order as o')->row();
        if ($order->rider_id != null) {
            $rider = $this->db->select('m.name,m.mobile')
                ->join('tb_rider as r', 'r.rider_id=o.rider_id')
                ->join('tb_member as m', 'm.m_id=r.m_id')
                ->where('o.order_id', $order_id)
                ->get('tb_order as o');
            if ($rider->num_rows() > 0) {
                $rider_name = $rider->row()->name;
                $rider_mobile = $rider->row()->mobile;
                $order->rider_name = $rider_name;
                $order->rider_phone = $rider_mobile;
            } else {
                $order->rider_name = null;
                $order->rider_phone = null;
            }
        } else {
            $order->rider_name = null;
            $order->rider_phone = null;
        }
        if ($order->slip_image != null) {
            $slip = json_decode($order->slip_image);
            foreach ($slip as $key => $value) {
                # code...
                $value->slip = photo() . $value->slip;
            }
            $order->slip_image = $slip;
        }
        if ($order->orderType == 1) {
            $order->orderType_name = delivery();
        } else {
            $order->orderType_name = onMarket();
        }

        // if($order->status_id==5){
        //     $order->del_cost=0;
        // }
        $order = $this->pushDetail_to_order($order);
        $order = $this->pushStore_to_order($order);
        return $order;
    }
    public function loadUser_allReoport()
    {
        $report = [];
        $store = $this->db->select('count(*) as count')
            ->where('status_active', 1)
            ->get('tb_store')->row()->count;
        $store_on = $this->db->select('count(*) as count')
            ->where('store_status', 1)
            ->where('status_active', 1)
            ->get('tb_store')->row()->count;
        $store_off = $this->db->select('count(*) as count')
            ->where('store_status', 0)
            ->where('status_active', 1)
            ->get('tb_store')->row()->count;
        $res = ["count" => $store, "active" => $store_on, "none" => $store_off];
        ##############################################################
        $employee = $this->db->select('count(*) as count')
            ->where('type_id', 2)
            ->get('tb_member')->row()->count;
        $employee_active = $this->db->select('count(*) as count')
            ->where('type_id', 2)
            ->where('m_active', 1)
            ->get('tb_member')->row()->count;
        $employee_block = $this->db->select('count(*) as count')
            ->where('type_id', 2)
            ->where('m_active', 0)
            ->get('tb_member')->row()->count;
        $employee_on = $this->db->select('count(*) as count')
            ->where('type_id', 2)
            ->where('m_active', 1)
            ->where('m_status', 1)
            ->get('tb_member')->row()->count;
        $employee_off = $this->db->select('count(*) as count')
            ->where('type_id', 2)
            ->where('m_status', 0)
            ->get('tb_member')->row()->count;
        $emp = ["count" => $employee, "active" => $employee_active, "none" => $employee_block, "online" => "$employee_on ", "offline" => " $employee_off "];
        ##############################################################

        $rider = $this->db->select('count(*) as count')
            ->where('type_id', 3)
            ->get('tb_member')->row()->count;
        $rider_active = $this->db->select('count(*) as count')
            ->where('type_id', 3)
            ->where('m_active', 1)
            ->get('tb_member')->row()->count;
        $rider_block = $this->db->select('count(*) as count')
            ->where('type_id', 3)
            ->where('m_active', 0)
            ->get('tb_member')->row()->count;
        $rider_on = $this->db->select('count(*) as count')
            ->join('tb_rider as r', 'r.m_id=m.m_id')
            ->where('m.type_id', 3)
            ->where('m.m_active', 1)
            ->where('r.r_active', 1)
            ->where('r.rider_status !=', 0)
            ->get('tb_member as m')->row()->count;
        $rider_off = $this->db->select('count(*) as count')
            ->join('tb_rider as r', 'r.m_id=m.m_id')
            ->where('m.type_id', 3)
            ->where('r.rider_status', 0)
            ->get('tb_member as m')->row()->count;

        $rid = ["count" => $rider, "active" => $rider_active, "none" => $rider_block, "online" => "$rider_on", "offline" => " $rider_off"];
        ##############################################################
        $waitress = $this->db->select('count(*) as count')
            ->where('type_id', 5)
            ->get('tb_member')->row()->count;
        $waitress_active = $this->db->select('count(*) as count')
            ->where('type_id', 5)
            ->where('m_active', 1)
            ->get('tb_member')->row()->count;
        $waitress_block = $this->db->select('count(*) as count')
            ->where('type_id', 5)
            ->where('m_active', 0)
            ->get('tb_member')->row()->count;
        $waitress_on = $this->db->select('count(*) as count')
            ->join('tb_rider as r', 'r.m_id=m.m_id')
            ->where('m.type_id', 5)
            ->where('m.m_active', 1)
            ->where('r.r_active', 1)
            ->where('r.rider_status !=', 0)
            ->get('tb_member as m')->row()->count;
        $waitress_off = $this->db->select('count(*) as count')
            ->join('tb_rider as r', 'r.m_id=m.m_id')
            ->where('m.type_id', 5)
            ->where('r.rider_status', 0)
            ->get('tb_member as m')->row()->count;
        $wait = ["count" => $waitress, "active" => $waitress_active, "none" => $waitress_block, "online" => "$waitress_on", "offline" => "$waitress_off"];
        ##############################################################

        $user = $this->db->select('count(*) as count')
            ->where('type_id', 4)
            ->get('tb_member')->row()->count;
        $user_active = $this->db->select('count(*) as count')
            ->where('type_id', 4)
            ->where('m_active', 1)
            ->get('tb_member')->row()->count;
        $user_block = $this->db->select('count(*) as count')
            ->where('type_id', 4)
            ->where('m_active', 0)
            ->get('tb_member')->row()->count;
        $use = ["count" => $user, "active" => $user_active, "none" => $user_block];
        ##############################################################
        $report = ["res" => $res, "employee" => $emp, "rider" => $rid, "user" => $use, "wait" => $wait];
        return $report;
    }
    public function loadReportGraphByYear($year)
    {
        for ($i = 1; $i <= 12; $i++) {
            if ($i < 10) {
                $month = '0' . $i;
            } else {
                $month = $i;
            }
            $key = $year . '-' . $month;
            $report_price = $this->db->select_sum('od.sum_price')
                ->join('tb_order as o', 'od.order_id= o.order_id')
                ->like('o.date_time', $key, 'both')
                ->where('o.status_id', 4)
                ->where('od.food_status !=', 0)
                ->get('tb_orderdetail as od')->row();
            if ($report_price->sum_price == null) {
                $sum[$i - 1] = 0;
            } else {
                $sum[$i - 1] = (int)$report_price->sum_price;
            }
        }
        return $sum;
    }
    public function loadReportGraphByMount($year, $month)
    {
        $kom = ($month == "01" || $month == "03" || $month == "05" || $month == "07" || $month == "08" || $month == "10" || $month == "12");
        $yon = ($month == "04" || $month == "06" || $month == "09" || $month == "11");
        if ($kom)
            $count = 31;
        elseif ($yon)
            $count = 30;
        elseif ($month == "02") {
            $checkYears = $year % 4;
            if ($checkYears == 0) {
                $count = 29;
            } else {
                $count = 28;
            }
        }
        for ($i = 1; $i <= $count; $i++) {
            if ($i < 10) {
                $day = '0' . $i;
            } else {
                $day = $i;
            }
            $key = $year . '-' . $month . '-' . $day;
            $report_price = $this->db->select_sum('od.sum_price')
                ->join('tb_order as o', 'od.order_id= o.order_id')
                ->like('o.date_time', $key, 'both')
                ->where('o.status_id', 4)
                ->where('od.food_status !=', 0)
                ->get('tb_orderdetail as od')->row();
            if ($report_price->sum_price == null) {
                $sum[$i - 1] = 0;
            } else {
                $sum[$i - 1] = (int)$report_price->sum_price;
            }
        }
        return $sum;
    }
    public function findOrder($keywords, $page)
    {
        $count = $this->db->select('o.order_id,o.order_num,o.date_time,o.order_price,st.*,o.m_id,o.rider_id')
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->like('o.order_num', $keywords, 'both')
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->num_rows();

        $order = $this->db->select('o.order_id,o.order_num,o.date_time,o.order_price,st.*,o.m_id,o.rider_id')
            ->limit(20, $page * 20)
            ->join('tb_orderstatus as st', 'st.status_id=o.status_id')
            ->like('o.order_num', $keywords, 'both')
            ->order_by('o.order_id', 'DESC')
            ->get('tb_order as o')->result();

        foreach ($order as $key => $value) {
            $member = $this->db->select('displayName,mobile')
                ->where('m_id', $value->m_id)
                ->get('tb_member')->row();
            $value->m_name = $member->displayName;
            $value->m_phone = $member->mobile;

            $rider = $this->db->select('m.name,m.mobile')
                ->join('tb_member as m', 'm.m_id=r.m_id')
                ->where('r.rider_id', $value->rider_id)
                ->get('tb_rider as r');
            if ($rider->num_rows() > 0) {
                $value->r_name = $rider->row()->name;
                $value->r_phone = $rider->row()->mobile;
            } else {
                $value->r_name = null;
                $value->r_phone = null;
            }
        }
        $data = array("data" => $order, "count" => $count);
        return $data;
    }
    public function findCustomer($keywords, $page)
    {
        $count = $this->db->select('count(*) as count')
            ->like('displayName', $keywords, 'both')
            ->where("type_id", 4)
            ->get('tb_member')->row()->count;

        $member = $this->db->limit(20, $page * 20)
            ->where("type_id", 4)
            ->like('displayName', $keywords, 'both')
            ->get('tb_member')->result();
        foreach ($member as $key => $value) {
            if ($value->m_active == 1) {
                $value->checked = true;
            } else {
                $value->checked = false;
            }
        }
        $data = array("data" => $member, "count" => $count);
        return $data;
    }
    public function findRider($keywords, $page)
    {
        $count =  $this->db->select('count(*) as count')
            ->like('m.name', $keywords, 'both')
            ->join('tb_rider as r', 'r.m_id=m.m_id')
            ->join('tb_riderstatus as rs', 'rs.rider_status=r.rider_status')
            ->where('m.type_id', 3)
            ->or_where('m.type_id', 5)
            ->get('tb_member as m')->row()->count;
        $rider =  $this->db->select('m.m_id,m.name,m.mobile,m.m_active,m.pictureUrl,r.rider_id,rs.*')
            ->limit(20, $page * 20)
            ->like('m.name', $keywords, 'both')
            ->join('tb_rider as r', 'r.m_id=m.m_id')
            ->join('tb_riderstatus as rs', 'rs.rider_status=r.rider_status')
            ->where('m.type_id', 3)
            ->or_where('m.type_id', 5)
            ->get('tb_member as m')->result();
        foreach ($rider as $key => $value) {
            if ($value->m_active == 1) {
                $value->checked = true;
            } else {
                $value->checked = false;
            }
        }
        $data = array("data" => $rider, "count" => $count);
        return $data;
    }
    public function findRestaurant($keywords, $page)
    {
        $count = $this->db->select('count(*) as count')
            ->like('store_name', $keywords, 'both')
            ->where('status_active', 1)
            ->like('store_name', $keywords, 'both')
            ->get('tb_store')->row()->count;

        $store = $this->db->where('status_active', 1)
            ->like('store_name', $keywords, 'both')
            ->limit(20, $page * 20)
            ->get('tb_store')->result();
        foreach ($store as $key => $value) {
            if ($value->store_status == 1) {
                $value->checked = true;
            } else {
                $value->checked = false;
            }
            $employee =  $this->db->where('store_id', $value->store_id)
                ->get('tb_member')->num_rows();
            $value->amount = $employee;
        }
        $data = array("data" => $store, "count" => $count);
        return $data;
    }
    public function loadStateOrder()
    {
        $statusOrderrder = $this->db->get('tb_orderstatus')->result();
        $statusStore = $this->db->get('tb_statusOrderInStore')->result();
        $data = array("statusOrder" => $statusOrderrder, 'statusStore' => $statusStore);
        return $data;
    }
    public function orderResChangeStatus($order_id, $store_id, $status)
    {
        $getCheckOrder = $this->db->where('order_id', $order_id)
            ->get('tb_order')->row();
        if ($getCheckOrder->status_id == 4 || $getCheckOrder->status_id == 5) {
            $noti = ['flag' => 0, 'ms' => "ไม่สามารถดำเนินการได้ เนื่องจากคำสั่งซื้อนี้ได้สิ้นสุดลงแล้ว"];
        } else {

            $food_id = $this->db->select('od.food_id')
                ->join('tb_food as f', 'f.food_id= od.food_id')
                ->where('od.order_id', $order_id)
                ->where('f.store_id', $store_id)
                ->get('tb_orderdetail as od')->result();

            foreach ($food_id as $key => $value) {
                $update =  $this->db->set('food_status', $status)
                    ->where('order_id', $order_id)
                    ->where('food_id', $value->food_id)
                    ->update('tb_orderdetail');
            }
            if ($update != 0) {
                if ($status == 0) {
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
                        $member = $this->db->select('rider_id,m_id,order_num,order_id')
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
                        $cancel = true;
                    } else {
                        $member = $this->db->select('rider_id,m_id,order_num,order_id')
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
                        $cancel = false;
                    }
                } else {
                    $cancel = false;
                }
                $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ", 'cancel' => $cancel];
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
            }
        }

        return $noti;
    }
    public function orderIDChangeStatus($order_id, $status)
    {
        $getCheckOrder = $this->db->where('order_id', $order_id)
            ->get('tb_order')->row();
        if ($getCheckOrder->status_id == 4 || $getCheckOrder->status_id == 5) {
            // if ($getCheckOrder->status_id == 10 || $getCheckOrder->status_id == 10) {
            $noti = ['flag' => 0, 'ms' => "ไม่สามารถดำเนินการได้ เนื่องจากคำสั่งซื้อนี้ได้สิ้นสุดลงแล้ว"];
        } else {

            $update =  $this->db->set('status_id', $status)
                ->where('order_id', $order_id)
                ->update('tb_order');

            if ($status == 5) {
                $foodStatus = $this->db->set('food_status', 0)
                    ->where('order_id', $order_id)
                    ->update('tb_orderdetail');
                $setTime = $this->db->set('sendTime_done', date('Y-m-d H:i:s'))
                    ->where('order_id', $order_id)
                    ->update('tb_order');
                $member = $this->db->select('rider_id,m_id,order_num,order_id')
                    ->where('order_id', $order_id)
                    ->get('tb_order');
                if ($member->num_rows() > 0) {
                    $rider_id = $member->row()->rider_id;
                    $m_id = $member->row()->m_id;
                    $this->Market_model->changeRiderToReady($rider_id);
                    $messaging = "คำสั่งซื้อ " . $member->row()->order_num . " ของคุณถูกยกเลิก ขออภัยในความไม่สะดวก";
                    $this->Line_model->sendMessageToUser($m_id, $messaging);
                }
            } elseif ($status == 4) {
                $foodStatus = $this->db->set('food_status', 3)
                    ->where('order_id', $order_id)
                    ->where('food_status !=', 0)
                    ->update('tb_orderdetail');
                $setTime = $this->db->set('sendTime_done', date('Y-m-d H:i:s'))
                    ->where('order_id', $order_id)
                    ->update('tb_order');
                $member = $this->db->select('rider_id,order_id')
                    ->where('order_id', $order_id)
                    ->get('tb_order');
                if ($member->num_rows() > 0) {
                    $rider_id = $member->row()->rider_id;
                    $this->Market_model->changeRiderToReady($rider_id);
                }
            }
            if ($update != 0) {
                $this->Firebase_line->adminChangeStatus($order_id);
                $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
            } else {
                $noti = ['flag' => 0, 'ms' => "โปรดลองอีกครั้ง"];
            }
        }

        return $noti;
    }
    public function loadPlanRider($orderType)
    {
        if ($orderType == 1) {
            $rider = $this->db->select('r.rider_id,r.rider_status,m.name,m.mobile,m.pictureUrl')
                ->join('tb_member as m', 'm.m_id=r.m_id')
                ->where('r.rider_status !=', 0)
                // ->where('r.rider_status', 1)
                ->where('m.m_active', 1)
                ->where('r.r_active', 1)
                ->where('m.type_id', 3)
                ->get('tb_rider as r')->result();
        } else if ($orderType == 2) {
            # code...

            $rider = $this->db->select('r.rider_id,r.rider_status,m.name,m.mobile,m.pictureUrl')
                ->join('tb_member as m', 'm.m_id=r.m_id')
                ->where('r.rider_status !=', 0)
                // ->where('r.rider_status', 1)
                ->where('m.m_active', 1)
                ->where('r.r_active', 1)
                ->where('m.type_id', 5)
                ->get('tb_rider as r')->result();
        }

        return $rider;
    }
    public function selectRiderPlan($order_id, $rider_id)
    {
        $order = $this->db->where('order_id', $order_id)
            ->get('tb_order')->row();
        if ($order->status_id == 4 || $order->status_id == 5) {
            $noti = ['flag' => 0, 'ms' => "ไม่สามารถดำเนินการได้ เนื่องจากคำสั่งซื้อนี้ได้สิ้นสุดลงแล้ว"];
        } else {
            $rider = $order->rider_id;
            $status = $order->status_id;
            $userId_rider = $this->db->join('tb_rider as r', 'r.m_id=m.m_id')
                ->where('r.rider_id', $rider_id)
                ->get('tb_member as m')->row()->userId;
            if ($status == 9) {
                $update = $this->db->set('rider_id', $rider_id)
                    ->set('status_id', 1)
                    ->where('order_id', $order_id)
                    ->update('tb_order');
                if ($update != 0) {
                    $this->Line_model->sendFlex_toRider($userId_rider, $order_id);
                    $this->Market_model->changeRiderToNotReady($rider_id);
                    $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
                }
            } elseif ($status == 8 || $status == 7) {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ โปรดยืนยันการชำระเงินก่อน"];
            } else {
                $update = $this->db->set('rider_id', $rider_id)
                    ->set('status_id', $status)
                    ->where('order_id', $order_id)
                    ->update('tb_order');
                if ($update != 0) {
                    if ($rider != null) {
                        $this->Market_model->changeRiderToReady($rider);
                        $messaging = "คำสั่งซื้อหมายเลข : " . $order->order_num . " ถูกโอนย้าย";
                        $this->Line_model->sendMessageToRider($rider, $messaging);
                    }
                    $this->Line_model->sendFlex_toRider($userId_rider, $order_id);
                    $this->Market_model->changeRiderToNotReady($rider_id);

                    $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
                }
            }
        }
        return $noti;
    }
    public function findTypeRes($search)
    {
        $type = $this->db->select('typeStore_id,typeStore_name')
            ->limit(5)
            ->like('typeStore_name', $search, 'both')
            ->where('ts_active', 1)
            ->get('tb_typeStore')->result();
        return $type;
    }
    public function loadTypeRes()
    {
        $typeStore = $this->db->where('ts_active', 1)
            ->get('tb_typeStore')->result();
        foreach ($typeStore as $key => $value) {
            $value->tsImage = photo() . $value->tsImage;
        }
        return $typeStore;
    }
    public function loadIcons()
    {
        $img = $this->db->get('tb_imageType')->result();
        foreach ($img as $key => $value) {
            $value->img_name = photo() . $value->img_name;
        }
        return $img;
    }
    public function saveTypeRes($data)
    {
        $tsImage = $this->db->where('img_id', $data->icon->img_id)
            ->get('tb_imageType')->row()->img_name;

        $addType = $this->db->set('tsImage', $tsImage)
            ->set('img_id', $data->icon->img_id)
            ->set('typeStore_name', $data->typeStore_name)
            ->insert('tb_typeStore');
        if ($addType != 0) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    public function updateTypeRes($data)
    {
        $tsImage = $this->db->where('img_id', $data->icon->img_id)
            ->get('tb_imageType')->row()->img_name;

        $addType = $this->db->set('tsImage', $tsImage)
            ->set('img_id', $data->icon->img_id)
            ->set('typeStore_name', $data->typeStore_name)
            ->where('typeStore_id', $data->typeStore_id)
            ->update('tb_typeStore');
        if ($addType != 0) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    public function deleteTypeRes($data)
    {
        $delete = $this->db->set('ts_active', 0)
            ->where('typeStore_id', $data->typeStore_id)
            ->update('tb_typeStore');
        if ($delete != 0) {
            $updateStoreType = $this->db->set('tj_active', 0)
                ->where('typeStore_id', $data->typeStore_id)
                ->update('tb_typeJoinStore');
            if ($updateStoreType != 0) {
                $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    public function loadListEmployee()
    {
        $employee =  $this->db->select('m_id,name,mobile,m_active,pictureUrl,m_status')
            ->where('type_id', 2)
            ->get('tb_member')->result();
        foreach ($employee as $key => $value) {
            if ($value->m_active == 1) {
                $value->checked = true;
            } else {
                $value->checked = false;
            }
            if ($value->m_status == 0) {
                $value->checkedOn = false;
            } else {
                $value->checkedOn = true;
            }
        }
        $count = count($employee);
        $data = array("data" => $employee, "count" => $count);
        return $data;
    }
    public function isLoginByLine($data)
    {
        $headers = apache_request_headers();
        // if (isset($headers['authorization'])) {
        //     $token = str_replace("Bearer ", "", $headers['authorization']);
        //     // echo $token;
        //     // exit;
        //     if ($token != 'null') {
        $in = 0;
        $up = 0;
        unset($data->statusMessage);
        $check = $this->db->where('userId', $data->userId)
            ->get('tb_member');
        if ($check->num_rows() > 0) {
            $m_id = $check->row()->m_id;
            $update = $this->db->set('last_active', date('Y-m-d H:i:s')) // ->set('token', $token)
                ->where('m_id', $m_id)
                ->update('tb_member', $data);
            $up = $update;
        } else {
            $insert = $this->db->set('date_time', date('Y-m-d H:i:s')) // ->set('token', $token)
                ->set('last_active', date('Y-m-d H:i:s'))
                ->insert('tb_member', $data);
            $m_id = $this->db->insert_id();
            $in = $insert;
        }
        if ($in != 0 || $up != 0) {
            $profile = $this->db->where('type_id', 1)
                ->where('m_id', $m_id)
                ->get('tb_member');
            if ($profile->num_rows() > 0) {
                $profile = $profile->row();
                $this->Line_model->setRichMenu($profile); //set Rich Menu
                // unset($profile->userId);
                $noti = ['flag' => 1, 'ms' => "เข้าสู่ระบบสำเร็จ", 'data' => $profile];
            } else {
                $noti = ['flag' => 0, 'ms' => "คุณไม่ใช่ผู้ดูแลระบบ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "เข้าสู่ระบบไม่สำเร็จ"];
        }
        //     } else {
        //         $noti = ['flag' => 0, 'ms' => "เข้าสู่ระบบไม่สำเร็จ"];
        //     }
        // } else {
        //     $noti = ['flag' => 0, 'ms' => "เข้าสู่ระบบไม่สำเร็จ"];
        // }
        return $noti;
    }
    public function loadEmpProfileBy($m_id)
    {
        $employee =  $this->db->where('m_id', $m_id)
            ->get('tb_member')->row();

        if ($employee->store_id != null) {
            $store = $this->db->select('store_id,store_name')
                ->where('store_id', $employee->store_id)
                ->get('tb_store')->row();
            $employee->store_id = $store->store_id;
            $employee->store_name = $store->store_name;
        } else {
            $employee->store_name = null;
        }
        return $employee;
    }
    public function saveEmpToRestaurant($m_id, $store_id)
    {
        $type = $this->db->select('type_id')
            ->where('m_id', $m_id)
            ->get('tb_member')->row();
        if ($type->type_id == 2) {
            $update = $this->db->set('store_id', $store_id)
                ->set('m_status', 0)
                ->where('m_id', $m_id)
                ->update('tb_member');
            if ($update != 0) {
                $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "ผู้ใช้นี้ไม่ได้เป็นพนักงาน"];
        }
        return $noti;
    }
    public function loadTypeEmp()
    {
        $type = $this->db->get('tb_typeuser')->result();
        return $type;
    }
    public function changeType($m_id, $type_id)
    {
        $update = $this->db->set('type_id', $type_id)
            ->where('m_id', $m_id)
            ->update('tb_member');
        if ($update != 0) {
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    public function exitEmpRestaurant($m_id)
    {
        $store_id = $this->db->where('m_id', $m_id)->get('tb_member');
        if ($store_id->num_rows() > 0) {
            $update = $this->db->set('store_id', null)
                ->where('m_id', $m_id)
                ->update('tb_member');
            if ($update != 0) {
                $this->checkEmp_store($store_id->row()->store_id);
                $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "ผู้ใช้นี้ไม่ได้เป็นพนักงาน"];
        }
        return $noti;
    }
    public function editProfileEmp($data)
    {

        $m_id = $data->m_id;
        $getType = $this->db->where('m_id', $m_id)
            ->get('tb_member');

        if ($getType->row()->type_id == 3 || $getType->row()->type_id == 5) {
            $this->db->set('r_active', 0)
                ->where('m_id', $m_id)
                ->update('tb_rider');
        }

        $datas = [
            'address' => $data->address,
            'email' => $data->email,
            'iden_number' => $data->id_card,
            'mobile' => $data->phone,
            'type_id' => $data->type,
            'name' => $data->name
        ];
        $update = $this->db->where('m_id', $m_id)
            ->update('tb_member', $datas);
        if ($data->type == 3 || $data->type == 5) { //rider
            $rider = $this->db->where('m_id', $m_id)
                ->get('tb_rider')->num_rows();
            $set = $this->db->set('store_id', null)
                ->where('m_id', $m_id)
                ->update('tb_member');
            if ($rider > 0) {
                $set = $this->db->set('m_id', $m_id)
                    ->set('r_active', 1)
                    ->set('rider_status', 0)
                    ->where('m_id', $m_id)
                    ->update('tb_rider');
            } else {
                $set = $this->db->set('m_id', $m_id)
                    ->set('r_active', 1)
                    ->set('rider_status', 0)
                    ->insert('tb_rider');
            }
        } elseif ($data->type == 4) {
            $set = $this->db->set('store_id', null)
                ->where('m_id', $m_id)
                ->update('tb_member');
        } elseif ($data->type == 1) {
            $set = $this->db->set('store_id', null)
                ->where('m_id', $m_id)
                ->update('tb_member');
        }
        if ($update != 0) {
            $getUser = $this->db->where('m_id', $m_id)
                ->get('tb_member')->row();
            $this->Line_model->setRichMenu($getUser); //set Rich Menu

            if ($getType->row()->type_id == 2) {
                $this->checkEmp_store($getType->row()->store_id);
            }
            $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ", 'data' => $getUser];
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
        }
        return $noti;
    }
    function checkEmp_store($store_id)
    {
        if ($store_id != null) {
            $emp = $this->db->where('store_id', $store_id)
                ->where('m_active', 1)
                ->where('m_status', 1)
                ->get('tb_member');
            if ($emp->num_rows() > 0) {
            } else {
                $offRes = $this->db->set('store_status', 0)
                    ->where('store_id', $store_id)
                    ->update('tb_store');
                if ($offRes != 0) {
                    $this->Firebase_line->setStore_open($store_id, 0);
                }
            }
        }
    }
    public function reportSalesRestaurant($store_id, $date)
    {
        $pp = 0; //โอน
        $pt = 0; //ปลายทาง
        $amount_pp = 0;
        $amount_pt = 0;

        $order = $this->db->select('od.sum_price,o.payment_id')
            ->join('tb_order as o', 'od.order_id = o.order_id')
            ->join('tb_food as f', 'f.food_id = od.food_id')
            ->like('od.orderDetail_date', $date, 'both')
            ->where('f.store_id', $store_id)
            ->where('o.status_id', 4)
            ->where('od.food_status', 3)
            ->get('tb_orderdetail as od')->result();
        foreach ($order as $key => $value) {
            # code...
            if ($value->payment_id == 1) {
                # code...
                $pp += $value->sum_price;
                $amount_pp++;
            } elseif ($value->payment_id == 2) {
                $pt += $value->sum_price;
                $amount_pt++;
            }
        }
        $data = ["price_pp" => $pp, "price_pt" => $pt, "amount_pp" => $amount_pp, "amount_pt" => $amount_pt, "total_price" => $pp + $pt, "total_amount" => $amount_pt + $amount_pp];
        return ['flag' => 1, 'data' => $data];
    }
    public function reportSalesRider($rider_id, $date)
    {
        $pp = 0; //โอน
        $pt = 0; //ปลายทาง
        $amount_pp = 0;
        $amount_pt = 0;
        $del_costpp = 0;
        $del_costpt = 0;
        $order = $this->db->select('o.order_id,o.payment_id,o.del_cost')
            ->select_sum('od.sum_price')
            ->join('tb_order as o', 'od.order_id = o.order_id')
            ->like('date_time', $date, 'both')
            ->where('o.status_id', 4)
            ->where('od.food_status', 3)
            ->where('rider_id', $rider_id)
            ->group_by('o.order_id')
            ->get('tb_orderdetail as od')->result();
        foreach ($order as $key => $value) {
            if ($value->payment_id == 1) {
                $pp += $value->sum_price;
                $amount_pp++;
                $del_costpp += $value->del_cost;
            } elseif ($value->payment_id == 2) {
                $pt += $value->sum_price;
                $amount_pt++;
                $del_costpp += $value->del_cost;
            }
        }
        $data = [
            "price_pp" => $pp,
            "price_pt" => $pt,
            "amount_pp" => $amount_pp,
            "amount_pt" => $amount_pt,
            "total_price" => $pp + $pt,
            "total_amount" => $amount_pt + $amount_pp,
            "delCost_pp" => $del_costpp,
            "delCost_pt" => $del_costpt,
            "total_delCost" => $del_costpp + $del_costpt
        ];
        return ["flag" => 1, "data" => $data];
    }
    public function grantAdminUse($m_id, $status)
    {
        $active = $this->db->where('m_id', $m_id)->get('tb_member')->row()->m_active;
        if ($active == 1) {
            $update = $this->db->set('m_status', $status)
                ->where('m_id', $m_id)
                ->update('tb_member');
            if ($update != 0) {
                $checkAdmin = $this->db->where('type_id', 1)->where('m_status', 1)->get('tb_member')->result();
                if (!$checkAdmin) {
                    $this->db->set('m_status', 1)
                        ->where('m_id', $m_id)->update('tb_member');
                    $noti = ['flag' => 0, 'ms' => "ไม่สามารถปิดระบบของผู้ดูแลได้ทั้งหมด"];
                } else {

                    $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                }
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "ผู้ดูแลนี้ถูกระงับกานใช้งาน"];
        }
        return $noti;
    }
    public function grantWorkUse($m_id, $status)
    {
        if ($status == 0 || $status == 1) {
            // if ($status==0) {
            $type_id = $this->db->where('m_id', $m_id)->get('tb_member')->row()->type_id;
            if ($type_id == 2) {
                $store_id = $this->db->select('store_id')
                    ->where('m_active', 1)
                    ->where('m_id', $m_id)
                    ->get('tb_member');
                $store_id = $store_id->row()->store_id;
                if ($store_id != null) {
                    $status = $this->db->set('m_status', $status)
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
                    $status = $this->db->set('m_status', $status)
                        ->where('m_id', $m_id)
                        ->update('tb_member');
                    if ($status) {
                        $noti = ['flag' => 1, 'ms' => "บันทึกข้อมูลสำเร็จ"];
                    } else {
                        $noti = ['flag' => 0, 'ms' => "บันทึกข้อมูลไม่สำเร็จ"];
                    }
                }
            } elseif ($type_id == 3 || $type_id == 5) {
                $update = $this->db->set('rider_status', $status)
                    ->where('m_id', $m_id)
                    ->update('tb_rider');
                if ($update != 0) {
                    $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
                }
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "ไม่สามารถเปิดระบบให้กับพนักงานได้"];
        }
        return $noti;
    }
    public function saveEmp($data)
    {
        $user = $this->db->select('emp_id')->get_where('tb_emp', ['emp_username' => $data->username])->num_rows();
        if ($user < 1) {
            if ($data->img->type) {
                $imgPath = explode(',', $data->img->url)[1];
                $imgDecode = base64_decode($imgPath);
                $directory = 'image/emp/emp' . rand(1, 999) . '.' . $data->img->type[1];
                $data->emp_image = $directory;
                $upload = file_put_contents($directory, $imgDecode);
                $data_new  = [
                    'emp_username' => $data->username,
                    'em_password' => md5($data->password),
                    'emp_name' => $data->name,
                    'emp_tel' => $data->tel,
                    'emp_img' => $data->emp_image,
                    'emp_createdate' => date('Y-m-d H:i:s'),
                ];
                $insert = $this->db->insert('tb_emp', $data_new);
                if ($insert) {
                    $noti = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
                } else {
                    $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
                }
            } else {
                $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ ข้อมูลรูปภาพไม่ถูกต้อง"];
            }
        } else {
            $noti = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ มีผู้ใช้งานนี้แล้วในระบบ"];
        }

        return $noti;
    }
}
