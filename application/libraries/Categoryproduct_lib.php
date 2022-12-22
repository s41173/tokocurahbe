<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Categoryproduct_lib extends Main_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'category';
        $this->field = $this->db->list_fields($this->tableName);
    }
    protected $field;
    
    function get()
    {
        $this->db->select('id, name');
        $this->db->where('deleted', NULL);
        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc');
        return $this->db->get($this->tableName)->result();
    }

    function combo()
    {
        $this->db->select('id, name');
        $this->db->where('deleted', NULL);
        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc');
        $val = $this->db->get($this->tableName)->result();
        $data['options'][0] = 'Top';
        foreach($val as $row){ $data['options'][$row->id] = ucfirst($row->name); }
        return $data;
    }
    
    function combo_code()
    {
        $this->db->select('id, name, code');
        $this->db->where('deleted', NULL);
        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc');
        $val = $this->db->get($this->tableName)->result();
        $data['options'][0] = 'Top';
        foreach($val as $row){ $data['options'][$row->id] = strtoupper($row->code); }
        return $data;
    }

    function combo_all()
    {
        $this->db->select('id, name');
        $this->db->where('deleted', NULL);
        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc');
        $data['options'][''] = '-- All --';
        $val = $this->db->get($this->tableName)->result();
        foreach($val as $row){ $data['options'][$row->id] = ucfirst($row->name); }
        return $data;
    }

    function combo_update($id)
    {
        $this->db->select('id, name');
        $this->db->where('deleted', NULL);
        $this->db->where('publish',1);
        $this->db->order_by('name', 'asc');
        $this->db->where_not_in('id', $id);
        $val = $this->db->get($this->tableName)->result();
        $data['options'][0] = 'Top';
        foreach($val as $row){ $data['options'][$row->id] = ucfirst($row->name); }
        return $data;
    }

    function get_name($id=null)
    {
        if ($id)
        {
            $this->db->select('id,name');
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->name; }
        }
        else if($id == 0){ return 'Top'; }
        else { return ''; }
    }
    
    function get_code($id=null)
    {
        if ($id)
        {
            $this->db->select('code');
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->code; }
        }
        else if($id == 0){ return 'Top'; }
        else { return ''; }
    }
    
    function get_id($id=null)
    {
        if ($id)
        {
            $this->db->select('id,name');
            $this->db->where('name', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->id; }else { return 0; }
        }
        else { return 0; }
    }
    
    function get_id_based_code($id=null)
    {
        if ($id)
        {
            $this->db->select('id,name');
            $this->db->where('code', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->id; }else { return 0; }
        }
        else { return 0; }
    }
    
    function get_id_based_permalink($permalink=null)
    {
        if ($permalink)
        {
            $this->db->select($this->field);
            $this->db->where('permalink', $permalink);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return $val->id; }else { return 0; }
        }
        else { return 0; }
    }

}

/* End of file Property.php */