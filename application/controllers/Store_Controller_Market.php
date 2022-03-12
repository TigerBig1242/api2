<?php
defined('BASEPATH') or exit('No direct script access allowed');

class StoreController extends CI_Controller
{
    private $dataEmpty = ['flag' => 0, 'ms' => "ไม่มีข้อมูล"];
    private $store_id;
    private $postData;
    public function __construct()
    {
        parent::__construct();
        // $token = $this->token();
        $this->load->model('Store_model');
        $this->load->model('Market_model');
        $this->load->model('line/Line_model');
        $this->load->model('line/Firebase_line');
        $this->postData =  json_decode(file_get_contents("php://input"));
        // if ($token['check'] == true) {
            // $store_id = $this->get_store_id($token['token']);
        // }
    }

    function get_store_id($token)
    {
        $store_id =  $this->Market_model->get_store_id($token);
        if ($store_id == 0) {
            echo json_encode(array('logout' => true));
            exit();
        } else {
            return $store_id;
        }
    }

    public function storeGet_food($store_id = 0)
    {
        $food = $this->Store_model->store_get_food($store_id);
        echo json_encode($food);
    }
    public function storeGet_listFood($store_id = 0)
    {
        $food = $this->Store_model->store_get_listFood($store_id);
        echo json_encode($food);
    }
    public function storeGet_food_inBin($store_id = 0)
    {
        $food = $this->Store_model->store_get_food_inBin($store_id);
        echo json_encode($food);
    }
    public function storeGet_foodId($store_id = 0, $food_id = 0)
    {
        $food = $this->Store_model->store_get_foodId($store_id, $food_id);
        echo json_encode($food);
    }
    public function storeRemove_typeFood()
    {
        $data = $this->postData;
        if ($data) {
            $typefood_id = $data->typefood_id;
            $store_id = $data->store_id;
            $remove = $this->Store_model->store_remove_typeFood($store_id, $typefood_id);
            echo json_encode($remove);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }

    public function storeGet_typeFood($store_id = 0)
    {
        $typeFood = $this->Store_model->store_get_typeFood($store_id);
        echo json_encode($typeFood);
    }
    // เพิ่ม อาหาร
    public function storeAdd_food()
    {
        $data = $this->postData;
        if ($data) {
            $addFood = $this->Store_model->store_add_food($data);
            echo json_encode($addFood);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    
    public function storeAdd_typeFood()
    {
        $data = $this->postData;
        if ($data) {
            $addTypeFood = $this->Store_model->store_add_typeFood($data);
            echo json_encode($addTypeFood);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    
    public function storeEdit_typeFood()
    {
        $data = $this->postData;
        $typefood_id = $data->typefood_id;
        $typefood_name = $data->typefood_name;
        $update = $this->Store_model->store_edit_typeFood($typefood_id, $typefood_name);
        echo json_encode($update);
    }

    public function storeProfile($store_id = 0)
    {
        $profile = $this->Store_model->store_profile($store_id);
        echo json_encode($profile);
    }
    public function storeGet_employee($store_id = 0)
    {
        if ($store_id !== 0) {
            $employee = $this->Store_model->store_get_employee($store_id);
            echo json_encode($employee);
        } else {
            json_encode($this->error);
        }
    }

    public function employeeOn_off()
    {
        $data = $this->postData;
        if ($data) {
            $m_id = $data->m_id;
            $m_status = $data->m_status;
            $status = $this->Store_model->employee_on_off($m_id, $m_status);
            echo json_encode($status);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    // แก้ไขอาหาร
    public function storeEdit_food()
    {
        $data = $this->postData;
        if ($data) {
            $food_id = $data->food_id;
            $edit = $this->Store_model->store_edit_food($food_id, $data);
            echo json_encode($edit);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }

    public function storeStatus_food()
    {
        $data = $this->postData;
        if ($data) {
            $food_id = $data->food_id;
            $status = $data->status;
            $status =  $this->Store_model->sotre_status_food($food_id, $status);
            echo json_encode($status);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    //ลบอาหาร
    public function storeRemove_food()
    {
        $data = $this->postData;
        if ($data) {
            $food_id = $data->food_id;
            $this->Store_model->store_remove_food($food_id);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    public function storeRestore_food()
    {
        $data = $this->postData;
        if ($data) {
            $food_id = $data->food_id;
            $restore = $this->Store_model->store_restore_food($food_id);
            echo json_encode($restore);
        }
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
    public function storeOrder_in($store_id = 0, $page = 0)
    {
        $order = $this->Store_model->store_order_in($store_id, $page);
        echo json_encode($order);
    }
    public function storeOrder_inDetail($store_id = 0, $order_id = 0)
    {
        $order = $this->Store_model->store_order_inDetail($store_id, $order_id);
        echo json_encode($order);
    }

    //Function detail order ใหม่
    public function storeOrder_inDetail_New()
    {
        // $orders = $this->Store_model->store_order_inDetail_New();  
        //     echo json_encode($orders);
        $data = $this->postData;
        if ($data) {
            $order_id = $data->order_id;
            $food_id = $data->food_id;
            $amount = $data->amount;
            $sum_price = $data->sum_price;
            $orders = $this->Store_model->store_order_inDetail_New($order_id, $food_id, $amount, $sum_price);  
            echo json_encode($orders);
        }   
    }
    // save list edit menu to temporder before 
    public function orderTemp() {
        $tempOrder = $this->postData;
        // pre($tempOrder);
        // if($tempOrder) {
        //     $order_id = $tempOrder->order_id;
        //     $food_id = $tempOrder->food_id;
        //     $amount = $tempOrder->amount;
        //     $sum_price = $tempOrder->sum_price;
        //     $orderstemp = $this->Store_model->orderTempDetail($order_id, $food_id, $amount, $sum_price);  
        //     echo json_encode($orderstemp);
        // }
        $orderstemp = $this->Store_model->orderTempDetail($tempOrder);
        // echo json_encode($orderstemp);
    
    }


    public function storeStatus_order()
    {
        $data = $this->postData;
        if ($data) {
            $store_id = $data->store_id;
            $order_id = $data->order_id;
            $statusOrStore_id = $data->statusOrStore_id;
            $status = $this->Store_model->store_order_status($store_id, $order_id, $statusOrStore_id);
            echo json_encode($status);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }

    public function storeCancel_order()
    {
        $data = $this->postData;
        if ($data) {
            $store_id = $data->store_id;
            $order_id = $data->order_id;
            $status = $this->Store_model->store_cancel_order($order_id, $store_id);
            echo json_encode($status);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }

    public function storeChange_status()
    {
        $data = $this->postData;
        if ($data) {
            $store_id = $data->store_id;
            $store_status = $data->store_status;
            $status = $this->Store_model->store_change_status($store_id, $store_status);
            echo json_encode($status);
        } else {
            echo json_encode($this->dataEmpty);
        }
    }
    public function storeGet_status($store_id = 0)
    {
        if ($store_id != 0) {
            $status = $this->Store_model->store_get_status($store_id);
            echo json_encode($status);
        }
    }

    public function storeOrder_history($store_id = 0, $date = 0)
    {
        $history = $this->Store_model->store_order_history($store_id, $date);
        echo json_encode($history);
    }
    public function storeGet_detail($store_id = 0, $order_id = 0)
    {
        $detail = $this->Store_model->store_get_detail($store_id, $order_id);
        echo json_encode($detail);
    }



    public function storeSales_summary($store_id = 0, $date = 0)
    {
        $sales = $this->Store_model->store_sales_summary($store_id, $date);
        echo json_encode($sales);
    }
    public function upload()
    {
        $newName = time() . "_" . uniqid();
        $image = $this->input->post("image");

        $image_name =  $newName;

        //convert base64 to image and save in specific location/dir
        $directory = "image/food/" . $image_name . ".png";
        $check = file_put_contents(
            $directory,
            base64_decode(
                str_replace('data:image/png;base64,', '', $image)
            )
        );
        if ($check != 0) {
            echo json_encode(array('flag' => 1, 'url' => $directory));
        } else {
            echo json_encode(array('flag' => 0));
        }
    }
    public function login()
    {
        $data = $this->postData;
        if ($data) {
            $insert = $this->Store_model->login($data);
            echo json_encode($insert);
        }
    }

    function token()
    {
        $actual_link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        if (strpos($actual_link, "StoreController/login")) {
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
                        // echo json_encode(array('logout' => true));
                        echo $token;
                        exit();
                    }
                } else {
                    // echo json_encode(array('logout' => true));
                    echo "d";

                    exit();
                }
            } else {
                // echo json_encode(array('logout' => true));
                echo "f";

                exit();
            }
        }
    }
    
    public function userLogin() {
       $data = $this->postData;
       $select = $this->Store_model->userLogin($data);  
            echo json_encode($select);
    }
    
    public function showDataStore($store_id) {
        //$data = $this->postData;
        $select = $this->Store_model->showDataStore($store_id);
            echo json_encode($select);
    }

    public function get_staff ($store_id) {
        if ($store_id !== 0) {
            $select = $this->Store_model->store_get_staff($store_id);
            echo json_encode($select);
        } else {
            json_encode($this->error);
        }
    }

    public function employee_Switch() {
        $data = $this->postData;
        if ($data) {
            $emp_id = $data->emp_id;
            $emp_status = $data->emp_status;
            $status = $this->Store_model->employee_Switch_On_Off($emp_id, $emp_status);
            echo json_encode($status);
        }
    }

}
