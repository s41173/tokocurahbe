<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Tax extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Tax_model', 'model', TRUE);

        $this->properti = $this->property->get();
//        $this->acl->otentikasi();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
                
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $acl, $api;
    
    function index()
    {
      if ($this->acl->otentikasi1($this->title) == TRUE){
            
         $datax = (array)json_decode(file_get_contents('php://input')); 
         if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
         if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }   
            
         $result = $this->model->get_last($this->limitx, $this->offsetx)->result(); 
         $this->count = $this->model->get_last($this->limitx, $this->offsetx,1);
        
         foreach($result as $res)
         { $this->resx[] = array ("id"=>$res->id, "code"=>$res->code, "name"=>$res->name, "value" => floatval($res->value)); }
         
         $data['record'] = $this->count;
         $data['result'] = $this->resx;
         $this->output = $data;
      }else{ $this->reject_token(); }
      $this->response('c');
    }

    function delete($uid,$type='hard')
    {
       if ($this->acl->otentikasi3($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){
        if ($type == 'soft'){
           if ($this->model->delete($uid) == true){ $this->error = "$this->title successfully soft removed..!"; }else{
              $this->reject('Failed to delete');
           }
       }
       else{  $this->model->force_delete($uid);  $this->error = "$this->title successfully removed..!"; }
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
      $this->response();
    }

    function add()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tcode', 'Name', 'required|callback_valid_tax');
        $this->form_validation->set_rules('tname', 'Name', 'required');
        $this->form_validation->set_rules('tvalue', 'Value', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {
            $tax = array('name' => strtolower($this->input->post('tname')), 'code' => $this->input->post('tcode'),
                         'value' => floatval($this->input->post('tvalue')/100), 'created' => date('Y-m-d H:i:s'));

            if ( $this->model->add($tax) == true){ 
                $this->model->log('create'); $this->output = $this->model->get_latest();
            }else{ $this->reject(); }             
        }
        else{ $this->reject(validation_errors(),400); }
        }else{ $this->reject_token(); }
        $this->response('c');
    }

    // Fungsi update untuk menset texfield dengan nilai dari database
    function get($uid=0)
    {        
      if ($this->acl->otentikasi1($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){
          $this->output = $this->model->get_by_id($uid)->row();
      }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
      $this->response('c');
    }


    public function valid_tax($name)
    {
        if ($this->model->valid('code',$name) == FALSE)
        {
            $this->form_validation->set_message('valid_tax', "This $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }

    function validation_tax($name,$id)
    {
	if ($this->model->validating('code',$name,$id) == FALSE)
        {
            $this->form_validation->set_message('validation_tax', 'This tax is already registered!');
            return FALSE;
        }
        else { return TRUE; }
    }

    // Fungsi update untuk mengupdate db
    function update($uid=0)
    {
        if ($this->acl->otentikasi1($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){

            // Form validation
            $this->form_validation->set_rules('tcode', 'Name', 'required|callback_validation_tax['.$uid.']');
            $this->form_validation->set_rules('tname', 'Name', 'required');
            $this->form_validation->set_rules('tvalue', 'Value', 'required');

            if ($this->form_validation->run($this) == TRUE)
            {
                $tax = array('name' => strtolower($this->input->post('tname')), 'code' => $this->input->post('tcode'),
                             'value' => floatval($this->input->post('tvalue')/100));
                
                if ($this->model->update($uid, $tax) == true){ $this->error = 'Data successfully saved..'; }else{ $this->error = 'Failed to posted'; $this->status = 401; }
            }
            else{ $this->error = validation_errors(); $this->status = 400; }
        }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }
    
}

?>