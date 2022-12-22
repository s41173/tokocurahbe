<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Ledger extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Ledger_model', 'Model', TRUE);
        
        $this->properti = $this->property->get();
        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));

        $this->currency   = new Currency_lib();
        $this->user       = $this->load->library('admin_lib');
        $this->account    = new Account_lib();
        $this->period     = new Period_lib();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $currency, $account;
    private $user, $period, $api, $acl;
    
    function index()
    {
        if ($this->acl->otentikasi1($this->title) == TRUE){ 
             
        // Form validation
        $this->form_validation->set_rules('taccount', 'Name', 'required');
        $this->form_validation->set_rules('tstart', 'Start Period', 'required');
        $this->form_validation->set_rules('tend', 'End Period', 'required');
        $data = null;
        
        if ($this->form_validation->run($this) == TRUE && $this->account->valid_coa($this->input->post('taccount')) == TRUE){
            
            $acc   = $this->input->post('taccount');
            $start = $this->input->post('tstart');
            $end = $this->input->post('tend');
            $trans = null;
            
            $accname = null; if($acc){ $accname = $this->account->get_name($this->account->get_id_code($acc)); }
            if($acc){ $ledgers = $this->Model->get_ledger($this->account->get_id_code($acc),$start,$end)->result(); }
            else { $ledgers = null; }

            $i = 0;
            if ($ledgers)
            {
                foreach ($ledgers as $ledger)
                {
                    $this->resx[] = array ("code" => $ledger->code.'-'.$ledger->no, "currency" => $ledger->currency, 
                                             "date" => tglin($ledger->dates), "notes" => $this->cek_space($ledger->notes),
                                             "debit" => $ledger->debit, "credit" => $ledger->credit);
                }
            }

            // ===== chart  =======
//            $data['graph'] = site_url('ledger')."/chart/IDR/".$this->account->get_id_code($acc);

            // balance
            $bl = $this->get_balance($this->account->get_id_code($acc));
            $data['begin'] = $bl[0];
            $data['end'] = $bl[1];
            $data['mutation'] = $bl[2];
            $data['debit'] = $bl[3];
            $data['credit'] = $bl[4];
            $data['trans'] = $this->resx;
            $data['graph'] = $this->chart('IDR', $this->account->get_id_code($acc));
            $this->output = $data;
        }
        elseif ($this->account->valid_coa($acc) != TRUE ){ $this->error = "Invalid COA..!"; $this->status = 403; }
        else{ $this->error = validation_errors(); $this->status = 401; }
        }else{ $this->reject_token(); }
        $this->response('c');
    }
    
    function get($acc=null)
    {
        if ($this->acl->otentikasi1($this->title) == TRUE && isset($acc)){ 
        $data = null;
        
        if ($this->account->valid_coa($acc) == TRUE){
            
            $ps = new Period();
            $ps->get();
            $trans = null;
            $accname = null; if($acc){ $accname = $this->account->get_name($this->account->get_id_code($acc)); }
            if($acc){ $ledgers = $this->Model->get_monthly($this->account->get_id_code($acc),$ps->month,$ps->year)->result(); }
            else { $ledgers = null; }

            $i = 0;
            if ($ledgers)
            {
                foreach ($ledgers as $ledger)
                {
                    $trans[] = array ("code" => $ledger->code.'-'.$ledger->no, "currency" => $ledger->currency, 
                                      "date" => tglin($ledger->dates), "notes" => $this->cek_space($ledger->notes),
                                      "debit" => $ledger->debit, "credit" => $ledger->credit);
                }
            }

            // ===== chart  =======
//            $data['graph'] = site_url('ledger')."/chart/IDR/".$this->account->get_id_code($acc);

            // balance
            $bl = $this->get_balance($this->account->get_id_code($acc));
            $data['begin'] = $bl[0];
            $data['end'] = $bl[1];
            $data['mutation'] = $bl[2];
            $data['debit'] = $bl[3];
            $data['credit'] = $bl[4];
            $data['graph'] = $this->chart('IDR', $this->account->get_id_code($acc));
            $data['ledger'] = $trans;
            $this->output = $data;
        }
        elseif ($this->account->valid_coa($acc) != TRUE ){ $this->error = "Invalid COA..!"; $this->status = 403; }
        }else{ $this->reject_token(); }
        $this->response('c');
    }

    private function get_balance($acc=null)
    {
        $ps = $this->period->get();
        $bl = new Balance_account_lib();
        $bl = $bl->get($acc, $ps->month, $ps->year);
        
        if ($bl){ $begin = $bl->beginning; }else{ $begin = 0; }
                
        $this->load->model('Account_model','am',TRUE);
        $val = $this->am->get_balance($acc,$ps->month,$ps->year)->row_array();
//        
        $res[0] = idr_format(floatval($begin)); //begin
        $res[1] = idr_format(floatval($begin + $val['vamount'])); //end
        $res[2] = idr_format($val['vamount']); // mutation
        $res[3] = idr_format($val['debit']); // debit
        $res[4] = idr_format($val['credit']); // credit
        
        return $res;
    }
    
    private function chart($cur='IDR',$acc=null)
    {
        $ps = new Period();
        $gl = new Gl();
        $bl = new Balances();
        $ps->get();
        
        $gl = $this->Model->get_monthly($acc,$ps->month,$ps->year)->result();
        
        $bl->where('month', $ps->month);
        $bl->where('account_id', $acc);
        $bl->where('year', $ps->year)->get();
        
        $i=0; $j=1; $k=2;
        $result = $bl->beginning; 
        
        $datax = array();
        if ($gl){
          foreach ($gl as $value)
          {
            $res = $this->Model->get_balance($acc,$value->no)->row_array();
            $res[$i] = $result;
            
            $point = array("label" => tglshort($value->dates) , "y" => $result + floatval($res['vamount']));
            array_push($datax, $point);  
            
            $result = $res[$i];
            $i++;
          }
        }
        return $datax;
//        echo json_encode($datax, JSON_NUMERIC_CHECK);
    }

    private function cek_space($val)
    {  $res = explode("<br />",$val);  if (count($res) == 1) { return $val;  } else { return implode('', $res); } }

