<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include_once 'definer.php';
class Slider extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Slider_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        
        $this->api = new Api_lib();
        $this->voucher = new Voucher_discount_lib();
//        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $voucher;
    private $role, $category, $api;
    
    function get_article($permalink=null){
      $article = new Article_lib();
      $this->output = $article->get_by_permalink($permalink);
      $this->response('c');
   }
            
    function splash(){
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        
        $limit=100;
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }
        else{ $this->limitx = $this->modul['limit']; }

        $result = $this->model->get_splash($this->limitx, $this->offsetx,0)->result();
        $this->count = $this->model->get_last($this->limitx, $this->offsetx,1);
        

        foreach($result as $res)
        {
           $img = $this->properti['image_url'].'slider/'.$res->image;
           $this->resx[] = array ("id"=>$res->id, "image"=> $img, "name"=>$res->name, "url"=>$res->url,
                                 );
        }

        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
        
        $this->response('content');
    }
    
    function shipping_gateway()
    {
        $sg = new Shipping_lib();        
        $result = $sg->request(null,null,1);

//        print_r($result[1]); // http code
//        $hasil = json_decode($result[0]);
        print_r($result);
    } 
     
    function payment_gateway()
    {
//        {
//                "external_id": "invoice-{{$timestamp}}",
//                "amount": 1800000,
//                "payer_email": "customer@domain.com",
//                "description": "Invoice Demo #123"
//        }
        $pg = new Payment_gateway_lib();
        $nilai = '{ "external_id":"381160-(14:21:28)", "amount":10000, "payer_email":"sanjaya.kiran@gmail.com", "description":"381160" }';
//        $nilai = '{ "external_id":"invoice-('.waktuindo().')", "amount":300000, "payer_email":"customer@domain.com", "description":"Invoice Demo" }';
//        $result = json_decode($pg->request("invoices",$nilai,1,'POST'));
        //        print_r($result->id.' - '.$result->invoice_url);
        
        $result = $pg->request("invoices",$nilai,1,'POST');

//        print_r($result[1]); // http code
        $hasil = json_decode($result[0]);
        print_r($hasil);
    } 
    
    function index()
    {
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        
        $limit=100;
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }
        else{ $this->limitx = $this->modul['limit']; }


         $result = $this->model->get_last($this->limitx, $this->offsetx,0)->result();
         $this->count = $this->model->get_last($this->limitx, $this->offsetx,1);
       
        foreach($result as $res)
        {   
           if ($res->voucher_id == null || $res->voucher_id == ""){ $url = '/shop'; }else{ $url = '/voucherdetail/'.$this->voucher->get_detail($res->voucher_id, 'code').'/home'; }
           $img = $this->properti['image_url'].'slider/'.$res->image;
           $this->resx[] = array ("id"=>$res->id, "image"=> $img, "name"=>$res->name, "url"=> $url, 
                                  "voucher"=> $this->voucher->get_detail($res->voucher_id, 'code')
                                 );
        }

        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
        
        $this->response('content');
    } 
    
    function get($uid=null)
    {        
        $valid = $this->model->valid_add_trans($uid, $this->title); 
        if ($valid == TRUE){
        
        $slider = $this->model->get_by_id($uid)->row(); 
        
        $img = $this->properti['image_url'].'slider/'.$slider->image;
        $article=null;
        if ($slider->voucher_id != null){
            $article = $this->voucher->get_by_id($slider->voucher_id)->row();
            $article = $article->text;
        }
         
        $data['id'] = $slider->id;
        $data['type'] = $slider->type;
        $data['name'] = $slider->name;
        $data['image'] = $img;
        $data['article'] = $article;
        $data['url'] = $slider->url;
        $data['created'] = $slider->created;
        $this->output = $data;
        }else{ $this->valid_404($valid); $this->reject_token(); }
        $this->response('content');
    }

}

?>