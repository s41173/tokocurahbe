<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payment_lib extends Main_model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'payment';
        $this->field = $this->db->list_fields($this->tableName);
    }
    protected $field;
       
    function get_name($id=null)
    {
        if ($id)
        {
            $this->db->select('id,name');
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return ucfirst($val->name); }
        }
        else if($id == 0){ return 'Top'; }
        else { return ''; }
    }
    
    function get_type($id=null)
    {
        if ($id)
        {
            $this->db->select('type');
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->type; }else { return 0; }
        }
        else { return 0; }
    }
    
    function cek_cash($id=null)
    {
        if ($id)
        {
            $this->db->select('cash');
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val->cash == 1){ return true; }else { return false; }
        }
        else { return false; }
    }
    
    function combo()
    {
        $this->db->select('id,name');
        $this->db->where('deleted', NULL);
        $this->db->order_by('name', 'asc');
        $val = $this->db->get($this->tableName)->result();
        foreach($val as $row){ $data['options'][$row->id] = ucfirst($row->name); }
        return $data;
    }
    
    function combo_pos()
    {
        $this->db->select('id,name');
        $this->db->where('deleted', NULL);
        $this->db->where('pos', 1);
        $this->db->order_by('defaults', 'desc');
        $val = $this->db->get($this->tableName)->result();
        foreach($val as $row){ $data['options'][$row->id] = strtoupper($row->name); }
        return $data;
    }
    
    function calculate($id,$amount=0){
        $val = $this->get_by_id($id)->row();
        $cost = floatval($val->cost/100);
        $res = intval($cost*$amount+$val->add_cost);    
//        if ($val->cost_type == 0){
//          $cost = floatval($val->cost/100);
//          $res = floatval($cost*$amount+$val->add_cost);    
//        }else{ $res = floatval($val->cost+$val->add_cost); }
        return $res;
    }


}

/* End of file Property.php */