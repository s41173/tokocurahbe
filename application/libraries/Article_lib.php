<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Article_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'article';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
       
    function get_details($cust)
    {
       $this->db->where('cust', $cust);
       $this->db->order_by('defaults', 'asc');
       return $this->db->get($this->tableName)->result(); 
    } 
    
    function get_by_permalink($permalink=null){
       $this->db->where('permalink', $permalink);
       $this->db->where('publish', 1);
       $res = $this->db->get($this->tableName);
       if ($res->num_rows() > 0){ return $res->row(); }
    }


}

/* End of file Property.php */