<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once 'definer.php';

class Posc extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Posc_model', 'model', TRUE);
        $this->load->model('Sales_item_posc_model', 'sitem', TRUE);

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
//        $this->journalgl = new Journalgl_lib();
        $this->branch = new Branch_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
        $this->tax = new Tax_lib();
//        $this->account = new Account_lib();
//        $this->wt = new Warehouse_transaction_lib();
        $this->user = new Admin_lib();
        $this->member = new Member_lib();
        $this->ledger = new Customer_ledger_lib();
        $this->mledger = new Member_ledger_lib();
        $this->devent = new Devent_lib();
        $this->customer = new Customer_lib();
    }

    private $properti, $modul, $title, $sales, $wt, $bank, $journalgl, $member, $ledger, $mledger, $devent, $customer;
    private $role, $currency, $user, $payment, $city, $product ,$category, $branch, $period, $tax, $account;
    

        
//     ============== ajax ===========================
    
    function get_product($pid)
    {
        $res = $this->product->get_detail_based_id($pid);
        if ($res){ echo intval($res->price-$res->discount); }else{ return 0; }
        
    }
    
    function get_product_based_sku($sku)
    {
        $res = $this->product->get_detail_based_sku($sku);
        echo @intval($res->price-$res->discount);
    }
    
    function valid_orderid($orderid)
    {
        if ($this->model->valid_orderid($orderid) == TRUE){ echo 'true'; }else{ echo 'false'; }
    }

    
    public function summary()
    {
        if ($this->apix->cotentikasi() == TRUE){
        
        $decoded = $this->apix->get_decoded();    
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
            
        if(!isset($datax['cancel']) && !isset($datax['date'])){ 
           $result = $this->model->get_last_summary($decoded->event,$decoded->userid,'0',$this->limitx, $this->offsetx)->result();
           $this->count = $this->model->get_last_summary($decoded->event,$decoded->userid,null,$this->limitx, $this->offsetx,1); 
           if ($result){
            foreach($result as $res){ $this->resx[] = array ("date" => tglin($res->dates), "amount" => $res->amount);}
           }  
        }
        else { $this->resx = $this->model->search_summary($decoded->event,$decoded->userid,$datax['cancel'],$datax['date']); }
        
        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
            
        }else{ $this->reject_token(); }
        $this->response('c');
    } 
    
    // harus ada 1 fungsi index pos untuk customer
    
    // fungsi index pos untuk tenant
    public function index()
    {
        if ($this->apix->cotentikasi() == TRUE){
        
        $decoded = $this->apix->get_decoded();
        $datax = (array)json_decode(file_get_contents('php://input')); 

        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        
        $result = $this->model->search($decoded->event,$datax['date'],$decoded->userid,$this->limitx, $this->offsetx)->result(); 
        $this->count = $this->model->search($decoded->event,$datax['date'],$decoded->userid,$this->limitx, $this->offsetx,1);
        
        $sum = $this->ledger->get_sum_transaction($decoded->userid);
        $data['balance'] = floatval($sum['vamount']);
        $data['orderid'] = $this->model->counter().mt_rand(99,9999);
        $data['total'] = $this->model->search_summary($decoded->event,$datax['date'],$decoded->userid);
        $data['record'] = $this->count; 
        $data['result'] = $result;
        $this->output = $data;
            
        }else{ $this->reject_token(); }
        $this->response('c');
    } 
    
    function report()
    {
        if ($this->apix->cotentikasi() == TRUE){
        $datax = (array)json_decode(file_get_contents('php://input'));
        $decoded = $this->apix->get_decoded();

        if(!isset($datax['start']) && !isset($datax['end']) && !isset($datax['cancel'])){ 
            $result = $this->model->report_monthly($decoded->event,$decoded->userid, null, $this->period->month, $this->period->year)->result();
            $this->count = $this->model->report_monthly($decoded->event,$decoded->userid, null, $this->period->month, $this->period->year,1); 
        }
        else {
            $result = $this->model->report($decoded->event,$decoded->userid,$datax['cancel'],$datax['start'],$datax['end'])->result(); 
            $this->count = $this->model->report($decoded->event,$decoded->userid,$datax['cancel'],$datax['start'],$datax['end'],1);
        }
        
        $data['record'] = $this->count; 
        $data['result'] = $result;
        $this->output = $data;
            
        }else{ $this->reject_token(); }
        $this->response('c');
    } 
        
    function get_trans($sid=0){
        
       if ($this->apix->cotentikasi() == TRUE && $this->model->valid_add_trans($sid, $this->title) == TRUE){  
            $sales = $this->model->get_by_id($sid)->row();
            $data['details'] = $sales;
            $data['items'] = $this->sitem->get_last_item($sid)->result();
            $this->output = $data;
       }
       else{ $this->valid_404($this->model->valid_add_trans($sid, $this->title)); $this->reject_token(); }
       $this->response('content'); 
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
    
    function valid_login()
    {
        if (!$this->session->userdata('username')){
            $this->form_validation->set_message('valid_login', "Transaction rollback relogin to continue..!");
            return FALSE;
        }else{ return TRUE; }
    }

}

?>