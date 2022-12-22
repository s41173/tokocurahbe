<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Payment extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Payment_model', 'model', TRUE);

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

    
    public function index($search=null)
    {
       if ($this->api->otentikasi() == TRUE){ 
           
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }   
           
        $result = $this->model->get_last($this->limitx, $this->offsetx)->result();
        $this->count = $this->model->get_last($this->limitx, $this->offsetx,1);
        
	foreach($result as $res){
            if ($res->cost_type == 0){ $ctype = '%'; }else{ $ctype = 'Nonimal'; }
            if ($search){ $labelid = 'value'; $labelname = 'label'; }else{ $labelid = 'id'; $labelname = 'name'; }
            $this->resx[] = array ($labelid => $res->id, $labelname => ucfirst($res->name), "image" => base_url().'images/payment/'.$res->image,
                                     "order"=>$res->orders, "acc_no"=>$res->acc_no, "acc_name"=>$res->acc_name,
                                     "cost_type"=>$ctype, "cost"=>$res->cost, "add_cost"=>$res->add_cost, "cash"=>$res->cash
                                    ); 
        }
        
        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
        
       }else{ $this->reject_token(); }
       $this->response('c');
    }
    
    function get($uid=0)
    {   
        if ($this->api->otentikasi() == TRUE && $this->model->valid_add_trans($uid, $this->title) == TRUE){ 
            $customer = $this->model->get_by_id($uid)->row_array();
            $this->output = $customer;
        }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }

}

?>