//    ===================== approval ===========================================

    public function valid_date($date)
    {
        $cur = $this->input->post('ccurrency');
        if ($this->journal->valid_journal($date,$cur) == FALSE)
        {
            $this->form_validation->set_message('valid_date', "Journal [ ".tgleng($date)." ] - ".$cur." already approved.!");
            return FALSE;
        }
        else {  return TRUE; }
    }

// ===================================== PRINT ===========================================

   function voucher($code=null,$no=0)
   {
        if ($this->acl->otentikasi1($this->title) == TRUE && isset($code) && isset($no)){ 
            
            $data = null;
            $gl = new Gl();
            $gl->where('code',$code)->where('no',$no)->get();

            // property display
            $data['p_name'] = $this->properti['name'];
            $data['logo'] = $this->properti['logo'];
            $data['paddress'] = $this->properti['address'];
            $data['p_phone1'] = $this->properti['phone1'];
            $data['p_phone2'] = $this->properti['phone2'];
            $data['p_city'] = ucfirst($this->properti['city']);
            $data['p_zip'] = $this->properti['zip'];
            
            $decoded = $this->api->otentikasi('decoded');
            $data['code']    = $gl->no;
            $data['dates']  = $gl->dates;
            $data['currency']   = $gl->currency;
            $data['notes'] = $gl->notes;
            $data['log']   = $gl->log;
            $data['codetrans']   = $gl->code;
            $data['docno']   = $gl->docno;
            $data['balance']   = $gl->balance;
            $data['log'] = $decoded->log;

            $result = $gl->order_by('id', 'desc')->transaction->get();
            $trans = null;
            foreach ($result as $res){
                $trans[] = array("acc" => $this->account->get_code($res->account_id).' : '.$this->account->get_name($res->account_id), "debit" => $res->debit, "credit" => $res->credit);
            }
            $data['transaction'] = $trans;
            $this->output = $data;

       }else{ $this->reject_token(); }
       $this->response('c');
   }

// ===================================== PRINT ===========================================

