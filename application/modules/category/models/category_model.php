<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Category_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('category');
        $this->tableName = 'category';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
    protected $com;
    
    function get_last_category($publish,$front,$limit, $offset=null, $count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('publish', $publish);
        $this->db->where('front', $front);
        $this->db->where('parent_id', 0);
        $this->db->order_by('orders', 'asc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function get_child_category($parent=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('publish', 1);
        $this->db->where('parent_id', $parent);
        $this->db->order_by('orders', 'asc'); 
        return $this->db->get(); 
    }

}

?>