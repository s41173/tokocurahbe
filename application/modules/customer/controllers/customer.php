<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/jwt/JWT.php';
use \Firebase\JWT\JWT;

class Customer extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Customer_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->city = new City_lib();
        $this->disctrict = new District_lib();
        $this->login = new Customer_login_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
        $this->notif = new Notif_lib();
        $this->api = new Api_lib();
        $this->acl = new Acl();
        $this->ledger = new Customer_ledger_lib();
        $this->balance = new Customer_balance_lib();
//        $this->memberevent = new Customer_event_lib();
        $this->shipping = new Shipping_lib();
        $this->sales = new Sales_lib();
        $this->whislist = new Whistlist_lib();
        $this->product = new Product_lib();
        $this->caddress = new Customer_address_lib();  
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 

    }

    private $properti, $modul, $title, $city, $disctrict, $balance, $whislist, $product;
    private $role, $login, $period, $notif, $api, $ledger,$shipping,$sales,$caddress;

    function index(){ }
    
    private function email_validation($param)
    {
        if ( filter_var($param, FILTER_VALIDATE_EMAIL) != TRUE ){ return FALSE; } else { return TRUE; }
    }
    
    function feedback(){
       if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){ 
         $datax = (array)json_decode(file_get_contents('php://input'));
         $decoded = $this->api->get_decoded();
         $customer = new Customer_lib();
            
         $droppoint = new Droppoint_lib();
         $mess = null;
         
         if (isset($datax['subject']) && isset($datax['outlet']) && isset($datax['email']) && isset($datax['message'])){
         
             if ($droppoint->cek_trans('id', $datax['outlet']) == FALSE){ $mess = 'Invalid Outlet'; }
             if ($this->valid_email($datax['email']) == FALSE){ $mess = 'Invalid Email Format'; }
             
              $this->load->library('email');
             
              $config['protocol']   = 'mail';
              $config['smtp_host']  = 'mail.geelaboba.com';
              $config['smtp_user']  = 'info@geelaboba.com';
              $config['smtp_pass']  = 'J4y_kiran';
              $config['smtp_port']  = '587';
              $config['priority']   = 1;
              $config['charset']    = 'utf-8';
              $config['wordwrap']   = TRUE;
              
              $this->email->initialize($config);
//              $this->email->from($customer->get_email($decoded->userid), $customer->get_name($decoded->userid));
              $this->email->from($datax['email'], $customer->get_name($decoded->userid));
              $this->email->to($this->properti['email']);
              $this->email->cc($this->properti['cc_email']);
              $this->email->subject($datax['subject']);
              $this->email->message($datax['message']);
              
              if ($mess == null)
              {
//                  if ($this->email->send() != TRUE){ print_r($this->email->print_debugger());}else{ echo 'berhasil'; }
                  
                if (@$this->email->send() != TRUE){ $this->reject($this->email->print_debugger()); }else{ $this->output = "Feedback kamu berhasil dikirim"; }
              }else{ $this->reject($mess); }
            
         }else{ $this->reject('Parameter Required'); }
         
       }else { $this->reject_token(); }
       $this->response('c');  
    }
    
    function notif(){
       if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){ 
         $datax = (array)json_decode(file_get_contents('php://input'));
         $decoded = $this->api->get_decoded();
         $uid = $decoded->userid;
         
         if (isset($datax['type']) && isset($datax['campaign']) && isset($datax['read']) && isset($datax['limit']) && isset($datax['offset'])){
         
            $nilai = '{ "type":"'.$datax['type'].'","campaign":"'.$datax['campaign'].'","read":"'.$datax['read'].'","limit":"'.$datax['limit'].'","offset":"'.$datax['offset'].'" }';

            $result = $this->api->request($this->properti['notif_url'].'notif/get_notif/'.$decoded->userid,$nilai,0,'POST');
            $result = json_decode($result);
//            print_r($result->content);
            $this->output = $result->content;
            
         }else{ $this->reject('Parameter Required'); }
         
       }else { $this->reject_token(); }
       $this->response('c');
    }
    
    function notif_detail($uid=0){
       if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){ 
         $datax = (array)json_decode(file_get_contents('php://input'));
         $decoded = $this->api->get_decoded();
//         $uid = $decoded->userid;
         
         $result = $this->api->request($this->properti['notif_url'].'notif/detail/'.$uid,null,0,'GET');
         $result = json_decode($result);
//         print_r($result);
         $this->output = $result->content->result;
            
       }else { $this->reject_token(); }
       $this->response('c');
    }
    
    function decode_token(){
        if ($this->api->otentikasi() == TRUE  && $this->api->get_decoded() != null){
            $decoded = $this->api->otentikasi('decoded');
            $this->output = array('userid' => $decoded->userid, 'name'=>$decoded->name, 'username' => $decoded->username, 'phone' => $decoded->phone, 'log' => $decoded->log);
//            print_r($decoded->userid);
        }else{ $this->reject_token(); }
        $this->response('c');
    }
    
   // ========================== api ==========================================
    
    function confirm_delivery($sid=0){
      if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){ 
        $datax = (array)json_decode(file_get_contents('php://input'));
        $decoded = $this->api->get_decoded();
        $uid = $decoded->userid;

         // cek apakah status ==1 & received != null & 
        if (isset($datax['rating']) && isset($datax['comment'])){
            $mess = null; $error = 200;
            if ($this->sales->cek_trans('id', $sid) != TRUE){ $mess = 'Sales Transaction Not Found'; $error = 404; }
            if ($this->shipping->cek_trans('sales_id', $sid) != TRUE){ $mess = 'Shipping Transaction Not Found'; $error = 404; }
            
          $shiptrans = $this->shipping->get_by_sales($sid)->row();  
          if ($mess == null && $shiptrans->rating == 0){
            $sales = $this->sales->get_by_id($sid)->row();
            if ($sales->cust == $uid){
                $shp = array('rating' => $datax['rating'], 'comments' => $datax['comment']); 
                if ($this->shipping->update_by_sales($sid, $shp) == true){
                   $this->error = "Rating Process..!";    // kirim notifikasi
                }else{ $this->reject("Rating Processed Fail"); }
            }else{ $this->reject('Invalid Sales Customer'); }
          }
          elseif ($shiptrans->rating != 0){  $this->reject("Rating has been given"); }
          else{ $this->reject($mess,$error); }
        }else{ $this->reject('Parameter Required'); }
      }else { $this->reject_token(); }
      $this->response('c');
    }
    
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
            
            $validuser = $this->model->cek_user($datas['username']);
            $validuserphone = $this->model->cek_user_phone($datas['username']);
