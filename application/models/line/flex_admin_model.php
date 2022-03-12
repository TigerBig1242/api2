<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Flex_admin_model extends CI_Model
{

    public function adminCheck_slip($order_id)
    {
        $slip = $this->db->select('slip_image')
            ->where('order_id', $order_id)
            ->get('tb_order');
        $slip_image = $slip->row()->slip_image;
        $slip_image = json_decode($slip_image);

        $slip_image = photo() . $slip_image[count($slip_image)-1]->slip;

        $order_price = 0;
        $list_food = '';
        $order =  $this->db->where('order_id', $order_id)
            ->get('tb_order');
        if ($order->num_rows() > 0) {
            $order_num = $order->row()->order_num;
            $del_cost = $order->row()->del_cost;
        }





        $detail = $this->db->select('f.food_name,od.food_price,od.sum_price,od.amount')
            ->join('tb_food as f', 'f.food_id=od.food_id')
            ->where('od.order_id', $order_id)
            ->get('tb_orderdetail as od')->result();
        // $counter = count($detail);
        $amount = 0;
        // for ($i = 0; $i < $counter; $i++) {

        //   $food_name = $detail[$i]->food_name;
        //   $sum_price = $detail[$i]->sum_price;
        //   $food_amount = $detail[$i]->amount;
        //   $food_price = $detail[$i]->food_price;
        //   $amount += $detail[$i]->amount;
        //   $order_price += $detail[$i]->sum_price;

        //   $listFood = '{
        //                     "type": "box",
        //                     "layout": "horizontal",
        //                     "contents": [
        //                       {
        //                         "type": "text",
        //                         "text": "x' . $food_amount . '  ' . $food_name . '(' . $food_price . ')",
        //                         "size": "sm",
        //                         "color": "#555555",
        //                         "flex": 0
        //                       },
        //                       {
        //                         "type": "text",
        //                         "text": "      ' . $sum_price . ' ฿",
        //                         "size": "sm",
        //                         "color": "#111111",
        //                         "align": "end"
        //                       }
        //                     ]
        //                   },';
        //   $list_food .= $listFood;
        // }
        foreach ($detail as $key => $value) {
            $food_name = $value->food_name;
            $sum_price = $value->sum_price;
            $food_amount = $value->amount;
            $food_price = $value->food_price;
            $amount += $value->amount;
            $order_price += $value->sum_price;

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
        $totalPtice = $del_cost + $order_price;
        $json = '{
                    "type": "flex",
                    "altText": "คำสั่งซื้อของคุณ",
                    "contents": {
                        "type": "bubble",
                        "size": "kilo",
                        "hero": {
                            "type": "image",
                            "url": "' . $slip_image . '",
                            "size": "full",
                            "aspectMode": "fit",
                            "action": {
                                "type": "uri",
                                "uri": "https://linecorp.com"
                            },
                            "position": "relative"
                        },
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
                                        },
                                        {
                                            "type": "box",
                                            "layout": "horizontal",
                                            "contents": [
                                                {
                                                    "type": "text",
                                                    "text": "ค่าจัดส่ง",
                                                    "size": "sm",
                                                    "color": "#555555"
                                                },
                                                {
                                                    "type": "text",
                                                    "text": "' . $del_cost . ' ฿",
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
                                                    "text": "ราคารวม",
                                                    "size": "sm",
                                                    "color": "#555555"
                                                },
                                                {
                                                    "type": "text",
                                                    "text": "' . $totalPtice . ' ฿",
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
                                }
                            ]
                        },
                        "footer": {
                            "type": "box",
                            "layout": "vertical",
                            "spacing": "md",
                            "contents": [
                                {
                                    "type": "button",
                                    "style": "primary",
                                    "action": {
                                        "type": "postback",
                                        "label": "สลิปถูกต้อง ยืนยัน",
                                        "data": "' . $order_id . ',SlipTrue"
                                    }
                                },
                                {
                                    "type": "box",
                                    "layout": "vertical",
                                    "margin": "lg",
                                    "spacing": "sm",
                                    "contents": [
                                        {
                                            "type": "box",
                                            "layout": "horizontal",
                                            "spacing": "sm",
                                            "contents": [
                                                {
                                                    "type": "button",
                                                    "style": "primary",
                                                    "action": {
                                                        "type": "postback",
                                                        "label": "แนบใหม่",
                                                        "data": "' . $order_id . ',SlipTryAgain"
                                                    },
                                                    "color": "#E79E4F"
                                                },
                                                {
                                                    "type": "button",
                                                    "style": "primary",
                                                    "action": {
                                                        "type": "postback",
                                                        "label": "ยกเลิก",
                                                        "data": "' . $order_id . ',SlipFalse"
                                                    },
                                                    "color": "#D72323"
                                                }
                                            ]
                                        }
                                    ]
                                }
                            ]
                        }
                    }
                }';
        return $json;
    }
}
