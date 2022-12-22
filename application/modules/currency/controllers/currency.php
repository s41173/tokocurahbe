<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Currency extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Currency_model', 'model', TRUE);

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
           
        $result = $this->model->get_last($this->limitx, $this->offsetx)->result(); 
        $this->count = $this->model->get_last($this->limitx, $this->offsetx,1); 
        
	foreach($result as $res){ $this->resx[] = array ("id" => $res->id, "code" => $res->code, "name" => $res->name); }
        
        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
       }else{ $this->reject_token(); }
       $this->response('c');    
    }
    
    function publish($uid = null)
    {
       if ($this->acl->otentikasi2($this->title,'ajax') == TRUE){ 
       $val = $this->model->get_by_id($uid)->row();
       if ($val->publish == 0){ $lng = array('publish' => 1); }else { $lng = array('publish' => 0); }
       $this->model->update($uid,$lng);
       echo 'true|Status Changed...!';
       }else{ echo "error|Sorry, you do not have the right to change publish status..!"; }
    }
    
    function defaults($uid = null)
    {        
       if ($this->acl->otentikasi2($this->title,'ajax') == TRUE){ 
           
        $val = $this->model->get_default()->row();
        $lng = array('defaults' => 0);
        $this->model->update($val->id,$lng);

        $lng = array('defaults' => 1);
        $this->model->update($uid,$lng);  
        echo 'true|Defaults Changed..!';
           
       }else{ echo "error|Sorry, you do not have the right to change publish status..!"; }
    }

    function delete($uid,$type='hard')
    {
       if ($this->acl->otentikasi3($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
       if ($type == 'soft'){
           if ($this->model->delete($uid) == true){ $this->error = "$this->title successfully removed..!"; }else{ $this->reject('Failed to delete'); }
       }
       else{ if ($this->model->force_delete($uid) == true){ $this->error = "$this->title successfully removed..!"; }else{ $this->reject('Failed to delete'); } }
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }

    function add()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

            // Form validation
            $this->form_validation->set_rules('tcode', 'Name', 'required|callback_valid_currency');
            $this->form_validation->set_rules('tname', 'Name', 'required');

            if ($this->form_validation->run($this) == TRUE)
            {
                $currency = array('name' => strtolower($this->input->post('tname')), 'code' => $this->input->post('tcode'), 'created' => date('Y-m-d H:i:s'));
                if ($this->model->add($currency) == true){ 
                    $this->model->log('create'); $this->output = $this->model->get_latest();
                }else{ $this->reject('Failed to post'); }
            }
            else{ $this->reject(validation_errors(),400); }
        }else{ $this->reject_token(); }
       $this->response('c');
    }

    // Fungsi update untuk menset texfield dengan nilai dari database
    function get($uid=null)
    {        
       if ($this->acl->otentikasi1($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
           $this->output = $this->model->get_by_id($uid)->row();
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title));  $this->reject_token(); }
       $this->response('c');
    }

    public function valid_currency($name)
    {
        if ($this->model->valid('code',$name) == FALSE)
        {
            $this->form_validation->set_message('valid_currency', "This $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }

    function validation_currency($name,$id)
    {
	if ($this->model->validating('code',$name,$id) == FALSE)
        {
            $this->form_validation->set_message('validation_currency', 'This currency is already registered!');
            return FALSE;
        }
        else { return TRUE; }
    }

    // Fungsi update untuk mengupdate db
    function update($uid=null)
    {
       if ($this->acl->otentikasi2($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 

	// Form validation
        $this->form_validation->set_rules('tcode', 'Name', 'required|callback_validation_currency['.$uid.']');
        $this->form_validation->set_rules('tname', 'Name', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {
            $currency = array('name' => strtolower($this->input->post('tname')), 'code' => $this->input->post('tcode'));
            if ($this->model->update($uid, $currency) == true){ $this->error = 'Data successfully saved..'; }else{ $this->reject('Failed to post'); }
        }
        else{ $this->reject(validation_errors(),400); }
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }

}

?>