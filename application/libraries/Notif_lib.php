<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Notif_lib extends Custom_Model {

    public function __construct($deleted=NULL)
    {
        $this->deleted = $deleted;
        $this->tableName = 'notif';
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('notif');
        $this->api = new Api_lib();
        $this->customer = new Customer_lib();
        $this->customerlogin = new Customer_login_lib();
        $this->courier = new Courier_lib();
        $this->courierlogin = new Courier_login_lib();
    }

    private $api,$customer,$customerlogin, $courier, $courierlogin;
    protected $field = array('id', 'customer', 'subject', 'content', 'type', 'reading', 'modul', 'status', 'publish', 'created', 'deleted');
    
    /*
        0 = email
        1= sms
        2 = email + sms
        3 = notif socket
        4 = socket + SMS
        5 = email + notif socket
        6 = email + sms+ notif socket
    */
    
    function get_type($val=0){
        
        $res = null;
        switch ($val) {
            case 0: $res = 'Email'; break;
            case 1: $res = 'SMS'; break;
            case 2: $res = 'Email + SMS'; break;
            case 3: $res = 'Socket'; break;
            case 4: $res = 'Socket + SMS'; break;
            case 5: $res = 'Email + Socket'; break;
            case 6: $res = 'Email + SMS + Socket'; break;
        }
        return $res;
    }
    
    private function post_notif($type,$sentto,$cust,$custname,$subject,$content,$modul){
        $postData = array(
              'type' => $type, 'customer' => $cust,'custname' => $custname, 'subject' => $subject,
              'sentto' => $sentto, 'content' => $content,'modul' => $modul
            );
        $postString = http_build_query($postData, '', '&');
        $req = $this->api->request_notif('notif/add_notif',$postString,'POST'); 
//        $res = json_decode($req[0]);        
        if ($req[1] == 200){return true;}else{ return false; }
    }
    
   // custtype 0 = member / 1 = member
    function send_notif($type=0,$custid=0,$subject="",$content="",$modul="none",$target=0){
       
        if ($target == 0){
           $customer = $this->customer->get_by_id($custid)->row(); 
           $device = $this->customerlogin->get_device($custid); 
           $custname = $customer->first_name.' - '.$customer->last_name;
           $email = $customer->email;
           $phone = $customer->phone1;
        }else{
           $customer = $this->courier->get_by_id($custid)->row(); 
           $device = $this->courierlogin->get_device($custid); 
           $custname = $customer->name;
           $email = $customer->email;
           $phone = $customer->phone;
        }
        
        $res = false; 
        if ($type == 0){
            $res = $this->post_notif(0,$email,$custid,$custname,$subject,$content,$modul);
        }elseif ($type == 1){
            $res = $this->post_notif(1,$phone,$custid,$custname,$subject,$content,$modul);
        }elseif ($type == 2){
          $res1 = $this->post_notif(0,$email,$custid,$custname,$subject,$content,$modul);
          $res2 = $this->post_notif(1,$phone,$custid,$custname,$subject,$content,$modul);
          if ($res1 == true && $res2 == true){ $res = true; }
        }elseif ($type == 3){
          $res = $this->post_notif(3,$device,$custid,$custname,$subject,$content,$modul);
        }elseif ($type == 4){
          $res = $this->post_notif(3,$device,$custid,$custname,$subject,$content,$modul);
          $res1 = $this->post_notif(1,$phone,$cust,$custname,$subject,$content,$modul);
          if ($res == true && $res1 == true){ $res = true; }
          
        }elseif ($type == 5){
          $res = $this->post_notif(3,$device,$custid,$custname,$subject,$content,$modul);
          $res1 = $this->post_notif(0,$email,$custid,$custname,$subject,$content,$modul);
          if ($res == true && $res1 == true){ $res = true; }
          
        }elseif ($type == 6){
          $res1 = $this->post_notif(0,$email,$custid,$custname,$subject,$content,$modul);
          $res2 = $this->post_notif(1,$phone,$custid,$custname,$subject,$content,$modul);
          $res3 = $this->post_notif(3,$device,$custid,$custname,$subject,$content,$modul);
          if ($res1 == true && $res2 == true && $res3 == true){ $res = true; }
        }
        return $res;
    }

}

/* End of file Property.php */
