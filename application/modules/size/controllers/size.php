<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Size extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Size_model', 'model', TRUE);
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

    function index()
    {
        if ($this->acl->otentikasi1($this->title) == TRUE){
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        
        $result = $this->model->get_last($this->limitx, $this->offsetx)->result();
        $this->count = $this->model->get_last($this->limitx, $this->offsetx,1);
       
	foreach($result as $res){ $this->resx[] = array ("id"=>$res->id, "name"=>$res->name, "desc"=>$res->descs); }
        
        $data['record'] = $this->count; 
        $data['result'] = $this->resx; 
        $this->output = $data;
        
        }else{ $this->reject_token(); }
        $this->response('content');
    } 
        
    function delete_all()
    {
      if ($this->acl->otentikasi_admin($this->title,'ajax') == TRUE ){
      
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
              $this->model->delete($cek[$i]); 
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
      if ($this->acl->otentikasi3($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
           $this->model->delete($uid);
           $this->error = "$this->title successfully removed..!";
      }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
      $this->response();
    }

    private function cek_relation($id)
    {   
        if ($this->cek_primary($id) == TRUE) { return TRUE; } else { return FALSE; }
    }

    function add()
    {
      if ($this->acl->otentikasi2($this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required|callback_valid');
        $this->form_validation->set_rules('tdesc', 'Code', '');

        if ($this->form_validation->run($this) == TRUE)
        {
            $category = array('name' => strtolower($this->input->post('tname')), 
                              'created' => date('Y-m-d H:i:s'), 'descs' => $this->input->post('tdesc'));

            if ($this->model->add($category) != true){ $this->error = $this->reject();
            }else{ $this->model->log('create'); $this->output = $this->model->get_latest(); }
        }
        else{ $this->reject(validation_errors(),400); }
      }else{ $this->reject_token(); }
      $this->response('c');
    }

    // Fungsi update untuk menset texfield dengan nilai dari database
    function get($uid=null)
    {       
        if ($this->acl->otentikasi1($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
        $category = $this->model->get_by_id($uid)->row();
        $data['name'] = $category->name;
        $data['desc'] = $category->descs;
        $this->output = $data;
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response('content');
    }

    function valid($code)
    {
        if ($this->model->valid('name',$code) == FALSE)
        {
            $this->form_validation->set_message('valid', "This $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }

    function validation($code,$id)
    {
	if ($this->model->validating('name',$code,$id) == FALSE)
        {
            $this->form_validation->set_message('validation', 'This color is already registered!');
            return FALSE;
        }
        else{ return TRUE; }
    }

    // Fungsi update untuk mengupdate db
    function update($uid=null)
    {
        if ($this->acl->otentikasi2($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required|callback_validation['.$uid.']');
        $this->form_validation->set_rules('tdesc', 'Code', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {
            $category = array('name' => strtolower($this->input->post('tname')),'descs' => $this->input->post('tdesc'));
            if ($this->model->update($uid,$category) != true){ $this->error = $this->reject('failed to post');
            }else{ $this->error = $this->title.' successfully saved..!'; }
        }
        else{ $this->reject(validation_errors(),400); }
      }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
      $this->response();
    }
    
    // ====================================== CLOSING ======================================
    function reset_process(){ $this->model->closing(); }

}

?>