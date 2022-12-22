<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Droppoint_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('droppoint');
        $this->tableName = 'drop_point';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
    protected $com;
    
    function get_last($limit, $offset=null, $count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('publish', 1);
        $this->db->order_by('name', 'asc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function get_default()
    {
        $this->db->where('defaults', 1);
        return $this->db->get($this->tableName);
    }
    
    function closing_defaults()
    {
        $this->db->where('defaults', 0);
        $this->db->delete($this->tableName);
    }

}

?>