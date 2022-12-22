<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Property {

    public function __construct($params=null)
    {
        // Do something with $params
        $this->ci = & get_instance();
    }

    private $table = 'property';
    private $ci;

//    private $id, $name, $address, $phone1, $phone2, $fax, $email, $billing_email, $technical_email, $cc_email,
//            $zip, $city, $account_name, $account_no, $bank, $site_name, $logo, $meta_description, $meta_keyword;


    public function get()
    {
//        $this->db->select('id,name,address,phone1,phone2,email,billing_email,technical_email, cc_email, zip,account_name,account_no,bank,city,site_name,meta_description,meta_keyword');
        $res = $this->ci->db->get($this->table)->row();
        $phours = explode('-', $res->operational_hours);
        if (!$res->url_upload){ $urlupload = './'; }else{ $urlupload = $res->url_upload; }
        if (!$res->image_url){ $imageurl = base_url().'images/'; }else{ $imageurl = $res->image_url; }
        $val = array('name' => $res->name, 'address' => $res->address, 'phone1' => $res->phone1, 'phone2' => $res->phone2, 'fax' => $res->fax,
                     'email' => $res->email, 'email_link' => $res->email_link, 'billing_email' => $res->billing_email, 'technical_email' => $res->technical_email, 'cc_email' => $res->cc_email,
                     'zip' => $res->zip, 'city' => $res->city, 'account' => $res->account_name, 'acc_no' => $res->account_no, 'bank' => $res->bank, 'manager' => $res->manager, 'accounting' => $res->accounting,
                     'sitename' => $res->site_name, 'logo' => $res->logo, 'url_upload'=>$urlupload, 'image_url'=>$imageurl, 'meta_desc' => $res->meta_description, 'meta_key' => $res->meta_keyword,
                     'notif_url'=>$res->notif_url, 'notif_token'=>$res->notif_token, 'pos_url'=>$res->pos_url, 'invoice_url'=>$res->invoice_url,
                     'pg_url'=>$res->pg_url, 'pg_token'=>$res->pg_token, 'ship_url'=>$res->ship_url, 'ship_token'=>$res->ship_token,
                     'courier_integration' => $res->courier_integration, 'start' => $phours[0], 'end' => $phours[1],
                     'distance_limit'=> $res->distance_limit, 'shipping_integration' => $res->shipping_integration, 'shipping_vendor' => strtolower($res->shipping_vendor)
                    );
        return $val;
    }
    
    function combo_email($param=null)
    {
        if ($param){ $data['options'][null] = ' -- ';  }
        $res = $this->ci->db->get($this->table)->row();
        $data['options'][strtolower($res->email)] = ucfirst($res->email);
        $data['options'][strtolower($res->billing_email)] = ucfirst($res->billing_email);
        $data['options'][strtolower($res->technical_email)] = ucfirst($res->technical_email);
        return $data;
    }
    
}

/* End of file Property.php */