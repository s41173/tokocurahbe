<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Adminmenu_lib extends Main_model {

    public function __construct($deleted=NULL)
    { 
        $this->deleted = $deleted;
        $this->role = new Role_lib();
        $this->api = new Api_lib();
        $this->decodedxx = $this->api->otentikasi('decoded');
//        $this->get_granted();
    }    
    
    protected $role,$granted,$api,$decodedxx;

    function combo()
    {
        $this->db->select('id, name');
        $this->db->where('deleted', $this->deleted);
        $val = $this->db->get($this->table)->result();
        foreach($val as $row){$data['options'][$row->id] = $row->name;}
        return $data;
    }
    
    function combo_parent()
    {
        $this->db->select('id, name');
        $this->db->where('deleted', $this->deleted);
        $this->db->where('parent_status', 1);
        $this->db->order_by('name', 'asc');
        $val = $this->db->get($this->table)->result();

        if ($val){
          foreach($val as $row){$data['options'][$row->id] = $row->name;}
        }
        else { $data['options'][''] = '--'; }
        foreach($val as $row){$data['options'][$row->id] = $row->name;}
        return $data;
    }
    

    public function getmenuname($val)
    {
        if ($val)
        {
           $this->db->select($this->field); 
           $this->db->where('id', $val);
           $res = $this->db->get($this->table)->row();

           if ($res) {  return $res->name; } else{ return null; }
        }
    }
    
    private function get_granted()
    {
      $this->granted = explode(',', $this->role->get_granted_menu($this->decodedxx->role));
    }
    
    public function get_parent_menu()
    {
       $this->get_granted();
        
       $this->db->select($this->field); 
       $this->db->where('parent_status', 1);
       $this->db->where('deleted', $this->deleted);
       $this->db->where_in('id', $this->granted);
       $this->db->order_by('menu_order', 'asc');
       return $this->db->get($this->table)->result();
    }
    
    public function get_child_menu($parent=null)
    {
       $this->db->select($this->field); 
       $this->db->where('parent_status', 0);
       $this->db->where('deleted', $this->deleted);
//       $this->db->where('parent_id', $parent);
       $this->cek_nol($parent, 'parent_id');
       $this->db->order_by('menu_order', 'asc');
       return $this->db->get($this->table)->result();
    }
    
    public function has_child($parent=0)
    {
       $this->db->select($this->field); 
       $this->db->where('parent_status', 0);
       $this->db->where('deleted', $this->deleted);
       $this->db->where('parent_id', $parent);
       if ( $this->db->get($this->table)->num_rows() > 0){ return TRUE; }else{ return FALSE; }
    }

}

/* End of file Property.php */