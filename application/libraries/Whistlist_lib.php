<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Whistlist_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'wishlist';
        $this->field = $this->db->list_fields($this->tableName);
        $this->api = new Api_lib();
    }
    
    protected $field,$api;
    
    function get_wishlist($pid=0){
        if ($this->api->otentikasi() == TRUE && $this->api->get_decoded() != null){ 
            $decoded = $this->api->get_decoded();
            if ($this->cek($decoded->userid, $pid) == FALSE){ return 1; }else{ return 0; }
        }else{ return 0; }
    }
    
    function get($cust,$limit=0,$offset=null,$count=0){
       $this->db->select($this->field);
       $this->db->where('customer', $cust); 
       $this->db->order_by('id','asc');
       $this->cek_count($count,$limit,$offset);
       if ($count==0){ return $this->db->get($this->tableName); }else{ return $this->db->get($this->tableName)->num_rows(); }
    }
    
    private function cek($cust, $pid)
    {
       $this->db->where('customer', $cust);
       $this->db->where('product_id', $pid);
       $res = $this->db->get($this->tableName)->num_rows();
       if ($res > 0){ return FALSE; }else { return TRUE; }
    }
    
    function create($cust=0, $pid=0)
    {   $this->cleaning();
        if ($this->cek($cust, $pid) == TRUE)
        {
          $trans = array('customer' => intval($cust), 'product_id' => intval($pid),'created' => date('Y-m-d H:i:s'));
          return $this->db->insert($this->tableName, $trans);
        }
        else { return $this->remove($cust, $pid);}
    }
    
    private function cleaning(){
        $this->db->where('customer', 0);
        return $this->db->delete($this->tableName);
    }
    
    private function remove($cust, $pid)
    {   
        $this->db->where('customer', $cust);
        $this->db->where('product_id', $pid);
        return $this->db->delete($this->tableName);
    }
     
}

/* End of file Property.php */