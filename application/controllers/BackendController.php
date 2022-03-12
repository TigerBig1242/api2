<?php
defined('BASEPATH') or exit('No direct script access allowed');
class BackendController extends CI_Controller
{
    private $postData;
    private $admin;

    public function __construct()
    {
        parent::__construct();
        // $token = $this->token();
        $this->load->model('Backend_model');
        $this->load->model('Market_model');
        $this->load->model('Rider_model');
        $this->load->model('line/Line_model');
        $this->load->model('line/Line_model');
        $this->load->model('line/Firebase_line');
        $this->postData =  json_decode(file_get_contents("php://input"));
        // if ($token['check'] == true) {
        // $this->admin = $this->get_admin($token['token']);
        // }
    }
    function get_admin($token)
    {
        $admin =  $this->Market_model->get_admin($token);
        if ($admin == 0) {
            // echo json_encode(array('logout' => true));
            echo "คุณไม่ใช่แอดมิน";
            exit();
        } else {
            // return $admin;
        }
    }
    public function loadListAdmin()
    {
        $admin = $this->Backend_model->load_list_admin();
        echo json_encode($admin);
    }
    public function loadListRestaurant()
    {
        $store = $this->Backend_model->load_list_restaurant();
        echo json_encode($store);
    }
    public function saveRestaurant()
    {
        $data = $this->postData;
        $insert = $this->Backend_model->save_restaurant($data);
        echo json_encode($insert);
    }
    public function updateRestaurant()
    {
        $data = $this->postData;
        $insert = $this->Backend_model->update_restaurant($data);
        echo json_encode($insert);
    }
    public function setRestaurantOpen()
    {
        $data = $this->postData;
        $store_id = $data->id;
        $store_status = $data->_isopen;
        $status = $this->Backend_model->set_restaurant_open($store_id, $store_status);
        echo json_encode($status);
    }
    public function deleteRestaurant()
    {
        $data = $this->postData;
        $store_id = $data->id;
        $delete = $this->Backend_model->delete_restaurant($store_id);
        echo json_encode($delete);
    }
    public function loadListCustomer()
    {
        $member = $this->Backend_model->loadListCustomer();
        echo json_encode($member);
    }
    public function grantCustomerUse()
    {
        $data = $this->postData;
        $m_id = $data->id;
        $m_active = $data->_isopen;
        $status = $this->Backend_model->grant_customerUse($m_id, $m_active);
        echo json_encode($status);
    }
    public function loadListRider()
    {
        $rider = $this->Backend_model->load_list_rider();
        echo json_encode($rider);
    }
    public function loadListAdminStore()
    {
        $data = $this->postData;
        $store_id = $data->store_id;
        $employee = $this->Backend_model->load_list_admin_store($store_id);
        echo json_encode($employee);
    }
    public function loadListFoodByRes($store_id = 0)
    {
        $food = $this->Backend_model->load_listFood_byRes($store_id);
        echo json_encode($food);
    }
    public function loadRestaurantProfileByID($store_id = 0)
    {
        $store = $this->Backend_model->load_restaurant_profileByID($store_id);
        echo json_encode($store);
    }
    public function loadLastorderByResID($store_id = 0)
    {
        $order = $this->Backend_model->loadLastorder_byResID($store_id);
        echo json_encode($order);
    }
    public function loadAdminByResID($store_id = 0)
    {
        $employee = $this->Backend_model->loadAdmin_byResID($store_id);
        echo json_encode($employee);
    }
    public function loadTypeMenuByResID($store_id = 0)
    {
        $typeFood = $this->Backend_model->loadTypeMenu_byResID($store_id);
        echo json_encode($typeFood);
    }
    public function updateTypeMenuByResID()
    {
        $data = $this->postData;
        $typefood_id = $data->id;
        $typefood_name = $data->name;
        $update = $this->Backend_model->updateTypeMenu_byResID($typefood_id, $typefood_name);
        echo json_encode($update);
    }
    public function insertTypeMenuByResID()
    {
        $data = $this->postData;
        $insert = $this->db->insert('tb_typefood', $data);
        if ($insert != 0) {
            $chack = ['flag' => 1, 'ms' => "บันทึกสำเร็จ"];
            echo json_encode($chack);
        } else {
            $chack = ['flag' => 0, 'ms' => "บันทึกไม่สำเร็จ"];
            echo json_encode($chack);
        }
    }
    public function deleteTypeMenuByResID()
    {
        $data = $this->postData;
        $typefood_id = $data->id_menu;
        $delete = $this->Backend_model->deteleTypeMenu_byResID($typefood_id);
        echo json_encode($delete);
    }
    public function getType_null($store_id = 0)
    {
        $food = $this->Backend_model->typeFood_null($store_id);
        echo json_encode($food);
    }
    public function insertMenuByResID()
    {
        $data = $this->postData;
        $insert = $this->Backend_model->insertMenu_byResID($data);
        echo json_encode($insert);
    }
    public function updateMenuByResID()
    {
        $data = $this->postData;
        $update = $this->Backend_model->updateMenu_byResID($data);
        echo json_encode($update);
    }
    public function deleteMenuByResID()
    {
        $data = $this->postData;
        $food_id = $data->id_menu;
        $delete = $this->Backend_model->deleteMenu_byResID($food_id);
        echo json_encode($delete);
    }
    public function loadListHistoryOrderRestaurantByID($store_id = 0, $page = 0)
    {
        $order = $this->Backend_model->loadListHistoryOrder_restaurantByID($store_id, $page);
        echo json_encode($order);
    }
    public function moreDetailOrderByID()
    {
        $data = $this->postData;
        $order_id = $data->order_id;
        $store_id = $data->res_id;
        $order = $this->Backend_model->moreDetailOrder_byID($store_id, $order_id);
        echo json_encode($order);
    }
    public function loadProfileCustomer($m_id = 0)
    {
        $member = $this->db->join('tb_typeuser as tu', 'tu.type_id=m.type_id')
            ->where("m.m_id", $m_id)
            ->get('tb_member as m')->row();
        echo json_encode($member);
    }
    public function loadOrderCustomerByID($m_id = 0, $page = 0)
    {
        $order = $this->Backend_model->loadOrder_customerByID($m_id, $page);
        echo json_encode($order);
    }
    public function moreDetailOrderCusByID()
    {
        $data = $this->postData;
        $order_id = $data->order_id;
        $order = $this->Backend_model->moreDetailOrder_cusByID($order_id);
        echo json_encode($order);
    }
    public function loadRiderProfileByID($rider_id = 0)
    {
        $rider = $this->Backend_model->loadRiderProfile_byID($rider_id);
        echo json_encode($rider);
    }
    public function loadRiderOrderLastByID($rider_id = 0)
    {
        $order = $this->Backend_model->loadRiderOrderLast_byID($rider_id);
        echo json_encode($order);
    }
    public function moreDetailOrderRiderByID()
    {
        $data = $this->postData;
        $order_id = $data->order_id;
        $rider_id = $data->rider_id;
        $order = $this->Backend_model->moreDetailOrderRider_byID($order_id, $rider_id);
        echo json_encode($order);
    }
    public function loadListHistoryOrderRiderByID($rider_id = 0, $page = 0)
    {
        $order = $this->Backend_model->loadListHistoryOrderRider_byID($rider_id, $page);
        echo json_encode($order);
    }
    public function loadOrderAll($page = 0)
    {
        $order = $this->Backend_model->loadOrder_All($page);
        echo json_encode($order);
    }
    public function moreDetailOrderAllByID($order_id = 0)
    {
        $order = $this->Backend_model->moreDetailOrderAll_byID($order_id);
        echo json_encode($order);
    }
    public function loadUserAllReoport()
    {
        $report = $this->Backend_model->loadUser_allReoport();
        echo json_encode($report);
    }
    public function loadReportGraphByYear()
    {
        $data = $this->postData;
        $year = $data->year;
        $report = $this->Backend_model->loadReportGraphByYear($year);
        echo json_encode($report);
    }
    public function loadReportGraphByMount()
    {
        $data = $this->postData;
        $year = $data->year;
        $month = $data->month;
        $report = $this->Backend_model->loadReportGraphByMount($year, $month);
        echo json_encode($report);
    }
    public function findOrder()
    {
        $data = $this->postData;
        $keywords = $data->str;
        $page = $data->page;

        $findOrder = $this->Backend_model->findOrder($keywords, $page);
        echo json_encode($findOrder);
    }
    public function findCustomer()
    {
        $data = $this->postData;
        $keywords = $data->str;
        $page = $data->page;
        $findOrder = $this->Backend_model->findCustomer($keywords, $page);
        echo json_encode($findOrder);
    }
    public function findRider()
    {
        $data = $this->postData;
        $keywords = $data->str;
        $page = $data->page;

        $findOrder = $this->Backend_model->findRider($keywords, $page);
        echo json_encode($findOrder);
    }
    public function findRestaurant()
    {
        $data = $this->postData;
        $keywords = $data->str;
        $page = $data->page;
        $findOrder = $this->Backend_model->findRestaurant($keywords, $page);
        echo json_encode($findOrder);
    }
    public function loadStateOrder()
    {
        $status = $this->Backend_model->loadStateOrder();
        echo json_encode($status);
    }
    public function orderResChangeStatus()
    {
        $data = $this->postData;
        $order_id = $data->order_id;
        $store_id = $data->res_id;
        $status = $data->status;
        $update = $this->Backend_model->orderResChangeStatus($order_id, $store_id, $status);
        echo json_encode($update);
    }
    public function orderIDChangeStatus()
    {
        $data = $this->postData;
        $order_id = $data->order_id;
        $status = $data->status;
        $update = $this->Backend_model->orderIDChangeStatus($order_id, $status);
        echo json_encode($update);
    }
    public function loadPlanRider($orderType=0)
    {
        // $data = $this->postData;
        // $orderType = $data->order_type;
        $rider = $this->Backend_model->loadPlanRider($orderType);
        echo json_encode($rider);
    }
    public function selectRiderPlan()
    {
        $data = $this->postData;
        $order_id = $data->order_id;
        $rider_id = $data->rider_id;
        $update = $this->Backend_model->selectRiderPlan($order_id, $rider_id);
        echo json_encode($update);
    }
    public function findTypeRes()
    {
        $data = $this->postData;
        $search = $data->search;
        $type = $this->Backend_model->findTypeRes($search);
        echo json_encode($type);
    }
    public function loadTypeRes()
    {
        $typeStore = $this->Backend_model->loadTypeRes();
        echo json_encode($typeStore);
    }
    public function loadIcons()
    {
        $img = $this->Backend_model->loadIcons();
        echo json_encode($img);
    }
    public function saveTypeRes()
    {
        $data = $this->postData;

        $addType = $this->Backend_model->saveTypeRes($data);
        echo json_encode($addType);
    }
    public function updateTypeRes()
    {
        $data = $this->postData;

        $addType = $this->Backend_model->updateTypeRes($data);
        echo json_encode($addType);
    }
    public function deleteTypeRes()
    {
        $data = $this->postData;
        $delete = $this->Backend_model->deleteTypeRes($data);
        echo json_encode($delete);
    }
    public function loadListEmployee()
    {
        $employee = $this->Backend_model->loadListEmployee();
        echo json_encode($employee);
    }
    public function isLoginByLine()
    {
        $data = $this->postData;
        if (isset($data->userId)) {
            # code...
            $insert = $this->Backend_model->isLoginByLine($data);
            echo json_encode($insert);
        }
    }
    public function loadEmpProfileByID($m_id = 0)
    {
        $employee = $this->Backend_model->loadEmpProfileBy($m_id);
        echo json_encode($employee);
    }
    public function saveEmpToRestaurant()
    {
        $data = $this->postData;
        $store_id = $data->res_id;
        $m_id = $data->m_id;
        $update = $this->Backend_model->saveEmpToRestaurant($m_id, $store_id);
        echo json_encode($update);
    }
    public function loadTypeEmp()
    {
        $type = $this->Backend_model->loadTypeEmp();
        echo json_encode($type);
    }
    public function changeType()
    {
        $data = $this->postData;
        $m_id = $data->m_id;
        $type_id = $data->type_id;
        $update = $this->Backend_model->changeeType($m_id, $type_id);
        echo json_encode($update);
    }
    public function exitEmpRestaurant()
    {
        $data = $this->postData;
        $m_id = $data->m_id;
        $update = $this->Backend_model->exitEmpRestaurant($m_id);
        echo json_encode($update);
    }
    public function editProfileEmp()
    {
        $data = $this->postData;
        $update = $this->Backend_model->editProfileEmp($data);
        echo json_encode($update);
    }
    public function reportSalesRestaurant()
    {
        $data = $this->postData;
        $date = $data->date;
        $store_id = $data->res_id;
        $report = $this->Backend_model->reportSalesRestaurant($store_id, $date);
        echo json_encode($report);
    }
    public function reportSalesRider()
    {
        $data = $this->postData;
        $date = $data->date;
        $rider_id = $data->rider_id;
        $report = $this->Backend_model->reportSalesRider($rider_id, $date);
        echo json_encode($report);
        // echo "a";
    }
    public function grantAdminUse()
    {
        $data = $this->postData;
        $m_id = $data->id;
        $status = $data->_isopen;
        $update = $this->Backend_model->grantAdminUse($m_id,$status);
        echo json_encode($update);
    }
    public function grantWorkUse()
    {
        $data = $this->postData;
        $m_id = $data->id;
        $status = $data->_isopen;
        $update = $this->Backend_model->grantWorkUse($m_id,$status);
        echo json_encode($update);
    }
    function token()
    {
        $actual_link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

        if (strpos($actual_link, "BackendController/isLoginByLine") || strpos($actual_link, "BackendController/loadReportGraphByMount") || strpos($actual_link, "BackendController/loadReportGraphByYear") || strpos($actual_link, "BackendController/loadUserAllReoport")) {
            return ['check' => false];
        } else {
            $headers = apache_request_headers();
            if (isset($headers['authorization'])) {
                $authorization = str_replace("Bearer ", "", $headers['authorization']);
                $token = $authorization;
                if ($token != 'null') {
                    $user = $this->db->get_where("tb_member", array('token' => $token));
                    if ($user->num_rows() > 0) {
                        $this->session->set_userdata("user", $user->row());
                        $this->db->update("tb_member", array('last_active' => date("Y-m-d H:i:s")), array('m_id' => $user->row()->m_id));
                        return  ['check' => true, 'token' => $token];
                    } else {
                        echo json_encode(array('logout' => true));
                        exit();
                    }
                } else {
                    echo json_encode(array('logout' => true));
                    exit();
                }
            } else {
                echo json_encode(array('logout' => true));
                exit();
            }
        }
    }
    public function saveEmp()
    {
        $data = $this->postData;
        $emp = $this->Backend_model->saveEmp($data);
        echo json_encode($emp);
    }
}
