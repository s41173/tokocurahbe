<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Event_detail_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('event');
        $this->tableName = 'event_details';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
    protected $com;
    
    function get_last($member,$limit, $offset=null, $count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('tenant_id', $member);
        $this->db->order_by('joined', 'asc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function get_by_member($member=0){
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('tenant_id', $member);
        $this->db->order_by('joined', 'desc'); 
        return $this->db->get();
    }
    
    function counter_model($type=0)
    {
       $this->db->select_max('id');
       $query = $this->db->get($this->tableName)->row_array(); 
       if ($type == 0){ return intval($query['id']+1); }else { return intval($query['id']); }
    }
    
    function valid_trans($transid=0){
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('transid', $transid);
        $val = $this->db->get()->row();
        if ($val->status == 0){ return TRUE; }else{ return FALSE; }
    }
    
    function update_bytrans($transid, $users)
    {
        $val = array('updated' => date('Y-m-d H:i:s'));
        $this->db->where('transid', $transid);
        $this->db->update($this->tableName, $val);
        
        $this->db->where('transid', $transid);
        return $this->db->update($this->tableName, $users);
    }

}

?>