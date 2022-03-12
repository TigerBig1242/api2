<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Flex_rider_model extends CI_Model
{
  private $apiKey = 'AIzaSyADQl79AootGyhKCW8MQ8xxz561gPFu0rA';

  public function send_flex_toRider($order_id)
  {
    $payment = '';
    $tb_order = $this->db->select('order_num,order_price,payment_id')
      ->where('order_id', $order_id)
      ->get('tb_order');
    if ($tb_order->num_rows() > 0) {
      $order_num = $tb_order->row()->order_num;
      $order_price = $tb_order->row()->order_price;
      $payment_id = $tb_order->row()->payment_id;
    }

    if ($payment_id == 1) {
      $money = ' ';
      $payment = 'ชำระแล้ว';
    } elseif ($payment_id == 2) {
      $money = "$order_price บาท";
      $payment = 'ชำระปลายทาง';
    }

    $json = '{
      "type": "flex",
      "altText": "หมายเลข order ของคุณ",
      "contents": 
            {
              "type": "bubble",
              "body": {
                "type": "box",
                "layout": "vertical",
                "contents": [
                  {
                    "type": "text",
                    "text": "ID : ' . $order_num . '",
                    "weight": "bold",
                    "size": "xxl",
                    "margin": "md",
                    "align": "center",
                    "color": "#1DB446"
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
                        "text": "' . $payment . '",
                        "size": "xs",
                        "color": "#aaaaaa",
                        "flex": 0
                      },
                      {
                        "type": "text",
                        "text": "' . $money . '",
                        "color": "#aaaaaa",
                        "size": "xs",
                        "align": "end"
                      }
                    ]
                  }
                ]
              },
              "footer": {
                "type": "box",
                "layout": "vertical",
                "contents": [
                  {
                    "type": "button",
                    "action": {
                      "type": "uri",
                      "label": "ดูรายละเอียด",
                      "uri": "http://linecorp.com/"
                    },
                    "style": "secondary"
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


  public function rider_orderIn($m_id, $order_id)
  {
    $location = $this->db->select('image_map,address')
      ->where('order_id', $order_id)
      ->get('tb_order');
    if ($location->num_rows() > 0) {
      $image_map = photo() . $location->row()->image_map;
      $address = $location->row()->address;
      $json = '{
      "type": "flex",
      "altText": "มีงานเข้า",
      "contents":
      {
          "type": "bubble",
          "hero": {
            "type": "image",
            "url": "' . $image_map . '",
            "size": "full",
            "aspectRatio": "20:13",
            "aspectMode": "cover",
            "action": {
              "type": "uri",
              "uri": "http://linecorp.com/"
            }
          },
          "body": {
            "type": "box",
            "layout": "vertical",
            "contents": [
              {
                "type": "text",
                "text": "สถานที่จัดส่ง",
                "weight": "bold",
                "size": "xl"
              },
              {
                "type": "box",
                "layout": "vertical",
                "margin": "lg",
                "spacing": "sm",
                "contents": [
                  {
                    "type": "box",
                    "layout": "baseline",
                    "spacing": "sm",
                    "contents": [
                      {
                        "type": "text",
                        "text": "Place",
                        "color": "#aaaaaa",
                        "size": "sm",
                        "flex": 1
                      },
                      {
                        "type": "text",
                        "text": "' . $address . '",
                        "wrap": true,
                        "color": "#666666",
                        "size": "sm",
                        "flex": 5
                      }
                    ]
                  }
                ]
              }
            ]
          },
          "footer": {
            "type": "box",
            "layout": "vertical",
            "spacing": "sm",
            "contents": [
              {
                "type": "button",
                "style": "link",
                "height": "sm",
                "action": 
                {
                  "type": "postback",
                  "label": "รับงาน",
                  "data": "' . $order_id . ',RiderConfirm"
                }
              },
              {
                "type": "spacer",
                "size": "sm"
              }
            ],
            "flex": 0,
            "backgroundColor": "#28FFBF"
          }
        }
      }';
      return $json;
    }
  }

  public function rider_orderInOnMarket($m_id,$order_id)
  {
    $location = $this->db->select('address')
      ->where('order_id', $order_id)
      ->get('tb_order')->row();
      
$location = $location->address;
    $json = '{
                "type": "flex",
                "altText": "มีงานเข้า",
                "contents": {
                  "type": "bubble",
                  "body": {
                    "type": "box",
                    "layout": "vertical",
                    "spacing": "md",
                    "contents": [
                      {
                        "type": "text",
                        "text": "โต๊ะที่ : '.$location.' "
                      }
                    ]
                  },
                  "footer": {
                    "type": "box",
                    "layout": "vertical",
                    "contents": [
                      {
                        "type": "button",
                        "action": {
                          "type": "postback",
                          "label": "รับงาน",
                          "data": "' . $order_id . ',RiderConfirm"
                        },
                        "style": "primary"
                      }
                    ]
                  }
                }
              }';
              return $json;
  }

  // "type": "uri",
  //                 "label": "รับงาน",
  //                 "uri": "https://market.deltafood.me/api/RiderController/resiveOrder?order_id=' . $order_id . '&&m_id=' . $m_id . '"
}
