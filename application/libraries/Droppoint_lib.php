<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Droppoint_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'drop_point';
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
    
    function combo()
    {
        $this->db->select($this->field);
        $this->db->where('deleted', NULL);
        $this->db->where('publish', 1);
        $this->db->order_by('code', 'asc');
        $val = $this->db->get($this->tableName)->result();
        $data = null;
        if ($val){
          foreach($val as $row){ $data['options'][$row->id] = strtoupper($row->code.' - '.$row->name); }    
        }else{ $data['options'][''] = '--'; }
        return $data;
    }
    
    function split($val=0){
        $res = explode(',', $val);
        $hasil = array();
        for ($i=0; $i<count($res); $i++){
            $hasil[$i] = $this->get_detail($res[$i], 'code');
        }
        return implode(' , ', $hasil);
    }

}

/* End of file Property.php */