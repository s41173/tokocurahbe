<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class City_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('city');
        $this->tableName = 'kabupaten';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
    protected $com;
    
    function get_last()
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->order_by('nama', 'asc'); 
        return $this->db->get();
    }
    
    function get_last_city_rj()
    {
        $field = $this->db->list_fields('kabupaten_rj');
        $this->db->select($field);
        $this->db->from('kabupaten_rj'); 
        $this->db->order_by('nama', 'asc'); 
        return $this->db->get();
    }
    
    function get_last_province_rj()
    {
        $field = $this->db->list_fields('provinsi_rj');
        $this->db->select($field);
        $this->db->from('provinsi_rj'); 
        $this->db->order_by('nama', 'asc'); 
        return $this->db->get();
    }
    
    function truncate($table=null){
      return $this->db->truncate($table);
    }
    
    function insert_data($table=null,$data){
      return $this->db->insert($table, $data);
    }

}

?>