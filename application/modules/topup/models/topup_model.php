<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Topup_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('topup');
        $this->tableName = 'topup_trans';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
    protected $com;
    
    function get_last($member,$limit, $offset=null, $count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->order_by('id', 'desc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function search($member=null,$status=null,$paid=null,$limit, $offset=null,$count=0)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('customer', $member);
        $this->db->where('deleted', $this->deleted);
        $this->cek_nol($status, 'status');
        if ($paid == '1'){ $this->db->where('paid_date IS NOT NULL');  }
        elseif($paid == '0'){ $this->db->where('paid_date IS NULL'); }
        $this->db->order_by('id', 'desc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function search_sku($sku=null)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
//        $this->db->where('deleted', $this->deleted);
        $this->cek_null_string($sku, 'sku');
        $this->db->order_by('name', 'asc'); 
        return $this->db->get(); 
    }
    
    function search_name($member,$name=null,$limit=null,$offset=null,$count=0)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('member_id', $member);
//        $this->db->like('name', $name);
        $this->db->like('name', $name, 'both'); 
        $this->db->order_by('name', 'asc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function search_list($cat=null,$manufacture=null,$currency=null)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->cek_null($cat, 'category');
        $this->cek_null($manufacture, 'manufacture');
        $this->cek_null($currency, 'currency');
        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc'); 
        return $this->db->get(); 
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
    
    function counters()
    {
        $this->db->select_max('id');
        $test = $this->db->get($this->tableName)->row_array();
        $userid=$test['id'];
	$userid = intval($userid+1);
	return $userid;
    }
    
    function max_id()
    {
        $this->db->select_max('id');
        $test = $this->db->get($this->tableName)->row_array();
        $userid=$test['id'];
	$userid = intval($userid);
	return $userid;
    }
    
    function valid_transid($transid=0){
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->where('transid', $transid);
        $val = $this->db->get()->num_rows(); 
        if ($val > 0){ return true; }else{ return false; }
    }
    
    function get_by_transid($uid=0)
    {
        $this->db->select($this->field);
        $this->db->where('transid', $uid);
        return $this->db->get($this->tableName);
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