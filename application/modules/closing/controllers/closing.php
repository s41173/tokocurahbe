<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Closing extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Closing_model', '', TRUE);
        
        $this->properti = $this->property->get();
        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));

        $this->load->library('currency_lib');
        $this->product = new Product_lib();
        $this->user = $this->load->library('admin_lib');
//        $this->sales = $this->load->library('sales');
        $this->journal = new Journalgl_lib();
        $this->component = new Components();
        $this->period = new Period();
        $this->period = $this->period->get();
        $this->balancelib = new Balance_account_lib();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $component,$balancelib;
    private $user,$product,$journal,$period,$api,$acl;
    
    private function index()
    {
       if ($this->acl->otentikasi3($this->title) == TRUE){  
         if ($this->period->month == $this->period->closing_month){ $this->annual();}else { $this->monthly(); }
       }else{ $this->reject_token(); }
       $this->api->response(array('error' => $this->error, 'content' => $this->output), $this->status); 
    }
    
    public function cek_component()
    {
        $result = $this->component->get_closing_aktif();
        $val=0;
        foreach ($result as $res)
        { if ($this->cek_closing_component($res->table) == 0){ $val = 0; break; }else { $val = 1; } }
        return $val;
    }
    
    private function cek_closing_component($table)
    {
       $month = $this->period->month;
       $year = $this->period->year;
       
       $this->db->where('approved', 0); 
       $this->db->where('MONTH(dates)', $month);
       $this->db->where('YEAR(dates)', $year);
       $val = $this->db->get($table)->num_rows();
       if (floatval($val) > 0){ return 0; }else{ return 1; }
    }
    

    function get_last_closing()
    {
        $this->acl->otentikasi1($this->title);

        $data['title'] = $this->properti['name'].' | Administrator  '.ucwords($this->modul['title']);
        $data['h2title'] = $this->modul['title'];
        $data['main_view'] = 'closing_view';
	$data['form_action'] = site_url($this->title.'/search');
        $data['link'] = array('link_back' => anchor('main/','<span>back</span>', array('class' => 'back')));
        
	$uri_segment = 3;
        $offset = $this->uri->segment($uri_segment);

	// ---------------------------------------- //
        $closings = $this->Closing_model->get_last_closing($this->modul['limit'], $offset)->result();
        $num_rows = $this->Closing_model->count_all_num_rows();

        if ($num_rows > 0)
        {
	    $config['base_url'] = site_url($this->title.'/get_last_closing');
            $config['total_rows'] = $num_rows;
            $config['per_page'] = $this->modul['limit'];
            $config['uri_segment'] = $uri_segment;
            $this->pagination->initialize($config);
            $data['pagination'] = $this->pagination->create_links();

            $tmpl = array('table_open' => '<table cellpadding="2" cellspacing="1" class="tablemaster">');

            $this->table->set_template($tmpl);
            $this->table->set_empty("&nbsp;");

            //Set heading untuk table
            $this->table->set_heading('No', 'Code', 'Date', 'Notes', 'Log');

            $i = 0 + $offset;
            foreach ($closings as $closing)
            {
                $datax = array('name'=> 'cek[]','id'=> 'cek'.$i,'value'=> $closing->id,'checked'=> FALSE, 'style'=> 'margin:0px');
                
                $this->table->add_row
                (++$i, 'CLO-00'.$closing->id, tgleng($closing->dates).' - '.$closing->times, $closing->notes, $closing->log);
            }
            $data['table'] = $this->table->generate();
        }
        else { $data['message'] = "No $this->title data was found!"; }

        // Load absen view dengan melewatkan var $data sbgai parameter
	$this->load->view('template', $data);
    }

    function calculate()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){ 
        $this->load->model('Account_model', 'am', TRUE);
        
        $accounts = new Accounts();
        $ps = new Period();
        $bl = new Balances();
        $accounts->get();
        $ps->get();
        
        $res = null;
        $next = $this->next_period();  
        
        $result1=true;
        
        foreach ($accounts as $account)
        {    
            $res_trans = $this->am->get_balance($account->id,$ps->month,$ps->year)->row_array(); 
            $res_trans = floatval($res_trans['vamount']);

            $bl->where('month', $ps->month);
            $bl->where('year', $ps->year);
            $bl->where('account_id', $account->id)->get();
            $res1 = floatval($bl->beginning + $res_trans + $bl->vamount);

            $status1 = $this->balancelib->create($account->id, $ps->month, $ps->year, floatval($bl->beginning), $res1); // create end saldo this month
            $status2 = $this->balancelib->create($account->id, $next[0], $next[1], $res1, 0); // create beginning saldo next month
            $bl->clear();
            
            if ($status1 != TRUE || $status2 != TRUE){ $result1 = FALSE; break; }
        }
        if ($result1 == TRUE){ $this->error = "Calculating Ending Balance Sucessed..!"; }else{ $this->reject("Pending Calculating..!"); } 
       }else{ $this->reject_token(); }
       $this->response();
    }
    
    function monthly()
    {
       if ($this->acl->otentikasi3($this->title) == TRUE){ 
        $this->load->model('Account_model', 'am', TRUE);
        $accounts = new Accounts();
        $ps = new Period();
        $bl = new Balances();
        $accounts->get();
        $ps->get();
        
        $res = null;
        $status1 = FALSE; $status2 = FALSE; $status3=FALSE;
        foreach ($accounts as $account)
        {    
            $next = $this->next_period();  

            $res_trans = $this->am->get_balance($account->id,$ps->month,$ps->year)->row_array(); 
            $res_trans = floatval($res_trans['vamount']);

            $bl->where('month', $ps->month);
            $bl->where('year', $ps->year);
            $bl->where('account_id', $account->id)->get();
            $res1 = floatval($bl->beginning + $bl->vamount + $res_trans);
            $status1 = $this->balancelib->create($account->id, $ps->month, $ps->year, floatval($bl->beginning), $res1); // create end saldo this month
            $status2 = $this->balancelib->create($account->id, $next[0], $next[1], $res1, 0); // create beginning saldo next month
            $bl->clear();  
        }
        // update ledger stock
        $stock = new Stock_ledger_lib();
        $stock->closing();
        
        // update fixed asset
        $asset = new Asset_lib();
        $status3 = $asset->closing();
        
        // update tank storage 
//        $tankledger = new Tankledger_lib();
//        $tankledger->calculate();

        if ($status1 == TRUE && $status2 == TRUE && $status3 == TRUE){ 
            
            // update periode akuntansi
            $ps->month = $next[0];
            $ps->year = $next[1];
            $ps->save();
            $this->error = "Monthly End Sucessed..!..!"; 
        }
        else{ $this->reject("Pending Calculating..!"); } 
      }else{ $this->reject_token(); }
      $this->response();
    }
    
    private function next_period()
    {
        $ps = new Period();
        $ps = $ps->get();
        
        $month = $ps->month;
        $year = $ps->year;
        
        if ($month == 12){$nmonth = 1;}else { $nmonth = $month +1; }
        if ($month == 12){ $nyear = $year+1; }else{ $nyear = $year; }
        $res[0] = $nmonth; $res[1] = $nyear;
        return $res;
    }
    
    function annual()
    {
      if ($this->acl->otentikasi3($this->title) == TRUE){  
        $ps = new Period();
        $ps->get();
        if ($ps->month == $ps->closing_month){ $status = $this->annual_process();
          if ($status == TRUE){ $this->error = "Annual Closing Sucessed..!";
          }else{ $this->reject("Annual Closing Failed.."); }
        }
        else { $this->reject("Annual Closing Rollback - Invalid Period..!"); } 
      }else{ $this->reject_token(); }
      $this->response();
    }
    
    private function annual_process()
    {
        $this->load->model('Account_model', 'am', TRUE);
        
        $accounts = new Accounts();
        $ps = new Period();
        $bl = new Balances();
        $accounts->get();
        $ps->get();
        
        $res = null;
        $status1 = FALSE; $status2 = FALSE;
        foreach ($accounts as $account)
        {    
           if ($account->id == 21)
           {
              $bl = new Balances();
              $bl->where('month', $ps->month);
              $bl->where('year', $ps->year);
              $bl->where('account_id', $account->id)->get();
              
              $res_trans = $this->am->get_balance($account->id,$ps->month,$ps->year)->row_array(); 
              $res_trans = floatval($res_trans['vamount']);
              
              $res1 = $bl->beginning + $res_trans;
              
              // memindahkan saldo awal + vamount menjadi end saldo
              $this->balancelib->create($account->id, $ps->month, $ps->year, $bl->beginning, $res1);
              
              // memindai nilai laba tahun berjalan ke akun laba di tahan
              $bl->clear();
              $bl = new Balances();
              
              $next = $this->next_period();
              
              $bl->account_id = 22;
              $bl->beginning  = $res1;
              $bl->end        = 0;
              $bl->month      = $next[0];
              $bl->year       = $next[1];
              $status1 = $this->balancelib->create(22, $next[0], $next[1], $res1, 0);
              
              // menset nilai akun bulan depan menjadi 0
              $bl->clear();
              $bl = new Balances();
              
              $bl->account_id = $account->id;
              $bl->beginning  = 0;
              $bl->end        = 0;
              $bl->month      = $next[0];
              $bl->year       = $next[1];
              $status2 = $this->balancelib->create($account->id, $next[0], $next[1], 0, 0);
           } 
           elseif ($account->id != 21 && $account->id != 22)
           {
              $res = $this->am->get_balance($account->id,$ps->month,$ps->year)->row_array();

              $bl->where('month', $ps->month);
              $bl->where('year', $ps->year);
              $bl->where('account_id', $account->id)->get();
              $res = floatval($res['vamount']) + floatval($bl->beginning) + floatval($bl->vamount); // saldo akhir bulan ini

              // update end saldo bulan ini
//              $bl->end = $res;
//              $bl->save();
              
              $status1 = $this->balancelib->create($account->id, $ps->month, $ps->year, $bl->beginning, $res); 

              // tambah nilai awal saldo bulan depan
              $bl->clear();
              $bl = new Balances();
              $bl->account_id = $account->id;
              $bl->beginning  = $res;
              $bl->end        = 0;
              $bl->month      = $next[0];
              $bl->year       = $next[1];
//              $bl->save();  
              $status2 = $this->balancelib->create($account->id, $next[0], $next[1], $res, 0); 
           } 
         }

          // closing jumlah siswa bulan ini
         
         // update ledger stock
          $stock = new Stock_ledger_lib();
          $stock->closing();
          
          if ($status1 == TRUE && $status2 == TRUE){
              // update periode akuntansi
              $ps->month = $next[0];
              $ps->year = $next[1];
              $ps->save();
              return TRUE;
          }else{ return FALSE; }
    }
    
                // ====================================== CLOSING ======================================
    function reset_process(){ $this->model->closing(); } 
    
}

?>
