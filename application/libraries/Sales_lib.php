<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sales_lib extends Custom_Model {
    
    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'sales';
        $this->field = $this->db->list_fields($this->tableName);
    }

    protected $field;
    
    function cek_relation($id,$type)
    {
       $this->db->where($type, $id);
       $query = $this->db->get('product')->num_rows();
       if ($query > 0) { return FALSE; } else { return TRUE; }
    }
    
    function get_detail_sales($id=null)
    {
        if ($id)
        {
           $this->db->select($this->field);
           $this->db->where('id', $id);
           $res = $this->db->get($this->tableName)->row();
           return $res;
        }
    }
    
    function get_transaction_sales($id=null)
    {
        if ($id)
        {
           $this->db->where('sales_id', $id);
           $res = $this->db->get('sales_item');
           return $res;
        }
    }
    
    function total($pid)
    {
        $this->db->select_sum('tax');
        $this->db->select_sum('amount');
        $this->db->select_sum('price');
        $this->db->select_sum('qty');
        $this->db->select_sum('weight');
        $this->db->where('sales_id', $pid);
        return $this->db->get('sales_item')->row_array();
    }
    
    // pos
    
    function create_pos($orderid,$dates,$payment,$log,$cust,$voucher=null,$pickup=0,$droppoint=0,$cash=0){
        
       $this->db->select($this->field);
       $this->db->where('code', $orderid);
       $num = $this->db->get($this->tableName)->num_rows();
       $res = 0;
       if ($num > 0){
           $res = $this->get_by_orderid($orderid,'id');
       }else{ $res = $this->create_pos_sales($orderid,$dates,$payment,$log,$cust,$voucher,$pickup,$droppoint,$cash); }
       return $res;
    }
    
    function get_by_orderid($code=null,$type=null)
    {
        $this->db->select($this->field);
        $this->db->where('code', $code);
        $res = $this->db->get($this->tableName)->row();
        if ($res){ if ($type){ return $res->$type; }else{ return $res; }}
    }
    
    function valid_orderid($code=0){
        if ($this->valid('code', $code) == FALSE){
            $val = $this->get_by_orderid($code);
            if ($val->approved == 1){ return FALSE; }else{ return TRUE; }
        }else{ return TRUE; }
    }
    
    
    private function create_pos_sales($orderid,$dates,$payment,$log,$cust,$voucher,$pickup,$droppoint,$cash){
//        if ($payment == 5){ $cash = 1; }else{ $cash = 0; }
        $sales = array('code' => $orderid ,'cust' => $cust, 'dates' => $dates, 'voucher' => $voucher, 'pickup' => $pickup,
                       'log'=>$log, 'payment_type' => $payment, 'drop_point' => $droppoint, 'cash' => $cash, 'created' => date('Y-m-d H:i:s'));
        $this->db->insert($this->tableName, $sales);
        
        return $this->get_by_orderid($orderid, 'id');
    }

}

/* End of file Property.php */