//            $login = $this->model->login($datas['username']);
            
            if ($validuser == true || $validuserphone == true){
                
                if ($validuser == true){
                  $userid = $this->model->get_by_username($datas['username'])->row();    
                }elseif ($validuserphone == true){
                  $userid = $this->model->get_by_phone($datas['username'])->row(); 
                }
                
//                $userid = $this->model->get_by_username($datas['username'])->row();
                if ($this->login->get_reqcount($userid->id, date('Y-m-d')) < 3){
                    $this->logid = mt_rand(1000,9999);
                    if ($this->login->set_otp($userid->id,$this->logid) == true){
                      $stts = $this->notif->send_notif(0,$userid->id, "OTP Req : ". waktuindo(), "Kode Pin OTP Anda : ".$this->login->get_by_userid($userid->id), 'member-forgot');
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
            
            $validuser = $this->model->cek_user($datas['username']);
            $validuserphone = $this->model->cek_user_phone($datas['username']);
            
            if (!$datas['otp']){ $this->reject("OTP Required",400); }
            elseif (!$datas['username']){ $this->reject("Username Required",400); }
            elseif ($validuser != true && $validuserphone != true){ $this->reject("User not found",400); }
            elseif (!$datas['new_password']){ $this->reject("New Password Required",400); }
            else{
                $mess = null;
                                
                if ($validuser == true){
                  $userid = $this->model->get_by_username($datas['username'])->row();    
                }elseif ($validuserphone == true){
                  $userid = $this->model->get_by_phone($datas['username'])->row(); 
                }
                $otp = $this->login->get_by_userid($userid->id);
                
                if (pass_verify ($datas['new_password'], $userid->password) == true){ $this->reject("Can't use previous password.",401); }
                elseif ($datas['otp'] != $otp){$this->reject("Invalid OTP",401);}
                elseif ($this->model->login($userid->email) != true){ $this->reject("Invalid Username",401); }
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
            
          $validuser = $this->model->cek_user($user);
          $validuserphone = $this->model->cek_user_phone($user);
          
          if($validuser != TRUE && $validuserphone != TRUE){$this->reject('User Not Found..!',404);}
          else{
              
              if ($validuser == TRUE){$res = $this->model->get_by_username($user)->row(); }
              elseif ($validuserphone == TRUE){ $res = $this->model->get_by_phone($user)->row();}

              if(pass_verify($pass, $res->password) == TRUE){
                  
                 if ($this->model->login($res->email) == TRUE){

                    // $sms = new Sms_lib();
                    // $push = new Push_lib();
                    $logid = mt_rand(1000,9999);
                    $res = $this->model->get_by_username($res->email)->row(); 
                    // $sms->send($user, $this->properti['name'].' : Login OTP Code : '.$logid);
                    // $push->send_device($userid, $this->properti['name'].' : Kode OTP : '.$logid);

                    $date = new DateTime();
                    $payload['userid'] = $res->id;
                    $payload['name'] = $res->first_name;
                    $payload['username'] = $user;
                    $payload['phone'] = $res->phone1;
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
        if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){

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
        if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){
            
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
    
    function whislist(){
       if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){ 
            
          $datax = (array)json_decode(file_get_contents('php://input'));  
          if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
          if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
           
            $decoded = $this->api->get_decoded();
            $result = $this->whislist->get($decoded->userid,$this->limitx, $this->offsetx)->result();
            $this->count = $this->whislist->get($decoded->userid,$this->limitx, $this->offsetx,1);
            
            foreach($result as $res)
            {  
                $product = $this->product->get_detail_based_id($res->product_id);
                $period = null;
                if ($product->restricted == 1){
                    $start = explode(':', $product->start); $start = $start[0].':'.$start[1];
                    $end = explode(':', $product->end); $end = $end[0].':'.$end[1];
                    $period = $start.' - '.$end;
                }
                
               $img = $this->properti['image_url'].'product/'.$product->image;
               $this->resx[] = array ("id"=>$res->id, "product_id"=>$res->product_id, "image"=> $img,
                                      "name"=> $product->name, "sku"=>$product->sku, 
                                      "price"=>floatval($product->price), "restricted" => $product->restricted, "period" => $period,
                                      "rating" => $product->rating, "publish"=>$product->publish,
                                      "date"=>tglin($res->created),
                                      "time"=>timein($res->created)
                                     );
            }
            
            $data['record'] = $this->count; 
            $data['result'] = $this->resx;
            $this->output = $data;
        
       }else { $this->reject_token(); }
       $this->response('c');
    }
    
    function get_by_id($uid=0)
    {   
        if ($this->model->valid_add_trans($uid, $this->title) == TRUE){
            
            $customer = $this->model->get_by_id($uid)->row();
            $data['fname'] = $customer->first_name;
            $data['lname'] = $customer->last_name;
            $data['type'] = $customer->type;
            $data['address'] = $customer->address;
            $data['shipping'] = $customer->shipping_address;
            $data['phone1'] = $customer->phone1;
            $data['phone2'] = $customer->phone2;
            $data['email'] = $customer->email;
            $data['website'] = $customer->website;
            $data['city'] = $customer->city;
            $data['district'] = $customer->region;
            $data['zip'] = $customer->zip;
            $data['note'] = $customer->notes;
            $data['npwp'] = $customer->npwp;
            $data['profession'] = $customer->profession;
            $data['organization'] = $customer->organization;
            $data['member_no'] = $customer->member_no;
            $data['instagram'] = $customer->instagram;
            $data['joined'] = tglincompletetime($customer->joined);
            $data['status'] = $customer->status;
            $data['image'] = $this->properti['image_url'].'customer/'.$customer->image;
            $this->output = $data;
        
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }
    
    function get()
    {   
        if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){ 
            
            $decoded = $this->api->get_decoded();
            $uid = $decoded->userid;

            $customer = $this->model->get_by_id($uid)->row();
            $data['id'] = $customer->id;
            $data['fname'] = $customer->first_name;
            $data['lname'] = $customer->last_name;
            $data['type'] = $customer->type;
            $data['address'] = $customer->address;
            $data['shipping'] = $customer->shipping_address;
            $data['phone1'] = $customer->phone1;
            $data['phone2'] = $customer->phone2;
            $data['email'] = $customer->email;
            $data['website'] = $customer->website;
            $data['city'] = $customer->city;
            $data['district'] = $customer->region;
            $data['zip'] = $customer->zip;
            $data['note'] = $customer->notes;
            $data['npwp'] = $customer->npwp;
            $data['profession'] = $customer->profession;
            $data['organization'] = $customer->organization;
            $data['member_no'] = $customer->member_no;
            $data['instagram'] = $customer->instagram;
            $data['joined'] = tglincompletetime($customer->joined);
            $data['status'] = $customer->status;
            $data['image'] = $this->properti['image_url'].'customer/'.$customer->image;
            $this->output = $data;
        
        }else { $this->reject_token(); }
        $this->response('c');
    }

    function add()
    {
	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required');
        $this->form_validation->set_rules('taddress', 'Address', 'required');
        $this->form_validation->set_rules('tzip', 'Zip', '');
        $this->form_validation->set_rules('tphone1', 'Phone 1', 'required'); // validasi nomor ponsel
        $this->form_validation->set_rules('temail', 'Email', 'required|valid_email|callback_valid_email');
        $this->form_validation->set_rules('ccity', 'City', 'required'); // kota / kabupaten
        $this->form_validation->set_rules('cdistrict', 'District', 'required'); // kecamatan / wilayah
        $this->form_validation->set_rules('tpassword', 'Password', 'required'); // password        

        if ($this->form_validation->run($this) == TRUE)
        {
            $config['upload_path'] = $this->properti['url_upload'].'customer/';
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
                $member = array('first_name' => strtolower($this->input->post('tname')),
                                'type' => 'member', 'address' => $this->input->post('taddress'),
                                'phone1' => $this->input->post('tphone1'),
                                'email' => $this->input->post('temail'), 'password' => pass_hash($this->input->post('tpassword')), 
                                'region' => $this->input->post('cdistrict'),
                                'city' => $this->input->post('ccity'), 'state' => $this->city->get_province_based_city($this->input->post('ccity')),
                                'zip' => $this->input->post('tzip'), 'joined' => date('Y-m-d H:i:s'),
                                'member_no' => $this->model->counter_model(). split_space(waktuindo()),
                                'image' => null, 'status'=>0, 'created' => date('Y-m-d H:i:s'));
            }
            else
            {
                $info = $this->upload->data();
                $member = array('first_name' => strtolower($this->input->post('tname')),
                                'type' => 'member', 'address' => $this->input->post('taddress'),
                                'phone1' => $this->input->post('tphone1'),
                                'email' => $this->input->post('temail'), 'password' => pass_hash($this->input->post('tpassword')),
                                'region' => $this->input->post('cdistrict'),
                                'city' => $this->input->post('ccity'), 'state' => $this->city->get_province_based_city($this->input->post('ccity')),
                                'zip' => $this->input->post('tzip'), 'joined' => date('Y-m-d H:i:s'),
                                'member_no' => $this->model->counter_model(). split_space(waktuindo()),
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
            
            $config['upload_path'] = $this->properti['url_upload'].'customer/';
            $config['file_name'] = split_space($decoded->userid.$val->first_name);
            $config['allowed_types'] = 'jpg|gif|png|jpeg|PNG';
            $config['overwrite'] = true;
            $config['max_size']	= '10000';
            $config['max_width']  = '30000';
            $config['max_height']  = '30000';
            $config['remove_spaces'] = TRUE;

            $this->load->library('upload', $config);

            if ( !$this->upload->do_upload("userfile")) // if upload failure
            {
                @unlink($this->properti['url_upload'].'customer/'.$val->image);
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
        $config['source_image'] = $this->properti['url_upload'].'customer/'.$filename;
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
        if ($this->api->otentikasi() == TRUE){
            
	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required');
        $this->form_validation->set_rules('twebsite', 'Website', '');
        $this->form_validation->set_rules('tname', 'Name', 'required');
        $this->form_validation->set_rules('taddress', 'Address', 'required');
        $this->form_validation->set_rules('tzip', 'Zip', '');
        $this->form_validation->set_rules('tphone1', 'Phone 1', 'required'); // validasi nomor ponsel
        $this->form_validation->set_rules('ccity', 'City', 'required'); // kota / kabupaten
        $this->form_validation->set_rules('cdistrict', 'District', 'required'); // kecamatan / wilayah
//        $this->form_validation->set_rules('tpassword', 'Password', 'required'); // password
            
        if ($this->form_validation->run($this) == TRUE)
        {
            // start update 1
            $config['upload_path'] = './images/customer/';
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

                $member = array('first_name' => strtolower($this->input->post('tname')), 
//                              'password' => pass_hash($this->input->post('tpassword')), 
                              'address' => $this->input->post('taddress'),
                              'phone1' => $this->input->post('tphone1'),
                              'website' => $this->input->post('twebsite'), 'region' => $this->input->post('cdistrict'),
                              'city' => $this->input->post('ccity'), 'state' => $this->city->get_province_based_city($this->input->post('ccity')),
                              'npwp' => $this->input->post('tnpwp'), 'profession' => $this->input->post('tprofession'), 
                              'organization' => $this->input->post('torganization'), 'instagram' => $this->input->post('tinstagram'),
                              'zip' => $this->input->post('tzip'), 'status'=> 0);

            }
            else{
                $info = $this->upload->data();

                $member = array('first_name' => strtolower($this->input->post('tname')), 
//                              'password' => pass_hash($this->input->post('tpassword')), 
                              'address' => $this->input->post('taddress'),
                              'phone1' => $this->input->post('tphone1'),
                              'website' => $this->input->post('twebsite'), 'region' => $this->input->post('cdistrict'),
                              'city' => $this->input->post('ccity'), 'state' => $this->city->get_province_based_city($this->input->post('ccity')),
                              'npwp' => $this->input->post('tnpwp'), 'profession' => $this->input->post('tprofession'), 
                              'organization' => $this->input->post('torganization'), 'instagram' => $this->input->post('tinstagram'),
                              'zip' => $this->input->post('tzip'), 'image' => $info['file_name'], 'status'=> 0);
            }
            
            $decoded = $this->api->get_decoded();
            if ($this->model->update($decoded->userid, $member) != true){ $this->reject($this->upload->display_errors());
            }else{ $this->error = $this->title.' successfully saved..!'; }
        }
        else{ $this->reject(validation_errors(),400);}
        
        }else{ $this->reject('Invalid Token or Expired..!',400); }
        $this->response('c');
    }
    
    // address
    function add_address()
    {
      if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){  
          
        $decoded = $this->api->get_decoded();
        $uid = $decoded->userid;  
        $customer = $this->model->get_by_id($uid)->row();
	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required|callback_valid_address['.$uid.']');
        $this->form_validation->set_rules('taddress', 'Address', 'required|callback_valid_count['.$uid.']');
        $this->form_validation->set_rules('tcoordinate', 'Coordinate', 'required|callback_valid_coordinate['.$uid.']');
        $this->form_validation->set_rules('cprov', 'Provinsi', 'required');
        $this->form_validation->set_rules('ccity', 'Kota', 'required');
        $this->form_validation->set_rules('cdistrict', 'Kecamatan', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {        
            $def=0;
            if (!$this->input->post('tphone')){ $phone = $customer->phone1; }else{ $phone = $this->input->post('tphone'); }
            if ($this->caddress->cek_defaults($uid) == TRUE){ $def = 1; }
            
            $member = array('name' => strtolower($this->input->post('tname')),
                            'cust' => $uid,
                            'description' => $this->input->post('taddress'),
                            'coordinate' => $this->input->post('tcoordinate'),
                            'phone' => $phone, 'defaults' => $def,
                            'prov_id' => $this->input->post('cprov'),
                            'city_id' => $this->input->post('ccity'), 'district_id' => $this->input->post('cdistrict'),
                            'created' => date('Y-m-d H:i:s'));

            if ($this->caddress->add($member) != true){ $this->reject('Failed to post');}
        }
        else{ $this->reject(validation_errors(),400); }
        
      }else { $this->reject_token(); }
      $this->response('c');
    }
    
    function default_address($uid=0)
    {
      if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){  
          
        $decoded = $this->api->get_decoded();
        $custid = $decoded->userid;  
        $customer = $this->model->get_by_id($custid)->row();
        
        if ($this->caddress->cek_trans('id', $uid) == TRUE){
            if ($this->caddress->reset_defaults($custid) == TRUE){
                if ($this->caddress->set_defaults($uid) == TRUE){
                  $this->output = "Alamat default berhasil";
                }else{ $this->reject('Set Default Failed'); }
            }else{ $this->reject('Set Default Failed'); }
        }else{ $this->reject('ID Not Found',404); }
        
      }else { $this->reject_token(); }
      $this->response('c');
    }
    
    function valid_count($val,$cust){

        if ($this->caddress->valid_count($cust) == FALSE)
        {
            $this->form_validation->set_message('valid_count','Maximum Address..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_address($val,$cust){

        if ($this->caddress->valid_address($val,$cust) == FALSE)
        {
            $this->form_validation->set_message('valid_address','Invalid Customer Address..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_coordinate($val,$cust){

        if ($this->caddress->valid_coordinate($val,$cust) == FALSE)
        {
            $this->form_validation->set_message('valid_coordinate','Invalid Customer Coordinate..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function get_address($type=0)
    {   
        if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){ 
            
            $decoded = $this->api->get_decoded();
            $uid = $decoded->userid;
            
            if ($type == 0){ $result = $this->caddress->get_details($uid);
            }else{ $result = $this->caddress->get_defaults($uid); }
            
            foreach($result as $res)
            {
               $this->resx[] = array ("id"=>$res->id, "name"=>$res->name,
                                      "coordinate"=> $res->coordinate, "address"=>$res->description,
                                      "phone" => $res->phone, "default" => $res->defaults,
                                      "created"=> tglincomplete($res->created)
                                     );
            }
            $data['result'] = $this->resx;
            $this->output = $data;
        
        }else { $this->reject_token(); }
        $this->response('c');
    }
    
    function get_address_by_id($uid=0){
       if ($this->api->otentikasi() == TRUE && $this->caddress->valid_add_trans($uid, $this->title) == TRUE){

            $customer = $this->caddress->get_by_id($uid)->row();
            $data['id'] = $customer->id;
            $data['name'] = $customer->name;
            $data['coordinate'] = $customer->coordinate;
            $data['address'] = $customer->description;
            $data['phone'] = $customer->phone;
            $this->output = $data;
        
       }else { $this->valid_404($this->caddress->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response('c');
    }
    
    function remove_address($uid=0){
       if ($this->api->otentikasi() == TRUE){
         if($this->caddress->remove($uid) == FALSE){ $this->reject("Failed to remove"); }
         else{ $this->output = 'Address successfully removed'; }
         
       }else { $this->valid_404($this->caddress->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response('c');
    }

}

?>