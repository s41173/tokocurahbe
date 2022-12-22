<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Voucher_discount_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'voucher_discount';
        $this->field = $this->db->list_fields($this->tableName);
        $this->vdiscountdetails = new Voucher_discount_detail_lib();
        $this->customer = new Customer_lib();
    }

    protected $field,$vdiscountdetails,$customer;
    
    function get_detail($id=null,$type=null)
    {
        $this->db->select($this->field);
        $this->db->where('id', $id);
        $val = $this->db->get($this->tableName)->row();
        if ($val){ return ucfirst($val->$type); }
    }

    function cek_voucher($voucher=0){
        $this->db->select($this->field);
        $this->db->where('id', $voucher);
        $query = $this->db->get($this->tableName)->num_rows();
        if($query > 0){ return FALSE; }
        else{ return TRUE; }
    }
    
    function get_voucher_daily($voucher=0){
       $this->db->select($this->field);
       $this->db->where('voucher_id', $voucher); 
       $this->db->where('DATE(created)', date('Y-m-d'));
       $query = $this->db->get($this->tableName)->num_rows();
       return $query;
    }
    
    function get_voucher_total($voucher=0){
       $this->db->select($this->field);
       $this->db->where('voucher_id', $voucher); 
       $query = $this->db->get($this->tableName)->num_rows();
       return $query;
    }
     
    //================== redeem =====================
    function redeem($uid=0,$userid=0,$orderid=0,$outlet=null,$type=0){
        
        $response[0] = false; $response[1] = null;
            $res = $this->get_by_id($uid)->row();

            $date1 = strtotime($res->end); // tanggal due date
            $date2 = strtotime(date('Y-m-d')); // tanggal sekarang
            $climit = $this->cek_limit($uid,$res->limit_type, $res->limit_count); 
            $vctype = $this->cek_voucher_type($userid, $res->target_audience);

            if ($res->status == 0){ $response[1] = 'Status voucher tidak aktif'; }
            elseif ($this->vdiscountdetails->cek_voucher($uid, $userid) == FALSE){ $response[1] ='Voucher telah digunakan'; }
            elseif($date1 < $date2){$response[1]="Masa berlaku voucher sudah habis";}
            elseif ($climit[0] == false){ $response[1]=$climit[1]; }
            elseif ($vctype[0] == false){ $response[1]=$vctype[1]; }
            else{
                // proses redeem - cek stock point
                if ($type != 0){
                     $voucher = array('voucher_id' => $uid, 'pin' => 0, 'orderid' => $orderid,
                                 'customer_id' => $userid, 'drop_point' => $outlet, 
                                 'created' => date('Y-m-d H:i:s'));

                if ($this->vdiscountdetails->add($voucher) != true){$response[1]='failed to post';}
                else{
                    $response[0] = true;
    //                  $lid = $this->vdiscountdetails->get_latest();
    //                  $this->send_confirmation_email($lid->id);
    //                  $this->output = "Voucher berhasil di redeem"; /* kirim notifikasi email + push notif */ 
                  }
                }else{ $response[0] = true; }
            }
        
       return $response;
    }
    
     private function cek_limit($voucher=0,$type=0,$limit=0){
        $stts[0] = true; $stts[1] = null;
        if ($type == 0){
            if ($this->vdiscountdetails->get_voucher_daily($voucher) >= $limit){ $stts[0] = false; $stts[1] = 'Limit voucher harian telah habis'; }
        }elseif ($type == 1){
            if ($this->vdiscountdetails->get_voucher_total($voucher) >= $limit){ $stts[0] = false; $stts[1] = 'Limit voucher telah habis'; }
        }
        return $stts;
    }
    
    private function cek_voucher_type($cust,$type){
        $stts[0] = true; $stts[1] = null;
        $customer = $this->customer->get_by_id($cust)->row();
        if ($type == 0){
            if ($customer->voucher_claimed != 0){ $stts[0] = false; $stts[1] = 'Voucher hanya berlaku untuk pelanggan baru'; }
        }elseif($type == 1){
            if ($customer->voucher_claimed == 0){ $stts[0] = false; $stts[1] = 'Voucher hanya berlaku untuk pelanggan existing'; }
        }
        return $stts;
    }
    //================== redeem =====================

}

/* End of file Property.php */