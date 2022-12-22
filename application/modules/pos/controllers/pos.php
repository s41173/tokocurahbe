<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once 'definer.php';

class Pos extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Pos_model', 'model', TRUE);
        $this->load->model('Sales_item_pos_model', 'sitem', TRUE);

        $this->properti = $this->property->get();
//        $this->acl->otentikasi();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->currency = new Currency_lib();
        $this->sales = new Sales_lib();
        $this->payment = new Payment_lib();
        $this->city = new City_lib();
        $this->product = new Product_lib();
        $this->bank = new Bank_lib();
        $this->category = new Categoryproduct_lib();
        $this->branch = new Droppoint_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
        $this->tax = new Tax_lib();
        $this->user = new Admin_lib();
        $this->ledger = new Customer_ledger_lib();
        $this->customer = new Customer_lib();
        $this->vdiscount = new Voucher_discount_lib();
        $this->paymentgateway = new Payment_gateway_lib();
        $this->shipping = new Shipping_lib();
        $this->matrix = new Googlematrix_lib();
        $this->courier = new Courier_lib();
        $this->rj = new Rajaongkir_lib();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    }

    private $properti, $modul, $title, $sales, $paymentgateway, $bank, $member, $ledger, $mledger, $customer, $matrix;
    private $role, $currency, $user, $payment, $city, $product ,$category, $branch, $period, $tax,$vdiscount,$shipping,$courier,$rj;

    
    public function index()
    {
        if ($this->apix->otentikasi() == TRUE){
        
        $decoded = $this->apix->get_decoded();
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        
        if(isset($datax['start']) && isset($datax['end']) && isset($datax['status']) && isset($datax['cancel']) && isset($datax['redeem']) && isset($datax['booked']) && isset($datax['pickup'])){ 
            $result = $this->model->search($datax['start'],$datax['end'],$decoded->userid,$datax['status'],$datax['cancel'],$datax['redeem'],$datax['booked'],$datax['pickup'],$this->limitx, $this->offsetx)->result(); 
            $this->count = $this->model->search($datax['start'],$datax['end'],$decoded->userid,$datax['status'],$datax['cancel'],$datax['redeem'],$datax['booked'],$datax['pickup'],$this->limitx, $this->offsetx,1); 

            $data['orderid'] = $this->model->counter().mt_rand(99,9999);
            $data['total_amount'] = $this->model->search_summary($datax['start'],$datax['end'],$decoded->userid,$datax['status'],$datax['cancel']);
            $data['record'] = $this->count; 
            $data['result'] = $result;
            
        }else{ $this->reject('Parameter Required',400); }
            
        $this->output = $data;
        }else{ $this->reject_token(); }
        $this->response('c');
    } 
    
    public function filtering()
    {
        if ($this->apix->otentikasi() == TRUE){
        
        $decoded = $this->apix->get_decoded();
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        
        if(isset($datax['type']) && isset($datax['period'])){ 
        
            $result = $this->model->filtering($datax['type'],$datax['period'], $this->limitx, $this->offsetx)->result(); 
            $this->count = $this->model->filtering($datax['type'],$datax['period'], $this->limitx, $this->offsetx,1); 

            $data['record'] = $this->count; 
            $data['result'] = $result;
        }else{ $this->reject('Parameter Required',400); }
            
        $this->output = $data;
        }else{ $this->reject_token(); }
        $this->response('c');
    } 
    
    public function search()
    {
        if ($this->apix->otentikasi() == TRUE){
        
        $decoded = $this->apix->get_decoded();
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        
        if(isset($datax['search'])){ 
        
            $this->count = 0; 
            if (is_numeric($datax['search']) == TRUE){
              $result = $this->model->searching($datax['search'],'code')->result(); 
            }else{ $result = $this->model->search_by_product($datax['search'])->result();  }

            $data['record'] = $this->count; 
            $data['result'] = $result;
        }else{ $this->reject('Parameter Required',400); }
            
        $this->output = $data;
        }else{ $this->reject_token(); }
        $this->response('c');
    } 
        
    function get_trans($code=0,$type=0){
        
       if ($type == 0){ $valid = $this->model->valid_orderid($code, $this->title);}
       else{ $valid = $this->model->valid_transid($code, $this->title); }
       
       if ($this->apix->otentikasi() == TRUE && $valid == TRUE){  
           
            if ($type == 0){ $sales = $this->model->get_by_code($code)->row(); }
            else{ $sales = $this->model->get_by_transid($code)->row(); }
            
            $data['details'] = $sales;
            foreach ($this->sitem->get_last_item($sales->id)->result() as $res) {
               $product = $this->product->get_by_id($res->product_id)->row();
               if ($product->image != null){ $img = $this->properti['image_url'].'product/'.$product->image; }else{ $img = null; }
               $this->resx[] = array ("id"=>$res->id,
                                      "sales_id"=>$res->sales_id, "code" => $sales->code,
                                      "product_id" => $res->product_id, "name"=> $product->name, "sku"=> $product->sku,
                                      "image"=> $img,
                                      "qty"=>$res->qty, "tax"=>$res->tax, "amount"=>intval($res->amount),
                                      "price"=>intval($res->price), "discount" => intval($res->discount),
                                      "attribute" => $res->attribute, "description" => $res->description
                                      ); 
            }
            $data['items'] = $this->resx;
            $data['payments'] = json_decode($this->paymentgateway->request('invoices/'.$sales->transid));
            
            if ($sales->booked == 1){
              $data['shipping'] = $this->get_ship_detail($sales->id);    
            }else{ $data['shipping'] =null; }
            
            $this->output = $data;
       }
       else{ $this->valid_404($valid); $this->reject_token(); }
       $this->response('content'); 
    }
    
     private function get_ship_detail($sid){
      $data = null;
      $sales = $this->sales->get_by_id($sid)->row();
      if ($sales->canceled != NULL){ $stts = "Pesanan dibatalkan"; }
      elseif ($sales->shipreceived != NULL){ $stts = "Pesanan selesai"; }
      else{$stts = "Pesanan sedang dikirim";}
      
      if ($this->properti['courier_integration'] == 0){
           $shipping = $this->shipping->get_by_sales($sid)->row();
           $data['courier'] = $this->courier->get_name($shipping->courier);
           $data['destination'] = $shipping->destination;
           $data['phone_destination'] = $shipping->phone;
           $data['distance'] = $shipping->distance;
           $data['received'] = $shipping->received;
           $data['received_description'] = $shipping->received_desc;
           $data['confirm_customer'] = $shipping->confirm_customer;
           $data['rating'] = $shipping->rating;
           $data['comments'] = $shipping->comments;
           $data['track_url'] = null;
           $data['status'] = $stts;
           $data['manifest'] = null;
      }else{
          
          if ($sales->expedition == null){
             $result = $this->shipping->request('orders?order_id='.$sales->shipid,null,1);
              if ($result[1] == 200){
                  $result = $result[0];
                  $hasil = json_decode($result); $hasil = $hasil->orders[0];

                  $data['courier'] = $hasil->courier;
                  $data['destination'] = $sales->receiver_address;
                  $data['phone_destination'] = $sales->receiver_phone;
                  $data['distance'] = 0;
                  if ($hasil->finish_datetime == null){$data['confirm_customer']=0;}else{$data['confirm_customer']=1;}
                  $data['received'] = $hasil->finish_datetime;
                  $data['received_description'] = $hasil->status_description;
                  $data['rating'] = 5;
                  $data['comments'] = null;
                  $data['track_url'] = $hasil->points[0]->tracking_url;
                  $data['manifest'] = null;
                  $data['status'] = $stts;
              }
          }else{
             $result = $this->rj->cek_awb($sales->shipid, $this->properti['shipping_vendor']);
//             print_r($result['rajaongkir']['status']);
             
             $data['courier'] = strtoupper($this->properti['shipping_vendor']).' - '.$sales->expedition.' - '.$sales->shipid;
             $data['awb'] = $sales->shipid;
             $data['destination'] = $sales->receiver_address;
             $data['phone_destination'] = $sales->receiver_phone;
             $data['distance'] = 0;
             $data['received'] = $sales->cust_received; 
             $data['rating'] = 5;
             $data['comments'] = null;
             $data['track_url'] = null;
             $data['received_description'] = $result['rajaongkir']['status']['description'];
             if ($result['rajaongkir']['status']['code'] == 200){
                 
                if ($result['rajaongkir']['result']['delivery_status']['status'] == 'delivered'){
                  $data['confirm_customer']=1;  
                }else{ $data['confirm_customer']=0; }
                
                $data['delivery_status'] = $result['rajaongkir']['result']['delivery_status'];
                $data['manifest'] = $result['rajaongkir']['result']['manifest'];
             }else{
               $data['confirm_customer']=0;
               $data['delivery_status'] = null;
               $data['manifest'] = null;
             }
             $data['status'] = $stts;
             
//             print_r($result['rajaongkir']['result']);
          }
      }
      return $data;
    }
    
    function posting($sid=0){
        if ($this->apix->otentikasi() == TRUE && $this->model->valid_add_trans($sid, $this->title) == TRUE && $this->valid_confirm($sid) == TRUE && $this->valid_cancel($sid) == TRUE){  
            $decoded = $this->apix->get_decoded();
            $sales = $this->model->get_by_id($sid)->row();
            $sum = $this->ledger->get_sum_transaction($sales->cust);
            $balance = intval($sum['vamount']);
            
            if ($balance < intval($sales->amount)){ $this->reject("Balance not sufficient"); }
            else{ $transaction = array('approved' => 1);
               if ($this->model->update($sid, $transaction) != true){$this->reject('Failed to post',500);}
               else{ 
                 // customer saldo berkurang  
                 $this->ledger->add('POS', $sid, $sales->dates, 0, intval($sales->amount), $sales->cust, $decoded->event);
                 // member saldo bertambah
                 $this->mledger->add('POS', $sid, $sales->dates, intval($sales->amount),0, $sales->member, $decoded->event);
               }
            }
        }
        elseif($this->valid_confirm($sid) != TRUE){ $this->reject("Sales Already Confirmed..!",401); }
        elseif($this->valid_cancel($sid) != TRUE){ $this->reject("Sales Already Canceled..!",401); }
        else{ $this->valid_404($this->model->valid_add_trans($sid, $this->title)); $this->reject_token(); }
        $this->response();
    }
    
    // fungsi untuk redeem pickup time
    function pickup_redeem($code=0){
        
        if ($this->model->get_by_code($code)->num_rows() > 0){ 
            $sid = $this->model->get_by_code($code)->row();
             if ($sid->pickup == 1 && $sid->pickup_time == null && $this->valid_confirm($sid->id) == FALSE && $this->valid_cancel($sid->id) == TRUE){  
                 $transaction = array('pickup_time' => date('Y-m-d H:i:s'));
                 if ($this->model->update($sid->id, $transaction) != true){$this->reject('Failed to post',500);}
            }
            elseif($sid->pickup == 0){ $this->reject("Invalid Pickup Transaction..!",401); }
            elseif($sid->pickup_time != null){ $this->reject("Transaction already pickuped..!",401); }
            elseif($this->valid_confirm($sid->id) == TRUE){ $this->reject("Sales Not Confirmed..!",401); }
            elseif($this->valid_cancel($sid->id) != TRUE){ $this->reject("Sales Already Canceled..!",401); }
        }
        else{ $this->reject("Trans code not found",404); }
        $this->response();
    }
    
    function expired_trans(){
        $datax = (array)json_decode(file_get_contents('php://input')); 
        $result = $this->model->outdated_sales()->result();
        foreach ($result as $res) {
            $ndate1 = new DateTime($res->dates);
            $ndate2 = new DateTime(date('Y-m-d H:i:s'));
            $interval = $ndate1->diff($ndate2);
//            echo $res->code.'<br>';
            if (intval($interval->h) >= 1){
                if ($this->model->canceled_trans($res->id) != true){ break; $this->reject('Failed to cleaning');}
            }
        }
        $this->response();
    }
    
    // callback
    function callback(){
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if ($datax['status'] == "PAID"){
          $transaction = array('redeem' => 1, 'redeem_date' => date('Y-m-d H:i:s'), 'approved' => 1);
          
          if ($this->model->update_transid($datax['id'], $transaction) == true){
              
               if ($this->paymentgateway->add($datax) != true){ $this->reject("History penerimaan pembayaran gagal"); }
               else{
                   // send notif
                    $sales = $this->model->get_by_transid($datax['id'])->row();
                    $nilai = '{ "orderid":"'.$sales->code.'", "type":"confirmation" }';
                    $this->apix->request($this->properti['invoice_url'],$nilai);
               }
          }else{ $this->reject('Proses penerimaan pembayaran gagal'); }
        }
        $this->response();
    }
    
    // fungsi cronjob
    function cek_callback_shipping(){
        // approve 1 - pickup 0 - shipid != null - shipreceived == null
        $count=0;
        $result = $this->model->get_unreceived_shipping()->result();
        foreach ($result as $res) {
            $results = $this->shipping->request("orders?order_id=".$res->shipid, null, 1);
            if ($results[1] == 200){
                $hasil = json_decode($results[0]);
                $hasil = $hasil->orders[0];
//                print_r($hasil->finish_datetime);
                if ($hasil->finish_datetime != null){
                  $transaction = array('shipreceived' => $hasil->finish_datetime,'cust_received' => $hasil->finish_datetime);
                  if ($this->model->update($res->id, $transaction) == true){ $count++; } 
                }
            }
        }
        $this->output = $count." transaction has been updated";
        $this->response('c');
    }
    
    // callback shipping == belum d pakai
