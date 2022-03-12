
defined('BASEPATH') or exit('No direct script access allowed');

class Welcome extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('line/Line_model');
		// $this->load->model('line/Flex_admin_model');
		// $this->load->model('line/Firebase_line');
		$this->load->model('Market_model');
	}
	function index()
	{
		$user = $this->db->get_where("tb_member", array("m_id" => 1, "type_id" => 1, "m_active" => 1))->row();
		print_r($user);
	}

	function riderSummary($rider_id = 0, $page = 0)
	{
		$pp = 0;
		$pd = 0;
		$tt = 0;

		// $rider_id =  $this->rider_id;
		$summary = $this->db->select('*')
			->select_sum('od.sum_price')
			->join('tb_order as o', 'o.order_id=od.order_id')
			->where('o.status_id', 4)
			->where('od.food_status', 3)
			->where('o.rider_id', $rider_id)
			->group_by('od.order_id')
			->get('tb_orderdetail as od')->result();
		foreach ($summary as $key => $value) {
			if ($value->payment_id == 1) {
				$pp += $value->sum_price;
			} elseif ($value->payment_id == 2) {
				$pd += $value->sum_price;
			}
			$tt += $value->sum_price;
		}
		$data = ["total" => $tt, "pp" => $pp, "pd" => $pd];
		echo json_encode($data);
	}

	public function reportDaySales($store_id, $date)
	{
		$pp = 0; //โอน
		$pd = 0; //ปลายทาง
		$amount_pp = 0;
		$amount_pd = 0;

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
				$pd += $value->sum_price;
				$amount_pd++;
			}
		}
		$data = ["pp" => $pp, "pd" => $pd, "amount_pp" => $amount_pp, "amount_pd" => $amount_pd];
		echo json_encode(['flag' => 1, 'data' => $data]);
	}


	public function reportRider($rider_id, $date)
	{
		$pp = 0; //โอน
		$pt = 0; //ปลายทาง
		$amount_pp = 0;
		$amount_pt = 0;
		$del_cost = 0;
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
			$del_cost += $value->del_cost;
			if ($value->payment_id == 1) {
				$pp += $value->sum_price;
				$amount_pp++;
			} elseif ($value->payment_id == 2) {
				$pt += $value->sum_price;
				$amount_pt++;
			}
		}
		$data = ["price_pp" => $pp, "price_pt" => $pt, "amount_pp" => $amount_pp, "amount_pt" => $amount_pt, "total_price" => $pp + $pt, "total_amount" => $amount_pt + $amount_pp, "del_cost" => $del_cost];
		echo json_encode($data);
		// ['flag' => 1, 'data' => $data];
	}
	// public function line()
	// {
	// $this->Line_model->flex_rider_orderIn(1);
	// }

	public function testRider()
	{
		$this->Line_model->flex_rider_orderIn(5);
	}
	public function slip()
	{
		$data = [
			[
				"slip" => "image/slip/1634029437_61654f7d188a7.jpg",
				"date" => "2021-10-12 18:15:12"
			],
			[
				"slip" => "image/slip/1634029437_61654f7d188a7.jpg",
				"date" => "2021-10-12 18:15:15"
			]
		];
		// echo 5;
		$data = json_encode($data);
		$this->db->set('slip_image',$data)
		->where('payment_id',1)
		->update('tb_order');
		exit;

		// $slip = $this->db->where('order_id',9)
		// 	->get('tb_order')->row();

		// $slip->slip_image = json_decode($slip->slip_image);
		// echo json_encode($slip);
		// echo json_encode($data);
	}
	public function setOption()
	{
		$data ='[
			{
				"title": "เล็ก",
				"optionD": {
					"count": 0,
					"price": 0,
					"default": false
				}
			},
			{
				"title": "พิเศษ",
				"optionD": {
					"count": 0,
					"price": 10,
					"default": false
				}
			}
		]';
		$this->db->set('option',$data)->update('tb_orderdetail');
	}
	// public function updateOrder()
	// {
	// 	$this->db->set('orderType', 2)
	// 	->where('del_cost',0)
	// 	->update('tb_order');
	// }
	 public function pushOption()
	 {
		 # code...
		 $option = '[{"title":"\u0e44\u0e02\u0e48","select_option":3,"option":[{"title":"\u0e44\u0e02\u0e48\u0e14\u0e32\u0e27","optionD":{"count":0,"price":7,"default":true}},{"title":"\u0e44\u0e02\u0e48\u0e40\u0e08\u0e35\u0e22\u0e27","optionD":{"count":0,"price":20,"default":false}},{"title":"\u0e44\u0e02\u0e48\u0e14\u0e34\u0e1a","optionD":{"count":0,"price":5,"default":false}}]},{"title":"\u0e19\u0e49\u0e33\u0e0b\u0e38\u0e1b","select_option":0,"option":[{"title":"\u0e19\u0e49\u0e33\u0e14\u0e33","optionD":{"count":0,"price":50,"default":false}},{"title":"\u0e19\u0e49\u0e33\u0e43\u0e2a","optionD":{"count":0,"price":0,"default":false}},{"title":"\u0e19\u0e49\u0e33\u0e02\u0e49\u0e19","optionD":{"count":0,"price":20,"default":false}}]}]';
		$a= $this->db->set('option',$option)->where('store_id',2)->update('tb_food');
		echo $a;
	 } 
}
/*

ยอดรวมทั้งหมด
	-รวมหมด หักออกที่ยกเลิก

ยอดโอนจ่าย
	-เอาเฉพาะโอนจ่าย หักออกที่ยกเลิก
ยอดปลายทาง
	-เอาเฉพาะปลายทาง หักออกที่ยกเลิก

*/