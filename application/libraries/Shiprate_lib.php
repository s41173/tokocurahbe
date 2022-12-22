
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Shiprate_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->property = new Property();
        $this->property = $this->property->get();
        $this->deleted = $deleted;
        $this->tableName = 'delivery_rate';
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('delivery');
        $this->api = new Api_lib();
        $this->field = $this->db->list_fields($this->tableName);
    }

    private $api,$property;
    protected $field;
        
    
   function get_rate($period=0,$distance=0,$payment="CASH",$minimum=0)
    {
        $this->db->select($this->field);
        $this->db->from($this->tableName); 
        $this->db->where('period_start <=', $period);
        $this->db->where('period_end >', $period);
        $this->db->where('distance_start <=', $distance);
        $this->db->where('distance_end >=', $distance);
        $this->db->where('payment_type', $payment);
        $this->db->where('minimum <=', $minimum);
        $this->db->where('deleted', $this->deleted);
        $val = $this->db->get()->row(); 
        if ($val){ return intval($val->rate); }else{ return 0; }
    }
    
    
    
}

/* End of file Property.php */