//    function callback_shipping(){
//        
//        $error = new Error_lib();
//        $headers = apache_request_headers();
//        $headerval=null;
//        foreach ($headers as $header => $value) {
////            echo "$header: $value <br />\n";
//            if ($header == "HTTP_X_DV_SIGNATURE"){ $headerval=$value; $error->create('shipping-header',$headerval); break; }
//        }
//              
//        if (!isset($headerval)) { 
//            $error->create('shipping-signaturefound','Error: Signature not found');
////            echo 'Error: Signature not found'; 
//            exit; 
//        }
//        
//        $array = (array)json_decode(file_get_contents('php://input'));
//        $string = implode(",",$array); 
//        $error->create('shipping-data',$string);
////        $data = (array)file_get_contents('php://input');
//
//        $signature = hash_hmac('sha256', $data, '2CA9602826AD090952FDEE15BA393C27AA57AE7C'); 
//        if ($signature != $headerval) { 
//            $error->create('shipping-valid','Error: Signature is not valid');
////            echo 'Error: Signature is not valid'; 
//            exit; 
//        } 
//
//        echo $data; 
//    }
            
    private function rollback($sid){
        try { if ($this->sitem->delete_sales($sid) == true && $this->shipping->delete_by_sales($sid) == true && $this->model->force_delete($sid) == true){ return true; }else{ return false; }}
        catch(Exception $e) { return false; }
    }
    
    private function cek_operational_time(){
        if (intval(date('H')) >= $this->properti['start'] && intval(date('H')) < $this->properti['end']){ return true; }else{ return false; }
    }
    
    function calculate_delivery(){
        
       $dropdistance = new Dropdistance_lib();
       if ($this->apix->otentikasi() == TRUE){
           
//        print_r($this->properti['shipping_integration']);  
           
        $datax = (array)json_decode(file_get_contents('php://input'));
        if (isset($datax['coordinate']) && isset($datax['droppoint']) && isset($datax['amount'])){
            
            $droppoint = explode(',', $datax['droppoint']);
            $droppoint = $droppoint[0];
            $pointdistance = $dropdistance->calculate_distance($datax['droppoint']);
            
            // jika pakai kurir manual sndri
            if ($this->properti['courier_integration'] == 0){

                $pymt = 'CASH';
                $coordinatedest = $datax['coordinate'];
                
                $custdistance = intval(round($this->matrix->calculation($this->branch->get_detail($droppoint, 'coordinate'),$coordinatedest)));
                $distance = intval($pointdistance+round($this->matrix->calculation($this->branch->get_detail($droppoint, 'coordinate'),$coordinatedest)));
                $nilai = $this->shipping->calculate_rate($distance, intval($datax['amount']), $pymt); // get shipping cost
                
                // response
                $data['source_coordinate'] = $this->branch->get_detail($droppoint, 'coordinate');
                $data['droppoint_distance'] = $pointdistance;
                $data['cust_distance'] = $custdistance;
                $data['distance'] = $distance;
                $data['shipping'] = $nilai;
            }else{
                
                $data = null;
                $droppoint = explode(',', $datax['droppoint']);

                // shipping integration - cek multiple outlet
                $shiperror=null;
                if ($this->properti['shipping_integration'] == 1){
                  if (count($droppoint) > 1){ $shiperror = 'Multiple Outlet Tidak Berlaku'; }
                }

                $dropaddress=null;
                for($i=0;$i<count($droppoint);$i++){
                    $dropaddress = $dropaddress.'{"address":"'.trim($this->branch->get_detail($droppoint[$i], 'address')).'","contact_person":{"phone":"'.$this->branch->get_detail($droppoint[$i], 'phone').'"}},';
                }

                $droppoint = $droppoint[0];
                $custdistance = intval(round($this->matrix->calculation($this->branch->get_detail($droppoint, 'coordinate'),$datax['coordinate'])));
                $distance = intval($pointdistance+$custdistance);

                if (intval($this->properti['distance_limit']) >= $distance && $shiperror == null){
                    $param = '{
                               "matter":"Food",
                               "points":['.$dropaddress.'{"address":"'.trim($datax['address']).'","contact_person":{"phone":"0"}}]
                              }';

                 $result = $this->shipping->request('calculate-order', $param, 1, 'POST');
                 if ($result[1] == 200){
                   $hasil = json_decode($result[0]);
                   $hasil = $hasil->order;
                   $nilai = $hasil->delivery_fee_amount;
//                       print_r($hasil);

                   $data['source_coordinate'] = $this->branch->get_detail($droppoint, 'coordinate');
                   $data['droppoint_distance'] = $pointdistance;
                   $data['cust_distance'] = $custdistance;
                   $data['distance'] = intval($pointdistance+$custdistance);
                   $data['shipping'] = intval($nilai);

                 }
                }
                elseif ($shiperror != null){ $this->reject($shiperror); }
                elseif(intval($this->properti['distance_limit']) < $distance){ $this->reject("Jarak pengiriman melewati batas ketentuan"); }
            }

//            return $nilai; 
            $this->output = $data;
        }else{ $this->reject('Parameter Required',400); }
         
      }else { $this->reject_token(); }
      $this->response('c');
    }
    
    // get courir rj
    function get_courier_list(){
        
      $cadress = new Customer_address_lib();  
      if ($this->apix->otentikasi() == TRUE){
          
        $datax = (array)json_decode(file_get_contents('php://input'));
        if (isset($datax['droppoint']) && isset($datax['addressid']) && isset($datax['weight'])){
            $error = null;
            $droppoint = explode(',', $datax['droppoint']);
            // shipping integration - cek multiple outlet
            if (count($droppoint) > 1){ $error = 'Multiple Outlet Tidak Berlaku'; }
            if($this->branch->cek_trans('id', $datax['droppoint']) == FALSE){ $error = 'Invalid Droppoint'; }
            if ($cadress->cek_trans('id', $datax['addressid']) == FALSE){ $error = "Invalid Address-ID"; }
            
            if ($error == null){
              $ori = $this->branch->get_detail($datax['droppoint'], 'district_id');
              $dest = $cadress->get_by_id($datax['addressid'])->row(); $dest = $dest->district_id;
              $this->output = $this->rj->get_cost_fee($ori, $dest, strtolower($this->properti['shipping_vendor']), $datax['weight']);
            }else{ $this->reject($error); }
        }else{ $this->reject('Parameter Required',400); }
        
      }else { $this->reject_token(); }
      $this->response('c');
    }
    
        // fungsi untuk cek shipping local atau third party
    private function calculate_shipping($sid=0,$address=null,$phone=0){
        
        $dropdistance = new Dropdistance_lib();
        $nilai = 0;
        $sales = $this->sales->get_by_id($sid)->row();
        $totals = $this->sitem->total($sid);
        if ($this->properti['courier_integration'] == 0){
            
            $droppoint = explode(',', $sales->drop_point);
            $droppoint = $droppoint[0];
            
            if ($sales->cash == 2){ $pymt = 'WALLET'; }else{ $pymt = 'CASH'; }
            $coordinatedest = $this->matrix->get_coordinate($address);
            $pointdistance = $dropdistance->calculate_distance($sales->drop_point);
            $custdistance = intval(round($this->matrix->calculation($this->branch->get_detail($droppoint, 'coordinate'),$coordinatedest)));
            $distance = round($pointdistance+$custdistance);
            $nilai = $this->shipping->calculate_rate($distance, intval($totals['amount']), $pymt); // get shipping cost
            $shiptrans = array('sales_id' => $sid, 'dates' => $sales->dates, 'coordinate' => $coordinatedest, 'drop_point' => $sales->drop_point, 'phone' => $phone, 'destination' => $address, 'point_distance' => $pointdistance, 'cust_distance' => $custdistance, 'distance' => $distance, 'amount' => $nilai, 'created' => date('Y-m-d H:i:s'));
            
            if ($this->shipping->add($shiptrans) == true){
                $transaction = array('shipping' => $nilai, 'receiver_address' => $address, 'receiver_phone' => $phone);
                if ($this->model->update($sid, $transaction) != true){ $nilai=0; }
            }else{ $nilai = 0; }
        }else{
            
             $droppoint = explode(',', $sales->drop_point);
             $dropaddress=null;
             for($i=0;$i<count($droppoint);$i++){
               $dropaddress = $dropaddress.'{"address":"'.trim($this->branch->get_detail($droppoint[$i], 'address')).'","contact_person":{"phone":"'.$this->branch->get_detail($droppoint[$i], 'phone').'"}},';
             }
            
//             $param = '{ "matter":"Food",
//                    "points":[{"address":"'.trim($this->properti['address']).'"},
//                   {"address":"'.trim($address).'"}]
//                  }';
             
              $param = '{"matter":"Food",
                         "points":['.$dropaddress.'{"address":"'.trim($address).'","contact_person":{"phone":"'.$phone.'"}}]
                        }';
             
            $result = $this->shipping->request('calculate-order', $param, 1, 'POST');
            if ($result[1] == 200){
              $hasil = json_decode($result[0]);
              $hasil = $hasil->order;
              $nilai = $hasil->delivery_fee_amount;
              $transaction = array('shipping' => $nilai, 'receiver_address' => $address, 'receiver_phone' => $phone);
              if ($this->model->update($sid, $transaction) != true){ $nilai=0; }
            }
        }
        return $nilai;
    }
    
    function xadd_multiple()
    { 
       if ($this->apix->otentikasi() == TRUE){ 
        $datax = (array)json_decode(file_get_contents('php://input')); 
        
//            print_r($datax['items']);
//              $this->output = $datax['items'];
              foreach ($datax['items'] as $value) {
                  echo $value->name.'-';
              }
        
       }else { $this->reject_token(); }
       $this->response('c');
    }
    
    // fungsi ini dilakukan oleh tenantx
    function add_multiple()
    { 
       if ($this->apix->otentikasi() == TRUE){ 
         
        $datax = (array)json_decode(file_get_contents('php://input')); 
        $decoded = $this->apix->get_decoded();
        
          if(isset($datax['items']) && isset($datax['voucher']) && isset($datax['droppoint']) && isset($datax['payment']) && isset($datax['ship_address']) && isset($datax['pickup']) && isset($datax['ship_phone']) && isset($datax['cash']) && isset($datax['shipping_amount']) && isset($datax['total_weight'])){
            $ordid = $this->model->counter().mt_rand(99,9999);
            $validcust = $this->customer->valid_cust($decoded->userid);
            $mess = null; $items = $datax['items']; $voucherminimum = 0;
            
            // operational time
            if ($this->cek_operational_time() == false){ $mess = 'Invalid Operational Hours'; }
            
            // droppoint
            if ($datax['droppoint'] == "" || $this->branch->cek_trans('id', $datax['droppoint']) != TRUE){ $mess = 'Invalid Droppoint';}
            
            // redeem voucher
            if ($datax['voucher'] != ""){
                $rdm = $this->vdiscount->redeem($datax['voucher'],$decoded->userid,$ordid,$datax['droppoint']);
                if ($rdm[0] != true){ $mess = $rdm[1]; }else{
                    $voucher = $this->vdiscount->get_by_id($datax['voucher'])->row();
                    $voucherminimum = intval($voucher->minimum);
                }
            }
            
            // shipping process
            if ($datax['pickup'] == 0){ 
                if ($datax['ship_address'] == ""){ $mess = 'Receiver address required'; } 
                if ($datax['ship_phone'] == ""){ $mess = 'Receiver phone required'; } 
            }
            
            // payment gateway
            if ($this->payment->cek_trans('id', $datax['payment']) != TRUE){ $mess = 'Invalid Payment Type'; }
            
            if ($mess == null && $validcust == TRUE && $this->sales->valid_orderid($ordid) == TRUE && count($datax['items']) > 0){
                
                if (!isset($datax['date'])){ $date = date('Y-m-d H:i:s'); }else{ $date = $datax['date']; }
                $sid = $this->sales->create_pos($ordid,$date,$datax['payment'],$decoded->log,$decoded->userid,$datax['voucher'],$datax['pickup'],$datax['droppoint'],$datax['cash']);
                foreach ($items as $value) {
                    if (!isset($value->sku) || !isset($value->qty) || !isset($value->price) || !isset($value->tax)){ $mess = "Invalid items parameter"; break; }
                    if ($this->product->get_id_by_sku($value->sku) == '0'){ $mess = 'Invalid SKU'; break; }
                    if ($this->valid_product($this->product->get_id_by_sku($value->sku), $sid) != TRUE){ $mess = "Items already registered..!"; break; }
//                    if ($this->valid_request($value->sku, $value->qty) != TRUE){ $mess = "Items ".$value->sku." qty not enough..!"; break; }
//                    if (intval($value->qty) == 0){ $mess = "Invalid Qty"; break; }
                    if (intval($value->price) == 0){ $mess = "Invalid Price"; break; }
//                    if ($this->valid_price(intval($value->price), $value->sku) != TRUE){ $mess = "Invalid Sales Price..!"; break; }
                    
                    // start transaction 
                    $this->db->trans_start(); 

                    $pid = $this->product->get_id_by_sku($value->sku);
                    $discount = floatval($value->discount);
                    $amt_price = floatval($value->qty*$value->price-$discount);
                    $tax = floatval($value->tax*$amt_price);
//                    $id = $this->model->counter();

//                    $hpp = $this->stock->min_stock($pid, $value->qty, $sid, 'SO', $id);

                    $sales = array('product_id' => $pid, 'sales_id' => $sid,
                                   'qty' => $value->qty, 'tax' => $tax, 'discount' => $discount,
                                   'price' => $value->price, 'amount' => intval($amt_price+$tax));

                    $this->sitem->add($sales);
                    $this->error = 'Transaction Posted';
                    $this->db->trans_complete();
                    // end transaction
                }
                
                if ($mess){ $this->sitem->delete_sales($sid); $this->model->force_delete($sid); $this->reject($mess,400); }
                else{ 
                    if ($datax['pickup'] == 0){ 
                        if ($datax['xpedition'] != null){
                          $transaction = array('expedition'=>$datax['xpedition'], 'totalweight'=>$datax['total_weight'],'shipping' => $datax['shipping_amount'], 'receiver_address' => $datax['ship_address'], 'receiver_phone' => $datax['ship_phone']);
                          if ($this->model->update($sid, $transaction) != true){ $this->reject('Failed to post shipping'); }
                        }else{
                          $shipping = $this->calculate_shipping($sid,$datax['ship_address'],$datax['ship_phone']);
                        }

                    }else{ $shipping = 0; }
                    if ($voucherminimum != 0){
                        $totals = $this->sitem->total($sid);
                        if (intval($totals['amount']) < $voucherminimum){ $this->reject('Tidak memenuhi minimum voucher'); }
                        else{
                            $this->vdiscount->redeem($datax['voucher'],$decoded->userid,$ordid,$datax['droppoint'],1);
                            $discpercentage = floatval($voucher->percentage/100);
                            $discount = intval($totals['amount'])*$discpercentage;
                            $this->update_trans($sid,$discount);  
                        }
                    }else{ 
                        $this->update_trans($sid); 
                        
                    }
                    
                } // form validation
            }
            elseif(count($datax['items']) == 0){ $this->reject("Empty Product Items"); }
            elseif($this->sales->valid_orderid($ordid) != TRUE){ $this->reject("Invalid Order-Id"); }
            elseif ($mess != null){ $this->reject($mess,400); }
            
          }else{ $this->reject('Parameter Required',400);}
        
       }else { $this->reject_token(); }
       $this->response('c');
    }
    
    private function update_trans($sid,$discount=0,$shipping=0)
    {
        $totals = $this->sitem->total($sid);
//        $price = intval($totals['qty']*$totals['price']);
        $price = intval($totals['amount']);
        $sales = $this->model->get_by_id($sid)->row();
        
        if ($sales->cash == 2){ // jika menggunakan wallet
            $amt = intval($totals['tax']+$price-$discount+$sales->shipping);
            $bl = $this->ledger->get_sum_transaction($sales->cust);
            $bl = floatval($bl['vamount']);
            
            $mutation = true; 
            if ($sales->cash == 2){
                if ($bl > $amt){
                  $mutation = $this->ledger->add('POS', $sales->code, $sales->dates, 0, $amt, $sales->cust);// potong saldo customer
                }else{ $mutation = false; $this->rollback($sid); $this->error = 'Saldo anda tidak mencukupi..!'; }
            }
            
            if ($mutation == true){
                  $transaction = array('tax' => $totals['tax'], 'total' => intval($price), 'discount' => $discount, 'amount' => $amt);
                  $this->model->update($sid, $transaction);
                  $this->error = "Transaksi anda berhasil, pesanan anda segera di proses.";
            }else{ $this->rollback($sid); $this->reject($this->error); } 
            
        }elseif ($sales->cash == 0){ // jika memakai payment gateway
            // api payment gateway
            $amtcost = intval($price+$sales->shipping-$discount);
            $cost = $this->payment->calculate($sales->payment_type,$amtcost); // get cost from payment type
            $nilai = '{ "external_id":"'.$sales->code.'-('.waktuindo().')", "amount":'.intval($totals['tax']+$price+$cost-$discount+$sales->shipping).', "payer_email":"'.$this->customer->get_name($sales->cust,'email').'", "description":"'.$sales->code.'" }';
            $result = $this->paymentgateway->request("invoices",$nilai,1,'POST');
            if ($result[1] == 200){
              $hasil = json_decode($result[0]);
              $transaction = array('tax' => $totals['tax'], 'cost' => $cost, 'total' => intval($price), 'discount' => $discount, 
                                   'amount' => intval($totals['tax']+$price+$cost-$discount+$sales->shipping),
                                   'transid' => $hasil->id, 'approved' => 1
                                  );
              $this->model->update($sid, $transaction);

              // send notif
              $nilai = '{ "orderid":"'.$sales->code.'", "type":"invoice" }';
              $this->apix->request($this->properti['invoice_url'],$nilai);
              
              $data['invoice_url'] = $hasil->invoice_url;
              $data['transid'] = $hasil->id;
              $data['orderid'] = $sales->code;
              $this->output = $data;

            }else{ $this->rollback($sid); $this->reject('Failed to create transaction'); }
            // api payment gateway
        }
        elseif ($sales->cash == 1){ // jika cod
            // api payment gateway
//            $cost = $this->payment->calculate($sales->payment_type,intval($price+$sales->shipping-$discount)); // get cost from payment type
            $amtcost = intval($price+$sales->shipping-$discount);
            $cost = $this->payment->calculate($sales->payment_type,$amtcost); // get cost from payment type
            $transaction = array('approved' => 1,'redeem' => 1,'tax' => $totals['tax'], 'cost' => $cost, 'total' => intval($price), 'discount' => $discount, 
                                 'amount' => intval($totals['tax']+$price+$cost-$discount+$sales->shipping)
                                  );
            $this->model->update($sid, $transaction);
            $data['orderid'] = $sales->code;
            $this->output = $data;
        }
    }
    
    function cancel_trans($transid=0)
    {
        if ($this->apix->otentikasi() == TRUE && $this->model->valid_transid($transid, $this->title) == TRUE){  
            // start transaction 
            $this->db->trans_start();
            
            $sales = $this->model->get_by_transid($transid)->row();

            $this->ledger->remove($sales->dates, 'POS', $sales->id); // hapus cust ledger
//            $this->mledger->remove($sales->dates, 'POS', $sales->id); // hapus member ledger

            $transaction = array('canceled_desc' => "Canceled by user", 'canceled' => date('Y-m-d H:i:s'));
            $this->model->update($sales->id, $transaction);

            $this->db->trans_complete();
            if ($this->db->trans_status() === TRUE){ $this->error = "1 item successfully canceled..!"; }
            else{ $this->reject(); }
            // end transaction   
        }
        else { $this->valid_404($this->model->valid_transid($transid, $this->title)); $this->reject_token(); }
        $this->response();
    }
    
    function delete($sid=0)
    {
        if ($this->apix->otentikasi() == TRUE && $this->model->valid_add_trans($sid, $this->title) == TRUE && $this->valid_confirm($sid) == FALSE){  
                // start transaction 
                $this->db->trans_start();
                $sales = $this->model->get_by_id($sid)->row();

                $this->ledger->remove($sales->dates, 'POS', $sid); // hapus cust ledger
                $this->mledger->remove($sales->dates, 'POS', $sid); // hapus member ledger
                
                $transaction = array('cancel' => 1, 'approved' => 0);
                $this->model->update($sid, $transaction);
                
                $this->db->trans_complete();
                if ($this->db->trans_status() === TRUE){ $this->error = "1 item successfully rollback..!"; }
                else{ $this->reject(); }
                // end transaction   
        }
        elseif($this->valid_confirm($sid) == TRUE){ $this->reject("Sales Not Posted..!",401); }
        else { $this->valid_404($this->model->valid_add_trans($sid, $this->title)); $this->reject_token(); }
        $this->response();
    }
    
    function delete_item($id=0)
    {
        if ($this->apix->otentikasi() == TRUE && $this->sitem->valid_add_trans($id, $this->title) == TRUE){   
           $sid = $this->sitem->get_salesid($id);
           if ($this->valid_confirm($sid) == TRUE){
                // start transaction 
                $this->db->trans_start();    
                 $this->sitem->delete($id); // memanggil model untuk mendelete data
                 $this->update_trans($sid);
                $this->db->trans_complete();
                if ($this->db->trans_status() === TRUE){ $this->error = "1 item successfully removed..!"; }
                else{ $this->reject(); }
                // end transaction
           }
           else{ $this->reject('Sales Already Confirmed..!'); }
        }
        else { $this->valid_404($this->sitem->valid_add_trans($id, $this->title)); $this->reject_token(); }
        $this->response();
    }
       
    function valid_required($val)
    {
        $stts = $this->input->post('cstts');
        if ($stts == 1){
            if (!$val){
              $this->form_validation->set_message('valid_required', "Field Required..!"); return FALSE;
            }else{ return TRUE; }
        }else{ return TRUE;  }
    }
    
    function valid_login()
    {
        if (!$this->session->userdata('username')){
            $this->form_validation->set_message('valid_login', "Transaction rollback relogin to continue..!");
            return FALSE;
        }else{ return TRUE; }
    }
    
    function valid_request($product,$request)
    {
        $branch = $this->branch->get_branch_default();
        $pid = $this->product->get_id_by_sku($product);
        $qty = $this->stockledger->get_qty($pid, $branch, $this->period->month, $this->period->year);
        
        if ($request > $qty){
            $this->form_validation->set_message('valid_request', "Qty Not Enough..!");
            return FALSE;
        }else{ return TRUE; }
    }
    
    function valid_product($id,$sid)
    {   
        if ($sid == 0 || $this->sitem->valid_product($id,$sid) == FALSE)
        {
            $this->form_validation->set_message('valid_product','Product already listed..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_name($val)
    {
        if ($this->salesmodel->valid('name',$val) == FALSE)
        {
            $this->form_validation->set_message('valid_name','Name registered..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_price($price,$sku){
        $pid = $this->product->get_id_by_sku($sku);
        if ($lowprice){ $lowprice = intval($lowprice->pricelow); }else{ $this->form_validation->set_message('valid_price','Invalid Product..!'); return FALSE; }
        if (intval($price) < intval($lowprice)){ $this->form_validation->set_message('valid_price','Invalid Sales Price..!'); return FALSE; }
        else{ return TRUE; }
    }
    
    function valid_payment($uid){
        if ($this->payment->cek_trans('id',$uid) == FALSE){
            $this->form_validation->set_message('valid_payment', 'Invalid Payment..!');
            return FALSE;
        }else{ return TRUE; }
//        print_r($this->payment->valid('id',$uid));
//        return FALSE;
    }
    
    function valid_confirm($sid)
    {
        $val = $this->model->get_by_id($sid)->row();
        if ($val->approved == 1)
        {
            $this->form_validation->set_message('valid_confirm','Sales Already Confirmed..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_cancel($sid)
    {
        $val = $this->model->get_by_id($sid)->row();
        if ($val->canceled != null)
        {
            $this->form_validation->set_message('valid_cancel','Sales Already Canceled..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_items($sid)
    {
        if ($this->sitem->valid_items($sid) == FALSE)
        {
            $this->form_validation->set_message('valid_items',"Empty Transaction..!");
            return FALSE;
        }
        else{ return TRUE; }
    }
}

?>