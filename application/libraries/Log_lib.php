<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Log_lib extends Main_model {

    public function __construct($deleted=NULL)
    {
        // Do something with $params
        $this->deleted = $deleted;
        $this->tableName = 'log';
    }

    public function max_log()
    {
        $this->db->select_max('id');
        $val = $this->db->get($this->tableName)->row_array();
        $val = $val['id'];
        return intval($val);
    }

    public function insert($userid=null, $date=null, $time=null, $activity=null, $com=0, $field=null, $desc=null, $prev=null)
    {
        $logs = array('userid' => $userid, 'date' => $date, 'time' => $time, 'activity' => $activity, 'component_id' => $com,
                      'field' => $field, 'description' => $desc, 'prev_val' => $prev);
        $this->db->insert($this->tableName, $logs);
    }
    
    public function insert_cust($userid=null, $date=null, $time=null, $activity=null, $com=0, $field=null, $desc=null, $prev=null)
    {
        $logs = array('type' => 'customer', 'userid' => $userid, 'date' => $date, 'time' => $time, 'activity' => $activity, 'component_id' => $com,
                      'field' => $field, 'description' => $desc, 'prev_val' => $prev);
        $this->db->insert($this->tableName, $logs);
    }
    
    function get_user($uid=0){
        $this->db->where('id', $uid);
        $res = $this->db->get($this->tableName)->row();
        if ($res){ return $res->userid; }
    }
}

/* End of file Property.php */