<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Customer_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'customer';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
       
    function get_name($id=null,$type=null)
    {
        if ($id)
        {
            $this->db->select($this->field);
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ 
                if (!$type){ return ucfirst($val->first_name.' '.$val->last_name);  }
                else{ return $val->$type; }
            }
        }
        else { return ''; }
    }
    
    function get_email($id=null)
    {
        if ($id)
        {
            $this->db->select('email');
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->email; }else { return null; }
        }
        else { return 0; }
    }
    
    function get_cust_type($type=null)
    {
        $this->db->select('email');
        $this->db->where('type', $type);
        $this->db->where('status', 1);
        $this->db->where('deleted', $this->deleted);
        $val = $this->db->get($this->tableName)->result();
        return $val;
    }
    
    function get_details($id)
    {
       $this->db->where('id', $id);
       return $this->db->get($this->tableName); 
    }
    
    function combo()
    {
        $this->db->select('id,first_name,last_name');
        $this->db->where('deleted', NULL);
        $this->db->where('status', 1);
        $this->db->order_by('first_name', 'asc');
        $val = $this->db->get($this->tableName)->result();
        if ($val){
            foreach($val as $row){ $data['options'][$row->id] = ucfirst($row->first_name.' '.$row->last_name); }
        }else{ $data['options'][''] = '--'; }
        return $data;
    }
    
    function valid_cust($uid=0){
        if ($this->cek_trans('id', $uid) == TRUE){
          $val = $this->get_details($uid)->row();
          if ($val->status == 1){ return TRUE; }else{ return FALSE; }
        }else{ return FALSE; }
    }


}

/* End of file Property.php */