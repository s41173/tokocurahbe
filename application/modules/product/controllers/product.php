<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include_once 'definer.php';
class Product extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('Product_model', 'model', TRUE);

        $this->properti = $this->property->get();

        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        $this->role = new Role_lib();
        $this->category = new Categoryproduct_lib();
        $this->currency = new Currency_lib();
        $this->product = new Product_lib();
        $this->branch = new Branch_lib();
        $this->period = new Period_lib();
        $this->period = $this->period->get();
        $this->droppoint = new Droppoint_lib();
        
        $this->api = new Api_lib();
        $this->whislist = new Whistlist_lib();
        $this->attribute = new Attribute_lib();
        $this->attributeproduct = new Attribute_product_lib();
//        $this->acl = new Acl();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $product, $wt, $branch, $period, $whislist, $droppoint, $attribute, $attributeproduct;
    private $role, $category, $currency, $api;
        
    function search(){
        $datax = (array)json_decode(file_get_contents('php://input')); 
        $name = null;
        if (isset($datax['filter'])){ $name = $datax['filter']; }
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }
        if ($name != null){ 
            $result = $this->model->search_name($name, $this->limitx, $this->offsetx)->result();
            $this->count = $this->model->search_name($name,$this->limitx, $this->offsetx,1);

            foreach($result as $res)
            {
                $period = null;
                if ($res->restricted == 1){
                    $start = explode(':', $res->start); $start = $start[0].':'.$start[1];
                    $end = explode(':', $res->end); $end = $end[0].':'.$end[1];
                    $period = $start.' - '.$end;
                }

                $img = $this->properti['image_url'].'product/'.$res->image;
                $this->resx[] = array ("id"=>$res->id, "category"=>$this->category->get_name($res->category),
                                       "image"=> $img, "sku"=>$res->sku, "name"=>$res->name,
                                       "price"=>floatval($res->price), "restricted" => $res->restricted, "period" => $period,
                                       "rating" => $res->rating, "publish"=>$res->publish,
                                        "wishlist"=> $this->whislist->get_wishlist($res->id)
                                      ); 
            }
            
            $data['record'] = $this->count; 
            $data['result'] = $this->resx;
            $this->output = $data;
        }
       $this->response('content'); 
    }
    
    function wishlist($uid=0){
        if ($this->api->otentikasi() == true && $this->model->valid_add_trans($uid, $this->title) == true){ 
            $decoded = $this->api->get_decoded();
            if ($this->whislist->create($decoded->userid, $uid) != true){
              $this->reject('Whislist Failured');
            }else{ $this->output = "Whislist Success"; }
        }
        else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
        $this->response();
    }
    
    function get_random(){
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        $result = $this->model->get_random($this->limitx, $this->offsetx)->result();
        
        foreach($result as $res)
        {
           $period = null;
           if ($res->restricted == 1){
               $start = explode(':', $res->start); $start = $start[0].':'.$start[1];
               $end = explode(':', $res->end); $end = $end[0].':'.$end[1];
               $period = $start.' - '.$end;
           }
           
           $img = $this->properti['image_url'].'product/'.$res->image;
           $this->resx[] = array ("id"=>$res->id, "category"=>$this->category->get_name($res->category),
                                  "image"=> $img, "sku"=>$res->sku, "name"=>$res->name,
                                  "price"=>floatval($res->price), "restricted" => $res->restricted, "period" => $period,
                                  "rating" => $res->rating, "publish"=>$res->publish,
                                  "wishlist"=> $this->whislist->get_wishlist($res->id),
                                  "droppoint"=> $res->drop_point
                                 );
        }

        $data['result'] = $this->resx;
        $this->output = $data;
        $this->response('content');
    }
    
    function index()
    {
        $datax = (array)json_decode(file_get_contents('php://input')); 
        if (isset($datax['offset'])){ $this->offsetx = $datax['offset']; }

        $cat=null; $limit=100; $recommend=0; $droppoint=null;
        if (isset($datax['category'])){ $cat = $datax['category']; }
        if (isset($datax['recommend'])){ $recommend = $datax['recommend']; }
        if (isset($datax['bestseller'])){ $bestseller = $datax['bestseller']; }
        if (isset($datax['latest'])){ $latest = $datax['latest']; }
        if (isset($datax['economic'])){ $economic = $datax['economic']; }
        if (isset($datax['limit'])){ $this->limitx = $datax['limit']; }else{ $this->limitx = $this->modul['limit']; }
        if (isset($datax['orderby'])){ $this->orderby = $datax['orderby']; }
        if (isset($datax['order'])){ $this->order = $datax['order']; }else{ $this->order = 'desc'; }
        if (isset($datax['droppoint'])){ $droppoint = $datax['droppoint']; }
        
        if($cat == null){ 
            $result = $this->model->get_last($this->limitx, $this->offsetx,$recommend,$bestseller,$latest,$economic, $this->orderby, $this->order, $droppoint, 0)->result();
            $this->count = $this->model->get_last($this->limitx, $this->offsetx,$recommend,$bestseller,$latest,$economic,$this->orderby, $this->order, $droppoint,1);
        }
        else{ 
            $result = $this->model->search($this->category->get_id_based_permalink($cat),$this->limitx, $this->offsetx, $this->orderby, $this->order, $droppoint)->result();
            $this->count = $this->model->search($this->category->get_id_based_permalink($cat),$this->limitx, $this->offsetx, $this->orderby, $this->order, $droppoint, 1);         
        }

        foreach($result as $res)
        {
           $period = null;
           if ($res->restricted == 1){
               $start = explode(':', $res->start); $start = $start[0].':'.$start[1];
               $end = explode(':', $res->end); $end = $end[0].':'.$end[1];
               $period = $start.' - '.$end;
           }
           
           $img = $this->properti['image_url'].'product/'.$res->image;
           $this->resx[] = array ("id"=>$res->id, "category"=>$this->category->get_name($res->category),
                                  "image"=> $img, "sku"=>$res->sku, "name"=>$res->name,
                                  "price"=>floatval($res->price), "restricted" => $res->restricted, "period" => $period,
                                  "rating" => $res->rating, "publish"=>$res->publish,
                                  "wishlist"=> $this->whislist->get_wishlist($res->id),
                                  "droppoint"=> $res->drop_point
                                 );
        }

        $data['record'] = $this->count; 
        $data['result'] = $this->resx;
        $this->output = $data;
        
        $this->response('content');
    } 
        
    function valids_sku($sku=0){
       if ($this->api->otentikasi($this->title) == TRUE && $this->model->cek_trans('sku', $sku) == TRUE){
           $this->output = $this->product->get_detail_based_sku($sku);
       }else{ $this->valid_404($this->model->cek_trans('sku', $sku)); $this->reject_token(); }
       $this->response('c');
    }
    
    
    // fungsi untuk cek product terbatas atau tidak
    function cek_restricted($uid){
       if ($this->model->valid_add_trans($uid, $this->title) == TRUE){
          $product = $this->model->get_by_id($uid)->row();
          if ($product->restricted == 1){
             $start = explode(':', $product->start); $start = $start[0];
             $end = explode(':', $product->end); $end = $end[0];
             if (date('H') >= $start && date('H') < $end){}else{ $this->reject("Item not available"); }
          }
       }else{ $this->valid_404($this->model->valid_add_trans($uid, $this->title)); $this->reject_token(); }
       $this->response();
    }
    
    private function cek_null_img($var,$img){
        if ($var){ return $this->properti['image_url'].'product/'.$var; }
        else{ return $img; }
    }
    
    function get($uid=null,$type=0)
    {        
        if ($type == 0){ $valid = $this->model->valid_add_trans($uid, $this->title); }
        else{ $valid = $this->model->cek_trans('sku', $uid); }
        if ($valid == TRUE){
        
        if ($type == 0){ $product = $this->model->get_by_id($uid)->row(); }
        else{ $product = $this->model->get_by_sku($uid)->row(); }
        
//        $img = $this->properti['image_url'].'product/'.$product->image;
        if ($product->image != null){ $img = $this->properti['image_url'].'product/'.$product->image; }else{ $img = null; }
        
         $period = null;
         if ($product->restricted == 1){
               $start = explode(':', $product->start); $start = $start[0].':'.$start[1];
               $end = explode(':', $product->end); $end = $end[0].':'.$end[1];
               $period = $start.' - '.$end;
         }
        
        $data['id'] = $product->id;
        $data['sku'] = $product->sku;
        $data['category'] = $product->category;
        $data['name'] = $product->name;
        $data['description'] = $product->description;
        $data['shortdesc'] = setnull($product->shortdesc);
        $data['price'] = intval($product->price);
        $data['status'] = intval($product->publish);
        $data['image'] = $img;
        $data['url1'] = $this->cek_null_img($product->url1,$img);
        $data['url2'] = $this->cek_null_img($product->url2,$img);
        $data['url3'] = $this->cek_null_img($product->url3,$img);
        $data['url4'] = $this->cek_null_img($product->url4,$img);
        $data['url5'] = $this->cek_null_img($product->url5,$img);
        $data['url6'] = $this->cek_null_img($product->url6,$img);
        $data['restricted'] = status($product->restricted);
        $data['period'] = $period;
        $data['rating'] = $product->rating;
        $data['weight'] = intval($product->weight);
        $data["wishlist"] = $this->whislist->get_wishlist($product->id);
        $droppoint = explode(',', $product->drop_point);
        $droparray = array();
        $i=0;
        foreach ($droppoint as $res) {
            $droparray[$i]['id'] = $res;
            $droparray[$i]['code'] = $this->droppoint->get_detail($res, 'code');
            $droparray[$i]['name'] = $this->droppoint->get_detail($res, 'name');
            $i++;
        }
        $data['droppoint'] = $droparray;
        
        $attribute = $this->attributeproduct->get_list($product->id)->result();
        $attrvalue = array();
        $i=0;
        foreach ($attribute as $res) {
//            $attrvalue[][0] = $this->attribute->get_name($res->attribute_id);
//            $attrvalue[$this->attribute->get_name($res->attribute_id)][$i] = $this->attributeproduct->get_based_attribute($res->attribute_id, $uid);
            $attrvalue[$this->attribute->get_name($res->attribute_id)] = $this->attributeproduct->get_based_attribute($res->attribute_id, $product->id);
            $i++;
//            print_r($res->attribute_id).'<br>';
        }
        $data['attribute'] = $attrvalue;

        $this->output = $data;
        }else{ $this->valid_404($valid); $this->reject_token(); }
        $this->response('content');
    }
    
    function valid_low_price($lowprice){
        $price = $this->input->post('tprice');
        if ($lowprice > $price){ $this->form_validation->set_message('valid_low_price', "Invalid Low-Price..!"); return FALSE; }
        else{ return TRUE; }
    }
    
    function valid_sku($val)
    {
        if ($this->model->valid('sku',$val) == FALSE)
        {
            $this->form_validation->set_message('valid_sku','SKU registered..!');
            return FALSE;
        }
        else{ return TRUE; }
    }
    
  
}

?>