<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Devent_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'event_details';
        $this->city = new City_lib();
        $this->member = new Member_lib();
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    private $city,$member;
    protected $field;
     
    function cek_active($uid=0,$event=0){
        if ($this->member->cek_trans('id',$uid) != FALSE){
          
          $this->db->select($this->field);
          $this->db->where('tenant_id', $uid);
          $this->db->where('event_id', $event);
          $val = $this->db->get($this->tableName)->row();
          if ($val->status == 1){ return TRUE; }else{ return FALSE; }
        }else{ return FALSE; }
    }

}

/* End of file Property.php */