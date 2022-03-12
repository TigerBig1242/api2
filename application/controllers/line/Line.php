

defined('BASEPATH') or exit('No direct script access allowed');
require 'line/autoload.php';

// require 'firebase_line/vendor/autoload.php';

use Firebase\FirebaseLib;

class Line extends CI_Controller
{

    private $bot;
    // private $assess_token = "sCGjaB6jSJrpVG+loHyCLflN6VU8p7TrHsCNSNYbwHQmH5pgSG0TP3zMNwQB0wVk4w73tDJHKd7D8/Pq7/MKUxL++ztu9gMWUZIeSMZgw8XBtZKFDqw/TLvseeocJqJ1CtKsbIBmVIah+qpCGUZ6CwdB04t89/1O/w1cDnyilFU=";
    // private $serect_key = '6f78a533aa418f9a0ab9e97d0b63d236';
    // private $assess_token = "bNbjZAR8emm86xH7oW6WkgYmU75pCAiJupQ4SjBG05NVq9mO5EnnHppcljiopIGGQ1GBlR9ZxdzVQlw4qzA1xPBK7CGqh5Nyv4KWFXwO4gWijhrVVJMsexEwc+Uap1GC4Qx9ykcN7BOo474wnXzr4AdB04t89/1O/w1cDnyilFU=";
    // private $serect_key = 'e91da921522c91af4b73a3b201274af7';
    private $assess_token = "z5x0eX294fz+az93iFrJRFtqB1dyVOR3RLw8du+Sy6x26iQ0PldFezCPQod7SdoQG2Z/ey0thHIk8B1/BjC7dcVrwYI7bE66fwKP60NsjTbzwIiFn3ysKFqfvj1zDfXWQtaABAl0dZrrKEzrP9llfgdB04t89/1O/w1cDnyilFU=";
    private $serect_key = '4883ca0e056faaecd2c40fd555414ccc';
    // private $userId_Admin = "U86e52901b3e6794f7fa94027d801251b";


