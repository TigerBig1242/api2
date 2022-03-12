<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'firebase_line/vendor/autoload.php';

use Firebase\FirebaseLib;

class Firebase_line extends CI_Model
{
    
    // private  $URL = 'https://z-one-7a721-default-rtdb.asia-southeast1.firebasedatabase.app/';
    // private  $TOKEN = 'XiFYUhYl9hDm9gWAuSIbzvaHaB0hEAsg7nJF9fHE';
    private  $URL = 'https://market-5cbca-default-rtdb.asia-southeast1.firebasedatabase.app/';
    private  $TOKEN = 'g9crkUgXf1NdYyJ4klbc1DX4TpJSab26QxZJOArZ';
    private float $ramdom;
    private $firebase;
    public function __construct()
    {
        parent::__construct();
        $this->firebase = new FirebaseLib($this->URL, $this->TOKEN);
        $this->ramdom = rand(10, 99) / 10000;
    }
    // public function setFireBaseChatLine($node = null, $value = null) {

    //     $firebase = new FirebaseLib($this->URL, $this->TOKEN);
    //     $PATH = $node;
    //     $value = $value;
    //     $val = $firebase->set($PATH, $node);
    // }
    public function firebaseRider_receive($order_id)
    {

        if (!empty($order_id)) {
            $order = $this->db->where('order_id', $order_id)
                ->get('tb_order')->row();

            $store = $this->db->select('f.store_id')
                ->join('tb_food as f', 'f.food_id = od.food_id')
                ->group_by('f.store_id')
                ->where('od.order_id', $order_id)
                ->get('tb_orderdetail as od')->result();

            $this->firebase->set('rider/rider_id' . $order->rider_id . '/status_list', $this->ramdom);
            $this->firebase->set('m_id/m_id' . $order->m_id . '/status_list', $this->ramdom);
            $this->firebase->set('order/by_id/' . $order->order_id, $this->ramdom);
            foreach ($store as $key => $value) {
                $this->firebase->set('store/store_id' . $value->store_id . '/status_list', $this->ramdom);
            }
        }
    }

    public function adminConfirm_order($order_id, $s)
    {
        //true = ยืนยันสลิป ต้อง random ค่า
        //false = ยกเลิก/แก้ไข ต้อง set ค่า

        if (!empty($order_id)) {
            $order = $this->db->where('order_id', $order_id)
                ->get('tb_order')->row();

            if ($s == true) {
                $this->firebase->set('m_id/m_id' . $order->m_id . '/status_list', $this->ramdom);
                $this->firebase->set('order/by_id/' . $order->order_id, $this->ramdom);
                $this->Firebase_line->removeFirebase($order_id); //remove FireBase
            } else {
                $this->firebase->set('m_id/m_id' . $order->m_id . '/status_list', $this->ramdom);
                $this->firebase->set('m_id/m_id' . $order->m_id . '/check_status/order_id', $order_id);
                $this->firebase->set('m_id/m_id' . $order->m_id . '/check_status/status', $order->status_id);
                $this->firebase->set('order/by_id/' . $order->order_id, $this->ramdom);
            }
        }
    }
    public function adminChangeStatus($order_id)
    {
        $order = $this->db->where('order_id', $order_id)
            ->get('tb_order')->row();
        $m_id = $order->m_id;
        $status = $order->status_id;
        if ($m_id) {
            if ($status == 4 || $status == 5 || $status == 7) {

                // $this->firebase->set('m_id/m_id' . $m_id . '/status_list', $this->ramdom);
                $this->firebase->set('m_id/m_id' . $m_id . '/check_status/order_id', $order_id);
                $this->firebase->set('m_id/m_id' . $m_id . '/check_status/status', $status);
                $this->firebase->set('order/by_id/' . $order->order_id, $this->ramdom);

                if ($order->rider_id != null && ($status == 5 || $status == 4)) {
                    $this->firebase->set('rider/rider_id' . $order->rider_id . '/status_list', $this->ramdom);
                    $this->firebase->set('rider/rider_id' . $order->rider_id . '/check_status/order_id', $order_id);
                    $this->firebase->set('rider/rider_id' . $order->rider_id . '/check_status/status', $status);
                }
            } else {

                // $this->firebase->set('m_id/m_id' . $m_id . '/status_list', $this->ramdom);
                $this->firebase->set('order/by_id/' . $order->order_id, $this->ramdom);

                if ($order->rider_id != null) {
                    $this->firebase->set('rider/rider_id' . $order->rider_id . '/status_list', $this->ramdom);
                }
                // }
                $store = $this->db->select('f.store_id')
                    ->join('tb_food as f', 'f.food_id = od.food_id')
                    ->group_by('f.store_id')
                    ->where('od.order_id', $order_id)
                    ->get('tb_orderdetail as od')->result();
                foreach ($store as $key => $value) {
                    $this->firebase->set('store/store_id' . $value->store_id . '/status_list', $this->ramdom);
                }
            }
        }
    }
    public function removeFirebase($order_id = 0)
    {
        $this->firebase->delete('order/by_id/' . $order_id);
    }
    public function setStore_open($store_id, $store_status)
    {
        if ($store_status == 1) $status = true;
        else $status = false;
        $this->firebase->set('store/store_id' . $store_id . '/status_open', $status);
    }
}
