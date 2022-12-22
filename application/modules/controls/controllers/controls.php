<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Controls extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Controls_model', 'model', TRUE);
        
        $this->properti = $this->property->get();
        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));

        $this->currency = new Currency_lib();
        $this->classification = new Classification_lib();
        $this->account = new Account_lib();
        $this->component = new Components();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');  
    }

    private $properti, $modul, $title, $account, $component;
    private $currency,$classification,$api,$acl;
    
    public function index()
    {
        if ($this->acl->otentikasi1($this->title) == TRUE){
            
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
            
            $result = $this->model->get_last($this->limitx, $this->offsetx)->result();
            $this->count = $this->model->get_last($this->limitx, $this->offsetx,1);
            foreach($result as $res)
            {  
               $this->resx[] = array ("id" => $res->id, "no" => $res->no, "desc" => $res->desc, 
                                  "account" => $this->account->get_code($res->account_id).' : '.$this->account->get_name($res->account_id), 
                                  "modul" => ucfirst($this->component->get_name($res->modul)), "status" => $res->status);
            }
            $data['record'] = $this->count; 
            $data['result'] = $this->resx;
            $this->output = $data;
        }else{ $this->reject_token(); }
        $this->response('c');
    }    
    
    function add()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

            // Form validation
            $this->form_validation->set_rules('tdesc', 'Name', 'required|callback_valid_control');
            $this->form_validation->set_rules('titem', 'Account Code', 'required');
            $this->form_validation->set_rules('cmodul', 'Component', 'required');

            if ($this->form_validation->run($this) == TRUE)
            {
                $account = array('no' => $this->model->counter(), 'desc' => $this->input->post('tdesc'),
                                 'account_id' => $this->account->get_id_code($this->input->post('titem')), 
                                 'modul' => $this->input->post('cmodul'), 'status' => 0,
                                 'created' => date('Y-m-d H:i:s'));

                if ($this->model->add($account) == true){ $this->model->log('create'); $this->output = $this->model->get_latest();}
                else{ $this->reject(); }
            }
            else{ $this->reject(validation_errors(),400); }
        }else { $this->reject_token(); }
        $this->response();
    }

    function get($uid=null)
    {
        if ($this->acl->otentikasi1($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){
            $control = $this->model->get_by_id($uid)->row();
            
            $this->output = array('id' => $control->id, 'no' => $control->no,
                             "desc" => $control->desc, 
                             'acc_code' => $this->account->get_code($control->account_id),
                             'acc_name' => $this->account->get_name($control->account_id),
                             'modul' => $control->modul, 'status' => $control->status,
                             'created' => $control->created, 'updated' => $control->updated, 
                             'deleted' => $control->deleted);
            
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }

    // Fungsi update untuk mengupdate db
    function update($uid=null)
    {
        if ($this->acl->otentikasi2($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){
            // Form validation
            $this->form_validation->set_rules('titem', 'Account', 'required');
            if ($this->form_validation->run($this) == TRUE)
            {   
                $account = array('account_id' => $this->account->get_id_code($this->input->post('titem')), 
                                 'modul' => $this->input->post('cmodul'));

                if ($this->model->update($uid, $account) == true){ $this->error = 'Data successfully saved..!'; }else{ $this->reject(); }
            }
            else{ $this->reject(validation_errors(),400); }
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }
    
    function delete($uid)
    {
        if ($this->acl->otentikasi3($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){
            if ($this->cek_status($uid) == TRUE)
            {
               $this->model->force_delete($uid);
               $this->error = "$this->title successfully removed..!";
            }
            else { $this->reject('Default control account can not removed..!'); }
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }
    
    function delete_all()
    {
      if ($this->acl->otentikasi3($this->title) == TRUE){
      
      $cek = $this->input->post('cek');
      $jumlah = count($cek);

      if($cek)
      {
        $jumlah = count($cek);
        $x = 0;
        for ($i=0; $i<$jumlah; $i++)
        {
           if ( $this->cek_status($cek[$i]) == TRUE ) 
           {
              $this->model->force_delete($cek[$i]); 
           }
           else { $x=$x+1; }  
        }
        $res = intval($jumlah-$x);
        $mess = "$res $this->title successfully removed &nbsp; - &nbsp; $x related to another component..!!";
        $this->error = $mess;
      }
      else
      { $mess = "No $this->title Selected..!!"; $this->error = $mess; $this->status = 403; }
      }else { $this->reject_token(); }
      $this->api->response(array('error' => $this->error), $this->status);
    }
    
    private function cek_status($id)
    {
        $res = $this->model->get_by_id($id)->row();
        if ($res->status == 0){ return TRUE; } else { return FALSE; }
    }


    public function valid_control($name)
    {        
        if ($this->model->valid('desc',$name) == FALSE)
        {
            $this->form_validation->set_message('valid_control', "This $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }   
    }

    public function validation_control($acc)
    {   
        $id = $this->session->userdata('langid');
	if ($this->model->validating('account_id',$this->account->get_id_code($acc),$id) == FALSE)
        {
            $this->form_validation->set_message('validation_control', 'This '.$this->title.' is already registered!');
            return FALSE;
        }
        else { return TRUE; }  
    }
    
   // ====================================== CLOSING ====================================== 
   function reset_process(){ $this->model->closing(); }

}

?>