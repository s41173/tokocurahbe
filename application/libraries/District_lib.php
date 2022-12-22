<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class District_lib extends Main_model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'kecamatan';
    }

    private $ci;
    
    function get_district($kabid=null){
      if ($kabid != null)
      {
         $this->db->where('id_kabupaten', $kabid);
         return $this->db->get($this->tableName)->result();
      }
    }
    
    function combo_district_db($cityid=null)
    {
        $data = null;
        if ($cityid != null)
        {
            $this->db->where('id_kabupaten', $cityid);
            $val = $this->db->get($this->tableName)->result();
        }
        else {$val = $this->db->get($this->tableName)->result(); }
//        
        foreach($val as $row){$data['options'][$row->id] = $row->nama;}
        return $data;
    }
    
    private function splits($val)
    {
      $res = explode(".",$val); 
      return $res[0];
    }
   

}

/* End of file Property.php */