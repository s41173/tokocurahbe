<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Account extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Accounts_model', 'Model', TRUE);
        
        $this->properti = $this->property->get();
//        $this->acl->otentikasi();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));

        $this->currency = new Currency_lib();
        $this->classification = new Classification_lib();
        $this->city = new City_lib;
        $this->account = new Account_lib();
        $this->balance = new Balance_account_lib();
        $this->period = new Period_lib();
        $this->journal = new Journalgl_lib();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');  
    }

    private $properti, $modul, $title, $account, $balance;
    private $currency, $classification, $city, $period, $journal,$api,$acl;
     
    public function index()
    {
        if ($this->acl->otentikasi1($this->title) == TRUE){
            
            $datax = (array)json_decode(file_get_contents('php://input')); 
            if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
            if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
            
            if(!isset($datax['classification']) && !isset($datax['publish'])){ 
                $result = $this->Model->get_last($this->limitx, $this->offsetx)->result(); 
                $this->count = $this->Model->get_last($this->limitx, $this->offsetx,1); 
            }
            else { $result = $this->Model->search($datax['classification'],$datax['publish'], $this->limitx, $this->offsetx)->result();
                   $this->count = $this->Model->search($datax['classification'],$datax['publish'],$this->limitx, $this->offsetx,1);
            }
        
            foreach($result as $res)
            {  
               $this->resx[] = array ("id" => $res->id, "classification" => $this->classification->get_name($res->classification_id),
                                        "type" => $this->classification->get_type($res->classification_id), "currency" => $res->currency, "code" => $res->code, "name" => $res->name,
                                        "alias" => $res->alias, "acc_no" => $res->acc_no,
                                        "bank" => $res->bank, "status" => $res->status, "default" => $res->default,
                                        "bank_status" => $res->bank_stts);
            }
            
          $data['record'] = $this->count; 
          $data['result'] = $this->resx;
          $this->output = $data;
            
        }else{ $this->reject_token(); }
        $this->response('c');
    }    
    
    public function get_asset_acc()
    {
        if ($this->acl->otentikasi1($this->title) == TRUE){
            $result = $this->account->combo_asset();
            foreach($result as $res)
            {  
               $this->output[] = array ("id" => $res->id, "code" => $res->code, "name" => $res->name);
            }
            
        }else{ $this->reject_token(); }
        $this->response('c');
    }    
    
    function publish($uid = null)
    {
       if ($this->acl->otentikasi3($this->title) == TRUE && $this->Model->valid_add_trans($uid, $this->title) == TRUE){ 
            $val = $this->Model->get_by_id($uid)->row();
            if ($val->status == 0){ $lng = array('status' => 1); }else { $lng = array('status' => 0); }
            if ($this->Model->update($uid,$lng) == true){ $this->error = 'Status Changed...!'; }else{ $this->reject(); }
       }else{ $this->valid_404($this->Model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }
    
    private function get_balance($acc=null)
    {
        $am = new Account_model();
        $ps = new Period();
        $gl = new Gl();
        $ps->get();
        
        $gl->where('approved', 1);
        $gl->where('MONTH(dates)', $ps->month);
        $gl->where('YEAR(dates)', $ps->year)->get();
        
        $this->load->model('Account_model','am',TRUE);
        $val = $am->get_balance($acc,$ps->month,$ps->year)->row_array();
        return $val['vamount'];
    }

    private function get_cost($acc=null,$month=0)
    {
        $ps = new Period();
        $bl = new Balances();
        $ps->get();
        
        $bl->where('account_id', $acc);
        $bl->where('month', $month);
        $num = $bl->where('year', $ps->year)->count();

        $val = null;
        if ( $num > 0)
        {
           $bl->where('account_id', $acc);
           $bl->where('month', $month);
           $bl->where('year', $ps->year)->get(); 
            
           $val[0] = get_month($month);
           $val[1] = $ps->year;
           $val[2] = $bl->beginning + $this->get_balance($acc);
        }
        else
        {
           $val[0] = get_month($month);
           $val[1] = $ps->year;
           $val[2] = 0; 
        }

        return $val;
    }

    // blm d gunakan
    function cost($acc = null)
    {
        if ($this->acl->otentikasi1($this->title) == TRUE && $this->Model->valid_add_trans($acc, $this->title) == TRUE){ 
            $account = null;
            for ($x=1; $x<=12; $x++)
            {
               $account[$x] = $this->get_cost($acc,$x);
               $this->output[] = array ("month" => $account[$x][0], "year" => $account[$x][1], "budget" => $account[$x][2]);
            }        
        }else{ $this->valid_404($this->Model->valid_add_trans($acc, $this->title)); $this->reject_token(); }
        $this->response('c');
    }

    
    function delete_all()
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
           if ( $this->journal->valid_account_transaction($cek[$i]) == TRUE && $this->valid_default($cek[$i]) == TRUE ) 
           {
              $this->Model->delete($cek[$i]); 
           }
           else { $x=$x+1; }
           
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
      }else { echo "error|Sorry, you do not have the right to edit $this->title component..!"; }
    }

    function delete($uid)
    {
        if ($this->acl->otentikasi3($this->title) == TRUE && $this->Model->valid_add_trans($uid, $this->title) == TRUE){ 
        
            if ( $this->journal->valid_account_transaction($uid) == TRUE && $this->valid_default($uid) == TRUE )
            {
                // hapus balance
                $this->balance->remove_balance($uid);
                $this->Model->force_delete($uid);
                $this->error = "$this->title successfully soft removed..!";
            }
            else{ $this->reject("$this->title related to another component..!"); }
        
        }else{ $this->valid_404($this->Model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }

    function add()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required|callback_valid_name');
        $this->form_validation->set_rules('tno', 'No', 'required|numeric');
        $this->form_validation->set_rules('tcode', 'Code', 'required|numeric|callback_valid_code');
        $this->form_validation->set_rules('ccurrency', 'Currency', 'required');
        $this->form_validation->set_rules('cclassification', 'Classification', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {            
            if ($this->input->post('cclassification') == 7 || $this->input->post('cclassification') == 8){ $bank = 1; }
            else { $bank  = $this->input->post('cbank'); }
            
            $account = array('classification_id' => $this->input->post('cclassification'), 'currency' => $this->input->post('ccurrency'),
                             'code' => $this->input->post('tcode').'-'.$this->input->post('tno'), 'name' => $this->input->post('tname'),
                             'alias' => $this->input->post('talias'), 'status' => $this->input->post('cactive'), 'bank_stts' => $bank,
                             'created' => date('Y-m-d H:i:s'));
            
            if ($this->Model->add($account) == true){
              $this->create_balance($this->input->post('tcode').'-'.$this->input->post('tno'));
              $this->Model->log('create'); $this->output = $this->Model->get_latest(); 
            }else{ $this->reject(); }
        }
        else{ $this->reject(validation_errors()); }
        }else { $this->reject_token(); }
        $this->response('c');
    }
    
    private function create_balance($code=null)
    {
        $ps = $this->period->get();
        $accid = $this->account->get_id_code($code);
        $this->balance->create($accid, $ps->month, $ps->year, 0, 0);
    }
    
     // Fungsi update untuk menset texfield dengan nilai dari database
    function get($uid=null,$type=null)
    {     
        if ($this->acl->otentikasi1($this->title) == TRUE){
            if ($type == 'code'){
                if ( $this->Model->get_by_code($uid)->row()){ $this->output = $this->Model->get_by_code($uid)->row(); }else{ $this->reject("Code Not Found",404); }
            }
            else{ if ($this->Model->get_by_id($uid)->row()){
               $this->output = $this->Model->get_by_id($uid)->row(); }else{ $this->reject("ID Not Found",404); }
            }
        }else { $this->reject_token(); }
        $this->response('c');
    }

    // Fungsi update untuk mengupdate db
    function update($uid=0)
    {
        if ($this->acl->otentikasi2($this->title) == TRUE && $this->Model->valid_add_trans($uid, $this->title) == TRUE){ 
            
	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required|callback_validation_name['.$uid.']');
        $this->form_validation->set_rules('tno', 'No', 'required|numeric');
        $this->form_validation->set_rules('tcode', 'Code', 'required|numeric|callback_validation_code['.$uid.']');
        $this->form_validation->set_rules('ccurrency', 'Currency', 'required');
        $this->form_validation->set_rules('cclassification', 'Classification', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {
            if ($this->input->post('cclassification') == 7 || $this->input->post('cclassification') == 8){ $bank = 1; }
            else { $bank  = $this->input->post('cbank'); }
            
            $account = array('classification_id' => $this->input->post('cclassification'), 'currency' => $this->input->post('ccurrency'),
                             'code' => $this->input->post('tcode').'-'.$this->input->post('tno'), 'name' => $this->input->post('tname'),
                             'alias' => $this->input->post('talias'), 'status' => $this->input->post('cactive'), 'bank_stts' => $bank);
            
            if ($this->Model->update($uid, $account) == true){
                $this->error = 'Data successfully saved..!';
            }else{ $this->reject(); }
        }
        else{ $this->reject(validation_errors()); }
        }else{ $this->valid_404($this->Model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }

    public function valid_name($name)
    {        
        if ($this->Model->valid('name',$name) == FALSE)
        {
            $this->form_validation->set_message('valid_name', "This $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    public function valid_default($uid=null)
    {
        if ($this->Model->valid_default($uid) == FALSE)
        {
            $this->form_validation->set_message('valid_default', "Default Account - [Can't Changed]..!");
            return FALSE;
        }
        else{ return TRUE; }
    }

    public function validation_name($name,$id)
    {   
	if ($this->Model->validating('name',$name,$id) == FALSE)
        {
            $this->form_validation->set_message('validation_name', 'This '.$this->title.' is already registered!');
            return FALSE;
        }
        else { return TRUE; }
    }

    public function validation_code($no,$id)
    {
        $code = $this->input->post('tno').'-'.$no;
	if ($this->Model->validating('name',$code,$id) == FALSE)
        {
            $this->form_validation->set_message('validation_code', 'This '.$this->title.' code is already registered!');
            return FALSE;
        }
        else { return TRUE; }
    }

    public function valid_code($code)
    {   
        $code = $code.'-'.$this->input->post('tno');
        if ($this->Model->valid('code',$code) == FALSE)
        {
            $this->form_validation->set_message('valid_code', "Account No already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }

// ====================================== REPORT =========================================


    function report()
    {
        $this->acl->otentikasi2($this->title);
        $data['title'] = $this->properti['name'].' | Report '.ucwords($this->modul['title']);

        $cur = $this->input->post('ccurrency');
        $status = $this->input->post('cstatus');

        $data['currency'] = 'null';
        $data['rundate'] = tgleng(date('Y-m-d'));
        $data['log'] = $this->session->userdata('log');

//        Property Details
        $data['company'] = $this->properti['name'];

        // assets
        $data['kas'] = $this->Model->report($cur,$status,7)->result();
        $data['bank'] = $this->Model->report($cur,$status,8)->result();
        $data['piutangusaha'] = $this->Model->report($cur,$status,20)->result();
        $data['piutangnonusaha'] = $this->Model->report($cur,$status,27)->result();
        $data['persediaan'] = $this->Model->report($cur,$status,14)->result();
        $data['biayadimuka'] = $this->Model->report($cur,$status,13)->result();
        $data['investasipanjang'] = $this->Model->report($cur,$status,29)->result();
        $data['hartatetapwujud'] = $this->Model->report($cur,$status,26)->result();
        $data['hartatetaptakwujud'] = $this->Model->report($cur,$status,30)->result();
        $data['hartalain'] = $this->Model->report($cur,$status,31)->result();
        
        // kewajiban
        $data['hutangusaha'] = $this->Model->report($cur,$status,10)->result();
        $data['pendapatandimuka'] = $this->Model->report($cur,$status,34)->result();
        $data['hutangjangkapanjang'] = $this->Model->report($cur,$status,35)->result();
        $data['hutangnonusaha'] = $this->Model->report($cur,$status,32)->result();
        $data['hutanglain'] = $this->Model->report($cur,$status,36)->result();
        
        // modal & laba
        $data['modal'] = $this->Model->report($cur,$status,22)->result();
        $data['laba'] = $this->Model->report($cur,$status,18)->result();
        
        // income
        $data['income'] = $this->Model->report($cur,$status,16)->result();
        $data['otherincome'] = $this->Model->report($cur,$status,37)->result();
        $data['outincome'] = $this->Model->report($cur,$status,21)->result();
        
        // biaya
        $data['biayausaha'] = $this->Model->report($cur,$status,15)->result();
        $data['biayausahalain'] = $this->Model->report($cur,$status,17)->result();
        $data['biayaoperasional'] = $this->Model->report($cur,$status,19)->result();
        $data['biayanonoperasional'] = $this->Model->report($cur,$status,24)->result();
        $data['pengeluaranluarusaha'] = $this->Model->report($cur,$status,25)->result();
        
        
        $this->load->view('account_report', $data); 
    }


// ====================================== REPORT =========================================
    
   function get_ajax_code(){ echo $this->classification->get_no($this->input->post('value')); }
   
// ====================================== CLOSING ======================================
   function reset_process(){ $this->Model->closing(); }

}

?>