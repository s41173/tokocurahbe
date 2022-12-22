<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Product_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('product');
        $this->tableName = 'product';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
    protected $com;
    
    function get_random($limit, $offset=null)
    {
        $this->db->select($this->field);
        $this->db->where('deleted', $this->deleted);
        $this->db->where('publish', 1);
        $this->db->order_by('rand()');
        $this->db->limit($limit, $offset);
        return $this->db->get($this->tableName);
    }
    
    function get_last($limit, $offset=null, $recom=0,$bestseller=0,$latest=0,$economic=0,$orderby=null,$order='desc',$droppoint=null,$count=0)
    {
        $this->db->select($this->field);
        $this->db->where('deleted', $this->deleted);
        $this->db->where('publish', 1);
        $this->cek_nol($recom, 'recommended');
        $this->cek_nol($bestseller, 'best_seller');
        $this->cek_nol($latest, 'latest');
        $this->cek_nol($economic, 'economic');
        $this->cek_orderbyl($orderby,$order);
        if ($droppoint != null || $droppoint != ""){
            $values = array_map('intval', explode(',', $droppoint));
            $this->db->where_in('drop_point',$values); 
        }
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get($this->tableName); }else{ return $this->db->get($this->tableName)->num_rows(); }
    }
    
    function search($cat=null,$limit, $offset=null,$orderby=null,$order='desc',$droppoint=null,$count=0)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName);
        $this->db->where('deleted', $this->deleted);
        $this->cek_null($cat, 'category');
        $this->db->where('publish', 1);
        
//        $this->db->order_by('name', 'asc'); 
        $this->cek_orderbyl($orderby,$order);
        if ($droppoint != null || $droppoint != ""){
            $values = array_map('intval', explode(',', $droppoint));
            $this->db->where_in('drop_point',$values); 
        }
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
    
    function search_name($name=null,$limit=null,$offset=null,$count=0)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
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
    
    function get_by_sku($uid)
    {
        $this->db->select($this->field);
        $this->db->where('sku', $uid);
        return $this->db->get($this->tableName);
    }
    
        
    function closing_trans(){
        $this->db->truncate('stock'); 
        $this->db->truncate('stock_ledger');
        $this->db->truncate('stock_temp');
        $this->db->truncate('warehouse_transaction');
    }
    

}

?>