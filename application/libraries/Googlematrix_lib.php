
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Googlematrix_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->property = new Property();
        $this->property = $this->property->get();
        $this->deleted = $deleted;
        $this->tableName = 'delivery';
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('delivery');
        $this->api = new Api_lib();
        $this->field = $this->db->list_fields($this->tableName);
        
        $this->url = trim($this->property['ship_url']);
        $this->apikey = trim("AIzaSyBqKPUcKxKOgOfSby8WB1lTsahkRjt0Qek");
//        $this->url = "https://robotapitest-id.borzodelivery.com/api/business/1.1/";
//        $this->apikey = "7EA1D40CAEABB7186C0D59609F77E47A567A33C5";
        $this->pass = null;
    }

    private $api,$url,$apikey,$pass,$property;
    protected $field;
    
//    SetiaBudiSquare,Jl.CactusRayaNo.7,TanjungSari,MedanSelayang,MedanCity,NorthSumatra20132
            
//    Setia Budi Square, Jl. Cactus Raya No.7, Tanjung Sari, Medan Selayang, Medan City, North Sumatra 20132
        
    function get_coordinate($param=null,$type=null)
    {   
       $address = preg_replace('/\s+/', '+', $param);
       $url = "https://maps.google.com/maps/api/geocode/json?address=".$address."&sensor=false&key=".$this->apikey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err = curl_error($ch);
        $data = json_decode($response, true); 
//        return $data['results'][0];
        return $data['results'][0]['geometry']['location']['lat'].','.$data['results'][0]['geometry']['location']['lng'];

//        curl_close($ch);
//        if (!$type){
//            if ($err) { return $err; }else { return $response; }
//        }else{
//            $result = array();
//            $result[0] = $response;
//            $result[1] = $info['http_code'];
//            return $result;
//        } 
    }
    
    function get_address($param=null,$type=null)
    {   
       $url = "https://maps.google.com/maps/api/geocode/json?latlng=".$param."&sensor=false&key=".$this->apikey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err = curl_error($ch);
        $data = json_decode($response, true); 
        return $data['results'][0]['formatted_address']; 
    }
    
    function calculation($origin=null,$dest=null)
    {   
       $origin = preg_replace('/\s+/', '', $origin);
       $dest = preg_replace('/\s+/', '', $dest);
       $url = "https://maps.googleapis.com/maps/api/distancematrix/json?destinations=".$dest."&origins=".$origin."&key=".$this->apikey; 
//       $url = "https://maps.google.com/maps/api/geocode/json?latlng=".$param."&sensor=false&key=".$this->apikey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err = curl_error($ch);
        $data = json_decode($response, true); 
        return floatval($data['rows'][0]['elements'][0]['distance']['value']/1000);
//        return $data['results'][0]['formatted_address']; 
    }
    
}

/* End of file Property.php */
