<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Event_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'event';
        $this->city = new City_lib();
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    private $city;
    protected $field;
    
    function get()
    {
        $this->db->select($this->field);
        $this->db->where('deleted', NULL);
        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc');
        return $this->db->get($this->tableName)->result();
    }
    
    function get_based_parent($parent=0)
    {
        $this->db->select($this->field);
        $this->db->where('deleted', NULL);
        $this->db->where('publish',1);
        $this->db->where('parent_id',$parent);
        $this->db->order_by('orders', 'asc');
        return $this->db->get($this->tableName)->result();
    }

    function combo($type=0)
    {
        $this->db->select($this->field);
        $this->db->where('deleted', NULL);
        $this->db->where('status',1);
//        $this->db->where('parent_id >',0);
        $this->db->order_by('name', 'asc');
        $val = $this->db->get($this->tableName)->result();
        if ($type == 0){ $data['options'][0] = 'Top'; }
        foreach($val as $row){ $data['options'][$row->id] = ucfirst($row->name); }
        return $data;
    }

    function combo_all()
    {
        $this->db->select($this->field);
        $this->db->where('deleted', NULL);
//        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc');
        $data['options'][''] = '-- All --';
        $val = $this->db->get($this->tableName)->result();
        foreach($val as $row){ $data['options'][$row->id] = ucfirst($row->name); }
        return $data;
    }

    function combo_update($id)
    {
        $this->db->select($this->field);
        $this->db->where('deleted', NULL);
        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc');
        $this->db->where_not_in('id', $id);
        $val = $this->db->get($this->tableName)->result();
        $data['options'][0] = 'Top';
        foreach($val as $row){ $data['options'][$row->id] = ucfirst($row->name); }
        return $data;
    }

    function get_details($id=null,$type='name')
    {
        if ($id)
        {
            $this->db->select($this->field);
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->$type; }
        }
        else if($id == 0){ return 'Top'; }
        else { return ''; }
    }
    
    function get_city($id=0){
       if ($id)
        {
            $this->db->select($this->field);
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $this->city->get_name($val->kabupaten); }
        }
        else if($id == 0){ return 'Top'; }
        else { return ''; } 
    }
    
    function get_id($id=null)
    {
        if ($id)
        {
            $this->db->select($this->field);
            $this->db->where('name', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->id; }else { return 0; }
        }
        else { return 0; }
    }
    
    function cek_active($uid=0){
        if ($this->cek_trans('id',$uid) != FALSE){
          $val = $this->get_by_id($uid)->row();
          if ($val->status == 1){ return TRUE; }else{ return FALSE; }
        }else{ return FALSE; }
    }

}

/* End of file Property.php */