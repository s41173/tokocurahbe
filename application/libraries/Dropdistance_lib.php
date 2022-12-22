<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dropdistance_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'drop_point_distance';
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
    
    function get_distance($source=null,$dest=null)
    {
        $this->db->select($this->field);
        $this->db->where('source_drop_point', intval($source));
        $this->db->where('dest_drop_point', intval($dest));
        $val = $this->db->get($this->tableName)->row();
        if ($val){ return $val->distance; }
    }
    
     function calculate_distance($param=null){
         $input = explode(',', $param);
         $distance = 0;
         if (count($input) >= 2){
             for($i=0; $i<count($input);$i++) {
                 $desti = $i+1;
                 if ($desti < count($input)){
                    $distance = $distance+intval($this->get_distance($input[$i], $input[$desti]));
                 }else{ break; }
            }
         }
         return $distance;
    }

}

/* End of file Property.php */