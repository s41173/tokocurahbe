<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Unit extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Unit_model', '', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $api, $acl;
    
    public function index()
    {
       if ($this->acl->otentikasi1($this->title) == TRUE){ 
         
         $datax = (array)json_decode(file_get_contents('php://input')); 
         if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
         if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }  
           
         $result = $this->Unit_model->get_last($this->limitx, $this->offsetx)->result(); 
         $this->count = $this->Unit_model->get_last($this->limitx, $this->offsetx,1); 
         
	 foreach($result as $res)
	 {
           $this->resx[] = array ("id" => $res->id, "code" => $res->code, "name" => $res->name, "desc"=> $res->desc);
	 }
         
         $data['record'] = $this->count; 
         $data['result'] = $this->resx;
         $this->output = $data;
         
       }else{ $this->reject_token(); }
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
           if ( $this->cek_relation($cek[$i]) == TRUE ) 
           {
              $this->Unit_model->delete($cek[$i]); 
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

    function delete($uid,$type='soft')
    {
       if ($this->acl->otentikasi3($this->title) == TRUE && $this->Unit_model->valid_add_trans($uid, $this->title) == TRUE){ 
          if ($this->Unit_model->force_delete($uid) == true){ $this->error = "$this->title successfully removed..!"; }else{ $this->reject('Failed to delete'); }
       }else{ $this->valid_404($this->Unit_model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }

    function add()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tcode', 'Name', 'required|callback_valid_unit');
        $this->form_validation->set_rules('tname', 'Name', 'required');
        $this->form_validation->set_rules('tdesc', 'Desc', '');

        if ($this->form_validation->run($this) == TRUE)
        {
            $unit = array('name' => strtolower($this->input->post('tname')), 'code' => $this->input->post('tcode'),
                          'desc' => $this->input->post('tdesc'), 'created' => date('Y-m-d H:i:s'));

            if ($this->Unit_model->add($unit) == true){ 
                $this->Unit_model->log('create'); $this->output = $this->Unit_model->get_latest();
            }else{ $this->reject('Failed to post'); }
        }
        else{ $this->reject(validation_errors(),400); }
        }else{ $this->reject_token(); }
        $this->response('c');
    }

    function get($uid=null)
    {        
       if ($this->acl->otentikasi1($this->title) == TRUE && $this->Unit_model->valid_add_trans($uid, $this->title) == TRUE){  
           $this->output = $this->Unit_model->get_by_id($uid)->row();
       }else{ $this->valid_404($this->Unit_model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response('c');
    }

    public function valid_unit($name)
    {
        if ($this->Unit_model->valid('code',$name) == FALSE)
        {
            $this->form_validation->set_message('valid_unit', "This $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }

    function validation_unit($name,$id)
    {
	if ($this->Unit_model->validating('code',$name,$id) == FALSE)
        {
            $this->form_validation->set_message('validation_unit', 'This unit is already registered!');
            return FALSE;
        }
        else { return TRUE; }
    }

    // Fungsi update untuk mengupdate db
    function update($uid=0)
    {
       if ($this->acl->otentikasi2($this->title) == TRUE && $this->Unit_model->valid_add_trans($uid, $this->title) == TRUE){ 

	// Form validation
        $this->form_validation->set_rules('tcode', 'Name', 'required|callback_validation_unit['.$uid.']');
        $this->form_validation->set_rules('tname', 'Name', 'required');
        $this->form_validation->set_rules('tdesc', 'Desc', '');

        if ($this->form_validation->run($this) == TRUE)
        {
            $unit = array('name' => strtolower($this->input->post('tname')), 'code' => $this->input->post('tcode'),
                          'desc' => $this->input->post('tdesc'));
            if ($this->Unit_model->update($uid, $unit) == true){ $this->error = 'Data posted'; }else{ $this->reject('Failed to post'); }
        }
        else{ $this->reject(validation_errors(),400); }
       }else{ $this->valid_404($this->Unit_model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }
    
    // ====================================== CLOSING ======================================
    function reset_process(){ $this->model->closing(); }

}

?>