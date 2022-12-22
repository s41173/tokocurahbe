<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Voucher_discount_detail_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'voucher_discount_details';
        $this->field = $this->db->list_fields($this->tableName);
    }

    protected $field;
    
    function get_detail($id=null,$type=null)
    {
        $this->db->select($this->field);
        $this->db->where('id', $id);
        $val = $this->db->get($this->tableName)->row();
        if ($val){ return ucfirst($val->$type); }
    }

    function cek_voucher($voucher=0,$cust=0){
        $this->db->select($this->field);
        $this->db->where('voucher_id', $voucher);
        $this->db->where('customer_id', $cust);
        $query = $this->db->get($this->tableName)->num_rows();
        if($query > 0){ return FALSE; }
        else{ return TRUE; }
    }
    
    function get_voucher_daily($voucher=0){
       $this->db->select($this->field);
       $this->db->where('voucher_id', $voucher); 
       $this->db->where('DATE(created)', date('Y-m-d'));
       $query = $this->db->get($this->tableName)->num_rows();
       return $query;
    }
    
    function get_voucher_total($voucher=0){
       $this->db->select($this->field);
       $this->db->where('voucher_id', $voucher); 
       $query = $this->db->get($this->tableName)->num_rows();
       return $query;
    }

}

/* End of file Property.php */