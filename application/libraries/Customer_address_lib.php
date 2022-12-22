<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Customer_address_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'customer_address';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
       
    function get_details($cust)
    {
       $this->db->where('cust', $cust);
       $this->db->order_by('defaults', 'asc');
       return $this->db->get($this->tableName)->result(); 
    }
    
    function get_defaults($cust)
    {
       $this->db->where('cust', $cust);
       $this->db->where('defaults', 1);
       $this->db->order_by('defaults', 'asc');
       return $this->db->get($this->tableName)->result(); 
    }
    
    function combo($cust=0)
    {
        $this->db->select($this->field);
        $this->db->where('deleted', NULL);
        $this->db->where('cust', $cust);
        $this->db->order_by('id', 'asc');
        $val = $this->db->get($this->tableName)->result();
        if ($val){
            foreach($val as $row){ $data['options'][$row->coordinate] = ucfirst($row->address); }
        }else{ $data['options'][''] = '--'; }
        return $data;
    }
    
    function valid_address($name=null,$cust=0){
        $this->db->select($this->field);
        $this->db->where('name', $name);
        $this->db->where('cust', $cust);
        $val = $this->db->get($this->tableName)->num_rows();
        if ($val>0){ return FALSE;}else{ return TRUE; }
    }
    
     function valid_coordinate($name=null,$cust=0){
        $this->db->select($this->field);
        $this->db->where('coordinate', $name);
        $this->db->where('cust', $cust);
        $val = $this->db->get($this->tableName)->num_rows();
        if ($val>0){ return FALSE;}else{ return TRUE; }
    }
    
    function remove($uid=0){
        $this->db->where('id', intval($uid));
        return $this->db->delete($this->tableName);
    }
    
    function valid_count($uid){
       $this->db->where('cust', $uid);
       $val = $this->db->get($this->tableName)->num_rows();
       if ($val >= 3){ return FALSE;}else{ return TRUE; }
    }
    
    function cek_defaults($custid=0){
      $this->db->where('cust', $custid);
      $this->db->where('defaults', 1);
      $val = $this->db->get($this->tableName)->num_rows();
      if ($val > 0){ return FALSE;}else{ return TRUE; }
    }
    
    function reset_defaults($custid){
        $data = array('defaults' => 0); 
        $this->db->where('cust', $custid);
        return $this->db->update($this->tableName, $data);
    }
    
    function set_defaults($uid=0){
        $data = array('defaults' => 1); 
        $this->db->where('id', $uid);
        return $this->db->update($this->tableName, $data);
    }
    


}

/* End of file Property.php */