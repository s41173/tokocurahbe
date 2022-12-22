<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Controls_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {   
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->tableName = $this->com->get_table($this->com->get_id('controls'));
        $this->com = $this->com->get_id('controls');
        $this->field = $this->db->list_fields($this->tableName);
    }

    function get_last($limit, $offset=null, $count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->order_by('no', 'asc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function counter()
    {
        $this->db->select_max('no');
        $test = $this->db->get($this->tableName)->row_array();
        $userid=$test['no'];
	$userid = intval($userid+1);
	return $userid;
    }

}

?>