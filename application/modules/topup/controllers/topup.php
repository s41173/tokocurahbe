<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/jwt/JWT.php';
use \Firebase\JWT\JWT;

class Topup extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Topup_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->city = new City_lib();
        $this->disctrict = new District_lib();
        $this->login = new Customer_login_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
//        $this->notif = new Notif_lib();
        $this->api = new Api_lib();
        $this->acl = new Acl();
        $this->customer = new Customer_lib();
        $this->payment = new Payment_lib();
    }

    private $properti, $modul, $title, $ledger, $city, $disctrict;
    private $role, $login, $period, $api, $customer, $payment;

    function index()
    {
       if ($this->api->cotentikasi() == TRUE){
            
            $datax = (array)json_decode(file_get_contents('php://input')); 
            if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }

            $status=null; $paid=null; $limit=100;
            if (isset($datax['status'])){ $status = $datax['status']; }
            if (isset($datax['paid'])){ $paid = $datax['paid']; }
            if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }
            else{ $this->limitx = $this->modul['limit']; }

            $decoded = $this->api->get_decoded();
        
            if($status == null && $paid == null){ 
                $result = $this->model->get_last($decoded->userid,$this->limitx, $this->offsetx,0)->result();
                $this->count = $this->model->get_last($decoded->userid,$this->limitx, $this->offsetx,1);
            }
            else{ 
                $result = $this->model->search($decoded->userid,$status,$paid,$this->limitx, $this->offsetx)->result();
                $this->count = $this->model->search($decoded->userid,$status,$paid,$this->limitx, $this->offsetx,1);         
            }
        
            foreach($result as $res)
            {
               $img = $this->properti['image_url'].'topup/'.$res->image;
               $this->resx[] = array ("id"=>$res->id, "customer"=>$this->customer->get_name($res->customer), "transid"=> $res->transid,
                                      "dates"=> tglin($res->dates).' - '. timein($res->dates),
                                      "payment"=> $this->payment->get_name($res->payment_type), 
                                      "amount"=>floatval($res->amount), "log"=> $this->decodedd->log,
                                      "sender_name"=>$res->sender_name, "sender_acc"=>$res->sender_acc, "sender_bank"=>$res->sender_bank,
                                      "image"=> $img, "paid_date"=>$res->paid_date, 
                                      "status"=>$res->status
                                     );
            }
        
            $data['record'] = $this->count; 
            $data['result'] = $this->resx;
            $this->output = $data;
        }
        else{ $this->reject_token(); }
        $this->response('content');
    } 
    
   // ========================== api ==========================================

    
    function get($uid=0)
    {        
        if ($this->api->cotentikasi() == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){
        $customer = $this->model->get_by_id($uid)->row();
        
        $data['dates'] = tglin($customer->dates).' - '.timein($customer->dates);
        $data['payment_type'] = $this->payment->get_name($customer->payment_type);
        $data['amount'] = floatval($customer->amount);
        $data['transid'] = $customer->transid;
        $data['paid_date'] = tglin($customer->paid_date).' - '.timein($customer->paid_date);
        $data['sender_name'] = $customer->sender_name;
        $data['sender_acc'] = $customer->sender_acc;
        $data['sender_bank'] = $customer->sender_bank;
        $data['status'] = $customer->status;
        $this->output = $data;
        
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }

    function add()
    {
        if ($this->api->cotentikasi() == TRUE){
	// Form validation
        $this->form_validation->set_rules('cpayment', 'Payment Type', 'required|callback_valid_payment');
        $this->form_validation->set_rules('tamount', 'Amount', 'required|numeric|is_natural_no_zero');

        if ($this->form_validation->run($this) == TRUE)
        {
            $decoded = $this->api->get_decoded();
            $val = $this->model->get_by_id($decoded->userid)->row();
            
            if ($this->payment->cek_cash($this->input->post('cpayment')) == true){ $paid = date('Y-m-d H:i:s'); }else{ $paid = null; }
            $topup = array('customer' => $decoded->userid, 
                 'dates' => date('Y-m-d H:i:s'),
                 'payment_type' => $this->input->post('cpayment'), 'amount' => $this->input->post('tamount'),
                 'log' => $this->decodedd->log, 'paid_date' => $paid,
                 'transid' => $this->model->counters().$decoded->userid.mt_rand(1000,9999),
                 'created' => date('Y-m-d H:i:s'));
            
            if ($this->model->add($topup) != true){ $this->reject();
            }else{$this->output = $this->model->get_latest(); }  
        }
        else{ $this->reject(validation_errors(),400); }
        
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }
    
    function payment_confirmation()
    {
        if ($this->api->cotentikasi() == TRUE){

            // Form validation            
            $this->form_validation->set_rules('transid', 'Trans-ID', 'required|callback_valid_transid|callback_valid_transid_user'); 
            $this->form_validation->set_rules('tdates', 'Transaction Date', 'required');
            $this->form_validation->set_rules('tsname', 'Sender Acc-Name', 'required');
            $this->form_validation->set_rules('tsacc', 'Sender Acc-No', 'required');
            $this->form_validation->set_rules('tsbank', 'Sender Bank', 'required');

            if ($this->form_validation->run($this) == TRUE)
            {
                $topup = array('sender_name' => $this->input->post('tsname'), 'paid_date' => $this->input->post('tdates'),
                               'sender_acc' => $this->input->post('tsacc'),
                               'sender_bank' => $this->input->post('tsbank'));

                if ($this->model->update_bytrans($this->input->post('transid'), $topup) != true){ $this->reject();}
                else{ $this->error = $this->title.' successfully saved..!'; }
            }
            else{ $this->reject(validation_errors(),400); }
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }
    
     function valid_transid($val){
        $valid = $this->model->valid_transid($val);
        
        if ($valid == TRUE){
            $value = $this->model->get_by_transid($val)->row();
            if ($value->paid_date != null){ 
                $this->form_validation->set_message('valid_transid','Transaction has been confirmed.'); return FALSE; 
            }else{ return TRUE; }  
        }else{
           $this->form_validation->set_message('valid_transid','Invalid Trans-ID');
           return FALSE; 
        }
    }
    
    function valid_transid_user($val){
        $decoded = $this->api->get_decoded();
        $value = $this->model->get_by_transid($val)->row();
        if (intval($decoded->userid) != intval($value->customer)){
            $this->form_validation->set_message('valid_transid_user','The transid does not match the user.'); 
            return FALSE; 
        }else{ return TRUE; }
    }
        
    function valid_payment($val){
        if ($this->payment->cek_trans('id', $val) == FALSE)
        {
            $this->form_validation->set_message('valid_payment','Invalid Payment Type..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    

}

?>