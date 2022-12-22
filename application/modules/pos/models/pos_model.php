<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Pos_model extends Custom_Model
{
    protected $logs;
    
    function __construct()
    {
        parent::__construct();
        $this->logs = new Log_lib();
        $this->com = new Components();
        $this->com = $this->com->get_id('pos');
        $this->tableName = 'sales';
        $this->field = $this->db->list_fields($this->tableName);
    }
    
    protected $field;
    protected $com;
    
    
    function outdated_sales(){
       $date = new DateTime("now");
       $curr_date = $date->format('Y-m-d ');
       
       $this->db->select($this->field);
       $this->db->from($this->tableName);
       $this->db->where('approved', 1);
       $this->db->where('cash', 0);
       $this->db->where('booked', 0);
       $this->db->where('redeem', 0);
       $this->db->where('canceled iS NULL');
       $this->db->where('DATE(dates) <> ',$curr_date);//use date function
//       $this->db->where('DATE(dates)',$curr_date);//use date function
       return $this->db->get();
    }
    
    // functin untuk mendapatkan shipping received == null
    // approve 1 - pickup 0 - shipid != null - shipreceived == null
    function get_unreceived_shipping(){
        $this->db->select($this->field);
        $this->db->from($this->tableName);
        $this->db->where('approved', 1);
        $this->db->where('pickup', 0);
        $this->db->where('shipid IS NOT NULL');
        $this->db->where('shipreceived IS NULL');
        $this->db->where('booked', 1);
        $this->db->where('redeem', 1);
        $this->db->where('canceled iS NULL');
        $this->db->where('shipstatus iS NOT NULL');
        $this->db->order_by('id', 'asc'); 
        return $this->db->get();
    }
    
    function search($start=null,$end=null,$user=null, $approved=null, $cancel=null, $redeem=null,$booked=null, $pickup=null, $limit, $offset=null, $count=0)
    {   
        $this->db->select($this->field);
        $this->db->from('sales');
        $this->cek_null($user, 'cust');
        if ($end != null){ $this->between('date(dates)', $start, $end); }
        else{ $this->cek_null($start, 'date(dates)'); }
        $this->cek_nol($approved, 'approved');
        $this->cek_nol($redeem, 'redeem');
        $this->cek_nol($booked, 'booked');
        
        if ($pickup == '1'){ $this->db->where('cust_received IS NOT NULL'); }
        elseif ($cancel == '0'){ $this->db->where('cust_received IS NULL'); }
        
        if ($cancel == '1'){ $this->db->where('canceled IS NOT NULL'); }
        elseif ($cancel == '0'){ $this->db->where('canceled IS NULL'); }
       
        $this->db->order_by('id', 'desc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function filtering($type=0,$period=0,$limit, $offset=null, $count=0)
    {   
        $this->db->select($this->field);
        $this->db->from('sales');
        
        if ($period == 0){
          $this->db->where('DATE(dates) > current_date - interval 30 day');
        }elseif ($period == 1){
          $this->db->where('DATE(dates) > current_date - interval 180 day');
        }elseif ($period == 2){
          $this->db->where('YEAR(sales.dates)', date('Y'));
        }elseif ($period == 3){
          $this->db->where('YEAR(sales.dates)', intval(date('Y')-1));
        }
        
        if ($type == 0){
            $this->db->where('approved',1);
            $this->db->where('redeem',1);
            $this->db->where('cust_received IS NULL');
            $this->db->where('canceled IS NULL');
            
        }elseif ($type == 1){
            $this->db->where('approved',1);
            $this->db->where('redeem',1);
            $this->db->where('cust_received IS NOT NULL');
            $this->db->where('canceled IS NULL');
            
        }elseif ($type == 2){
            $this->db->where('approved',1);
            $this->db->where('canceled IS NOT NULL');
        }
       
        $this->db->order_by('id', 'desc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function searching($value=null,$field='code')
    {   
        $this->db->select($this->field);
        $this->db->from('sales');
        $this->db->like($field, $value, 'both'); 
//        $this->db->like('code', $value, 'both'); 
        $this->db->where('approved', 1);
        $this->db->order_by('id', 'desc'); 
        return $this->db->get();
    }
    
    function search_by_product($product=null)
    {
        $this->db->select('sales.id,sales.code,sales.drop_point,sales.dates,sales.cust,sales.amount,sales.tax,sales.cost,'
                . 'sales.discount, sales.total, sales.shipping, sales.shipid, sales.shipstatus, sales.shipreceived, sales.receiver_address, sales.receiver_phone,'
                . 'sales.payment_type, sales.transid, sales.redeem, sales.redeem_date, sales.canceled, sales.canceled_desc, sales.voucher, sales.approved,'
                . 'sales.log, sales.booked, sales.booked_by, sales.pickup, sales.pickup_time, sales.cash, sales.created, sales.updated, sales.deleted');
        $this->db->from('sales, sales_item, product');
        $this->db->where('sales.id = sales_item.sales_id');
        $this->db->where('product.id = sales_item.product_id');
        $this->db->where('sales.approved', 1);
        $this->db->like('product.name', $product, 'both'); 
        return $this->db->get();
    }
    
        
    function search_summary($start=null,$end=null,$user=null, $approved=null, $cancel=null)
    {   
        $this->db->select_sum('amount');  
        $this->cek_null($user, 'cust');
        if ($end != null){ $this->between('date(dates)', $start, $end); }
        else{ $this->cek_null($start, 'date(dates)'); }
        $this->cek_null($approved, 'approved');
        if ($cancel == '1'){ $this->db->where('canceled IS NOT NULL'); }
        elseif ($cancel == '0'){ $this->db->where('canceled IS NULL'); }
        $query = $this->db->get($this->tableName)->row_array();
        return floatval($query['amount']);
    }
    
    
    function counter($type=0)
    {
       $this->db->select_max('id');
       $query = $this->db->get($this->tableName)->row_array(); 
       if ($type == 0){ return intval($query['id']+1); }else { return intval($query['id']); }
    }
    
    function get_amount_based_orderid($uid){
        
      $this->db->select_sum('amount');  
      $this->db->where('id', $uid);
      $query = $this->db->get($this->tableName)->row_array();
      return floatval($query['amount']);
    }
    
    function valid_orderid($orderid){
        
        $this->db->select($this->field);
        $this->db->where('code', $orderid);
        $num = $this->db->get($this->tableName)->num_rows();
        if ($num > 0){ return TRUE; }else{ return FALSE; }
    }
    
    function valid_transid($orderid){
        
        $this->db->select($this->field);
        $this->db->where('transid', $orderid);
        $num = $this->db->get($this->tableName)->num_rows();
        if ($num > 0){ return TRUE; }else{ return FALSE; }
    }
    
    function get_by_code($code=null)
    {
        $this->db->select($this->field);
        $this->db->where('code', $code);
        return $this->db->get($this->tableName);
    }
    
    function get_by_transid($code=null)
    {
        $this->db->select($this->field);
        $this->db->where('transid', $code);
        return $this->db->get($this->tableName);
    }
    
    // chart
    function total_chart($event=null,$member=null,$month=0,$year=0,$type=0)
    {
        if ($type==0){ $this->db->select('sales.dates'); }
        $this->db->select_sum('sales.amount');
        $this->db->from($this->tableName);
        $this->db->where('MONTH(sales.dates)', $month);
        $this->db->where('YEAR(sales.dates)', $year);
        $this->cek_null($event, 'event');
        $this->cek_null($member, 'member');
        $this->db->where('pos', 1);
        $this->db->where('approved', 1);
        if ($type == 0){ $this->db->group_by("CAST(sales.dates as date)"); return $this->db->get()->result(); }
        else{ return $this->db->get()->row_array(); }
        
        // SELECT SUM(total) AS total_sales, dates FROM sales
        // GROUP BY CAST(dates as date)
        // ORDER BY 1
        // WHERE MONTH(DATES) = 1 AND YEAR(DATES) = 2020
    }
    
    function total_chart_best_selling($event=null,$member=null,$month=0,$year=0,$type=0)
    {
        $this->db->select('sales_item.product_id');
        $this->db->select_sum('sales_item.amount');
        $this->db->from('sales, sales_item');
        $this->db->where('sales.id = sales_item.sales_id');
        $this->db->where('YEAR(sales.dates)', $year);
        $this->db->where('MONTH(sales.dates)', $month); 
        $this->cek_null($event, 'event');
        $this->cek_null($member, 'member');
        $this->db->where('sales.pos', 1);
        $this->db->where('sales.approved', 1);
        $this->db->group_by("sales_item.product_id");
        if ($type==1){ $this->db->limit(1); return $this->db->get()->row(); }
        else{ return $this->db->get()->result(); }
        
        // SELECT SUM(amount) AS total_sales,product_id 
        // FROM sales_item
        // GROUP BY product_id
    }
    
    function total_chart_pid($event=null,$member=null,$sku=0,$month=0,$year=0)
    {
        $this->db->select_sum('sales_item.amount');
        $this->db->from('sales, sales_item, product');
        $this->db->where('sales.id = sales_item.sales_id');
        $this->db->where('product.id = sales_item.product_id');
        $this->db->where('YEAR(sales.dates)', $year);
        $this->db->where('MONTH(sales.dates)', $month); 
        $this->cek_null($event, 'event');
        $this->cek_null($member, 'member');
        $this->db->where('sales.pos', 1);
        $this->db->where('sales.approved', 1);
        $this->db->where('product.sku', $sku);
        return $this->db->get()->row();
    }
    
    function update_transid($transid, $users)
    {
       $this->db->where('transid', $transid);
       $val = $this->db->get($this->tableName)->num_rows();
       if ($val > 0){
        $this->db->where('transid', $transid);
        return $this->db->update($this->tableName, $users);   
       }else{ return false; }
    }
    
    function canceled_trans($id)
    {
       $transaction = array('canceled_desc' => "Expired Payment", 'canceled' => date('Y-m-d H:i:s')); 
       $this->db->where('id', $id);
       return $this->db->update($this->tableName, $transaction);  
    }
        
}

?>