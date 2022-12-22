
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Shipping_lib extends Custom_Model {

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
        $this->shiprate = new Shiprate_lib();
        
        $this->url = trim($this->property['ship_url']);
        $this->apikey = trim($this->property['ship_token']);
//        $this->url = "https://robotapitest-id.borzodelivery.com/api/business/1.1/";
//        $this->apikey = "7EA1D40CAEABB7186C0D59609F77E47A567A33C5";
        $this->pass = null;
    }

    // using xendit
    private $api,$url,$apikey,$pass,$property,$shiprate;
    protected $field;
        
    function request($urltype=null,$param=null,$type=null,$method='GET')
    {   
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->url.$urltype,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $param,
        CURLOPT_HTTPHEADER => array(
          'Content-Type: application/x-www-form-urlencoded',
          'X-DV-Auth-Token: '.$this->apikey
        ),
      ));

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $err = curl_error($curl);
//        $data = json_decode($response, true); 

        curl_close($curl);
        if (!$type){
            if ($err) { return $err; }else { return $response; }
        }else{
            $result = array();
            $result[0] = $response;
            $result[1] = $info['http_code'];
            return $result;
        } 
    }
    
    // manual driver
    function calculate_rate($distance=0,$amount=0,$payment="CASH"){
        $rate = $this->shiprate->get_rate(date('H'), $distance, $payment, $amount);
        return intval($distance*$rate);
    }
    
    // ===== courier get ongoing =============
    
    function get_ongoing(){
        
        $this->db->where('status', 0);
        $this->db->where('courier', 0);
        $this->db->where('received', null);
        $this->db->where('deleted', $this->deleted);
        $this->db->limit(1);
        return $this->db->get($this->tableName)->row();
    }
    
    function delete_by_sales($uid){
       $this->db->where('sales_id', $uid);
       return $this->db->delete($this->tableName); 
    }
    
    function get_by_sales($sid=0){
        $this->db->select($this->field);
        $this->db->where('sales_id', $sid);
        return $this->db->get($this->tableName);
    }
    
    function update_by_sales($uid, $users)
    {      
        $this->db->where('sales_id', $uid);
        return $this->db->update($this->tableName, $users);
    }
    
    function search($start=null,$end=null,$courier=null, $approved=null,$limit, $offset=null, $count=0)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName);
        $this->cek_null($courier, 'courier');
        if ($end != null){ $this->between('date(dates)', $start, $end); }
        else{ $this->cek_null($start, 'date(dates)'); }
        $this->cek_null($approved, 'status');
       
        $this->db->order_by('id', 'desc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function get_pending_trans($courier){
        $this->db->select($this->field);
        $this->db->where('courier', $courier);
        $this->db->where('status', 0);
        $this->db->where('received IS NULL');
        $val = $this->db->get($this->tableName)->num_rows();
        if ($val > 0){ return FALSE; }else{ return TRUE; }
    }
    
}

/* End of file Property.php */
