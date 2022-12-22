<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Roles extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Role_model', 'model', TRUE);
        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->menu = new Adminmenu_lib();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');  
    }

    private $properti, $modul, $title, $menu, $api, $acl;

    function index(){ 
        
        if ($this->acl->otentikasi_admin() == TRUE){

           $result = $this->model->get_last_role($this->modul['limit'])->result();

           foreach($result as $res){
               $this->output[] = array ("id" => $res->id, "name" => $res->name, "desc" => $res->desc, "rules" => self::get_rules($res->rules));
           }
       }else{ $this->reject_token(); }
       $this->response('c');
    }
    
    private function get_rules($val)
    {
        $re = null;
        switch ($val)
        {
            case 1:
              $re = "read";
              break;
            case 2:
              $re = "read / write";
              break;
            case 3:
              $re = "full control";
              break;
            case 4:
              $re = "approval";
              break;
        }
        return $re;
    }
    
    private function split_array($val){ return implode(",",$val); }
    
    function delete_all()
    {
      if ($this->acl->otentikasi_admin() == TRUE){
          
         $cek = $this->input->post('cek');
         $jumlah = count($cek);

            if($cek)
            {
              $jumlah = count($cek);
              $x = 0;
              for ($i=0; $i<$jumlah; $i++)
              {
                 $this->model->delete($cek[$i]);
                 $x=$x+1;
              }
              $res = intval($jumlah-$x);
              $mess = "$res $this->title successfully removed &nbsp; - &nbsp; $x related to another component..!!";
              $this->error = $mess;
            }
            else{ $mess = "No $this->title Selected..!!"; $this->error = $mess; $this->status = 401; }    
      }else{ $this->reject_token(); }
      $this->api->response(array('error' => $this->error), $this->status);
    }

    function delete($uid)
    {
        if ($this->acl->otentikasi_admin() == TRUE){
            $this->model->delete($uid);
            $this->error = "1 $this->title successfully removed..!";
        }else{ $this->reject_token(); }
        $this->api->response(array('error' => $this->error, 'content' => $this->output), $this->status);
    }

    function add()
    {
        if ($this->acl->otentikasi_admin() == TRUE){

            // Form validation
            $this->form_validation->set_rules('tname', 'Role Name', 'required|maxlength[100]|callback_valid_roles');
            $this->form_validation->set_rules('tdesc', 'Role Description', 'required');
            $this->form_validation->set_rules('crules', 'Rules', 'required');

            if ($this->form_validation->run($this) == TRUE)
            {//
                $roles = array('name' => $this->input->post('tname'), 'desc' => $this->input->post('tdesc'), 'rules' => $this->input->post('crules'),
                               'created' => date('Y-m-d H:i:s'), 'granted_menu' => $this->split_array($this->input->post('cmenu')));

                $this->model->add($roles);
                $this->model->log('create'); $this->output = $this->model->get_latest();
            }
            else{ $this->reject(validation_errors(),400); }
        }
        else { $this->reject_token(); }
        $this->response('c');
    }

    // Fungsi update untuk menset texfield dengan nilai dari database
    function get($uid=null)
    {     
        if ($this->acl->otentikasi_admin() == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){
            $this->output = $this->model->get_by_id($uid)->row();
        }else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }


    function valid_roles($val)
    {
        if ($this->model->valid('name',$val) == FALSE)
        {
            $this->form_validation->set_message('valid_roles', $this->title.' registered');
            return FALSE;
        }
        else{  return TRUE; }
    }

    function validating_roles($val,$id)
    {
	if ($this->model->validating('name',$val,$id) == FALSE)
        {
            $this->form_validation->set_message('validating_roles', "This $this->title name is already registered!");
            return FALSE;
        }
        else{ return TRUE; }
    }

    // Fungsi update untuk mengupdate db
    function update($uid)
    {
        if ($this->acl->otentikasi_admin() == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){

            // Form validation
            $this->form_validation->set_rules('tname', 'Role Name', 'required|maxlength[100]|callback_validating_roles['.$uid.']');
            $this->form_validation->set_rules('tdesc', 'Role Description', 'required');
            $this->form_validation->set_rules('crules', 'Rules', 'required');

            if ($this->form_validation->run($this) == TRUE)
            {
                $roles = array('name' => $this->input->post('tname'), 'desc' => $this->input->post('tdesc'), 'rules' => $this->input->post('crules'),
                               'granted_menu' => $this->split_array($this->input->post('cmenu')));

              $this->model->update($uid, $roles);
              $this->error = "One $this->title has successfully updated..!";

            }
            else{ $this->reject(validation_errors(),400); }
        }
        else { $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }

}

?>