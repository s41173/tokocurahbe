<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Procomment_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('procomment');
        $this->tableName = 'product_comment';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field,$searchfield;
    protected $com;
    
    function get_last($pid=null, $cust=null, $limit, $offset=null, $count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('publish', 1);
        $this->cek_null($pid, 'product_id');
        $this->cek_null($cust, 'cust_id');
        $this->db->order_by('id', 'desc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function valid_comment($cust=0,$pid=0,$sales=0){
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('cust_id', $cust);
        $this->db->where('product_id', $pid);
        $this->db->where('sales_id', $sales);
        $val = $this->db->get()->num_rows();
        if ($val > 0){ return FALSE; }else{ return TRUE; }
    }

}

?>