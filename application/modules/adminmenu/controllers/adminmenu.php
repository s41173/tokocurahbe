<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/jwt/JWT.php';
use \Firebase\JWT\JWT;

class Adminmenu extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Adminmenu_model', 'model', TRUE);
        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->city = new City_lib();
        $this->menu = new Adminmenu_lib();
        $this->component = new Components();
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $api, $acl;
    private $role,$city,$menu,$component;

    // dashboard purpose 
    function get(){
        
      if ($this->api->otentikasi() == TRUE){
        $result = $this->menu->get_parent_menu();
        foreach($result as $res){
            
            if ($res->parent_status == 1){ $stts = 'parent'; }else { $stts = 'child'; }
            $this->output[] = array ("id" => $res->id, "parent" => $this->menu->getmenuname($res->parent_id), "name" => $res->name,
                               "modul" => $res->modul, "url" => $res->url, "order" => $res->menu_order, "class_style" => $res->class_style,
                               "id_style" => $res->id_style, "icon" => $res->icon, "target" => $res->target, $stts);
        }
//        $response['content'] = $output;
        
     }else{ $this->reject_token(); }
     $this->response('content');
    }
    
    function get_child($parent=0){
        
       if ($this->api->otentikasi() == TRUE){    
        if ($parent != 0){ $result = $this->menu->get_child_menu($parent);
        }else{ $result = $this->menu->get_child_menu(); }
        
        foreach($result as $res){

            if ($res->parent_status == 1){ $stts = 'parent'; }else { $stts = 'child'; }
            $this->output[] = array ("id" => $res->id, "parent" => $this->menu->getmenuname($res->parent_id), "name" => $res->name,
                               "modul" => $res->modul, "url" => $res->url, "order" => $res->menu_order, "class_style" => $res->class_style,
                               "id_style" => $res->id_style, "icon" => $res->icon, "target" => $res->target, $stts);
        }
     }else{ $this->reject_token(); }
     $this->response('content');
    }
    
    function index()
    {
        if ($this->acl->otentikasi_admin() == TRUE){
           
            $datax = (array)json_decode(file_get_contents('php://input')); 
            if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
            if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
            
            $result = $this->model->get_last($this->limitx, $this->offsetx)->result();
            $this->count = $this->model->get_last($this->limitx, $this->offsetx,1);
            
            foreach($result as $res){

                if ($res->parent_status == 1){ $stts = 'parent'; }else { $stts = 'child'; }
                $this->resx[] = array ("id" => $res->id, "parent" => $this->menu->getmenuname($res->parent_id), "name" => $res->name,
                                   "modul" => $res->modul, "url" => $res->url, "order" => $res->menu_order, "class_style" => $res->class_style,
                                   "id_style" => $res->id_style, "icon" => $res->icon, "target" => $res->target, $stts);
            }
            
          $data['record'] = $this->count; 
          $data['result'] = $this->resx;
          $this->output = $data;
          
        }else{ $this->reject_token(); }
        $this->response('content');
    }
    
    function add()
    {
        if ($this->acl->otentikasi_admin() == TRUE){

            // Form validation
            $this->form_validation->set_rules('tname', 'Name', 'required|callback_valid_name');
            $this->form_validation->set_rules('cparent', 'Parent Adminmenu', 'callback_valid_parent');
            $this->form_validation->set_rules('cmodul', 'Modul', 'required');
            $this->form_validation->set_rules('turl', 'URL', 'required');
            $this->form_validation->set_rules('tmenuorder', 'Menu Order', 'required');
            $this->form_validation->set_rules('tclass', 'Class', '');
            $this->form_validation->set_rules('tid', 'ID', '');
            $this->form_validation->set_rules('ctarget', 'Target', 'required');
            $this->form_validation->set_rules('cstatus', 'Parent Status', 'required');

            if ($this->form_validation->run($this) == TRUE)
            {
               if ($this->input->post('cstatus') == 1) { $parent = 0; }else { $parent = $this->input->post('cparent'); }
               $menu = array('parent_id' => $parent,'name' => $this->input->post('tname'),
                              'modul' => $this->input->post('cmodul'), 'url' => $this->input->post('turl'),
                              'menu_order' => $this->input->post('tmenuorder'), 'class_style' => $this->input->post('tclass'),
                              'id_style' => $this->input->post('tid'),'icon' => null, 'target' => $this->input->post('ctarget'),
                              'parent_status' => $this->input->post('cstatus'), 'created' => date('Y-m-d H:i:s'));

                $this->model->add($menu);
                $this->model->log('create'); $this->output = $this->model->get_latest(); 
            }
            else{ $this->reject(validation_errors(),400); }
        }
        else { $this->reject_token(); }
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
             $this->model->delete($cek[$i]);
             $x=$x+1;
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
      }else{ echo "error|Sorry, you do not have the right to edit $this->title component..!"; }
      
    }
    
    function delete($uid)
    {
        if ($this->acl->otentikasi_admin($this->title) == TRUE){
            $this->model->delete_child($uid); // delete child related parent menu
            $this->model->force_delete($uid);
            $this->error = "1 $this->title successfully removed..!";
        }else { $this->reject_token(); }
        $this->response();
    }
    
    function update($uid=null)
    {        
        $admin = $this->model->get_by_id($uid)->row();
               
	$this->session->set_userdata('langid', $admin->id);
        
        echo $uid.'|'.$admin->parent_id.'|'.$admin->name.'|'.$admin->modul.'|'.$admin->url.
             '|'.$admin->menu_order.'|'.$admin->class_style.'|'.$admin->id_style.'|'.$admin->icon.'|'.
              $admin->target.'|'.$admin->parent_status;
    }

    // Fungsi update untuk mengupdate db

    function update_process()
    {
        if ($this->acl->otentikasi_admin($this->title,'ajax') == TRUE){

        $data['title'] = $this->properti['name'].' | Administrator  '.ucwords($this->modul['title']);
        $data['h2title'] = $this->modul['title'];
        $data['main_view'] = 'admin_update';
	$data['form_action'] = site_url($this->title.'/update_process');
	$data['link'] = array('link_back' => anchor('admin/','<span>back</span>', array('class' => 'back')));

	// Form validation
        $this->form_validation->set_rules('tname', 'Name', 'required|callback_validating_name');
        $this->form_validation->set_rules('cparent', 'Parent Adminmenu', 'callback_valid_parent');
        $this->form_validation->set_rules('cmodul', 'Modul', 'required');
        $this->form_validation->set_rules('turl', 'URL', 'required');
        $this->form_validation->set_rules('tmenuorder', 'Menu Order', 'required');
        $this->form_validation->set_rules('tclass', 'Class', '');
        $this->form_validation->set_rules('tid', 'ID', '');
        $this->form_validation->set_rules('ctarget', 'Target', 'required');

        if ($this->form_validation->run($this) == TRUE)
        {
            if ($this->input->post('cstatus') == 1) { $parent = 0; }else { $parent = $this->input->post('cparent'); }
            $menu = array('parent_id' => $parent,'name' => $this->input->post('tname'),
                              'modul' => $this->input->post('cmodul'), 'url' => $this->input->post('turl'),
                              'menu_order' => $this->input->post('tmenuorder'), 'class_style' => $this->input->post('tclass'),
                              'id_style' => $this->input->post('tid'),'icon' => null, 'target' => $this->input->post('ctarget'),
                              'parent_status' => $this->input->post('cstatus'));

	    $this->model->update($this->session->userdata('langid'), $menu);
            $this->session->set_flashdata('message', "One $this->title has successfully updated!");
          //  $this->session->unset_userdata('langid');
            echo "true|One $this->title has successfully updated..!";
        }
        else{ echo 'warning|'.validation_errors(); }
        }else { echo "error|Sorry, you do not have the right to edit $this->title component..!"; }
    }
    
    function valid_name($name)
    {
        if ($this->model->valid('name',$name) == FALSE)
        {
            $this->form_validation->set_message('valid_name', $this->title.' name registered');
            return FALSE;
        }
        else{ return TRUE; }
    }

    function validating_name($name)
    {
	$id = $this->session->userdata('langid');
	if ($this->model->validating('name',$name,$id) == FALSE)
        {
            $this->form_validation->set_message('validating_name', "This $this->title name is already registered!");
            return FALSE;
        }
        else{ return TRUE; }
    }
    
    function valid_parent($cparent)
    {
        $stts = $this->input->post('cstatus');
        if ($stts == 0){ 
            if (!$cparent){ $this->form_validation->set_message('valid_parent', "parent menu required..!"); return FALSE; }
            else { return TRUE; }            
        }
        else { return TRUE; }
    }
    
    function remove_img()
    {
        $img = $this->model->get_by_id(1)->row();
        $img = $img->logo;
        if ($img){ $img = "./images/property/".$img; unlink("$img"); }
    }
    
        // ====================================== CLOSING ======================================
    function reset_process(){ $this->model->closing(); } 

}

?>