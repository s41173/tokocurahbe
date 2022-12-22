<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/jwt/JWT.php';
use \Firebase\JWT\JWT;

class Member extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Member_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->city = new City_lib();
        $this->disctrict = new District_lib();
        $this->login = new Member_login_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
//        $this->notif = new Notif_lib();
        $this->api = new Api_lib();
        $this->acl = new Acl();
        $this->event = new Event_lib();
//        $this->memberevent = new Member_event_lib();
        $this->ledger = new Member_ledger_lib();
    }

    private $properti, $modul, $title, $ledger, $city, $disctrict, $event;
    private $role, $login, $period, $notif, $api, $memberevent;

    function index(){  }
    
   // ========================== api ==========================================
    
    function req_otp(){
        $datas = (array)json_decode(file_get_contents('php://input'));
        
        if (isset($datas['username'])){
            if ($this->model->login($datas['username']) == true){
                $logid = mt_rand(1000,9999);
                $userid = $this->model->get_by_username($datas['username'])->row();
                $this->login->set_otp($userid->id,$logid);
                $this->output = 'OTP has been sent.';
                // kirim otp
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
        $event = $datas['event'];
        
        $logid = null;
        $token = null;
            
          if($this->model->cek_user($user) != TRUE){$this->reject('User Not Found..!',404);}
          elseif($this->event->cek_active($event) != TRUE){$this->reject('Invalid Event..!',400);}
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
                    $payload['username'] = $res->first_name;
                    $payload['phone'] = $user;
                    $payload['log'] = $logid;
                    $payload['event'] = $event;
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
        if ($this->api->otentikasi() == TRUE){

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
        if ($this->api->otentikasi() == TRUE){
            
           $datax = (array)json_decode(file_get_contents('php://input')); 
           $event=null; $month= null; $year = null;
           if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
           if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; } 
           if (isset($datax['event'])){ $event = $datax['event']; }
           if (isset($datax['month'])){ $month = $datax['month']; }
           if (isset($datax['year'])){ $year = $datax['year']; }
        
           $decoded = $this->api->get_decoded();
           $result = $this->ledger->get_transaction($decoded->userid,$month,$year, $event, $this->limitx, $this->offsetx)->result();
           $this->count = $this->ledger->get_transaction($decoded->userid,$month,$year, $event, $this->limitx, $this->offsetx,1);
        
           foreach($result as $res)
           {
               if ($res->code == 'MP'){ $notes = 'PAYMENT'; }else{ $notes = 'POS'; }
               $this->resx[] = array ("id"=>$res->id, "event"=>$this->event->get_details($res->event_id),
                                      "code"=> $res->code, "no"=>$res->no, "date"=>tglin($res->dates),
                                      "time"=>timein($res->dates),"notes"=>$notes,"amount"=> idr_format($res->vamount)
                                     );
           }
           
           $sum = $this->ledger->get_sum_transaction($decoded->userid, $event);
           $data['balance'] = $sum['vamount'];
           $data['record'] = $this->count; 
           $data['result'] = $this->resx;
           $this->output = $data;
            
        }else { $this->reject_token(); }
        $this->response('c');
    }
    
    function get($uid=0)
    {        
        $decoded = $this->api->get_decoded();
        if ($uid==0){ $uid = $decoded->userid; $oten = $this->api->otentikasi(); }else{ $oten = $this->api->cotentikasi(); }
        if ($oten == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){

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
        $data['image'] = $this->properti['image_url'].'member/'.$customer->image;
        $this->output = $data;
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
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
            $config['upload_path'] = $this->properti['url_upload'].'member/';
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
                                'image' => null, 'created' => date('Y-m-d H:i:s'));
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
                                'image' => $info['file_name'], 'created' => date('Y-m-d H:i:s'));
            }

//            $this->balance->create($this->Member_model->counter(1), $this->period->month, $this->period->year);
            
            if ($this->model->create($member) != true){ $this->reject($this->upload->display_errors());
            }else{$this->output = $this->model->get_latest(); }  
        }
        else{ $this->reject(validation_errors(),400); }
        $this->response('c');
    }
    
    function upload_image()
    {
        if ($this->api->otentikasi() == TRUE){

            $decoded = $this->api->get_decoded();
            $val = $this->model->get_by_id($decoded->userid)->row();
            
            $config['upload_path'] = $this->properti['url_upload'].'member/';
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
        $config['source_image'] = $this->properti['url_upload'].'member/'.$filename;
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
            $this->form_validation->set_message('valid_member','Invalid Member..!');
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
	if ($this->Member_model->validating('email',$val,$id) == FALSE)
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
        $this->form_validation->set_rules('tpassword', 'Password', 'required'); // password
            
        if ($this->form_validation->run($this) == TRUE)
        {
            // start update 1
            $config['upload_path'] = './images/member/';
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
                              'password' => pass_hash($this->input->post('tpassword')), 
                              'address' => $this->input->post('taddress'),
                              'phone1' => $this->input->post('tphone1'),
                              'website' => $this->input->post('twebsite'), 'region' => $this->input->post('cdistrict'),
                              'city' => $this->input->post('ccity'), 'state' => $this->city->get_province_based_city($this->input->post('ccity')),
                              'npwp' => $this->input->post('tnpwp'), 'profession' => $this->input->post('tprofession'), 
                              'organization' => $this->input->post('torganization'), 'instagram' => $this->input->post('tinstagram'),
                              'zip' => $this->input->post('tzip'));

            }
            else{
                $info = $this->upload->data();

                $member = array('first_name' => strtolower($this->input->post('tname')), 
                              'password' => pass_hash($this->input->post('tpassword')), 
                              'address' => $this->input->post('taddress'),
                              'phone1' => $this->input->post('tphone1'),
                              'website' => $this->input->post('twebsite'), 'region' => $this->input->post('cdistrict'),
                              'city' => $this->input->post('ccity'), 'state' => $this->city->get_province_based_city($this->input->post('ccity')),
                              'npwp' => $this->input->post('tnpwp'), 'profession' => $this->input->post('tprofession'), 
                              'organization' => $this->input->post('torganization'), 'instagram' => $this->input->post('tinstagram'),
                              'zip' => $this->input->post('tzip'), 'image' => $info['file_name']);
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