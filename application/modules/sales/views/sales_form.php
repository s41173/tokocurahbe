 <!-- Datatables CSS -->
<link href="<?php echo base_url(); ?>js/datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/buttons.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/fixedHeader.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/scroller.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/dataTables.tableTools.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>css/icheck/flat/green.css" rel="stylesheet" type="text/css">

<!-- Date time picker -->
 <script type="text/javascript" src="http://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
 
 <!-- Include Date Range Picker -->
<script type="text/javascript" src="http://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>
<link rel="stylesheet" type="text/css" href="http://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />


<style type="text/css">
  a:hover { text-decoration:none;}
</style>

<script src="<?php echo base_url(); ?>js/moduljs/sales.js"></script>
<script src="<?php echo base_url(); ?>js-old/register.js"></script>

<script type="text/javascript">

	var sites_add  = "<?php echo site_url('sales/add_process/');?>";
	var sites_edit = "<?php echo site_url('sales/update_process/');?>";
	var sites_del  = "<?php echo site_url('sales/delete/');?>";
	var sites_get  = "<?php echo site_url('sales/update/');?>";
    var sites  = "<?php echo site_url('sales');?>";
	var source = "<?php echo $source;?>";
    var url  = "<?php echo $graph;?>";
	
</script>

          <div class="row"> 
            <div class="col-md-12 col-sm-12 col-xs-12">
              <div class="x_panel" >
              
              <!-- xtitle -->
              <div class="x_title">
              
                <ul class="nav navbar-right panel_toolbox">
                  <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a> </li>
                  <li><a class="close-link"><i class="fa fa-close"></i></a> </li>
                </ul>
                
                <div class="clearfix"></div>
              </div>
              <!-- xtitle -->
                
                <div class="x_content">
                    
<!--
  <div id="errors" class="alert alert-danger alert-dismissible fade in" role="alert"> 
     <?php // $flashmessage = $this->session->flashdata('message'); ?> 
	 <?php // echo ! empty($message) ? $message : '' . ! empty($flashmessage) ? $flashmessage : ''; ?> 
  </div>