// ====================================== REPORT =========================================


    function report()
    {
        if ($this->acl->otentikasi2($this->title)){ 
            
        $this->form_validation->set_rules('ccurrency', 'Name', 'required');
        $this->form_validation->set_rules('taccstart', 'Acc-Start', 'required|callback_valid_coa');
        $this->form_validation->set_rules('taccend', 'Acc-End', 'required|callback_valid_coa');
        $this->form_validation->set_rules('tstart', 'Start Period', 'required');
        $this->form_validation->set_rules('tend', 'End Period', 'required');
        $data = null;
        
         if ($this->form_validation->run($this) == TRUE){

            $data['title'] = $this->properti['name'].' | Report '.ucwords($this->modul['title']);
            $cur = $this->input->post('ccurrency');
            $accstart = $this->input->post('taccstart');
            $accend   = $this->input->post('taccend');

            $start = $this->input->post('tstart');
            $end = $this->input->post('tend');

            $data['cur'] = $cur;
            $data['start'] = $start;
            $data['end'] = $end;
            $data['rundate'] = tgleng(date('Y-m-d'));
            $data['log'] = $this->session->userdata('log');

            // Property Details
            $data['company'] = $this->properti['name'];
            $trans = null;
            $trans = $this->get_ledger($this->account->get_id_code($accstart), $this->account->get_id_code($accend), $start, $end);
            $data['transaction'] = $trans;
            $this->output = $data;
         }
         else{ $this->reject(validation_errors(),400); }
         
       }else{ $this->reject_token(); }
       $this->response('c');
    }
    
    function get_prev_balance($pid=null,$date=null){
        
        if ($pid != null && $date != null){
            
            $opening = $this->get_begin_bl($pid, $date);
            $date = new DateTime($date); // For today/now, don't pass an arg.
            $date->modify("-1 day");
            $prevdate = $date->format("Y-m-d");
            $bulan = date('n', strtotime($prevdate));
            $tahun = date('Y', strtotime($prevdate));
 
            $tglawal = date('Y-m-d',strtotime($bulan.'/1/'.$tahun));
            $res_trans = $this->get_end_balance($pid, $tglawal, $prevdate);
            return $opening+$res_trans;
        }else{ return 0; }
    }
    
    private function get_ledger($accstart,$accend,$start,$end){       
       $coa = null;       
       $result = $this->Model->report($accstart,$accend)->result();
       foreach ($result as $account) {
           $begin = $this->get_prev_balance($account->id, $start); 
           $debit = $this->get_debit($account->id, $start, $end);
           $credit = $this->get_credit($account->id, $start, $end);
           $mutation = $this->get_end_balance($account->id, $start, $end);
           $endbalance = floatval($begin+$mutation);
           $trans = $this->get_journal($account->id, $start, $end);
           $coa[] = array('code' => $account->code, 'name' => $account->name, 'begin_balance' => $begin, 'mutation' => $mutation, 'end_balance' => $endbalance, 'debit' => $debit, 'credit' => $credit, 'transaction' => $trans);
       } 
       return $coa;
    }
    
    private function get_journal($acc,$start,$end)
    {
        $result = $this->Model->get_ledger($acc,$start,$end)->result();
        $begin = $this->get_prev_balance($acc,$start);
        $trans = null;

        foreach($result as $res)
        {
            $begin = $begin + $res->vamount;
            $trans[] = array("date"=>tglin($res->dates), "code" => $res->code, "refno" => $res->code.'-00'.$res->no,
                           "notes"=>$res->notes, "debit" => $res->debit, "credit" => $res->credit, "balance" => $begin);    
        }
        return $trans;
    }
    
    private function get_begin_bl($acc,$date){
        
        $acc_lib = new Account_lib();
        $cla_lib = new Classification_lib();

        $type = $cla_lib->get_type($acc_lib->get_classi($acc));    

        $month = date('n', strtotime($date));	
        $year = date('Y', strtotime($date));	
        $bl = new Balances();
        $bl->where('account_id', $acc);
        $bl->where('month', $month);
        $bl->where('year', $year)->get();

        if ($type == 'pendapatan' ){ return 0; }
        elseif ($type == 'biaya'){ return 0; }
        else{ return floatval($bl->beginning); }
    }
    
    private function get_end_balance($acc,$start,$end)
    {
        $result = $this->Model->get_sum_balance($acc,$start,$end)->row_array();
        return floatval($result['vamount']);
    }
    
    private function get_debit($acc,$start,$end)
    {
       $result = $this->Model->get_sum_balance($acc,$start,$end)->row_array();
       return floatval($result['debit']);
    }

    private function get_credit($acc,$start,$end)
    {
       $result = $this->Model->get_sum_balance($acc,$start,$end)->row_array();
       return floatval($result['credit']);
    }

// ====================================== REPORT =========================================

    public function valid_coa($acc){
        if ($this->account->valid_coa($acc) == FALSE){
          $this->form_validation->set_message('valid_coa', "Invalid COA.!");
          return FALSE;  
        }else{ return TRUE; }
    }


    public function valid_part($part,$po)
    {
        if ($this->sinvoice->valid_part($part,$po) == FALSE)
        {
            $this->form_validation->set_message('valid_part', "Payment term already registered.!");
            return FALSE;
        }
        else {  return TRUE; }
    }
    
    // ====================================== CLOSING ======================================
    function reset_process(){ }


}

?>