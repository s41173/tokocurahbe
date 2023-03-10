<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Adminmenu_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('adminmenu');
        $this->tableName = 'admin_menu';
    }
    
    protected $field = array('id', 'parent_id', 'name', 'modul', 'url', 'menu_order', 'class_style', 'id_style', 
                             'icon', 'target', 'parent_status');
    protected $com;
            
    
    function get_last($limit, $offset=null,$count=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('deleted', $this->deleted);
        $this->db->limit($limit, $offset);
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function delete_child($parent)
    {
       $this->db->where('parent_id', $parent);
       $this->db->delete($this->tableName);
    }

}

?>