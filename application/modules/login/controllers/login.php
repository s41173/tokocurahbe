<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/jwt/JWT.php';
use \Firebase\JWT\JWT;

class Login extends MX_Controller {


   public function __construct()
   {
        parent::__construct();

        $this->load->model('Login_model', '', TRUE);

        $this->load->helper('date');
        $this->log = new Log_lib();
        $this->load->library('email');
        $this->login = new Login_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('login');

        $this->properti = $this->property->get();
        $this->api = new Api_lib();
        $this->user = new Admin_lib();
        $this->branch = new Droppoint_lib();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
   }

   private $date,$time,$login,$branch;
   private $properti,$com,$api,$user;
   
    // function untuk memeriksa input user dari form sebagai admin
    function index()
    {
        $datax = (array)json_decode(file_get_contents('php://input')); 

        $status = 200;
        $error = "null";
        $logid = null;
        $token = null;
        
        if (isset($datax['user']) && isset($datax['pass'])){

            $username = $datax['user'];
            $password = $datax['pass'];
            $branch = $datax['branch'];
            
            if ($this->Login_model->check_user($username,$password) == TRUE)
            {
                $this->date  = date('Y-m-d');
                $this->time  = waktuindo();
                $userid = $this->Login_model->get_userid($username);
                $role = $this->Login_model->get_role($username);
                $rules = $this->Login_model->get_rules($role);
//                $branch = $this->Login_model->get_branch($username);
                $logid = intval($this->log->max_log()+1);
                $waktu = tgleng(date('Y-m-d')).' - '.waktuindo().' WIB';
                
                // create branch
//                if ($branch){ $this->session->set_userdata('branch', $branch); }
                
                // add JWT
                $payload['userid'] = $userid;
                $payload['role'] = $role;
                $payload['rules'] = $rules;
                $payload['log'] = $logid;
                $payload['time'] = $waktu;
                $payload['branch'] = $branch;
                $token = JWT::encode($payload, 'vinkoo');
                
                $this->log->insert($userid, $this->date, $this->time, 'login');
                $this->login->add($userid, $logid, $token);
            }
            else{ $status = 401; $error = 'Invalid Login'; }
       }else{ $status = 401; $error = 'Invalid Format'; }   
       
       $output = array('token' => $token,'error' => $error, 'log' => $logid, 'branch' => $branch); 
       $this->api->response($output,$status); 
    }

    // function untuk logout
    function logout()
    {             
        if ($this->api->user_otentikasi() == TRUE){
            
          $status = 200;
          $error = null;
          $decoded = $this->api->user_otentikasi('decoded');
          
          $this->login->logout($decoded->userid);
          
//          $this->date  = date('Y-m-d');
//          $this->time  = waktuindo();
//          $this->log->insert($decoded->userid, $this->date, $this->time, 'logout');
            
        }else{ $error = 'Invalid Token or Expired..!'; $status = 400; }
        $this->api->response(array('error' => $error), $status);
    }

    function decode(){
        
        $status = 200;
        $response = null;
        $decoded = $this->api->user_otentikasi('decoded');
        if ($this->api->user_otentikasi() == TRUE){
            $decoded = $this->api->user_otentikasi('decoded');
            $response = array('userid' => $decoded->userid, 'username' => $this->user->get_username($decoded->userid), 'role' => $decoded->role, 'rules' => $decoded->rules, 'log' => $decoded->log, 'time' => $decoded->time, 'branch' => $decoded->branch);
//            print_r($decoded->userid);
        }else{ $response = 'Invalid Token or Expired..!'; $status = 401; }
        $this->api->response(array('content' => $response), $status);
    }
    
    function forgot()
    {
        $datax = (array)json_decode(file_get_contents('php://input')); 
        $status = 200;
        $error = "null";

        $username = $datax['user'];
        if ($username != null){
            
            if ($this->Login_model->check_username($username) == FALSE)
            {
               $this->session->set_flashdata('message', 'Username not registered ..!!');
               $error = 'Username / Email not registered...!'; $status = 401;
            }
            else
            {  
                try
                {
                  if ($this->send_email($username) == TRUE){ $error = 'Password has been sent to your email..!';
                  }else{ $error = 'Password Submission Process Failed..!'; $status = 401;}
                }
                catch(Exception $e) {  
                    $this->log->insert(0, date('Y-m-d'), waktuindo(), 'error', $this->com, $e->getMessage());
                    $error = $e->getMessage(); $status = 404;
                } 
            } 
        }else{ $error = 'Invalid Format'; $status = 400; }
        
        $output = array('error' => $error); 
        $this->api->response($output,$status); 
    }
    
    // ajax function not neccesaary when use postman
    function cek_login(){
//        if ($this->session->userdata('username')){ echo 'true'; }else{ echo 'false'; } 
      if ($this->acl->otentikasi1('main','ajax') == FALSE){ echo 'false'; }else{ echo 'true'; }    
    }
    
    private function send_email($username)
    {
        $email = $this->Login_model->get_email($username);
        $pass = $this->Login_model->get_pass($username);
        $mess = "
          ".$this->properti['name']." - ".base_url()."
          <br> <b> FORGOT PASSWORD : </b> <br>

          Your Username is: ".$username."
          Your Password : ".$pass." <hr />
Your password for this account has been recovered . You don't need to do anything, this message is simply a notification to protect the security of your account.
Please note: your password may take awhile to activate. If it doesn't work on your first try, please try it again later
DO NOT REPLY TO THIS MESSAGE. For further help or to contact support, please email to ".$this->properti['email']."
****************************************************************************************************************** ";

        $params = array($this->properti['email'], $this->properti['name'], $email, 'Password Recovery', $mess, 'html');
        $se = $this->load->library('send_email',$params);

        if ( $se->send_process() == TRUE ){ return TRUE; }else { return FALSE;}
    }
    

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */