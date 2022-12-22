<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Droppoint extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Droppoint_model', 'model', TRUE);

        $this->properti = $this->property->get();
        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->product = new Product_lib();
        $this->city = new City_lib();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title;
    private $city;

    function index()
    {
//       if ($this->acl->otentikasi1($this->title) == TRUE){
           
          $result = $this->model->get_last($this->modul['limit'])->result();
          $data['result'] = $result;
          $this->output = $data;
//       }
//       else{ $this->reject_token(); }
       $this->response('c');
    }

    private function cek_relation($id)
    {
        $product = $this->product->cek_relation($id, $this->title);
        if ($product == TRUE) { return TRUE; } else { return FALSE; }
    }

    // Fungsi update untuk menset texfield dengan nilai dari database
    function get($uid=null)
    {       
       if ($this->model->valid_add_trans($uid, $this->title) == TRUE){
        $this->output = $this->model->get_by_id($uid)->row_array();
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response('c');
    }
    
    // ====================================== CLOSING ======================================
    function reset_process(){ $this->model->closing_defaults(); }

}

?>