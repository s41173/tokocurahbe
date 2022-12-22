<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/jwt/JWT.php';
use \Firebase\JWT\JWT;

class Vdiscount extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Vdiscount_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->login = new Customer_login_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
        $this->notif = new Notif_lib();
        $this->api = new Api_lib();
        $this->acl = new Acl();
        $this->customer = new Customer_lib();
        $this->droppoint = new Droppoint_lib();
        $this->vdiscountdetails = new Voucher_discount_detail_lib();
        $this->payment = new Payment_lib();
        $this->voucher = new Voucher_discount_lib();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $droppoint,$vdiscountdetails;
    private $role, $login, $period, $api, $customer,$payment,$voucher,$notif;

    
    function index()
    {
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }

        $status=null; $paid=null; $limit=100;
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }
        else{ $this->limitx = $this->modul['limit']; }
        
        if ($this->input->get_request_header('X-auth-token')){
            $decoded = $this->api->get_decoded();
            $result = $this->model->get_last($this->limitx, $this->offsetx,0)->result();     
            
            foreach($result as $res){
               if ($res->target_audience == 0){ $target = "pelanggan baru"; }
               elseif ($res->target_audience == 1){ $target = "pelanggan aktif"; }
               elseif ($res->target_audience == 2){ $target = "semua pelanggan aktif"; }
               
               if ($this->vdiscountdetails->cek_voucher($res->id, $decoded->userid) == TRUE){
                   $this->resx[] = array ("id"=>$res->id, "name"=>$res->name, "code"=> $res->code,
                                      "period"=> tglin($res->start).' - '. tglin($res->end),
                                      "type"=> $res->type, "payment_type"=> $res->payment_type, "minimum"=> $res->minimum, "percentage"=> $res->percentage,
                                      "limit_type"=> $res->limit_type, "limit_count"=> $res->limit_count,
                                      "target_audience"=> $res->target_audience,  "target_audience_label"=> $target,
                                      "target_audience_start"=> $res->target_audience_start, "target_audience_end"=> $res->target_audience_end,
                                      "target_drop_point"=> $this->droppoint->split($res->target_drop_point),
                                      "status"=>$res->status
                                     );
               }
            }
            
        }else{ 
           $result = $this->model->get_last($this->limitx, $this->offsetx,0)->result();
           $this->count = $this->model->get_last($this->limitx, $this->offsetx,1); 
        }

        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
        
        $this->response('content');
    } 
    
    function get_droppoint($uid=0,$type=0){
        if ($this->api->otentikasi() == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
          $res = $this->model->get_by_id($uid)->row();
          $dp = explode(',', $res->target_drop_point);
          foreach($dp as $res){
            $this->resx[] = array ("id"=>$res, "code"=> strtoupper($this->droppoint->get_detail($res, 'code')), "name"=> $this->droppoint->get_detail($res, 'name'));
          }
          
         $data['result'] = $this->resx;
         $this->output = $data;
          
       }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       if ($type==0){$this->response('content'); }else{ return $this->resx; }
       
    }
    
    private function send_confirmation_email($pid=0)
    {   
        // property display
       $data['p_logo'] = $this->properti['image_url'].'property/'.$this->properti['logo'];
       $data['p_name'] = $this->properti['name'];
       $data['p_site_name'] = $this->properti['sitename'];
       $data['p_address'] = $this->properti['address'];
       $data['p_zip'] = $this->properti['zip'];
       $data['p_city'] = $this->properti['city'];
       $data['p_phone'] = $this->properti['phone1'];
       $data['p_email'] = $this->properti['email'];
       
       $voucherdetails = $this->vdiscountdetails->get_by_id($pid)->row();
//       $customer = $this->customer->get_by_id($voucherdetails->customer_id)->row();
//       $voucher = $this->voucher->get_detail($voucherdetails->voucher_id, 'code');

       $data['custname'] = $this->customer->get_name($voucherdetails->customer_id);
       $data['vouchercode'] = $this->voucher->get_detail($voucherdetails->voucher_id, 'code');
       $data['vouchername'] = $this->voucher->get_detail($voucherdetails->voucher_id, 'name');
       $data['voucherpercentage'] = $this->voucher->get_detail($voucherdetails->voucher_id, 'percentage');
       $data['voucherdate'] = tglincompletetime($this->voucher->get_detail($voucherdetails->voucher_id, 'created'));
         
//       $this->load->view('voucher_receipt',$data); 
        // email send
       $html = $this->load->view('voucher_receipt',$data,true); 
       return $this->notif->send_notif(0, $voucherdetails->customer_id, $data['p_name'].' - Voucher Redeem - '.$data['vouchercode'].' - '.$data['custname'], $html, 'customer');
    }
    
     //================== calculate redeem =====================
    function calculate_redeem(){
        
       $datax = (array)json_decode(file_get_contents('php://input')); 
       if ($this->api->otentikasi() == TRUE && $this->model->valid_add_trans($datax['id'], $this->title) == TRUE){
           
         $outlet = explode(',', $datax['droppoint']);
         if (count($outlet) > 1){ $this->reject('Multiple outlet tidak berlaku..!'); }
         elseif ($outlet[0] == ""){ $this->reject('Outlet diperlukan...!'); }
         elseif (count($outlet) == 0){ $this->reject('Outlet diperlukan...!'); }
         else{
            
            $decoded = $this->api->get_decoded();
            $res = $this->model->get_by_id($datax['id'])->row();
            $response[0] = false; $response[1] = null;
            if ($this->droppoint->cek_trans('id', $outlet[0]) == FALSE){ $response[1] = "Invalid Outlet"; }
            else{
               $droppoint = explode(',', $res->target_drop_point);
               if (in_array($outlet[0], $droppoint) == FALSE){ $response[1]="Voucher tidak berlaku untuk outlet ini"; };
            }
             
            $date1 = strtotime($res->end); // tanggal due date
            $date2 = strtotime(date('Y-m-d')); // tanggal sekarang
            $climit = $this->cek_limit($datax['id'],$res->limit_type, $res->limit_count); 
            $vctype = $this->cek_voucher_type($decoded->userid, $res->target_audience);

            if ($res->status == 0){ $response[1] = 'Status voucher tidak aktif'; }
            elseif ($this->vdiscountdetails->cek_voucher($datax['id'], $decoded->userid) == FALSE){ $response[1] ='Voucher telah digunakan'; }
            elseif($date1 < $date2){$response[1]="Masa berlaku voucher sudah habis";}
            elseif ($climit[0] == false){ $response[1]=$climit[1]; }
            elseif ($vctype[0] == false){ $response[1]=$vctype[1]; }
            elseif (intval($datax['amount']) < $res->minimum){ $response[1] = "Total belanja tidak mencapai nilai minimum voucher"; }
            elseif ($datax['payment'] != $res->payment_type){ $response[1] = "Jenis pembayaran ini tidak berlaku menggunakan voucher"; }
            
            if ($response[1] != null){ $this->reject($response[1]);  }
         }
           
       }else { $this->valid_404($this->model->valid_add_trans($datax['id'], $this->title)); $this->reject_token(); }
       $this->response('content');
    }
    
    function redeem($uid=0,$pin=0,$outlet=null){
       
       if ($this->api->otentikasi() == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
         
          $decoded = $this->api->get_decoded();
          $res = $this->model->get_by_id($uid)->row();
          
          $date1 = strtotime($res->end); // tanggal due date
          $date2 = strtotime(date('Y-m-d')); // tanggal sekarang
          $climit = $this->cek_limit($uid,$res->limit_type, $res->limit_count); 
          $vctype = $this->cek_voucher_type($decoded->userid, $res->target_audience);

          if ($res->status == 0){ $this->reject('Status voucher tidak aktif'); }
          elseif ($this->vdiscountdetails->cek_voucher($uid, $decoded->userid) == FALSE){ $this->reject('Voucher telah digunakan'); }
          elseif($date1 < $date2){$this->reject("Masa berlaku voucher sudah habis");}
          elseif ($climit[0] == false){ $this->reject($climit[1]); }
          elseif ($vctype[0] == false){ $this->reject($vctype[1]); }
          else{
              // proses redeem - cek stock point
              $stts = $this->api->request_get($this->properti['pos_url'].'log/cek_pin/'.$pin.'/'.$outlet);
              if ($stts[1] == 200){ 
                $voucher = array('voucher_id' => $uid, 'pin' => $pin,
                                 'customer_id' => $decoded->userid, 'drop_point' => $outlet, 
                                 'created' => date('Y-m-d H:i:s'));

                if ($this->vdiscountdetails->add($voucher) != true){ $this->reject('failed to post');
                }else{ 
                    $lid = $this->vdiscountdetails->get_latest();
                    $this->send_confirmation_email($lid->id);
                    $this->output = "Voucher berhasil di redeem"; /* kirim notifikasi email + push notif */ }
              }
              else{ $this->reject($stts[2]);}
          }
          
         $data['result'] = $this->output;
       }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response('content'); 
    }
    
    private function cek_voucher_type($cust,$type){
        $stts[0] = true; $stts[1] = null;
        $customer = $this->customer->get_by_id($cust)->row();
        if ($type == 0){
            if ($customer->voucher_claimed != 0){ $stts[0] = false; $stts[1] = 'Voucher hanya berlaku untuk pelanggan baru'; }
        }elseif($type == 1){
            if ($customer->voucher_claimed == 0){ $stts[0] = false; $stts[1] = 'Voucher hanya berlaku untuk pelanggan existing'; }
        }
        return $stts;
    }
    
    private function cek_limit($voucher=0,$type=0,$limit=0){
        $stts[0] = true; $stts[1] = null;
        if ($type == 0){
            if ($this->vdiscountdetails->get_voucher_daily($voucher) >= $limit){ $stts[0] = false; $stts[1] = 'Limit voucher harian telah habis'; }
        }elseif ($type == 1){
            if ($this->vdiscountdetails->get_voucher_total($voucher) >= $limit){ $stts[0] = false; $stts[1] = 'Limit voucher telah habis'; }
        }
        return $stts;
    }
    
   // ========================== api ==========================================

    
    function get($uid=0,$type=0)
    {        
        if ($this->api->otentikasi() == TRUE){
        
            $valid = FALSE;
            if ($type == 0){ $valid = $this->model->valid_add_trans($uid, $this->title); }
            else { $valid = $this->model->cek_trans('code',$uid); }

            if ($valid == TRUE){
                
               if ($type == 0){ $voucher = $this->model->get_by_id($uid)->row(); }
               else{ $voucher = $this->model->get_by_code($uid)->row(); }
               
               $data['id'] = $voucher->id;
               $data['code'] = strtoupper($voucher->code);
               $data['name'] = strtoupper($voucher->name);
               $data['period'] = tglin($voucher->start).' - '.timein($voucher->end);
               $data['type'] = $voucher->type;
               $data['payment_type'] = $voucher->payment_type;
               $data['minimum'] = floatval($voucher->minimum);
               $data['percentage'] = $voucher->percentage.'%';
               $data['limit_type'] = $voucher->limit_type;
               $data['limit_count'] = $voucher->limit_count;
               $data['target_audience'] = $voucher->target_audience;
               $data['target_audience_period'] = tglin($voucher->target_audience_start).' - '.tglin($voucher->target_audience_end);

               $data['target_drop_point'] = $this->get_droppoint($voucher->id,1);
               $data['image'] = $this->properti['image_url'].'voucher/'.$voucher->image;
               $data['text'] = $voucher->text;
               $data['status'] = $voucher->status;
               
               $this->output = $data;
            }
            elseif($valid == FALSE){ $this->reject('Item Not Found',404); }
            
        }else { $this->reject_token(); }
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