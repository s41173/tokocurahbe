<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Bank extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Bank_model', 'model', TRUE);

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
            $result = $this->model->get_last($this->modul['limit'])->result();
            $this->count = $this->model->get_last($this->limitx, $this->offsetx,1);

            foreach($result as $res){
                $this->resx[] = array ("id" => $res->id, "currency"=>$res->currency,
                                         "acc_name"=>$res->acc_name, "acc_no"=>$res->acc_no, "acc_bank"=>$res->acc_bank 
                                        ); 
            }
            $data['record'] = $this->count; 
            $data['result'] = $this->resx;
            $this->output = $data; 
            
       }else{ $this->reject_token(); }
       $this->response('c');
    }

    function delete($uid,$type='soft')
    {
       if ($this->acl->otentikasi3($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
          if ($this->model->delete($uid) == true){ $this->error = "$this->title successfully soft removed..!"; }else{ $this->reject('Failed to deleted');} 
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }

    function add()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tname', 'Account Name', 'required');
        $this->form_validation->set_rules('tno', 'Account No', 'required|callback_valid_bank');
        $this->form_validation->set_rules('tbank', 'Bank Description', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {
            $bank = array('acc_name' => strtolower($this->input->post('tname')),
                          'currency' => 'IDR',
                          'acc_no' => $this->input->post('tno'), 
                          'acc_bank' => $this->input->post('tbank'), 'created' => date('Y-m-d H:i:s'));

            if ($this->model->add($bank) != true){ $this->reject('failed to post');
            }else{ $this->model->log('create'); $this->output = $this->model->get_latest(); }
        }
        else{ $this->reject(validation_errors(),400); }
        }else{ $this->reject_token(); }
        $this->response('c');
    }

    // Fungsi update untuk menset texfield dengan nilai dari database
    function get($uid=0)
    {     
        if ($this->acl->otentikasi1($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
           $this->output = $this->model->get_by_id($uid)->row_array();
        }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }

    public function valid_bank($name)
    {
        if ($this->model->valid('acc_no',$name) == FALSE)
        {
            $this->form_validation->set_message('valid_bank', "This $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }

    function validation_bank($name,$id)
    {
	if ($this->model->validating('acc_no',$name,$id) == FALSE)
        {
            $this->form_validation->set_message('validation_bank', 'This bank is already registered!');
            return FALSE;
        }
        else { return TRUE; }
    }

    // Fungsi update untuk mengupdate db
    function update($uid=null)
    {
        if ($this->acl->otentikasi2($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tname', 'Account Name', 'required');
        $this->form_validation->set_rules('tno', 'Account No', 'required|callback_validation_bank['.$uid.']');
        $this->form_validation->set_rules('tbank', 'Bank Description', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {            
            $bank = array('acc_name' => strtolower($this->input->post('tname')),
                          'acc_no' => $this->input->post('tno'), 
                          'acc_bank' => $this->input->post('tbank'));
            
            if ($this->model->update($uid,$bank) != true){ $this->error = $this->reject('failed to post');
            }else{ $this->error = $this->title.' successfully saved..!'; }
        }
        else{ $this->reject(validation_errors(),400); }
        }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }

}

?>