<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Posc_model extends Custom_Model
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
    
    
    function search($event=null,$dates=null,$user=null,$limit, $offset=null, $count=0)
    {   
        $this->db->select($this->field);
        $this->db->from('sales');
        $this->cek_null($user, 'cust');
//        $this->cek_null($payment, 'sales.payment_id');
        $this->cek_null($dates, 'date(dates)');
        $this->cek_null($event, 'event');
        
        $this->db->where('pos', 1);
        $this->db->order_by('id', 'desc'); 
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function report($event=null,$member=null,$cancel=null,$start=null,$end=null,$count=0)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName);
        
        $this->cek_null($event, 'sales.event');
        $this->cek_null($member, 'sales.cust');
        $this->cek_null($cancel, 'sales.cancel');
        $this->between('sales.dates', $start, $end);
        
        $this->db->order_by('id', 'desc'); 
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function report_monthly($event=null,$member=null,$cancel=null,$month=null,$year=null,$count=0)
    {   
        $this->db->select($this->field);
        $this->db->from($this->tableName);
        
        $this->cek_null($event, 'sales.event');
        $this->cek_null($member, 'sales.cust');
        $this->cek_null($cancel, 'sales.cancel');
        $this->db->where('MONTH(sales.dates)', $month);
        $this->db->where('YEAR(sales.dates)', $year);
        
        $this->db->order_by('id', 'desc'); 
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
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
        $this->db->where('orderid', $orderid);
        $num = $this->db->get($this->tableName)->num_rows();
        if ($num > 0){ return TRUE; }else{ return FALSE; }
    }
    
    // summary report
    function get_last_summary($event=null,$member=null,$cancel=null,$limit, $offset=null, $count=0)
    {
        $this->db->select('sales.dates, SUM(sales.amount) as amount');
        $this->db->from($this->tableName);
        $this->db->where('sales.pos', 1);
        $this->cek_nol($cancel, 'cancel');
        $this->cek_null($event, 'event');
        $this->cek_null($member, 'cust');
        $this->db->where('sales.approved', 1);
        $this->db->group_by("date(sales.dates)");
        $this->cek_count($count,$limit,$offset);
        if ($count==0){ return $this->db->get(); }else{ return $this->db->get()->num_rows(); }
    }
    
    function search_summary($event=null,$member=null,$cancel=null,$dates=null)
    {   
        $this->db->select_sum('amount');  
        $this->cek_nol($cancel, 'cancel');
        $this->cek_null($dates, 'date(dates)');
        $this->cek_null($event, 'event');
        $this->cek_null($member, 'cust');
        $this->db->where('pos', 1);
        $this->db->where('approved', 1);
        $query = $this->db->get($this->tableName)->row_array();
        return floatval($query['amount']);
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
        $this->cek_null($member, 'cust');
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
        $this->cek_null($member, 'cust');
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
        $this->cek_null($member, 'cust');
        $this->db->where('sales.pos', 1);
        $this->db->where('sales.approved', 1);
        $this->db->where('product.sku', $sku);
        return $this->db->get()->row();
    }
    

    


    

}

?>