-->
  
  <div id="step-1">
    <!-- form -->
    <form id="salesformdata" data-parsley-validate class="form-horizontal form-label-left" method="POST" 
    action="<?php echo $form_action; ?>" 
      enctype="multipart/form-data">
		
    <style type="text/css">
       .xborder{ border: 1px solid red;}
       #custtitlebox{ height: 90px; background-color: #E0F7FF; border-top: 3px solid #2A3F54; margin-bottom: 10px; }
        #amt{ color: #000; margin-top: 35px; text-align: right; font-weight: bold;}
        #amt span{ color: blue;}
        .labelx{ font-weight: bold; color: #000;}
        #table_summary{ font-size: 16px; color: #000;}
        .amt{ text-align: right;}
    </style>

<!-- form atas   -->
    <div class="row">
       
<!-- div untuk customer place  -->
       <div id="custtitlebox" class="col-md-12 col-sm-12 col-xs-12">
            
           <div class="form-group">
               
               <div class="col-md-3 col-sm-12 col-xs-12">
                   <label class="control-label labelx"> * Customer </label>

         <?php $js = "class='select2_single form-control' id='ccustomer' tabindex='-1' style='min-width:150px;' "; 
	     echo form_dropdown('ccustomer', $customer, isset($default['customer']) ? $default['customer'] : '', $js); ?>
               </div>
               
               <div class="col-md-3 col-sm-12 col-xs-12 col-md-offset-1">
                   <label class="control-label labelx"> Email </label>
                   <input type="email" class="form-control" id="temail" required name="temail" readonly value="<?php echo isset($default['email']) ? $default['email'] : '' ?>" >
               </div>
               
               <div class="col-md-4 col-sm-12 col-xs-12 col-md-offset-1">
                   <h2 id="amt"> Total Amount : Rp. <span class="amt"> <?php echo isset($total) ? idr_format($total) : '0'; ?> </span>,- </h2>
               </div>
               
           </div>
           
       </div>
<!-- div untuk customer place  -->

<!-- div alamat penagihan -->
       <div class="col-md-3 col-sm-12 col-xs-12">
           <div class="col-md-12 col-sm-12 col-xs-12">
              <label class="control-label labelx"> Shipping Address </label>
      <textarea id="tshipadd" style="width:100%;" rows="4"><?php echo isset($default['ship_address']) ? $default['ship_address'] : '' ?></textarea>
           </div>
       </div>
<!-- div alamat penagihan -->

<!-- div tgl transaksi -->
    <div class="col-md-2 col-sm-12 col-xs-12">
       
       <div class="col-md-12 col-sm-12 col-xs-12">
          <label class="control-label labelx"> Transaction Date </label>
          <input type="text" title="Date" class="form-control" id="ds1" name="tdates" required
           value="<?php echo isset($default['dates']) ? $default['dates'] : '' ?>" /> 
       </div>
        
        <!-- due date    -->
       <div class="col-md-12 col-sm-12 col-xs-12">
          <label class="control-label labelx"> Due Date </label>
          <input type="text" title="Due Date" class="form-control" id="ds2" name="tduedates" required 
           value="<?php echo isset($default['due_date']) ? $default['due_date'] : '' ?>" /> 
       </div>    
        
    </div>
<!-- div tgl transaksi -->
        
<!-- div no transaksi -->
  <div class="col-md-2 col-sm-12 col-xs-12">
       
      <div class="col-md-12 col-sm-12 col-xs-12">
          <label class="control-label labelx"> Trans Code </label>
          <input type="text" title="Trans Code" class="form-control" readonly name="tcode" value="SO-0<?php echo $counter; ?>" /> 
       </div>  
      
      <div class="col-md-12 col-sm-12 col-xs-12">
          <label class="control-label labelx"> Payment Type </label>
          <?php $js = "class='form-control' id='cpayment' tabindex='-1' style='min-width:150px;' "; 
	      echo form_dropdown('cpayment', $payment, isset($default['payment']) ? $default['payment'] : '', $js); ?>
      </div>  
        
  </div>
<!-- div no transaksi -->
        
<!-- div landed cost -->
  <div class="col-md-2 col-sm-12 col-xs-12">
       
      <div class="col-md-12 col-sm-12 col-xs-12">
          <label class="control-label labelx"> Landed Cost </label>
          <input type="number" title="Landed Cost" class="form-control" name="tcosts" value="<?php echo isset($default['costs']) ? $default['costs'] : '' ?>" /> 
      </div>
      
      <div class="col-md-12 col-sm-12 col-xs-12">
          <label class="control-label labelx"> Total Discount </label>
          <input type="number" title="Landed Cost" class="form-control" name="ttotal_discount" readonly value="<?php echo isset($default['discount']) ? $default['discount'] : '' ?>" /> 
      </div>  
        
  </div>
<!-- div landed cost -->
        
<!-- div down payment -->
  <div class="col-md-2 col-sm-12 col-xs-12">
             
      <div class="col-md-12 col-sm-12 col-xs-12">
          <label class="control-label labelx"> Down Payment </label>
          <input type="number" title="Landed Cost" class="form-control" id="tp1" name="tp1" value="<?php echo isset($default['p1']) ? $default['p1'] : '' ?>" /> 
      </div> 
      
      <div class="col-md-12 col-sm-12 col-xs-12">
      <label class="control-label labelx"> Cash Status </label>
      <select class="form-control" id="ccash" name="ccash" style="width:60px;">
<option value="1"<?php echo set_select('ccash', '1', isset($default['cash']) && $default['cash'] == '1' ? TRUE : FALSE); ?>> Y </option>  
<option value="0"<?php echo set_select('ccash', '0', isset($default['cash']) && $default['cash'] == '0' ? TRUE : FALSE); ?>> N </option>  
      </select>
      </div>
        
  </div>
<!-- div down payment -->

</div>
<!-- form atas   -->
      
      <div class="ln_solid"></div>
      <div class="form-group">
        <div class="col-md-4 col-sm-4 col-xs-12 col-md-offset-9">
          <div class="btn-group">    
          <button type="submit" class="btn btn-success" id="button"> Save </button>
          <button type="reset" class="btn btn-danger" id=""> Cancel </button>
          <a class="btn btn-primary" href="<?php echo site_url('sales/add/'); ?>"> New Transaction </a> 
          </div>
        </div>
      </div>
      
	</form>
      
    <!-- end div layer 1 -->
      
<!-- form transaction table  -->
 
                    
<?php
                        
$atts2 = array(
	  'class'      => 'btn btn-primary button_inline',
	  'title'      => 'Product',
	  'width'      => '800',
	  'height'     => '600',
	  'scrollbars' => 'yes',
	  'status'     => 'yes',
	  'resizable'  => 'yes',
	  'screenx'    =>  '\'+((parseInt(screen.width) - 800)/2)+\'',
	  'screeny'    =>  '\'+((parseInt(screen.height) - 600)/2)+\'',
);

?>      
      
<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
    
 <!-- searching form -->
           
   <form id="ajaxtransform" class="form-inline" method="post" action="<?php echo $form_action_trans; ?>">
      <div class="form-group">
        <label class="control-label labelx"> Product </label> <br>
        <input id="titems" class="form-control col-md-3 col-xs-12" type="text" readonly name="cproduct" required>
        <div class="btn-group">  
        <?php echo anchor_popup(site_url("product/get_list/titems/".$branch), '[ ... ]', $atts2); ?>
        <button type="button" id="bget" class="btn btn-success button_inline"> GET </button>
        </div>
        &nbsp;
      </div>

      <div class="form-group">
        <label class="control-label labelx"> Qty </label> <br>
        <input type="number" name="tqty" class="form-control" style="width:70px;" maxlength="3" required value="1"> &nbsp;
      </div>
      
      <div class="form-group">
        <label class="control-label labelx"> Price </label> <br>
        <input type="number" name="tprice" id="tprice" class="form-control" style="width:120px;" maxlength="8" required value="0"> &nbsp;
      </div>
       
      <div class="form-group">
        <label class="control-label labelx"> Discount </label> <br>
        <input type="number" name="tdiscount" id="tdiscount" class="form-control" style="width:120px;" maxlength="8" required value="0"> &nbsp;
      </div>   
       
      <div class="form-group">
        <label class="control-label labelx"> Tax </label> <br>
         <?php $js = "class='form-control' id='ctax' tabindex='-1' style='width:100px;' "; 
	     echo form_dropdown('ctax', $tax, isset($default['tax']) ? $default['tax'] : '', $js); ?>
        &nbsp;
      </div>

      <div class="form-group"> <br>
       <div class="btn-group">      
       <button type="submit" class="btn btn-primary button_inline"> Post </button>
       <button type="button" onClick="load_data();" class="btn btn-danger button_inline"> Reset </button>
       </div>
      </div>
  </form> <br>


   <!-- searching form --> 
        
    </div>
    
<!-- table -->
  <div class="col-md-12 col-sm-12 col-xs-12">  
    <div class="table-responsive">
      <table class="table table-striped jambo_table bulk_action">
        <thead>
          <tr class="headings">
            <th class="column-title"> No </th>
            <th class="column-title"> Product </th>
            <th class="column-title"> Qty </th>
            <th class="column-title"> Price </th>
            <th class="column-title"> Discount </th>
            <th class="column-title"> Tax </th>
            <th class="column-title"> Amount </th>
            <th class="column-title no-link last"><span class="nobr">Action</span>
            </th>
            <th class="bulk-actions" colspan="7">
              <a class="antoo" style="color:#fff; font-weight:500;">Bulk Actions ( <span class="action-cnt"> </span> ) <i class="fa fa-chevron-down"></i></a>
            </th>
          </tr>
        </thead>

        <tbody>
            
        <?php
           
            function product($pid)
            {
                $val = new Product_lib();
                return ucfirst($val->get_name($pid));
            }
            
            if ($items)
            {
                $i=1;
                foreach($items as $res)
                {
                    echo "
                     <tr class=\"even pointer\">
                        <td> ".$i." </td>
                        <td> ".product($res->product_id)." </td>
                        <td> ".$res->qty." </td>
                        <td class=\"a-right a-right \"> ".idr_format(intval($res->qty*$res->price))." </td>
                        <td class=\"a-right a-right \"> ".idr_format($res->discount)." </td>
                        <td class=\"a-right a-right \"> ".idr_format($res->tax)." </td>
                        <td class=\"a-right a-right \"> ".idr_format($res->amount)." </td>
<td class=\" last\"> 
<a class=\"btn btn-danger btn-xs\" href=\"".site_url('sales/delete_item/'.$res->id.'/'.$res->sales_id)."\"> 
<i class=\"fa fas-2x fa-trash\"> </i> 
</a> </td>
                      </tr>
                    "; $i++;
                }
            }
            
        ?> 

        </tbody>
      </table>
    </div>
    </div>
<!-- table -->
    
<!-- kolom total -->
    <div class="col-md-3 col-sm-12 col-xs-12 col-md-offset-9">
        
        <table id="table_summary" style="width:100%;">
            <tr> <td> Sub Total </td> <td class="amt"> <?php echo isset($total) ? idr_format($total) : '0'; ?>,- </td> </tr>
<tr> <td> Discount (-) </td> <td class="amt"> <?php echo isset($discount) ? idr_format($discount) : '0'; ?>,- </td> </tr>
            <tr> <td> Tax </td> <td class="amt"> <?php echo isset($tax_total) ? idr_format($tax_total) : '0' ?>,- </td> </tr>
            <tr> <td> Shipping </td> <td class="amt"> 
                <span id="shipn"><?php echo isset($shipping) ? idr_format($shipping) : '0' ?></span>,- 
            </td> </tr>
<tr> <td> Down Payment (-) </td> <td class="amt"> <?php echo isset($p1) ? idr_format($p1) : '0' ?>,- </td> </tr>
<tr> <td> <h3 style="color:#337AB7; font-weight:bold;"> Total </h3> </td> 
     <td class="amt"> <h3 style="color:#337AB7; font-weight:bold;"> <?php echo isset($tot_amt) ? idr_format($tot_amt) : '0' ?>,- </h3> </td> </tr>
        </table>
        
    </div>
<!-- kolom total -->
    
</div>
<!-- form transaction table  -->  
        
  </div>
                  
     </div>
       
       <!-- links -->
       <?php if (!empty($link)){foreach($link as $links){echo $links . '';}} ?>
       <!-- links -->
                     
    </div>
  </div>
      
      <script src="<?php echo base_url(); ?>js/icheck/icheck.min.js"></script>
      
       <!-- Datatables JS -->
        <script src="<?php echo base_url(); ?>js/datatables/jquery.dataTables.min.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/dataTables.bootstrap.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/jszip.min.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/pdfmake.min.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/vfs_fonts.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/dataTables.fixedHeader.min.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/dataTables.keyTable.min.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/dataTables.responsive.min.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/responsive.bootstrap.min.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/dataTables.scroller.min.js"></script>
        <script src="<?php echo base_url(); ?>js/datatables/dataTables.tableTools.js"></script>
    
    <!-- jQuery Smart Wizard -->
    <script type="text/javascript" src="<?php echo base_url(); ?>js/wizard/jquery.smartWizard.js"></script>
        
        <!-- jQuery Smart Wizard -->
    <script type="text/javascript">
      $(document).ready(function() {
        $('#wizard').smartWizard();

        $('#wizard_verticle').smartWizard({
          transitionEffect: 'slide'
        });

      });
    </script>
    <!-- /jQuery Smart Wizard -->
    
    
