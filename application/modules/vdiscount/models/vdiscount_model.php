<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Vdiscount_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('vdiscount');
        $this->tableName = 'voucher_discount';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field,$searchfield;
    protected $com;
    
    function get_last($limit, $offset=null, $count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('status', 1);
        $this->db->order_by('id', 'desc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function get_by_code($code)
    {
        $this->db->select($this->field);
        $this->db->where('code', $code);
        return $this->db->get($this->tableName);
    }
    
    
    function report($cat=null,$manufacture=null)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->cek_null($cat, 'category');
        $this->cek_null($manufacture, 'manufacture');
        
        $this->db->order_by('name', 'asc'); 
        return $this->db->get(); 
    }


}

?>