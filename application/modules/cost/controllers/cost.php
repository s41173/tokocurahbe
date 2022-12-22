<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Cost extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Cost_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->account = new Account_lib();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $account, $api, $acl;
    
    public function index()
    {
        if ($this->acl->otentikasi1($this->title) == TRUE){ 
          
         $datax = (array)json_decode(file_get_contents('php://input'));   
         if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
         if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }    
            
        $result = $this->model->get_last($this->limitx, $this->offsetx)->result(); 
        $this->count = $this->model->get_last($this->limitx, $this->offsetx,1); 
        
	foreach($result as $res){
            $this->resx[] = array ("id" => $res->id, "name"=>$res->name,
                                     "account"=>$this->get_acc($res->account_id), "desc"=>$res->descs
                                    ); 
        }
        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
       }else{ $this->reject_token(); }
       $this->response('c');
    }
    
    private function get_acc($acc){ return $this->account->get_code($acc).' : '.$this->account->get_name($acc); }
    
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
           if ( $this->cek_relation($cek[$i]) == TRUE ) 
           {
              $this->model->delete($cek[$i]); 
           }
           else { $x=$x+1; }
        }
        $res = intval($jumlah-$x);
        $mess = "$res $this->title successfully removed &nbsp; - &nbsp; $x related to another component..!!";
        $this->error = $mess;
      }
      else{ $mess = "No $this->title Selected..!!"; $this->reject($mess); }
      }else{ $this->reject_token(); }
      $this->api->response(array('error' => $this->error), $this->status);
    }

    function delete($uid,$type='hard')
    {
       if ($this->acl->otentikasi3($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
           if ($this->model->delete($uid) == true){ $this->error = "$this->title successfully removed..!"; }else{ $this->reject('Failed to deleted');}         
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }

    function add()
    {
        if ($this->acl->otentikasi2($this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required|callback_valid_cost');
        $this->form_validation->set_rules('titem', 'Account', 'required');
        $this->form_validation->set_rules('tdesc', 'Desc', '');

        if ($this->form_validation->run($this) == TRUE)
        {
            $cost = array('name' => ucfirst($this->input->post('tname')), 
                          'account_id' => $this->account->get_id_code($this->input->post('titem')),
                          'descs' => $this->input->post('tdesc'), 'created' => date('Y-m-d H:i:s'));

            if ($this->model->add($cost) != true){ $this->error = $this->reject('failed to post');
            }else{ $this->model->log('create'); $this->output = $this->model->get_latest(); }
        }
        else{ $this->reject(validation_errors()); }
        }else{ $this->reject_token(); }
        $this->response('c');
    }

    function get($uid=null)
    {        
        if ($this->acl->otentikasi1($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
          $cost = $this->model->get_by_id($uid)->row();
          $this->output = array("id"=>$uid, "name"=>$cost->name, "acc_code"=>$this->account->get_code($cost->account_id),
                        "account"=>$cost->account_id, "desc"=>$cost->descs
                        );
        }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }


    public function valid_cost($name)
    {
        if ($this->model->valid('name',$name) == FALSE)
        {
            $this->form_validation->set_message('valid_cost', "This $this->title is already registered.!");
            return FALSE;
        }
        else{ return TRUE; }
    }

    function validation_cost($name,$id)
    {
	if ($this->model->validating('name',$name,$id) == FALSE)
        {
            $this->form_validation->set_message('validation_cost', 'This cost is already registered!');
            return FALSE;
        }
        else { return TRUE; }
    }

    // Fungsi update untuk mengupdate db
    function update($uid=null)
    {
        if ($this->acl->otentikasi2($this->title) == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){

	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required|callback_validation_cost['.$uid.']');
        $this->form_validation->set_rules('titem', 'Account', 'required');
        $this->form_validation->set_rules('tdesc', 'Desc', '');

        if ($this->form_validation->run($this) == TRUE)
        {
            $cost = array('name' => ucfirst($this->input->post('tname')), 
                          'account_id' => $this->account->get_id_code($this->input->post('titem')),
                          'descs' => $this->input->post('tdesc'));
            
            if ($this->model->update($uid,$cost) != true){ $this->error = $this->reject('failed to post');
            }else{ $this->error = $this->title.' successfully saved..!'; }
        }
        else{ $this->reject(validation_errors(),400); }
        }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }

}

?>