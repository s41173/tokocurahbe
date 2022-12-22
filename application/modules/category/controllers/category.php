<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Category extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Category_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->product = new Product_lib();
        $this->category = new Categoryproduct_lib();
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');  
    }

    private $properti, $modul, $title, $api, $acl;
    private $product,$category;
    
    function index($label='name',$letter=0)
    {
        $datax = (array)json_decode(file_get_contents('php://input')); 
        $publish = 1; $front=1;
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        if (isset($datax['publish'])){ $publish = $datax['publish']; }
        if (isset($datax['front'])){ $front = $datax['front']; }
        
        $result = $this->model->get_last_category($publish,$front,$this->limitx, $this->offsetx)->result();
        $this->count = $this->model->get_last_category($publish,$front,$this->limitx, $this->offsetx,1);

	foreach($result as $res)
	{   
            $img = $this->properti['image_url'].'category/'.$res->image;
            if ($letter == 0){ 
                $this->resx[] = array ("id"=>$res->id, $label=>$res->name, "parent"=>$this->category->get_name($res->parent_id),
                            "image"=>$img, "publish"=>$res->publish, "front"=>$res->front,
                            "permalink"=>$res->permalink, "orders"=>$res->orders, "child"=>$this->get_child($res->id));
            }else{
                $this->resx[] = array ("id"=>$res->id, $label=> strtoupper($res->name), "parent"=>$this->category->get_name($res->parent_id),
                            "image"=>$img, "publish"=>$res->publish, "front"=>$res->front,
                            "permalink"=>$res->permalink, "orders"=>$res->orders, "child"=>$this->get_child($res->id));
            }
	}
        
        $submenu = null;
        foreach ($this->model->get_last_category(1,0,$this->limitx, $this->offsetx)->result() as $res) {
          $submenu[] = array ("id"=>$res->id, $label=>$res->name, "parent"=>$this->category->get_name($res->parent_id),
                              "image"=>$img, "publish"=>$res->publish, "front"=>$res->front,
                              "permalink"=>$res->permalink, "orders"=>$res->orders, "child"=>$this->get_child($res->id));
        }
        
        $data['submenu'] = $this->model->get_last_category(1,0,$this->limitx, $this->offsetx,1);
        $data['submenuresult'] = $submenu;
        $data['record'] = $this->count; 
        $data['result'] = $this->resx; 
        $this->output = $data;
        $this->response('c');
    } 
    
    private function get_child($parent=0,$label='name'){
        $data = null;
        $result = $this->model->get_child_category($parent)->result();
        foreach ($result as $res) {
            $img = $this->properti['image_url'].'category/'.$res->image;
            $data = array ("id"=>$res->id, $label=>$res->name, "parent"=>$this->category->get_name($res->parent_id),
                            "image"=>$img, "publish"=>$res->publish, "front"=>$res->front,
                            "permalink"=>$res->permalink, "orders"=>$res->orders);
        }
        return $data;
    }

    private function cek_relation($id)
    {
        $product = $this->product->cek_relation($id, $this->title);
        if ($product == TRUE) { return TRUE; } else { return FALSE; }
    }
    
    function get($uid=null)
    {        
        if ($this->model->valid_add_trans($uid, $this->title) == TRUE){
          $category = $this->model->get_by_id($uid)->row();
          $this->output = $category;
        }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response('c');
    }

}

?>