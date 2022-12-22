<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/jwt/JWT.php';
use \Firebase\JWT\JWT;

class Courier extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Courier_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->city = new City_lib();
        $this->disctrict = new District_lib();
        $this->login = new Courier_login_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
        $this->notif = new Notif_lib();
        $this->api = new Api_lib();
        $this->acl = new Acl();
        $this->ledger = new Courier_ledger_lib();
        $this->balance = new Courier_balance_lib();
        $this->shipping = new Shipping_lib();
        $this->customer = new Customer_lib();
        $this->droppoint = new Droppoint_lib();
        $this->sales = new Sales_lib();
        $this->cledger = new Customer_ledger_lib();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 

    }

    private $properti, $modul, $title, $city, $disctrict, $balance, $droppoint, $sales;
    private $role, $login, $period, $notif, $api, $ledger, $shipping, $customer, $cledger;

    function index(){ }
    
    
//    ========================== courier sales ===============================
    
    function get_ongoing(){
        
        if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){ 
            $shipping = $this->shipping->get_ongoing();
            
            if($shipping){ 

                $sales = $this->sales->get_by_id($shipping->sales_id)->row();
                if ($sales->cash == 0){ $payment = 'Online Payment';}else{ $payment = 'COD'; }
                $this->resx[] = array ("id" => $shipping->id, "code" => $sales->code, "dates" => tglincomplete($shipping->dates).' '. timein($shipping->dates),
                                   "drop_point" => $this->droppoint->get_detail($shipping->drop_point, 'code').' - '.$this->droppoint->get_detail($shipping->drop_point, 'address'),
                                   "cust" => $sales->cust, 'customer' => $this->customer->get_name($sales->cust), 'customer_phone' => $shipping->phone, "total" => intval($sales->amount+$sales->shipping), "amount" => floatval($sales->amount),
                                   "shipping" => floatval($sales->shipping),
                                   "payment_type" => $payment, "coordinate" => $shipping->coordinate, "destination" => $shipping->destination, "distance" => $shipping->distance);            
            }
            $data['result'] = $this->resx;
            $this->output = $data;
        }else{ $this->reject_token(); }
        $this->response('c');
    }
        
    // fungsi untuk melakukan booking dari driver
    function book_order($sid=0){
        
       if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){  
           
           $decoded = $this->api->get_decoded();

           if ($this->shipping->cek_trans('sales_id', $sid) == TRUE){
              // cek dulu apakah sudah d ambil atau belum
              $ship = $this->shipping->get_by_sales($sid)->row();
              if ($ship->courier == 0){
                $shp = array('courier' => $decoded->userid, 'status' => 1, 'dates' => date('Y-m-d H:i:s'));  // update courier di shipping
                if ($this->shipping->update_by_sales($sid, $shp) == true){
                   $this->error = "Booked Process..!";    // kirim notifikasi
                }else{ $this->reject("Book Processed Fail"); }
              }else{ $this->reject('Transaction has been taken'); }
           }
           else{ $this->reject('Order Not Found',404); }
       }
       else{ $this->reject_token(); }
       $this->response();
    }
    
    // fungsi untuk redeem pesanan
    function redeem($sid=0){
        if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){  
           $datax = (array)json_decode(file_get_contents('php://input')); 
           $decoded = $this->api->get_decoded();
           
           // verifikasi kurir
           if ($this->shipping->cek_trans('sales_id', $sid) == TRUE){
              // cek dulu apakah sudah d ambil atau belum
              $ship = $this->shipping->get_by_sales($sid)->row();
              if ($ship->courier == $decoded->userid && $ship->received == null){
                $shp = array('received' => date('Y-m-d H:i:s'), 'received_desc' => $datax['desc']);  // update courier di shipping
                if ($this->shipping->update_by_sales($sid, $shp) == true){
                   $this->error = "Redeem Process..!";    // kirim notifikasi
                }else{ $this->reject("Redeem Processed Fail"); }
              }
              elseif($ship->courier != $decoded->userid){ $this->reject('Courier not suitable'); }
              elseif($ship->received != null){ $this->reject('Transaction has been redeem..!'); }
           }
           else{ $this->reject('Order Not Found',404); }
       }
       else{ $this->reject_token(); }
       $this->response(); 
    }
    
    function transaction(){
        if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){  
           $datax = (array)json_decode(file_get_contents('php://input')); 
           $decoded = $this->api->get_decoded();
           
           if(isset($datax['start']) && isset($datax['end']) && isset($datax['cancel']) && isset($datax['limit']) && isset($datax['offset'])){
             
             if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
             if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }  
               
             $result = $this->shipping->search($datax['start'],$datax['end'],$decoded->userid,1, $this->limitx, $this->offsetx)->result();   
             $this->count = $this->shipping->search($datax['start'],$datax['end'],$decoded->userid,1,$this->limitx, $this->offsetx,1);   
             foreach($result as $res)
             {
                if ($res->status == "0"){$status = 'Not Delivered';}else{ $status = "Delivered"; }
                $sales = $this->sales->get_by_id($res->sales_id)->row();
                $this->resx[] = array ("id"=>$res->id, "sales_id" => $res->sales_id, "code" => $sales->code,
                                       "date"=>tglin($res->created), "time"=>timein($res->created),
                                       "pickup_date"=>tglin($res->dates), "pickup_time"=>timein($res->dates),
                                       "drop_point_code"=> $this->droppoint->get_detail($res->drop_point, 'code'), "drop_point_address"=> $this->droppoint->get_detail($res->drop_point, 'address'),
                                       "coordinate" => $res->coordinate, "destination"=> $res->destination, "destination_phone"=> $res->phone, "distance"=> $res->distance,
                                       "received" => tglin($res->received).' - '.timein($res->received),
                                       "amount" => floatval($sales->amount), "shipping_amount"=> floatval($res->amount),
                                       "status" => $status
                                      );
             }
             
             $data['record'] = $this->count; 
             $data['result'] = $this->resx;
               
           }else{ $this->reject('Parameter Required',400);}
           $this->output = $data;
       }
       else{ $this->reject_token(); }
       $this->response('c'); 
    }
    
    // fungsi untuk menerima location coordinate dari kurir
    function post_location(){
       if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){  
           $datax = (array)json_decode(file_get_contents('php://input')); 
           $decoded = $this->api->get_decoded();
           if (isset($datax['coordinate'])){
               $res = $this->login->set_coordinate($decoded->userid, $datax['coordinate']);
               if ($res != true){ $this->reject('Failed post location'); }
           }else{ $this->reject('Parameter Required'); }
       }
       else{ $this->reject_token(); }
       $this->response(); 
    }
    
     // fungsi top up saldo customer
     function topup($cust=0,$amount=0){
       if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){  
           $decoded = $this->api->get_decoded();
           
           if ($this->customer->valid_cust($cust) == TRUE && $this->shipping->get_pending_trans($decoded->userid) == TRUE){
               // action
               $counter = $this->cledger->counter()->row();
               $ordid = intval($counter->id+1).mt_rand(99,9999);
               $stts  = $this->cledger->add('TOP', $ordid, date('Y-m-d H:i:s'), floatval($amount), 0, $cust);
               $stts1 = $this->ledger->add('TOP', $ordid, date('Y-m-d H:i:s'), floatval($amount), 0, $decoded->userid);
               if ($stts == TRUE && $stts1 == TRUE){
                   $this->error = "Top up successuly on : ". date('Y-m-d H:i:s');
               }else{ $this->reject('Failed to topup'); }
           }
           elseif ($this->shipping->get_pending_trans($decoded->userid) != TRUE){ $this->reject('Courier still have pending transaction'); }
           elseif ($this->customer->valid_cust($cust) != TRUE){ $this->reject("Invalid Customer"); }
       }
       else{ $this->reject_token(); }
       $this->response(); 
    }
    
    
   // ========================== api ==========================================
    
    private function send_confirmation_email($pid=0)
    {   
        // property display
       $data['p_logo'] = $this->properti['logo'];
       $data['p_name'] = $this->properti['name'];
       $data['p_site_name'] = $this->properti['sitename'];
       $data['p_address'] = $this->properti['address'];
       $data['p_zip'] = $this->properti['zip'];
       $data['p_city'] = $this->properti['city'];
       $data['p_phone'] = $this->properti['phone1'];
       $data['p_email'] = $this->properti['email'];
       
       $customer = $this->model->get_by_id($pid)->row();

       $data['code']    = $customer->member_no;
       $data['name']    = strtoupper($customer->first_name.' '.$customer->last_name);
       $data['type']    = strtoupper($customer->type);
       $data['phone']    = $customer->phone1;
       $data['email']    = $customer->email;
       $data['joined']  = tglin($customer->joined).' / '. timein($customer->joined);
         
//       $this->load->view('customer_receipt',$data); 
        // email send
       $html = $this->load->view('customer_receipt',$data,true); 
       return $this->notif->send_notif(0, $pid, $data['p_name'].' - Welcome Member - '.$data['code'], $html, 'customer');
//       return $this->notif->create($pid, $html, 0, $this->title, 'Wamenak E-Welcome - '.strtoupper($data['code']));
    }
    
    function req_otp(){
        $datas = (array)json_decode(file_get_contents('php://input'));
        
        if (isset($datas['username'])){
            if ($this->model->login($datas['username']) == true){
                
                $userid = $this->model->get_by_username($datas['username'])->row();
                if ($this->login->get_reqcount($userid->id, date('Y-m-d')) < 3){
                    $this->logid = mt_rand(1000,9999);
                    if ($this->login->set_otp($userid->id,$this->logid) == true){
                      $stts = $this->notif->send_notif(0,$userid->id, "OTP Req : ". waktuindo(), "Kode Pin OTP Anda : ".$this->login->get_by_userid($userid->id), 'member-forgot',1);
                      if ($stts != true){ $this->reject('Failure to send otp'); }else{ $this->output = 'OTP has been sent.'; }
                    }
                }else{ $this->reject('Maximum OTP Request',401); }
            }else{ $this->reject('Invalid Username',401); }
        }else{ $this->reject('Username required',400); }
        $this->response('c');
    }
    
    function forgot(){
        $datas = (array)json_decode(file_get_contents('php://input'));
        
        if (isset($datas['username']) && isset($datas['new_password']) && isset($datas['otp'])){
            if (!$datas['otp']){ $this->reject("OTP Required",400); }
            elseif (!$datas['username']){ $this->reject("Username Required",400); }
            elseif (!$datas['new_password']){ $this->reject("New Password Required",400); }
            else{
                $mess = null;
                $userid = $this->model->get_by_username($datas['username'])->row();
                $otp = $this->login->get_by_userid($userid->id);
                
                if (pass_verify ($datas['new_password'], $userid->password) == true){ $this->reject("Can't use previous password.",401); }
                elseif ($datas['otp'] != $otp){$this->reject("Invalid OTP",401);}
                elseif ($this->model->login($datas['username']) != true){ $this->reject("Invalid Username",401); }
                else{
                   $member = array('password' => pass_hash($datas['new_password']));
                   if ($this->model->update($userid->id, $member) == true){$this->output = 'Password has been changed.';
                   }else{ $this->reject('Update Failed',500); }
                }
            }
        }else{ $this->reject('Parameter required',400); }
        $this->response('c');
    }
    
            // ------ json login -------------------
    function login(){
        
        $datas = (array)json_decode(file_get_contents('php://input')); 
        $user = $datas['username'];
        $pass = $datas['password'];
        
        $logid = null;
        $token = null;
            
          if($this->model->cek_user($user) != TRUE){$this->reject('User Not Found..!',404);}
          else{
              
              $res = $this->model->get_by_username($user)->row();
              if(pass_verify($pass, $res->password) == TRUE){
                  
                 if ($this->model->login($user) == TRUE){

                    // $sms = new Sms_lib();
                    // $push = new Push_lib();
                    $logid = mt_rand(1000,9999);
                    $res = $this->model->get_by_username($user)->row(); 
                    // $sms->send($user, $this->properti['name'].' : Login OTP Code : '.$logid);
                    // $push->send_device($userid, $this->properti['name'].' : Kode OTP : '.$logid);

                    $date = new DateTime();
                    $payload['userid'] = $res->id;
                    $payload['name'] = $res->name;
                    $payload['username'] = $user;
                    $payload['phone'] = $res->phone;
                    $payload['log'] = $logid;
                    // $payload['iat'] = $date->getTimestamp();
                    // $payload['exp'] = $date->getTimestamp() + 60*60*2;
                    $token = JWT::encode($payload, 'vinkoo');
                    $this->login->add($res->id, $token, $datas['device']);
                 }else{ $this->reject("Invalid Credential..!",401); }
                  
              }else{ $this->reject('Invalid Password',401); }
          }

        $this->output = array('token' => $token, 'log' => $logid); 
        $this->response('c');
    }
    
    function logout(){
        if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){

          $decoded = $this->api->otentikasi('decoded');
          $this->login->logout($decoded->userid);
          $this->date  = date('Y-m-d');
          $this->time  = waktuindo();
          $this->log->insert($decoded->userid, $this->date, $this->time, 'logout');
            
        }else{ $this->reject('Invalid Token or Expired..!',400); }
        $this->response();
    }
    
    function ledger()
    {        
        if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){
            
           $datax = (array)json_decode(file_get_contents('php://input')); 
           $month= $this->period->month; $year = $this->period->year;
           
           if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
           if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
           if (isset($datax['month']) && $datax['month'] != null){ $month = $datax['month']; }
           if (isset($datax['year']) && $datax['year'] != null){ $year = $datax['year']; }
        
           $decoded = $this->api->get_decoded();
           $result = $this->ledger->get_transaction($decoded->userid,$month,$year,$this->limitx, $this->offsetx)->result();
           $this->count = $this->ledger->get_transaction($decoded->userid,$month,$year,$this->limitx, $this->offsetx,1);
           
           foreach($result as $res)
           {
               if ($res->code == 'TOP'){ $notes = 'TOPUP'; }
               elseif($res->code == 'POS'){ $notes = 'POS'; }
               elseif($res->code == 'WD'){ $notes = 'WD'; }
               
               $this->resx[] = array ("id"=>$res->id, 
                                      "code"=> $res->code, "no"=>$res->no, "date"=>tglin($res->dates),
                                      "time"=>timein($res->dates),"notes"=>$notes,"amount"=> floatval($res->vamount)
                                     );
           }
           
           $sum = $this->ledger->get_sum_transaction($decoded->userid, $month, $year);
           $bl = $this->balance->get($decoded->userid, $month, $year);
           $data['balance'] = floatval($bl->beginning + $sum['vamount']);
           $data['record'] = $this->count; 
           $data['result'] = $this->resx;
           $this->output = $data;
            
        }else { $this->reject_token(); }
        $this->response('c');
    }
    
    function get()
    {   
        if ($this->api->motentikasi() == TRUE && $this->api->get_decoded() != null){ 
            
            $decoded = $this->api->get_decoded();
            $uid = $decoded->userid;

            $customer = $this->model->get_by_id($uid)->row();
            $data['ic'] = $customer->ic;
            $data['name'] = $customer->name;
            $data['address'] = $customer->address;
            $data['phone'] = $customer->phone;
            $data['email'] = $customer->email;
            $data['company'] = $customer->company;
            $data['joined'] = tglincompletetime($customer->joined);
            $data['status'] = $customer->status;
            $data['image'] = $this->properti['image_url'].'courier/'.$customer->image;
            $this->output = $data;
        
        }else { $this->reject_token(); }
        $this->response('c');
    }

    function add()
    {
	// Form validation
        $this->form_validation->set_rules('tic', 'IC', 'required|callback_valid_ic');
        $this->form_validation->set_rules('tname', 'Name', 'required');
        $this->form_validation->set_rules('taddress', 'Address', 'required');
        $this->form_validation->set_rules('tphone', 'Phone', 'required'); // validasi nomor ponsel
        $this->form_validation->set_rules('temail', 'Email', 'required|valid_email|callback_valid_email');
        $this->form_validation->set_rules('tpassword', 'Password', 'required'); // password        

        if ($this->form_validation->run($this) == TRUE)
        {
            $config['upload_path'] = $this->properti['url_upload'].'courier/';
            $config['file_name'] = split_space($this->input->post('tname').'_'.waktuindo());
            $config['allowed_types'] = 'jpg|gif|png|jpeg';
            $config['overwrite'] = true;
            $config['max_size']	= '50000';
            $config['max_width']  = '30000';
            $config['max_height']  = '30000';
            $config['remove_spaces'] = TRUE;

            $this->load->library('upload', $config);

            if ( !$this->upload->do_upload("userfile")) // if upload failure
            {   
                $info['file_name'] = null;
                $data['error'] = $this->upload->display_errors();
                $member = array('name' => strtolower($this->input->post('tname')),
                                'ic' => $this->input->post('tic'),
                                'address' => $this->input->post('taddress'),
                                'phone' => $this->input->post('tphone'),
                                'email' => $this->input->post('temail'),
                                'password' => pass_hash($this->input->post('tpassword')), 
                                'joined' => date('Y-m-d H:i:s'),
                                'image' => null, 'status'=>0, 'created' => date('Y-m-d H:i:s'));
            }
            else
            {
                $info = $this->upload->data();
                $member = array('name' => strtolower($this->input->post('tname')),
                                'ic' => $this->input->post('tic'),
                                'address' => $this->input->post('taddress'),
                                'phone' => $this->input->post('tphone'),
                                'email' => $this->input->post('temail'),
                                'password' => pass_hash($this->input->post('tpassword')), 
                                'joined' => date('Y-m-d H:i:s'),
                                'image' => $info['file_name'], 'status'=>0, 'created' => date('Y-m-d H:i:s'));
            }

            if ($this->model->create($member) != true){ $this->reject($this->upload->display_errors());
            }else{
                $this->balance->create($this->model->counter_model(1), $this->period->month, $this->period->year);
                $lid = $this->model->get_latest();
                // create login lib
if ($this->login->add($lid->id) == true){ $this->output = $this->model->get_latest(); /* panggil fungsi request otp */ }
            }  
        }
        else{ $this->reject(validation_errors(),400); }
        $this->response('c');
    }
    
    function verify($uid=0,$otp=0){
        if ($this->model->valid_add_trans($uid, $this->title) == TRUE){
            $dbotp = $this->login->get_by_userid($uid);
            if ($dbotp == $otp){ 
                
               $member = array('status' => 1); 
               if ($this->model->update($uid, $member) != true){ $this->reject("Failed to verfify user.");}
               else{ 
                  if ($this->send_confirmation_email($uid) == true){$this->output = $this->model->get_latest();}
                  else{ $this->reject('Failed to sent notif',201); } 
               }
            }
            else{ $this->reject('Invalid OTP'); }
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }
    
    function upload_image()
    {
        if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){

            $decoded = $this->api->get_decoded();
            $val = $this->model->get_by_id($decoded->userid)->row();
            
            $config['upload_path'] = $this->properti['url_upload'].'courier/';
            $config['file_name'] = split_space($decoded->userid.$val->name);
            $config['allowed_types'] = 'jpg|gif|png|jpeg|PNG';
            $config['overwrite'] = true;
            $config['max_size']	= '10000';
            $config['max_width']  = '30000';
            $config['max_height']  = '30000';
            $config['remove_spaces'] = TRUE;

            $this->load->library('upload', $config);

            if ( !$this->upload->do_upload("userfile")) // if upload failure
            {
                @unlink($this->properti['url_upload'].'member/'.$val->image);
                $info['file_name'] = null;
                $data['error'] = $this->upload->display_errors();
            }
            else{ $info = $this->upload->data(); }
            
            $member = array('image' => $info['file_name']);
            $this->model->update($decoded->userid, $member);
            $this->crop_image($info['file_name']);
            
            if ($this->upload->display_errors()){ $this->output = $this->upload->display_errors(); }
        
       }else{ $this->reject('Invalid Token or Expired..!',401); }
       $this->response('c');
    }
    
    private function crop_image($filename,$width=500,$height=500){
        
        $config['image_library'] = 'gd2';
        $config['source_image'] = $this->properti['url_upload'].'courier/'.$filename;
        $config['maintain_ratio'] = TRUE;
        $config['width']  = $width;
        $config['height'] = $height;
        $this->load->library('image_lib', $config); 
        if (!$this->image_lib->resize()){ return FALSE; }
    }
    
    function valid_image($val)
    {
        if ($val == 0)
        {
            if (!$this->input->post('turl')){ $this->form_validation->set_message('valid_image','Image Url Required..!'); return FALSE; }
            else { return TRUE; }            
        }
    }
    
    function valid_member($val){
        
        if ($this->model->cek_user($val) == FALSE)
        {
            $this->form_validation->set_message('valid_member','Invalid Customer..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_ic($val)
    {
        if ($this->model->valid_ic($val) == FALSE)
        {
            $this->form_validation->set_message('valid_ic','IC registered..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_email($val)
    {
        if ($this->model->valid_member($val, $this->input->post('tphone1')) == FALSE)
        {
            $this->form_validation->set_message('valid_email','Email / Phone registered..!');
            return FALSE;
        }
        else{ return TRUE; }
    }

    function validating_email($val)
    {
	$id = $this->session->userdata('langid');
	if ($this->Customer_model->validating('email',$val,$id) == FALSE)
        {
            $this->form_validation->set_message('validating_email', "Email registered!");
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    // Fungsi update untuk mengupdate db
    function update($param=0)
    {
        if ($this->api->motentikasi() == TRUE){
            
	// Form validation
        $this->form_validation->set_rules('taddress', 'Address', 'required');
        $this->form_validation->set_rules('tphone', 'Phone', 'required'); // validasi nomor ponsel     
                        
        if ($this->form_validation->run($this) == TRUE)
        {
            // start update 1
            $config['upload_path'] = './images/courier/';
            $config['file_name'] = split_space($this->input->post('tname').'_'.waktuindo());
            $config['allowed_types'] = 'jpg|gif|png';
            $config['overwrite'] = true;
            $config['max_size']	= '10000';
            $config['max_width']  = '30000';
            $config['max_height']  = '30000';
            $config['remove_spaces'] = TRUE;

            $this->load->library('upload', $config);

            if ( !$this->upload->do_upload("userfile")) // if upload failure
            {
                $info['file_name'] = null;
                $data['error'] = $this->upload->display_errors();

                $member = array('address' => $this->input->post('taddress'),
                                'phone' => $this->input->post('tphone')
                               );

            }
            else{
                $info = $this->upload->data();

                $member = array('address' => $this->input->post('taddress'),
                                'phone' => $this->input->post('tphone'),
                                'image' => $info['file_name']);
            }
            
            $decoded = $this->api->get_decoded();
            if ($this->model->update($decoded->userid, $member) != true){ $this->reject($this->upload->display_errors());
            }else{ $this->error = $this->title.' successfully saved..!'; }
        }
        else{ $this->reject(validation_errors(),400);}
        
        }else{ $this->reject('Invalid Token or Expired..!',400); }
        $this->response('c');
    }

}

?>