<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Role_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->tableName = $this->com->get_table($this->com->get_id('roles'));
        $this->com = $this->com->get_id('roles');
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
    protected $com;
            
   
    function get_last_role($limit, $offset=null)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->order_by('name', 'asc'); 
        $this->db->limit($limit, $offset);
        return $this->db->get(); 
    }

}

?>