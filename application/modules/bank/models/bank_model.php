<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Bank_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('bank');
        $this->tableName = 'bank';
    }
    
    protected $field = array('id', 'acc_name', 'acc_no', 'acc_bank', 'currency', 'created', 'updated', 'deleted');
    protected $com;
                
    function get_last($limit, $offset=null,$count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->order_by('acc_bank', 'asc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }

}

?>