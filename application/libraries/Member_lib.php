<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Member_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'member';
        $this->field = $this->db->list_fields($this->tableName);
    }
       
    protected $field;
    
    function get($id=null,$type='first_name')
    {
        if ($id)
        {
            $this->db->select($this->field);
            $this->db->where('id', $id);
            $val = $this->db->get($this->tableName)->row();
            if ($val){ return ucfirst($val->$type); }
        }
        else { return ''; }
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


}

/* End of file Property.php */