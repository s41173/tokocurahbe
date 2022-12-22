<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Courier_ledger_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'wallet_courier_ledger';
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('topup');
        $this->field = $this->db->list_fields($this->tableName);
    }
    protected $field;


    private function cek($code, $no, $cust, $date)
    {
       $this->db->where('dates', $date);
       $this->db->where('code', $code);
       $this->db->where('no', $no);
       $this->db->where('courier', $cust);
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
                         'vamount' => $vamount, 'courier' => $cust, 'created' => date('Y-m-d H:i:s'));
          return $this->db->insert($this->tableName, $trans);
        }
        else { $this->edit($code, $no, $date, $debit, $credit, $cust); }
    }
    
    private function edit($code, $no, $date, $debit=0, $credit=0, $cust)
    {   
        $id = $this->get_id($code, $no, $cust, $date);
        
        $vamount = intval($debit-$credit);
        $trans = array('code' => $code, 'no' => $no, 'dates' => $date, 'debit' => $debit, 'credit' => $credit, 'vamount' => $vamount, 'courier' => $cust);
        $this->db->where('id', $id);
        return $this->db->update($this->tableName, $trans);
    }
    
    private function get_id($code, $no, $cust, $date)
    {
       $this->db->where('dates', $date);
       $this->db->where('code', $code);
       $this->db->where('no', $no);
       $this->db->where('courier', $cust);
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
        $this->db->delete($this->tableName);
        // ===================================================
    }
    
    function remove_redeem($codetrans,$no)
    {
        // ============ update transaction ===================
        $this->db->where('code', $codetrans);
        $this->db->where('no', $no);
        $this->db->delete($this->tableName);
        // ===================================================
    }
    
    function get_transaction($cust,$month,$year,$limit=0,$offset=null,$count=0)
    {
        $this->db->select($this->field);
        $this->db->where('courier', $cust);
        $this->db->where('MONTH(dates)', $month);
        $this->db->where('YEAR(dates)', $year);
        $this->db->order_by('id','asc');
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get($this->tableName); }else{ return $this->db->get($this->tableName)->num_rows(); }
    }
    
    function get_sum_transaction($cust,$month,$year)
    {
        $this->db->select_sum('debit');
        $this->db->select_sum('credit');
        $this->db->select_sum('vamount');
        
        $this->db->where('courier', $cust);
        $this->db->where('MONTH(dates)', $month);
        $this->db->where('YEAR(dates)', $year);
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
        $this->db->where('courier', $cust);
        $this->db->where('type', $type);
        $this->db->where("dates BETWEEN '".setnull($start)."' AND '".setnull($end)."'");
        $res = $this->db->get($this->tableName)->row_array();
        return $res;
    }
    
}

/* End of file Property.php */