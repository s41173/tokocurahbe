<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Customer_login_lib
{
    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'customer_login_status';
        $this->ci = & get_instance();
    }

    private $ci,$tableName,$deleted;
    
    public function add($user=0, $log=0, $device=null)
    {
        $trans = array('userid' => $user, 'log' => $log, 'device' => $device, 'joined' => date('Y-m-d H:i:s'));
        if ($this->cek($user) == TRUE){ return $this->ci->db->insert($this->tableName, $trans); }
        else { $this->edit($user,$log,$device); }
    }
    
    function set_otp($user=0, $otp=0)
    {
        if ($this->get_reqcount($user, date('Y-m-d')) == 0){
             $reqcount = 1;
        }else{  $reqcount = intval($this->get_by_userid($user,'req_count')+1); }
        
        $trans = array('log' => $otp, 'req_count'=> $reqcount, 'req_created'=> date('Y-m-d H:i:s'));
        $this->ci->db->where('userid', $user);
        return $this->ci->db->update($this->tableName, $trans);
    }
    
    function get_reqcount($user,$date){
        $this->ci->db->where('userid', $user);
        $val = $this->ci->db->where('DATE(req_created)', $date)->get($this->tableName)->row();
        if ($val){ return intval($val->req_count);}else{ return intval(0); }
    }
    
    function logout($user){
        $trans = array('log' => null, 'device' => null, 'joined' => null);
        $this->ci->db->where('userid', $user);
        $this->ci->db->update($this->tableName, $trans);
    }

    private function cek($user)
    {
        $this->ci->db->where('userid', $user);
        $num = $this->ci->db->get($this->tableName)->num_rows();
        if ($num > 0){ return FALSE; }else { return TRUE; }
    }
    
    private function edit($user,$log,$device=null)
    {
        $trans = array('log' => $log, 'device' => $device, 'joined' => date('Y-m-d H:i:s'));
        $this->ci->db->where('userid', $user);
        return $this->ci->db->update($this->tableName, $trans);
    }
    
    function valid($user,$log)
    {
       $this->ci->db->where('userid', $user);
       $this->ci->db->where('log', $log);
       $num = $this->ci->db->get($this->tableName)->num_rows(); 
       if ($num > 0){ return TRUE; }else { return FALSE; }
    }
    
    function get_by_userid($user,$type='log')
    {
       $this->ci->db->where('userid', $user);
       $res = $this->ci->db->get($this->tableName)->row(); 
       return $res->$type;
    }
    
    function get_device($user){
       
       $this->ci->db->where('userid', $user);
       $res = $this->ci->db->get($this->tableName)->row(); 
       if ($res){ return $res->device; }else{ return null; }
    }
     
}


/* End of file Property.php */