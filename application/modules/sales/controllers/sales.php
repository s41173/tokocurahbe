<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once 'definer.php';

class Sales extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Sales_model', '', TRUE);
        $this->load->model('Sales_item_model', 'sitem', TRUE);

        $this->properti = $this->property->get();
//        $this->acl->otentikasi();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->currency = new Currency_lib();
        $this->sales = new Product_lib();
        $this->customer = new Customer_lib();
        $this->payment = new Payment_lib();
        $this->city = new City_lib();
        
        $this->product = new Product_lib();
//        $this->shipping = new Shipping_lib();
        $this->bank = new Bank_lib();
        $this->category = new Categoryproduct_lib();
//        $this->stock = new Stock_lib();
//        $this->journalgl = new Journalgl_lib();
        
        $this->branch = new Branch_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
//        $this->stockledger = new Stock_ledger_lib();
        $this->tax = new Tax_lib();
        
        
//        $this->account = new Account_lib();
//        $this->wt = new Warehouse_transaction_lib();
//        $this->trans = new Trans_ledger_lib();
//        $this->contract = new Contract_lib();
//        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');  
    }

    private $properti, $modul, $title, $sales, $wt ,$shipping, $bank, $stock, $journalgl, $stockledger, $trans, $contract;
    private $role, $currency, $customer, $payment, $city, $product ,$category, $branch, $period, $tax, $account;
    
    function callback_payment(){
         $datax = (array)json_decode(file_get_contents('php://input'));  
//         print_r($datax['id']);
    }
    
    public function index()
    {
        if ($this->acl->otentikasi1($this->title) == TRUE){
            
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
            
        if(!isset($datax['branch']) && !isset($datax['paid']) && !isset($datax['confirm']) && !isset($datax['customer']) && !isset($datax['date'])){ 
            $result = $this->Sales_model->get_last($this->limitx, $this->offsetx)->result();
            $this->count = $this->Sales_model->get_last($this->limitx, $this->offsetx,1); 
        }
        else {
            $result = $this->Sales_model->search($datax['branch'],$datax['paid'],$datax['confirm'],$datax['customer'],$datax['date'])->result(); 
            $this->count = $this->Sales_model->search($datax['branch'],$datax['paid'],$datax['confirm'],$datax['customer'],$datax['date'],1);
        }
        
        if ($result){
          foreach($result as $res)
          {  
            $total = intval($res->amount);  
            if ($res->paid_date){ $status = 'S'; }else{ $status = 'C'; } 
            if ($this->shipping->cek_shiping_based_sales($res->id) == true){ $ship = 'Shipped'; }else{ $ship = '-'; } // shipping status

            $this->resx[] = array ("id" => $res->id, "dates" => tglin($res->dates), "contract_id" =>$res->contract_id,
                                   "contract" => $this->contract->get($res->contract_id),
                                   "customer" => $this->customer->get_name($res->cust_id), "total" => $total,
                                   "shipping" => $res->shipping, "status"=>$status, "ship_status"=>$ship, 
                                   "posted" => $res->confirmation, "branch" => $this->branch->get_name($res->branch_id)
                                  ); 
          }
        }
        
        $data['orderid'] = $this->Sales_model->counterx();
        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
            
        }else{ $this->reject_token(); }
        $this->response('c');
    } 
        
    
    function chart($month=0,$year=0)
    {   
       if ($this->acl->otentikasi1($this->title) == TRUE){ 
           
        $data = $this->category->get();
        if ($month == 0){ $month = $this->period->month; }
        if ($year == 0){ $year = $this->period->year; }
        $datax = array();
        foreach ($data as $res) 
        {  
           $tot = $this->Sales_model->get_sales_qty_based_category($res->id,$month,$year); 
           $point = array("label" => $res->name , "y" => $tot);
           array_push($datax, $point);      
        }
//        $data['result'] = $datax;
        $this->output = $datax;  
        
       }else{ $this->reject_token(); }
       $this->response('c');
    }
    
    function publish($uid=0)
    {
       if ($this->acl->otentikasi3($this->title) == TRUE && $this->Sales_model->valid_add_trans($uid, $this->title) == TRUE){
         $val = $this->Sales_model->get_by_id($uid)->row();
         $mess = null;
         if ($val->confirmation == 1){ $mess = 'Transaction already posted.'; }
         if ($val->amount <= 0){ $mess = "Transaction has not value"; }
         if ($this->valid_period($val->dates) == FALSE){ $mess = "Invalid Period"; }
         if ($this->valid_items($uid) != TRUE){ $mess = 'Empty Transaction..!'; }
         if ($this->contract->cek_approval_contract($val->contract_id) != TRUE){ $mess = 'Contract Not Posted..!'; }
         
         if (!$mess){
            if ($val->cash == 1){ $paid = $val->dates; }else{ 
              // membuat kartu hutang
              $this->trans->add('bank', 'SO', $val->id, 'IDR', $val->dates, intval($val->amount), 0, $val->cust_id, 'AR');
              $paid = null; 
            }
            $param = array('p1' => 0, 'paid_date' => setnull($paid), 'confirmation' => 1); 
            if ($this->Sales_model->update($uid, $param) == TRUE && $this->update_trans($uid) == TRUE){
               $val = $this->Sales_model->get_by_id($uid)->row();
               $this->create_journal($uid);
               $this->contract->update_balance($val->contract_id,$val->amount);
               $this->add_wt($uid); // add warehouse transaction
               $this->error = 'Transaction Posted...!';
            }else{ $this->reject('Posting Failure..!'); }
         }else{ $this->reject($mess); }
         
       }else{ $this->valid_404($this->Sales_model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }
    
    function delete_all($type='hard')
    {
      if ($this->acl->otentikasi_admin($this->title,'ajax') == TRUE){
      
        $cek = $this->input->post('cek');
        $jumlah = count($cek);

        if($cek)
        {
          $jumlah = count($cek);
          $x = 0;
          for ($i=0; $i<$jumlah; $i++)
          {
             if ($type == 'soft') { $this->Sales_model->delete($cek[$i]); }
             else { $this->shipping->delete_by_sales($cek[$i]);
                    $this->Sales_model->force_delete($cek[$i]);  
             }
             $x=$x+1;
          }
          $res = intval($jumlah-$x);
          //$this->session->set_flashdata('message', "$res $this->title successfully removed &nbsp; - &nbsp; $x related to another component..!!");
          $mess = "$res $this->title successfully removed &nbsp; - &nbsp; $x related to another component..!!";
          echo 'true|'.$mess;
        }
        else
        { //$this->session->set_flashdata('message', "No $this->title Selected..!!"); 
          $mess = "No $this->title Selected..!!";
          echo 'false|'.$mess;
        }
      }else{ echo "error|Sorry, you do not have the right to edit $this->title component..!"; }
      
    }

    function delete($uid=0)
    {
        if ($this->acl->otentikasi3($this->title) == TRUE && $this->Sales_model->valid_add_trans($uid, $this->title) == TRUE){
           
            $sales = $this->Sales_model->get_by_id($uid)->row();
            if ($sales->confirmation == 1){
             $param = array('confirmation' => 0, 'paid_date' => null, 'updated' => date('Y-m-d H:i:s'));
             $this->Sales_model->update($uid, $param);   
             
             $this->journalgl->remove_journal('SO', $uid);
             $this->journalgl->remove_journal('CS', $uid);
             $this->journalgl->remove_journal('CR', '0000'.$uid);
             
             $this->contract->update_balance($sales->contract_id,$sales->amount,1); // rollback contract amount
             $this->error = $this->title." successfully rollback..!";
            }else{
              $this->journalgl->remove_journal('SO', $uid);
              $this->journalgl->remove_journal('CS', $uid);
              $this->journalgl->remove_journal('CR', '0000'.$uid);
              
              $this->stock->rollback('SO', $uid); // rollback stock
              $this->wt->remove($sales->dates, 'SO-'.$uid); // delete wt
              $this->sitem->delete_sales($uid);
              $this->Sales_model->force_delete($uid);
              $this->error = $this->title." successfully removed..!";   
            }
            
       }else{ $this->valid_404($this->Sales_model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }

    function add()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('ccustomer', 'Customer', 'required|callback_valid_customer'); 
        $this->form_validation->set_rules('tdates', 'Transaction Date', 'required');
        $this->form_validation->set_rules('tduedates', 'Transaction Due Date', 'required|callback_valid_due'); // valid due date
        $this->form_validation->set_rules('cpayment', 'Payment Type', 'required|callback_valid_payment'); // valid payment
        $this->form_validation->set_rules('tcosts', 'Landed Cost', 'numeric');
        $this->form_validation->set_rules('ccash', 'Cash Status', 'required|numeric');
        $this->form_validation->set_rules('ccontract', 'Contract', 'callback_valid_contract');

        if ($this->form_validation->run($this) == TRUE)
        {
            $sales = array('cust_id' => $this->input->post('ccustomer'), 'contract_id' => setnol($this->input->post('ccontract')),
                           'dates' => date("Y-m-d H:i:s"), 
                           'branch_id' => $this->branch->get_branch_default(), 'cost' => $this->input->post('tcosts'),
                           'due_date' => $this->input->post('tduedates'), 'payment_id' => $this->input->post('cpayment'), 
                           'cash' => $this->input->post('ccash'), 
                           'created' => date('Y-m-d H:i:s'));

            if ($this->Sales_model->add($sales) != true){ $this->reject();
            }else{ $this->Sales_model->log('create'); $this->output = $this->Sales_model->get_latest(); } 
        }
        else{ $this->reject(validation_errors(),400); }
        }else { $this->reject_token(); }
        $this->response('c');
    }
    
    function add_item($sid=0)
    { 
       if ($this->acl->otentikasi2($this->title) == TRUE && $this->Sales_model->valid_add_trans($sid, $this->title) == TRUE){
       
         // Form validation
        $this->form_validation->set_rules('cproduct', 'Product', 'required|callback_valid_product['.$sid.']|callback_valid_request['.$this->input->post('tqty').']');
        $this->form_validation->set_rules('tqty', 'Qty', 'required|numeric');
        $this->form_validation->set_rules('tprice', 'Price', 'required|numeric');
        $this->form_validation->set_rules('tdiscount', 'Price', 'required|numeric');
        $this->form_validation->set_rules('ctax', 'Tax Type', 'required');

            if ($this->form_validation->run($this) == TRUE && $this->valid_confirm($sid) == TRUE)
            {
                // start transaction 
                $this->db->trans_start(); 
                
                $pid = $this->product->get_id_by_sku($this->input->post('cproduct'));
                $discount = intval($this->input->post('tqty')*$this->input->post('tdiscount'));
                $amt_price = intval($this->input->post('tqty')*$this->input->post('tprice')-$discount);
                $tax = intval($this->input->post('ctax')*$amt_price);
                $id = $this->sitem->counter();
                
                $hpp = $this->stock->min_stock($pid, $this->input->post('tqty'), $sid, 'SO', $id);
                
                $sales = array('id' => $id, 'product_id' => $pid, 'sales_id' => $sid,
                               'qty' => $this->input->post('tqty'), 'tax' => $tax, 'discount' => $this->input->post('tdiscount'), 'weight' => $this->product->get_weight($pid),
                               'hpp' => $hpp, 'price' => $this->input->post('tprice'), 'amount' => intval($amt_price+$tax));

                $this->sitem->add($sales);
                $this->update_trans($sid);
                $this->error = "Sales Transaction data successfully saved!";
                
                $this->db->trans_complete();
                // end transaction
            }
            else{ $this->reject(validation_errors(),400); }  
        
       }else{ $this->valid_404($this->Sales_model->valid_add_trans($sid, $this->title)); $this->reject_token(); }
       $this->response();
    }
    
    private function update_trans($sid)
    {
        $totals = $this->sitem->total($sid);
        $price = intval($totals['qty']*$totals['price']);
        
        $sales = $this->Sales_model->get_by_id($sid)->row();
        $cost = $sales->cost;
        
        // shipping total        
        $transaction = array('tax' => $totals['tax'], 'total' => $price, 'discount' => $totals['discount'], 
                             'amount' => intval($totals['tax']+$price+$cost-$totals['discount']-$sales->p1), 'shipping' => $this->shipping->total($sid));
	return $this->Sales_model->update($sid, $transaction);
    }
    
    function delete_item($id=0)
    {
        if ($this->acl->otentikasi2($this->title) == TRUE && $this->sitem->valid_add_trans($id, $this->title) == TRUE){ 
          $sid = $this->sitem->get_by_id($id)->row(); 
          $sid = $sid->sales_id;
          if ($this->valid_confirm($sid) == TRUE){
            // start transaction 
            $this->db->trans_start();    
                $this->stock->rollback('SO', $sid, $id);
                $this->sitem->delete($id); // memanggil model untuk mendelete data
                $this->update_trans($sid);
                $this->error = "1 item successfully removed..!";
                $this->db->trans_complete();
            //  end transaction
          }else{ $this->reject("Sales Already Confirmed..!");}
       }else{ $this->valid_404($this->sitem->valid_add_trans($id, $this->title)); $this->reject_token(); }
       $this->response();
    }
    
    private function split_array($val)
    { return implode(",",$val); }
   

    // Fungsi update untuk menset texfield dengan nilai dari database
    function get($param=0)
    {
        if ($this->acl->otentikasi2($this->title) == TRUE && $this->Sales_model->valid_add_trans($param, $this->title) == TRUE){
            
        $data['branch_id'] = $this->branch->get_branch_default();
        $data['branch'] = $this->branch->get_name($data['branch_id']);
        $sales = $this->Sales_model->get_by_id($param)->row();
        $customer = $this->customer->get_details($sales->cust_id)->row();
        $data['contract_id'] = $sales->contract_id;
        $data['contract'] = $this->contract->get($sales->contract_id);
        $data['cust_id'] = $sales->cust_id;
        $data['customer'] = $this->customer->get_name($sales->cust_id);
        $data['email'] = $customer->email;
        $data['ship_address'] = $customer->shipping_address;
        $data['dates'] = $sales->dates;
        $data['due_date'] = $sales->due_date;
        $data['payment_id'] = $sales->payment_id;
        $data['payment'] = $this->payment->get_name($sales->payment_id);
        $data['costs'] = floatval($sales->cost);
        $data['discount'] = floatval($sales->discount);
        $data['cash'] = $sales->cash;
        $data['p1'] = $sales->p1;
        $data['total'] = floatval($sales->total);
        $data['shipping'] = floatval($sales->shipping);
        $data['tot_amt'] = intval($sales->amount+$sales->shipping);
        
        // weight total
        $total = $this->sitem->total($param);
        $data['weight'] = round($total['weight']);
        $data['tax_total'] = floatval($sales->tax);
        $data['discount']  = floatval($sales->discount);
        $data['p1'] = floatval($sales->p1);
        
        // transaction table
        foreach ($this->sitem->get_last_item($param)->result() as $res){
            $this->resx[] = array ("id" => $res->id, "orderid" => $res->orderid,
                                   "product_id" => $res->product_id, "product" => $this->product->get_name($res->product_id),
                                   "sku" => $this->product->get_sku($res->product_id),
                                   "weight" => $res->weight, "qty" => floatval($res->qty), "discount" => floatval($res->discount), "tax" => floatval($res->tax),
                                   "amount" => floatval($res->amount), "price" => floatval($res->price), "hpp" => floatval($res->hpp)
                                  ); 
        }
        $data['items'] = $this->resx;
        $this->output = $data;
        
       }else{ $this->valid_404($this->Sales_model->valid_add_trans($param, $this->title)); $this->reject_token(); }
       $this->response('c');
    }
    
    function update($param=0)
    {
        if ($this->acl->otentikasi2($this->title) == TRUE && $this->Sales_model->valid_add_trans($param, $this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('ccustomer', 'Customer', 'required|callback_valid_customer'); 
        $this->form_validation->set_rules('tdates', 'Transaction Date', 'required');
        $this->form_validation->set_rules('tduedates', 'Transaction Due Date', 'required|callback_valid_due');
        $this->form_validation->set_rules('cpayment', 'Payment Type', 'required|callback_valid_payment');
        $this->form_validation->set_rules('tcosts', 'Landed Cost', 'numeric');
        $this->form_validation->set_rules('ccash', 'Cash Status', 'required|numeric');    
        $this->form_validation->set_rules('ccontract', 'Contract', 'callback_valid_contract');
            
        if ($this->form_validation->run($this) == TRUE && $this->valid_confirm($param) == TRUE)
        {   
            if ($this->input->post('ccash') == 1){ $p1 = 0; $confirm = 1; $paid = $this->input->post('tdates'); }
            else{ $p1 = $this->input->post('tp1'); $confirm = 0; $paid=null;}
            
            $sales = array('cust_id' => $this->input->post('ccustomer'), 'contract_id' => setnol($this->input->post('ccontract')), 'dates' => $this->input->post('tdates'), 'branch_id' => $this->branch->get_branch_default(),
                           'due_date' => $this->input->post('tduedates'), 'payment_id' => $this->input->post('cpayment'), 'cost' => $this->input->post('tcosts'),
                           'p1' => $p1, 'paid_date' => $paid, 'confirmation' => $confirm,
                           'cash' => $this->input->post('ccash'), 
                           'updated' => date('Y-m-d H:i:s'));

            $this->Sales_model->update($param, $sales);
            $this->update_trans($param);
////            $this->mail_invoice($param); // send email confirmation
            $this->error = "One $this->title data successfully saved!";
        }
        elseif ($this->valid_confirm($param) != TRUE){ $this->reject("Sales Already Confirmed..!"); }
        else{ $this->reject(validation_errors(),400); }
        
       }else{ $this->valid_404($this->Sales_model->valid_add_trans($param, $this->title)); $this->reject_token(); }
       $this->response();
    }
      
    private function add_wt($sid)
    {
        $sales = $this->Sales_model->get_by_id($sid)->row();
        $item = $this->sitem->get_last_item($sid)->result();
        
        $this->wt->remove($sales->dates, 'SO-'.$sid);
        
        foreach ($item as $value) {    
           $hpp = intval($value->hpp/$value->qty); 
           $this->wt->add( $sales->dates, 'SO-'.$sales->id, $sales->branch_id, 'idr', $value->product_id, 0, $value->qty,
                           $hpp, $value->hpp, $this->session->userdata('log')); 
        }
    }
    
    private function create_journal($sid)
    {
        $this->journalgl->remove_journal('SO', $sid);
        $this->journalgl->remove_journal('CS', $sid);
        $this->journalgl->remove_journal('CR', '0000'.$sid);
        
        $sales = $this->Sales_model->get_by_id($sid)->row();
        $totals = $this->sitem->total($sid);
        
        $cm = new Control_model();
        
        $landed   = $cm->get_id(2);
        $discount = $cm->get_id(4);
        $tax      = $cm->get_id(18);
        $stock    = $this->branch->get_acc($sales->branch_id, 'stock');
        $ar       = $this->branch->get_acc($sales->branch_id, 'ar');
        $bank     = $this->branch->get_acc($sales->branch_id, 'bank');
        $kas      = $this->branch->get_acc($sales->branch_id, 'cash');
        $salesacc = $this->branch->get_acc($sales->branch_id, 'sales');
        $cost     = $this->branch->get_acc($sales->branch_id, 'unit');
        $hpp      = intval($totals['hpp']);
        
        if ($sales->cash == 1){
           if ($this->payment->get_name($sales->payment_id) == 'Cash'){ $account = $kas; } // kas
           else { $account = $bank; }    
        }else{ $account = $ar; }
        
        
        if ($sales->p1 > 0)
        {  
           // create journal- GL
           $this->journalgl->new_journal($sales->id,$sales->dates,'SO','IDR','Sales Order',$sales->amount, $this->session->userdata('log'));
           $this->journalgl->new_journal('0000'.$sales->id,$sales->dates,'CR','IDR','Customer DP Payment : SO'.$sales->id,$sales->p1, $this->session->userdata('log'));
           
           $jid = $this->journalgl->get_journal_id('SO',$sales->id);
           $dpid = $this->journalgl->get_journal_id('CR','0000'.$sales->id);
           
           $this->journalgl->add_trans($jid,$cost, $hpp, 0); // tambah biaya 1 (hpp)
           $this->journalgl->add_trans($jid,$stock,0,$hpp); // kurang persediaan
           $this->journalgl->add_trans($jid,$ar,$sales->p1+$sales->amount,0); // piutang usaha bertambah
           $this->journalgl->add_trans($jid,$salesacc,0,$sales->total); // tambah penjualan
           
           if ($sales->tax > 0){ $this->journalgl->add_trans($jid,$tax,0,$sales->tax); } // pajak penjualan
           if ($sales->cost > 0){ $this->journalgl->add_trans($jid,$landed,0,$sales->cost); } // landed costs
           if ($sales->discount > 0){ $this->journalgl->add_trans($jid,$discount,$sales->discount,0); } // discount
           
           //DP proses
           if ($this->payment->get_name($sales->payment_id) == 'Cash'){ $dp_acc = $kas; } // kas
           else { $dp_acc = $bank; }    
           
           $this->journalgl->add_trans($dpid,$dp_acc,$sales->p1,0); //bank penjualan
           $this->journalgl->add_trans($dpid,$ar,0,$sales->p1); // piutang usaha kurang dp
           
        }
        else
        {   
            $this->journalgl->new_journal($sales->id,$sales->dates,'SO','IDR','Sales Order',$sales->amount, $this->session->userdata('log'));
            $jid = $this->journalgl->get_journal_id('SO',$sales->id);
            
            $this->journalgl->add_trans($jid,$cost, $hpp, 0); // tambah biaya 1 (hpp)
            $this->journalgl->add_trans($jid,$stock, 0, $hpp); // kurang persediaan
            $this->journalgl->add_trans($jid,$account,$sales->p1+$sales->amount,0); // piutang usaha bertambah
            $this->journalgl->add_trans($jid,$salesacc,0,$sales->total); // tambah penjualan
           
            if ($sales->tax > 0){ $this->journalgl->add_trans($jid,$tax,0,$sales->tax); } // pajak penjualan
            if ($sales->cost > 0){ $this->journalgl->add_trans($jid,$landed,0,$sales->cost); } // landed costs
            if ($sales->discount > 0){ $this->journalgl->add_trans($jid,$discount,$sales->discount,0); } // discount
        }
    }

    
    function payment_confirmation($param=0)
    {
       if ($this->acl->otentikasi2($this->title) == TRUE && $this->Sales_model->valid_add_trans($param, $this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tcdates', 'Confirmation Date', 'callback_valid_required');
        $this->form_validation->set_rules('taccname', 'Account Name', 'callback_valid_required');
        $this->form_validation->set_rules('taccno', 'Account No', 'callback_valid_required');
        $this->form_validation->set_rules('taccbank', 'Account Bank', 'callback_valid_required');
        $this->form_validation->set_rules('tamount', 'Amount', 'numeric|callback_valid_required');
        $this->form_validation->set_rules('cbank', 'Merchant Bank', 'callback_valid_required|callback_valid_bank');
        $this->form_validation->set_rules('cstts', 'Status', 'required|numeric');

        if ($this->form_validation->run($this) == TRUE && $this->valid_confirm($param) == FALSE)
        {
            if ($this->input->post('cstts') == 1){
                $sales = array('updated' => date('Y-m-d H:i:s'),
                               'paid_date' => $this->input->post('tcdates'),
                               'sender_name' => $this->input->post('taccname'), 'sender_acc' => $this->input->post('taccno'),
                               'sender_bank' => $this->input->post('taccbank'), 'sender_amount' => $this->input->post('tamount'),
                               'bank_id' => $this->input->post('cbank')
                              );
                $stts = 'confirmed!';
                $this->Sales_model->update($param, $sales);
                $this->confirmation_journal($param);
            }
            else { $sales = array('updated' => date('Y-m-d H:i:s')); 
                   $stts = 'unconfirmed!'; 
                   $this->Sales_model->update($param, $sales);
                   $this->journalgl->remove_journal('CS', $param);
                   $status = true;
            }
            $status = true;
            if ($status == true){ $this->error = "One $this->title data payment successfully ".$stts;  
            }else { $this->reject("Error Sending Mail...!! "); }
        }
        elseif ($this->valid_confirm($param) == TRUE){ $this->reject("Sales Order Not Confirmed..!"); }
        else{ $this->reject(validation_errors(),400); }
       }else{ $this->valid_404($this->Sales_model->valid_add_trans($param, $this->title)); $this->reject_token(); }
       $this->response();
    }
    
    private function confirmation_journal($sid)
    {
        $sales = $this->Sales_model->get_by_id($sid)->row();
        $ar   = $this->branch->get_acc($sales->branch_id, 'ar');
        $bank = $sales->bank_id;
        
        $this->journalgl->new_journal($sales->id,$sales->paid_date,'CS','IDR','Payment Confirmation',$sales->amount, $this->session->userdata('log'));
        $jid = $this->journalgl->get_journal_id('CS',$sales->id);
        
        $this->journalgl->add_trans($jid,$bank, $sales->amount, 0); // tambah bank
        $this->journalgl->add_trans($jid,$ar, 0, $sales->amount); // kurang piutang
    }
    
    public function valid_period($date=null)
    {
        $p = new Period();
        $p->get();

        $month = date('n', strtotime($date));
        $year = date('Y', strtotime($date));

        if ( intval($p->month) != intval($month) || intval($p->year) != intval($year) )
        {
            $this->form_validation->set_message('valid_period', "Invalid Period.!");
            return FALSE;
        }
        else {  return TRUE; }
    }
    
    function valid_due($due){
        $dates = $this->input->post('tdates');
        if ($due < $dates){ $this->form_validation->set_message('valid_due', "Invalid Due Date..!"); return FALSE; }
        else{ return TRUE; }
    }
    
    function valid_payment($val){
        if ($this->payment->cek_trans('id', $val) == FALSE){
            $this->form_validation->set_message('valid_payment', "Invalid Payment..!"); return FALSE; 
        }else{ return TRUE; }
    }
    
    function valid_bank($val){
        if ($this->account->cek_trans('id', $val) == FALSE){
            $this->form_validation->set_message('valid_bank', "Invalid Bank Destination..!"); return FALSE; 
        }else{ return TRUE; }
    }
    
    function valid_customer($val){
        if($this->input->post('ccontract')){
            if ($this->contract->get($val,'cust_id') != $val){ $this->form_validation->set_message('valid_customer', "The customer is not in accordance with the contract!"); return FALSE; }
            else{ return TRUE; }
        }
        else{
            if ($this->customer->cek_trans('id', $val) == FALSE){
                $this->form_validation->set_message('valid_customer', "Invalid Customer..!"); return FALSE; 
            }else{ return TRUE; }
        }
    }
    
     function valid_contract($val){
        if ($val){
            if ($this->contract->cek_trans('id', $val) == FALSE){
                $this->form_validation->set_message('valid_contract', "Invalid Contract..!"); return FALSE; 
            }else{ return TRUE; }
        }else{ return TRUE; }
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
        if ($this->product->valid_sku($id) == TRUE){
           if ($this->sitem->valid_product($this->product->get_id_by_sku($id),$sid) == FALSE)
           {
             $this->form_validation->set_message('valid_product','Product already listed..!');
             return FALSE;
           }
           else{ return TRUE; }
        }else{ $this->form_validation->set_message('valid_product','Invalid SKU..!'); return FALSE; }
    }
    
    function valid_name($val)
    {
        if ($this->Sales_model->valid('name',$val) == FALSE)
        {
            $this->form_validation->set_message('valid_name','Name registered..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_confirm($sid)
    {
        if ($this->Sales_model->valid_confirm($sid) == FALSE)
        {
            $this->form_validation->set_message('valid_confirm','Sales Already Confirmed..!');
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
    
    function report()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

            // form validation
            $this->form_validation->set_rules('ccustomer', 'Customer', 'callback_valid_customer'); 
            $this->form_validation->set_rules('cbranch', 'Branch', 'callback_valid_branch'); 
            $this->form_validation->set_rules('start', 'Start Date', 'required');
            $this->form_validation->set_rules('end', 'End Date', 'required');
            $this->form_validation->set_rules('cpaid', 'Paid Status', '');
            $this->form_validation->set_rules('cconfirm', 'Confirm Status', '');
            $this->form_validation->set_rules('cproduct', 'SKU', 'numeric');
            $this->form_validation->set_rules('ctype', 'Report Type', 'required|numeric');
            
            if ($this->form_validation->run($this) == TRUE)
            {
                $start = $this->input->post('start');
                $end = $this->input->post('end');
                $paid = $this->input->post('cpaid');
                $confirm = $this->input->post('cconfirm');
                $cust = $this->input->post('ccustomer');
                $product = $this->input->post('cproduct');
                $branch = $this->input->post('cbranch');

                $data['branch'] = $this->branch->get_name($branch);
                $data['start'] = tglin($start);
                $data['end'] = tglin($end);
                if (!$paid){ $data['paid'] = ''; }elseif ($paid == 1){ $data['paid'] = 'Paid'; }else { $data['paid'] = 'Unpaid'; }
                if (!$confirm){ $data['confirm'] = ''; }elseif ($confirm == 1){ $data['confirm'] = 'Confirmed'; }else { $data['confirm'] = 'Unconfirmed'; }

                if ($this->input->post('ctype') == 0){ 
                    
                    $reports = $this->Sales_model->report($branch,$cust,$start,$end,$paid,$confirm)->result(); 
                    foreach ($reports as $res) {
$this->resx[] = array ("id" => $res->id, "dates" => tglin($res->dates), "time" => timein($res->dates),
                       "customer" => $this->customer->get_name($res->cust_id), "customer_id" => $res->cust_id,
                       "amount" => floatval($res->amount), "tax" => floatval($res->tax), "cost" => floatval($res->cost), "total" => floatval($res->total),
                       "shipping" => floatval($res->shipping), "branch"=> $this->branch->get_name($res->branch_id),
                       "payment_id"=>$res->payment_id, "payment"=> $this->payment->get_name($res->payment_id), 
                       "bank" => $this->account->get_name($res->bank_id),
                       "paid_date" => tglin($res->paid_date), 'paid_contact' => $res->paid_contact, "due_date" => tglin($res->due_date),
                       "discount" => floatval($res->discount), "p1" => floatval($res->p1), "cc_no" => $res->cc_no, "cc_name" => $res->cc_name, "cc_bank" => $res->cc_bank,
                       "sender_name" => $res->sender_name, "sender_acc" => $res->sender_acc, "sender_bank" => $res->sender_bank, "sender_amount" => floatval($res->sender_amount),
                       "confirmation" => $res->confirmation, "approved" => $res->approved, "cash" => $res->cash, "log" => $res->log,
                       "created" => $res->created, "updated" => $res->updated, "deleted" => $res->deleted
                       ); 
                    }
$data['reports'] = $this->resx;
                }
elseif ($this->input->post('ctype') == 1){
   $reports = $this->Sales_model->report_category($branch,$product,$start,$end,$paid,$confirm)->result();
   foreach ($reports as $res) {
$this->resx[] = array ("id" => $res->id, "dates" => tglin($res->dates), "time" => timein($res->dates),
                       "branch"=> $this->branch->get_name($res->branch_id),
                       "sku"=>$res->sku, "name"=> $res->name, "qty"=>$res->qty,
                       "price" => $res->price, "confirmation" => $res->confirmation,
                       "category" => $res->category, 'manufacture' => $res->manufacture
                       ); 
   }
   $data['reports'] = $this->resx;
}
                $this->output = $data;
            }
            else{ $this->reject(validation_errors(),400); }
            
        }else { $this->reject_token(); }
        $this->response('c');
    }   

}

?>