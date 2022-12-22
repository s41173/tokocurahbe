<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class City extends MX_Controller
{
    function __construct()
    {
        parent::__construct();
        
        $this->load->model('City_model', 'model', TRUE);

        $this->properti = $this->property->get();
        $this->modul = $this->components->get(strtolower(get_class($this)));
        $this->title = strtolower(get_class($this));
        
        $this->api = new Api_lib();
        $this->acl = new Acl();
        $this->district = new District_lib();
        $this->rj = new Rajaongkir_lib();
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token'); 
    }

    private $properti, $modul, $title, $api, $acl, $district,$rj;
    
    function index()
    {   
        $result = $this->model->get_last()->result();

        foreach($result as $res){
            $this->resx[] = array ("value" => $res->id, "label"=>$res->nama); 
        }
        $data['result'] = $this->resx;
        $this->output = $data; 
            
       $this->response('c');
    }
    
    function get_district($kabid=0){
        $result = $this->district->get_district($kabid);

        foreach($result as $res){
            $this->resx[] = array ("value" => $res->id, "label"=>$res->nama); 
        }
        $data['result'] = $this->resx;
        $this->output = $data; 
            
       $this->response('c');
    }
    
    function recon_province(){
        if ($this->model->truncate('provinsi_rj') == true){
           $json = $this->rj->get_location();
           $datax = json_decode($json, true);
    //        print_r($datax['rajaongkir']['results']);
           foreach ($datax['rajaongkir']['results'] as $value) {
//                print_r($value['province_id']);
                $data = array('id' => $value['province_id'],'nama' => $value['province']);
                $this->model->insert_data('provinsi',$data);
           }
        }
    }
    
    function recon_city(){
        if ($this->model->truncate('kabupaten_rj') == true){
           $json = $this->rj->get_location(1);
           $datax = json_decode($json, true);
//            print_r($datax['rajaongkir']['results']);
           foreach ($datax['rajaongkir']['results'] as $value) {
//                print_r($value);
                $data = array('id' => $value['city_id'], 'id_prov' => $value['province_id'],
                              'province' => $value['province'], 'type'=>$value['type'], 
                              'nama' => $value['city_name'], 'zip' => $value['postal_code']);
                $this->model->insert_data('kabupaten',$data);
           }
        }
    }
    
    // RajaOngkir
    
    function get_province_rj()
    {   
        $result = $this->model->get_last_province_rj()->result();
        foreach($result as $res){
            $this->resx[] = $res; 
        }
        $data['result'] = $this->resx;
        $this->output = $data;           
        $this->response('c');
    }
    
    function get_city_rj()
    {   
       $result = $this->model->get_last_city_rj()->result();
       foreach($result as $res){
//         $this->resx[] = $res; 
           $this->resx[] = array ("value" => $res->id, "label"=>$res->nama); 
       }
       $data['result'] = $this->resx;
       $this->output = $data;      
       $this->response('c');
    }
    
    function get_district_rj($cityid=0){
       $json = $this->rj->get_location(2,$cityid);
       $datax = json_decode($json, true); 
       foreach($datax['rajaongkir']['results'] as $res){
//           print_r($res);
//           $this->resx[]=$res; 
          $this->resx[] = array ("value" => $res['subdistrict_id'], "label"=>$res['subdistrict_name']); 
       }
       $data['result'] = $this->resx;
       $this->output = $data;      
       $this->response('c');
    }
    
    function get_district_by_id($uid=0){
       $json = $this->rj->get_location(3,$uid);
       $datax = json_decode($json, true);  
//       print_r($datax['rajaongkir']['results']);
       $this->resx = $datax['rajaongkir']['results'];
       $data['result'] = $this->resx;
       $this->output = $data;      
       $this->response('c');
    }

}

?>