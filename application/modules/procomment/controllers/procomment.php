<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/jwt/JWT.php';
use \Firebase\JWT\JWT;

class Procomment extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Procomment_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->login = new Customer_login_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
        $this->api = new Api_lib();
        $this->acl = new Acl();
        $this->customer = new Customer_lib();
        $this->product = new Product_lib();
        $this->sales = new Sales_lib();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $product, $sales;
    private $role, $login, $period, $api, $customer;

    
    function index()
    {
        $datax = (array)json_decode(file_get_contents('php://input')); 

        $pid=null; $cust=null;
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['product_id'])){ $pid = $datax['product_id']; }
        if (isset($datax['cust_id'])){ $cust = $datax['cust_id']; }
        
        $result = $this->model->get_last($pid, $cust, $this->limitx, $this->offsetx,0)->result();     
        $this->count = $this->model->get_last($pid, $cust, $this->limitx, $this->offsetx,1);

        foreach($result as $res){

            $this->resx[] = array ("id"=>$res->id, "cust"=> $this->customer->get_name($res->cust_id),
                                   "sku"=> $this->product->get_sku($res->product_id),
                                   'product'=> $this->product->get_name($res->product_id),
                                   "sales"=> $res->sales_id, "rating"=> intval($res->rating), "comments"=> $res->comments,
                                   "created"=> tglincompletetime($res->created)
                              );
        }
            
        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
        
        $this->response('content');
    } 

    function post()
    {
        if ($this->api->otentikasi() == TRUE){
	// Form validation
        $decoded = $this->api->get_decoded();
        $this->form_validation->set_rules('tsku', 'Product-SKU', 'required|callback_valid_product');
        $this->form_validation->set_rules('tcode', 'Code', 'required|callback_valid_code['.$decoded->userid.']');
        $this->form_validation->set_rules('tcomment', 'Comment', 'required');
        $this->form_validation->set_rules('trating', 'Rating', 'required|numeric|is_natural_no_zero');
        
        $valid = $this->model->valid_comment($decoded->userid, $this->product->get_id_by_sku($this->input->post('tsku')), $this->sales->get_by_orderid($this->input->post('tcode'), 'id'));
        if ($this->form_validation->run($this) == TRUE && $valid == TRUE)
        {
            $decoded = $this->api->get_decoded();
            $comments = array('cust_id' => $decoded->userid, 
                 'product_id' => $this->product->get_id_by_sku($this->input->post('tsku')), 
                 'sales_id' => $this->sales->get_by_orderid($this->input->post('tcode'), 'id'),
                 'comments' => $this->input->post('tcomment'), 'rating' => $this->input->post('trating'),
                 'created' => date('Y-m-d H:i:s'));
//            
            if ($this->model->add($comments) != true){ $this->reject();
            }else{$this->output = $this->model->get_latest(); }  
        }
        elseif ($valid != TRUE){ $this->reject('Comment already posted'); }
        else{ $this->reject(validation_errors(),400); }
        
        }else { $this->reject_token(); }
        $this->response('c');
    }
    
    function valid_product($val){
        if ($this->product->cek_trans('sku', $val) == FALSE){
            $this->form_validation->set_message('valid_product','Invalid SKU..!');
            return FALSE;
        }else{ return TRUE; }
    }
    
    function valid_code($val,$uid){
        if ($this->sales->cek_trans('code', $val) == FALSE){
            $this->form_validation->set_message('valid_code','Invalid Sales..!');
            return FALSE;
        }else{ 
            $sales = $this->sales->get_by_orderid($val,'cust');
            if ($sales != $uid){
                $this->form_validation->set_message('valid_code','Invalid Sales Cust..!');
                return FALSE;
            }
        }
    }
    

}

?>