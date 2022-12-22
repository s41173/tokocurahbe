<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Courier_login_lib extends Custom_Model
{
    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'courier_login_status';
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('courier');
        $this->field = $this->db->list_fields($this->tableName);
    }

     protected $field;
    
    public function add($user=0, $log=0, $device=null)
    {
        $trans = array('userid' => $user, 'log' => $log, 'device' => $device, 'joined' => date('Y-m-d H:i:s'));
        if ($this->cek($user) == TRUE){ return $this->db->insert($this->tableName, $trans); }
        else { $this->edit($user,$log,$device); }
    }
    
    function set_coordinate($user=0, $coordinate=null)
    {   
        $trans = array('coordinate' => $coordinate);
        $this->db->where('userid', $user);
        return $this->db->update($this->tableName, $trans);
    }
    
    function set_otp($user=0, $otp=0)
    {
        if ($this->get_reqcount($user, date('Y-m-d')) == 0){
             $reqcount = 1;
        }else{  $reqcount = intval($this->get_by_userid($user,'req_count')+1); }
        
        $trans = array('log' => $otp, 'req_count'=> $reqcount, 'req_created'=> date('Y-m-d H:i:s'));
        $this->db->where('userid', $user);
        return $this->db->update($this->tableName, $trans);
    }
    
    function get_reqcount($user,$date){
        $this->db->where('userid', $user);
        $val = $this->db->where('DATE(req_created)', $date)->get($this->tableName)->row();
        if ($val){ return intval($val->req_count);}else{ return intval(0); }
    }
    
    function logout($user){
        $trans = array('log' => null, 'device' => null, 'joined' => null);
        $this->db->where('userid', $user);
        $this->db->update($this->tableName, $trans);
    }

    private function cek($user)
    {
        $this->db->where('userid', $user);
        $num = $this->db->get($this->tableName)->num_rows();
        if ($num > 0){ return FALSE; }else { return TRUE; }
    }
    
    private function edit($user,$log,$device=null)
    {
        $trans = array('log' => $log, 'device' => $device, 'joined' => date('Y-m-d H:i:s'));
        $this->db->where('userid', $user);
        return $this->db->update($this->tableName, $trans);
    }
    
    function valid($user,$log)
    {
       $this->db->where('userid', $user);
       $this->db->where('log', $log);
       $num = $this->db->get($this->tableName)->num_rows(); 
       if ($num > 0){ return TRUE; }else { return FALSE; }
    }
    
    function get_by_userid($user,$type='log')
    {
       $this->db->where('userid', $user);
       $res = $this->db->get($this->tableName)->row(); 
       return $res->$type;
    }
    
    function get_device($user){
       
       $this->db->where('userid', $user);
       $res = $this->db->get($this->tableName)->row(); 
       if ($res){ return $res->device; }else{ return null; }
    }
     
}


/* End of file Property.php */