    public function __construct()
    {
        parent::__construct();
        // Do your magic here
        // $res = $this->db->select("line_settings")->get_where("restaurant", array('id_res_auto' => res_id()));
        // if ($res->num_rows() > 0) {
        //     $setting = unserialize($res->row()->line_settings);
        //     if ($setting) {
        // $this->assess_token = $setting->token;
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->assess_token);
        $this->bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $this->serect_key]);
        //     }
        // }
    }

    function base64_to_jpeg($base64_string, $output_file)
    {
        // open the output file for writing
        $ifp = fopen($output_file, 'wb');

        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode(',', $base64_string);

        // we could add validation here with ensuring count( $data ) > 1
        fwrite($ifp, base64_decode($data[1]));

        // clean up the file resource
        fclose($ifp);

        return $output_file;
    }

    public function index()
    {
        //         "type": "image",
        //    "originalContentUrl": "https://example.com/original.jpg",
        //    "previewImageUrl": "https://example.com/preview.jpg"
        //     exit();

        $response = $this->bot->getMessageContent('13523934123674');
        if ($response->isSucceeded()) {
            $tempfile = tmpfile();
            //            echo $tempfile;
            $dataBinary = $response->getRawBody();
            echo '<img src="' . $dataBinary . '">';
            $fileFullSavePath = 'Test_line.jpg';
            file_put_contents($fileFullSavePath, $dataBinary);
            //            echo $response->getRawBody();
        } else {
            error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
        }
        //        $this->unlinkFromUser();
        //        $this->pushMsg($arrayPostData);
    }

    function pushMsg($arrayPostData)
    {
        $accessToken = $this->assess_token;
        $arrayHeader = array();
        $arrayHeader[] = "Content-Type: application/json";
        $arrayHeader[] = "Authorization: Bearer {$accessToken}";
        $strUrl = "https://api.line.me/v2/bot/message/push";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $strUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayPostData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
    }

    // public function setFireBaseChatLine($node = null, $mm_id = 0) {
    //     $URL = 'https://panda-restaurant.firebaseio.com/';
    //     $TOKEN = 'lOfgA9YKtKYrTzgOSZisRKPPY2joblmP6LV3ww1m';
    //     $firebase = new FirebaseLib($URL, $TOKEN);
    //     $PATH = $node . '/chat/member_' . $mm_id;
    //     $val = $firebase->set($PATH, rand(1, 100));
    //     var_dump('this is set');
    //     echo "<br>";
    //     $val = $firebase->get($PATH);
    //     var_dump('this is get val : ' . $val);
    // }

    public function sendMessage($u_id = '', $message = '')
    {
        //        var_dump($u_id, $message);
        $textMessageBuilder = new LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
        $response = $this->bot->pushMessage($u_id, $textMessageBuilder);
        return $response->getHTTPStatus();
    }

    function getFormatTextMessage($text)
    {
        $datas = [];
        $datas['type'] = 'text';
        $datas['text'] = $text;
        return $datas;
    }

    public function webhook($id_res_auto = 0)
    {
        $datas = file_get_contents('php://input');
        $deCode = json_decode($datas, true);
        file_put_contents('log.json', file_get_contents('php://input') . PHP_EOL, FILE_APPEND);
        foreach ($deCode['events'] as $deCode_val) {
            $type = $deCode_val['type'];
            $replyToken = $deCode_val['replyToken'];
            $userId = $deCode_val['source']['userId'];
            $text = $deCode_val['message'];
            if ($type == 'message') {
                $user = $this->db->select('m.mm_id,m.userId,m.id_res_auto,r.code')
                    ->join('restaurant as r', 'm.id_res_auto = r.id_res_auto')
                    ->get_where('member_main as m', ['m.userId' => $userId]);
                if ($user->num_rows() > 0) {
                    $user = $user->row();
                    $chat_data = [
                        'id_res_auto' => $user->id_res_auto,
                        'mm_id' => $user->mm_id,
                        'chat_text' => serialize($text),
                        'by_user' => 1,
                    ];
                    $chat = $this->db->insert('member_line_chat', $chat_data);
                    $node = $user->id_res_auto . '_' . $user->code;
                    // $this->setFireBaseChatLine($node, $user->mm_id);
                } else {
                    $response = $this->bot->getProfile($userId);

                    if ($response->isSucceeded()) {
                        $profile = $response->getJSONDecodedBody();
                        $data_insert = [
                            'mm_name' => $profile['displayName'],
                            'pic_url' => isset($profile['pictureUrl']) ? $profile['pictureUrl'] : '',
                            'userId' => $userId,
                            'statusMessage' => isset($profile['statusMessage']) ? $profile['statusMessage'] : '',
                            'id_res_auto' => $id_res_auto,
                            'follow' => 1,
                        ];
                        $newUser = $this->db->insert('member_main', $data_insert);

                        $user = $this->db->select('mm_id,userId,id_res_auto')->get_where('member_main', ['userId' => $userId])->row();
                        $chat_data = [
                            'id_res_auto' => $user->id_res_auto,
                            'mm_id' => $user->mm_id,
                            'chat_text' => serialize($text),
                            'by_user' => 1,
                        ];
                        $chat = $this->db->insert('member_line_chat', $chat_data);

                        $node = $user->id_res_auto . '_' . $user->code;
                        // $this->setFireBaseChatLine($node, $user->mm_id);
                    } else {
                    }
                }
            } else if ($type == 'follow' || $type == 'unfollow') {
                // update member //
                $val_type = 0;
                if ($type == 'follow') {
                    $val_type = 1;
                    $this->sendMessage($userId, 'ยินดีต้อนรับกลับมา(Follow)');
                }
                $follow = $this->db->where('userId', $userId)->update('member_main', ['follow' => $val_type]);
            } else if ($type == 'postback') {

                $data = $deCode_val['postback']['data'];

                parse_str(html_entity_decode($data), $out);

                file_put_contents('log.json', json_encode($out) . PHP_EOL, FILE_APPEND);
                if ($out['type'] == "confirmorder") {
                    $order = $this->db->get_where("line_noti_confirm", array('id' => $out['c_id']));
                    $messages = '';
                    if ($order->num_rows() > 0) {
                        $or = $order->row();
                        if ($or->status == '') {
                            $this->db->update("line_noti_confirm", array('status' => $out['status']), array('id' => $or->id, 'id_res_auto' => $id_res_auto));
                            $this->db->update("order_data", array("member_id" => $or->mm_id), array('order_id' => $or->order_id, 'id_res_auto' => $id_res_auto));
                            if ($out['status'] == "confirm") {
                                $messages = "ยืนยันเรียบร้อยแล้ว";
                            } else {
                                $messages = "ยกเลิกเรียบร้อยแล้ว";
                            }
                        } else {
                            if ($order->row()->status = 'confirm') {
                                $messages = "ถูกยืนยันไปแล้ว";
                            } else {
                                $messages = "ถูกยกเลิกไปแล้ว";
                            }
                        }
                    } else {
                        $messages = "ไม่มีผล";
                    }
                    $data = array('type' => 'text', 'text' => $messages);
                    // echo send_line_message($data, 'Ud0fc174412184fe1f26806d3b08b1974');
                    //                    $this->sendMessage($userId, $messages);
                    //                    $results = $this->sentMessage($encodeJson, $LINEDatas);
                }
                // $this->sendMessage($userId, 'เข้า else');
            }
        }
        // $type = $deCode['events'][0]['type'];
        // $replyToken = $deCode['events'][0]['replyToken'];
        // $userId = $deCode['events'][0]['source']['userId'];
        // $text = $deCode['events'][0]['message'];
    }

    public function setRichMenu()
    {
        $richMenuID = "richmenu-414d901c48042d06f3ac59915d7717a5";
        $userId = "U86e52901b3e6794f7fa94027d801251b";
        $imagePath = 'linerichme.jpg';
        $contentType = 'image/jpeg';
        $response = $this->bot->uploadRichMenuImage($richMenuID, $imagePath, $contentType);
        $response = $this->bot->linkRichMenu($userId, $richMenuID);
        print_r($response);
    }

    public function createNewRichmenu()
    {
        $admin = '{
            "size": {
              "width": 2500,
              "height": 843
            },
            "selected": true,
            "name": "Rich_admin",
            "chatBarText": "เมนู",
            "areas": [
              {
                "bounds": {
                  "x": 0,
                  "y": 0,
                  "width": 1695,
                  "height": 843
                },
                "action": {
                  "type": "uri",
                  "uri": "https://market.deltafood.me/demo/backend"
                }
              },
              {
                "bounds": {
                  "x": 1699,
                  "y": 0,
                  "width": 801,
                  "height": 843
                },
                "action": {
                  "type": "uri",
                  "uri": "https://market.deltafood.me/demo/user"
                }
              }
            ]
          }';
        $user = '{
            "size": {
              "width": 2500,
              "height": 843
            },
            "selected": true,
            "name": "Rich_user",
            "chatBarText": "เมนู",
            "areas": [
              {
                "bounds": {
                  "x": 5,
                  "y": 0,
                  "width": 2495,
                  "height": 843
                },
                "action": {
                  "type": "uri",
                  "uri": "https://market.deltafood.me/demo/user"
                }
              }
            ]
          }';
        $rider = '{
            "size": {
              "width": 2500,
              "height": 843
            },
            "selected": true,
            "name": "Rich_rider",
            "chatBarText": "เมนู",
            "areas": [
              {
                "bounds": {
                  "x": 0,
                  "y": 0,
                  "width": 1691,
                  "height": 843
                },
                "action": {
                  "type": "uri",
                  "uri": "https://market.deltafood.me/demo/rider"
                }
              },
              {
                "bounds": {
                  "x": 1694,
                  "y": 0,
                  "width": 806,
                  "height": 843
                },
                "action": {
                  "type": "uri",
                  "uri": "https://market.deltafood.me/demo/user"
                }
              }
            ]
          }';
         $store = '{
            "size": {
              "width": 2500,
              "height": 843
            },
            "selected": true,
            "name": "Rich_store",
            "chatBarText": "เมนู",
            "areas": [
              {
                "bounds": {
                  "x": 0,
                  "y": 0,
                  "width": 1695,
                  "height": 843
                },
                "action": {
                  "type": "uri",
                  "uri": "https://market.deltafood.me/demo/store"
                }
              },
              {
                "bounds": {
                  "x": 1699,
                  "y": 0,
                  "width": 801,
                  "height": 843
                },
                "action": {
                  "type": "uri",
                  "uri": "https://market.deltafood.me/demo/user"
                }
              }
            ]
          }';
        $channelAccessToken = $this->assess_token;
        $sh = <<< EOF
  curl -X POST \
  -H 'Authorization: Bearer $channelAccessToken' \
  -H 'Content-Type:application/json' \
  -d '{
    "size": {
      "width": 2500,
      "height": 843
    },
    "selected": true,
    "name": "Rich_store",
    "chatBarText": "เมนู",
    "areas": [
      {
        "bounds": {
          "x": 0,
          "y": 0,
          "width": 1695,
          "height": 843
        },
        "action": {
          "type": "uri",
          "uri": "https://market.deltafood.me/demo/store"
        }
      },
      {
        "bounds": {
          "x": 1699,
          "y": 0,
          "width": 801,
          "height": 843
        },
        "action": {
          "type": "uri",
          "uri": "https://market.deltafood.me/demo/user"
        }
      }
    ]
  }' https://api.line.me/v2/bot/richmenu;
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        if (isset($result['richMenuId'])) {
            echo $result['richMenuId'];
        } else {
            echo $result['message'];
        }
    }

    public function getListOfRichmenu()
    {
        $channelAccessToken = $this->assess_token;
        $sh = <<< EOF
  curl \
  -H 'Authorization: Bearer $channelAccessToken' \
  https://api.line.me/v2/bot/richmenu/list;
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        //        foreach ($result['richmenus'] as $value) {
        //            $this->deleteRichmenu($value['richMenuId']);
        //        }
        // pre($result);
    }

    public function checkRichmenuOfUser($channelAccessToken, $userId)
    {
        $sh = <<< EOF
  curl \
  -H 'Authorization: Bearer $channelAccessToken' \
  https://api.line.me/v2/bot/user/$userId/richmenu
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        if (isset($result['richMenuId'])) {
            return $result['richMenuId'];
        } else {
            return $result['message'];
        }
    }

    public function unlinkFromUser($userId = 'Ud0fc174412184fe1f26806d3b08b1974')
    {
        $channelAccessToken = $this->assess_token;
        $sh = <<< EOF
  curl -X DELETE \
  -H 'Authorization: Bearer $channelAccessToken' \
  https://api.line.me/v2/bot/user/$userId/richmenu
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        if (isset($result['message'])) {
            return $result['message'];
        } else {
            return 'success';
        }
    }

    public function deleteRichmenu($richmenuId)
    {
        $channelAccessToken = $this->assess_token;
        if (!$this->isRichmenuIdValid($richmenuId)) {
            return 'invalid richmenu id';
        }
        $sh = <<< EOF
  curl -X DELETE \
  -H 'Authorization: Bearer $channelAccessToken' \
  https://api.line.me/v2/bot/richmenu/$richmenuId
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        if (isset($result['message'])) {
            return $result['message'];
        } else {
            return 'success';
        }
    }

    public function linkToUser($channelAccessToken, $userId, $richmenuId)
    {
        if (!$this->isRichmenuIdValid($richmenuId)) {
            return 'invalid richmenu id';
        }
        $sh = <<< EOF
  curl -X POST \
  -H 'Authorization: Bearer $channelAccessToken' \
  -H 'Content-Length: 0' \
  https://api.line.me/v2/bot/user/$userId/richmenu/$richmenuId
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        if (isset($result['message'])) {
            return $result['message'];
        } else {
            return 'success';
        }
    }

    public function uploadRandomImageToRichmenu($channelAccessToken, $richmenuId)
    {
        if (!$this->isRichmenuIdValid($richmenuId)) {
            return 'invalid richmenu id';
        }
        $randomImageIndex = rand(1, 5);
        $imagePath = realpath('') . '/' . 'controller_0' . $randomImageIndex . '.png';
        $sh = <<< EOF
  curl -X POST \
  -H 'Authorization: Bearer $channelAccessToken' \
  -H 'Content-Type: image/png' \
  -H 'Expect:' \
  -T $imagePath \
  https://api.line.me/v2/bot/richmenu/$richmenuId/content
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        if (isset($result['message'])) {
            return $result['message'];
        } else {
            return 'success. Image #0' . $randomImageIndex . ' has uploaded onto ' . $richmenuId;
        }
    }

    public function isRichmenuIdValid($string)
    {
        if (preg_match('/^[a-zA-Z0-9-]+$/', $string)) {
            return true;
        } else {
            return false;
        }
    }

    public function get_chat($id_res_auto, $mm_ids = array(), $out)
    {
        if (count($mm_ids) > 0) {
            $this->db->where_not_in("c.mm_id", $mm_ids);
        }
        $this->db->order_by("c.create_date", 'desc')
            ->select('m.mm_name,m.userId,m.pic_url,c.mm_id,c.chat_text,c.read_chat,c.create_date')
            ->join('member_main as m', 'c.mm_id = m.mm_id');
        $chat = $this->db->get_where("member_line_chat c", array('c.id_res_auto' => $id_res_auto));
        if ($chat->num_rows() > 0) {
            $c = $chat->row();
            $c->chat_text = unserialize($c->chat_text);
            $c->text = '';
            //            pre($c);
            if ($c->chat_text['type'] == "location") {
                $c->text = "ส่งที่อยู่";
            } else if ($c->chat_text['type'] == 'image') {
                $c->text = "ส่งรูปภาพ";
            } else if ($c->chat_text['type'] == 'sticker') {
                $c->text = "ส่งสติ๊กเกอร์";
            } else {
                $c->text = $c->chat_text['text'];
            }
            array_push($out, $c);
            array_push($mm_ids, $c->mm_id);
            if (count($mm_ids) == 10) {
                return $out;
            }
            return $this->get_chat($id_res_auto, $mm_ids, $out);
        } else {

            return $out;
        }
    }

    // public function getUserChatAll() {
    //     $user = $this->get_chat(res_id(), array(), array());
    //     echo json_encode($user);
    // }

    public function getUserChatById($mm_id = 0)
    {
        $read = $this->db->where(['by_user' => 1, 'mm_id' => $mm_id])->update('member_line_chat', ['read_chat' => 1]);
        //       echo  $this->db->last_query();
        $chat = $this->db->limit(20)->order_by('create_date', 'DESC')->select('*')->get_where('member_line_chat', ['id_res_auto' => 2, 'mm_id' => $mm_id])->result();
        foreach ($chat as $c_val) {
            $c_val->chat_text = unserialize($c_val->chat_text);
            $c_val->id_line_chat -= 0;
        }

        usort($chat, function ($a, $b) {
            return $a->id_line_chat - $b->id_line_chat;
        });
        echo json_encode($chat);
    }

    // public function sendRestaurantMS() {
    //     $p = _post();

    //     $mebmer = $this->db->get_where("member_main", array('mm_id' => $p->mm_id));
    //     if ($mebmer->num_rows() > 0) {
    //         $status = $this->sendMessage($mebmer->row()->userId, $p->text);
    //         echo '--' . $status . '--';
    //         if ($status == 200) {
    //             $obj = [
    //                 "type" => "text",
    //                 "id" => "0",
    //                 "text" => $p->text
    //             ];
    //             $ms = [
    //                 'id_res_auto' => res_id(),
    //                 'mm_id' => $p->mm_id,
    //                 'read_chat' => 1,
    //                 'chat_text' => serialize($obj),
    //                 'by_user' => 0
    //             ];
    //             echo $this->db->insert('member_line_chat', $ms);
    //         }
    //     }
    // }

    public function checkLineOAtoken()
    {
        // $p = _post();
        // $assess_token = 'sTWh9w572YnoeuP0aZxq3w36uTgA9+XvsQcwSmEu23inRnWTzc+tVSjNVOegEIVyYC4SQ4bzneqb6A7vYSVJxy5k9JTJfeRjCsOXslHos6YVQ9dEGSnPAo1sBSkZRT+ULq5b2Znu+URwJvjaz8KU1wdB04t89/1O/w1cDnyilFU=';
        // $serect_key = '6a4655c648e2ef0ad929d31f9e4ae1ab';
        $assess_token = 'sTWh9w572YnoeuP0aZxq3w36uTgA9+XvsQcwSmEu23inRnWTzc+tVSjNVOegEIVyYC4SQ4bzneqb6A7vYSVJxy5k9JTJfeRjCsOXslHos6YVQ9dEGSnPAo1sBSkZRT+ULq5b2Znu+URwJvjaz8KU1wdB04t89/1O/w1cDnyilFU=';
        $serect_key = '6a4655c648e2ef0ad929d31f9e4ae1ab';
        $p = (object) [
            'assess_token' => $assess_token,
            'serect_key' => $serect_key
        ];

        $client = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($p->assess_token);
        $bot = new \LINE\LINEBot($client, ['channelSecret' => $p->serect_key]);

        $textMessageBuilder = new LINE\LINEBot\MessageBuilder\TextMessageBuilder('Hellow Test');
        $response = $bot->pushMessage('Uc645317b3aebd54dc15fa2f52e67474e', $textMessageBuilder);
        echo $response->getHTTPStatus() . ' ' . $response->getRawBody();

        // pre($p->assess_token);
    }

    //     public function read($mm_id)
    //     {
    //         $update = $this->db->where(['mm_id'=>$mm_id])->update('member_line_chat',['read_chat'=>0]);
    //     }
    public function test()
    {

        $channelAccessToken = "/9hD1DYL4DRY2RYif7Nu59cJ1so1C618ey+PNmmxZM7LNZleDl4lp8cHgWiVKzhp3avEddwTTop1sMIfRPGezwXb05UhfMTtZ9yZ5+iFr6moRNBUnr2YYF9dbGJBUyl4T2KzD/K9J1a1ANaXvwpXZgdB04t89/1O/w1cDnyilFU=";
        $sh = <<< EOF
  curl \
  -H 'Authorization: Bearer $channelAccessToken' \
  https://api.line.me/v2/bot/followers/ids;
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        // pre($result);
    }

    public function hook()
    {
        $token = "C7t+WeaBIbBMXHAbZcYfm+60uOgT2xP3wuFRsdCOPK+8b6ec/+nG/P8fuKShvLNc3avEddwTTop1sMIfRPGezwXb05UhfMTtZ9yZ5+iFr6kqAiGlTPY679DsTWX2IPq2qdkgxAkScHqs6VETsAlIHQdB04t89/1O/w1cDnyilFU=";



        $sh = <<< EOF
  curl -X GET \
  -H 'Authorization: Bearer C7t+WeaBIbBMXHAbZcYfm+60uOgT2xP3wuFRsdCOPK+8b6ec/+nG/P8fuKShvLNc3avEddwTTop1sMIfRPGezwXb05UhfMTtZ9yZ5+iFr6kqAiGlTPY679DsTWX2IPq2qdkgxAkScHqs6VETsAlIHQdB04t89/1O/w1cDnyilFU=' \
  -H 'Content-Length: 0' \
  -H 'Content-Type: application/json' \
  https://api.line.me/v2/bot/followers/ids
EOF;
        $result = json_decode(shell_exec(str_replace('\\', '', str_replace(PHP_EOL, '', $sh))), true);
        var_dump($result);
        exit();



        // $datas = file_get_contents('php://input');
        // $deCode = json_decode($datas, true);
        file_put_contents('log_demo.json', file_get_contents('php://input') . PHP_EOL, FILE_APPEND);
    }

    public function curl()
    {
        $data = [
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        ];
        echo json_encode($data);
    }
}

/* End of file Line.php */
