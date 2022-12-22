<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Customer_ledger_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'wallet_ledger';
        $this->field = $this->db->list_fields($this->tableName);
        $this->balance = new Customer_balance_lib();
    }

    protected $field, $balance;
    
    private function cek($code, $no, $cust, $date)
    {
       $this->db->where('dates', $date);
       $this->db->where('code', $code);
       $this->db->where('no', $no);
       $this->db->where('customer', $cust);
       $res = $this->db->get($this->tableName)->num_rows();
       if ($res > 0){ return FALSE; }else { return TRUE; }
    }
    
    function add($code, $no, $date, $debit=0, $credit=0, $cust)
    {  
        if ($this->cek($code, $no, $cust, $date) == TRUE)
        {
          $vamount = intval($debit-$credit);
          $trans = array('code' => $code, 'no' => $no, 'dates' => $date,
                         'debit' => intval($debit), 'credit' => intval($credit), 
                         'vamount' => $vamount, 'customer' => $cust, 'created' => date('Y-m-d H:i:s'));
          return $this->db->insert($this->tableName, $trans);
        }
        else { return $this->edit($code, $no, $date, $debit, $credit, $cust); }
    }
    
    private function edit($code, $no, $date, $debit=0, $credit=0, $cust)
    {   
        $id = $this->get_id($code, $no, $cust, $date);
        
        $vamount = intval($debit-$credit);
        $trans = array('code' => $code, 'no' => $no, 'dates' => $date, 'debit' => $debit, 'credit' => $credit, 'vamount' => $vamount, 'customer' => $cust);
        $this->db->where('id', $id);
        return $this->db->update($this->tableName, $trans);
    }
    
    private function get_id($code, $no, $cust, $date)
    {
       $this->db->where('dates', $date);
       $this->db->where('code', $code);
       $this->db->where('no', $no);
       $this->db->where('customer', $cust);
       $res = $this->db->get($this->tableName)->row();
       return $res->id;
    }

//    =================  remove transaction journal =================

    function remove($dates,$codetrans,$no)
    {
        // ============ update transaction ===================
        $this->db->where('dates', $dates);
        $this->db->where('code', $codetrans);
        $this->db->where('no', $no);
        return $this->db->delete($this->tableName);
        // ===================================================
    }
    
    function get_transaction($cust,$month=null,$year=null,$limit,$offset=null,$count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('customer', $cust);
        $this->cek_null($year, 'YEAR(dates)');
        $this->cek_null($month, 'MONTH(dates)');
        $this->db->order_by('id','desc');
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function get_sum_transaction($cust=0)
    {
        $this->db->select_sum('debit');
        $this->db->select_sum('credit');
        $this->db->select_sum('vamount');
        
        $this->db->where('customer', $cust);
        $res = $this->db->get($this->tableName)->row_array();
        return $res;
    }
     
     // closing function
    function get_sum_transaction_balance($acc, $cur, $start,$end,$cust,$type)
    {
        $this->db->select_sum('debit');
        $this->db->select_sum('credit');
        $this->db->select_sum('vamount');
        
        $this->db->where('acc', $acc);
        $this->db->where('currency', $cur);
        $this->db->where('customer', $cust);
        $this->db->where('type', $type);
        $this->db->where("dates BETWEEN '".setnull($start)."' AND '".setnull($end)."'");
        $res = $this->db->get($this->tableName)->row_array();
        return $res;
    }
    
}

/* End of file Property.php */