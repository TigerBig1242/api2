<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Flex_store_model extends CI_Model
{

    public function store_order($order_id, $store_id)
    {

        $list_food = '';
        $order =  $this->db->select('*')
            ->where('order_id', $order_id)
            ->get('tb_order');
        if ($order->num_rows() > 0) {
            $order_num = $order->row()->order_num;
            
            $payment_id = $order->row()->payment_id;
        }
        if ($payment_id == 1) {
            $message = 'ชำระแล้ว';
        } else {
            $message = 'ชำระปลายทาง';
        }
        $order_price=0;
        $detail = $this->db->select('f.food_name,od.food_price,od.sum_price,od.amount,')
            ->join('tb_food as f', 'f.food_id=od.food_id')
            ->where('od.order_id', $order_id)
            ->where('f.store_id', $store_id)
            ->get('tb_orderdetail as od')->result();
        $counter = count($detail);
        $amount = 0;
        for ($i = 0; $i < $counter; $i++) {

            $food_name = $detail[$i]->food_name;
            $food_amount = $detail[$i]->amount;
            $food_price = $detail[$i]->food_price;
            $amount += $detail[$i]->amount;
            $sum_price = $detail[$i]->sum_price;
            $order_price +=$detail[$i]->sum_price;
            $listFood = '{
                    "type": "box",
                    "layout": "horizontal",
                    "contents": [
                      {
                        "type": "text",
                        "text": "x' . $food_amount . '  ' . $food_name . '(' . $food_price . ')",
                        "size": "sm",
                        "color": "#555555",
                        "flex": 0
                      },
                      {
                        "type": "text",
                        "text": "      ' . $sum_price . ' ฿",
                        "size": "sm",
                        "color": "#111111",
                        "align": "end"
                      }
                    ]
                  },';
            $list_food .= $listFood;
        }
        $json = '{
            "type": "flex",
            "altText": "คำสั่งซื้อของคุณ",
            "contents": {
                "type": "bubble",
                "size": "kilo",
                "body": {
                  "type": "box",
                  "layout": "vertical",
                  "contents": [
                    {
                      "type": "text",
                      "text": "ID : ' . $order_num . '",
                      "weight": "bold",
                      "size": "xl",
                      "color": "#81B214",
                      "align": "center",
                      "style": "normal",
                      "decoration": "none"
                    },
                    {
                      "type": "separator",
                      "margin": "lg"
                    },
                    {
                      "type": "box",
                      "layout": "vertical",
                      "margin": "lg",
                      "spacing": "sm",
                      "contents": [
                       ' . $list_food . '


                        {
                          "type": "separator",
                          "margin": "lg"
                        },
                        {
                          "type": "box",
                          "layout": "horizontal",
                          "margin": "lg",
                          "contents": [
                            {
                              "type": "text",
                              "text": "จำนวน",
                              "size": "sm",
                              "color": "#555555"
                            },
                            {
                              "type": "text",
                              "text": "' . $amount . '",
                              "size": "sm",
                              "color": "#111111",
                              "align": "end"
                            }
                          ]
                        },
                        {
                          "type": "box",
                          "layout": "horizontal",
                          "contents": [
                            {
                              "type": "text",
                              "text": "ราคา",
                              "size": "sm",
                              "color": "#555555"
                            },
                            {
                              "type": "text",
                              "text": "' . $order_price . ' ฿",
                              "size": "sm",
                              "color": "#111111",
                              "align": "end"
                            }
                          ]
                        }
                      ]
                    },
                    {
                      "type": "separator",
                      "margin": "xxl"
                    },
                    {
                      "type": "box",
                      "layout": "horizontal",
                      "margin": "md",
                      "contents": [
                        {
                          "type": "text",
                          "text": "' . $message . '",
                          "size": "xs",
                          "color": "#aaaaaa",
                          "flex": 0
                        }
                      ]
                    }
                  ]
                },
                "styles": {
                  "footer": {
                    "separator": true
                  }
                }
              }
          }';
        return $json;
    }
}
