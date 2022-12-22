<meta http-equiv="Refresh" content="60">
 <!-- Datatables CSS -->
<link href="<?php echo base_url(); ?>js/datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/buttons.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/fixedHeader.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/responsive.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/scroller.bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>js/datatables/dataTables.tableTools.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>css/icheck/flat/green.css" rel="stylesheet" type="text/css">

<script src="<?php echo base_url(); ?>js/moduljs/pos.js"></script>
<script src="<?php echo base_url(); ?>js-old/register.js"></script>

<!--canvas js-->
<script type="text/javascript" src="<?php echo base_url().'js-old/' ?>canvasjs.min.js"></script>

<!-- Date time picker -->
 <script type="text/javascript" src="http://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
 
 <!-- Include Date Range Picker -->
<script type="text/javascript" src="http://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>
<link rel="stylesheet" type="text/css" href="http://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />

<style type="text/css">
    
    .normal_p{ text-decoration: line-through; margin: 0;}
    .discount_p { color: red;}
</style>

<!-- bootstrap toogle -->
<!--<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>-->

<script type="text/javascript">

	var sites_add  = "<?php echo site_url('pos/add_process/');?>";
	var sites_edit = "<?php echo site_url('pos/update_process/');?>";
	var sites_del  = "<?php echo site_url('pos/delete/');?>";
	var sites_get  = "<?php echo site_url('pos/update/');?>";
    var sites_confirmation  = "<?php echo site_url('pos/confirmation/');?>";
    var sites_print_invoice  = "<?php echo site_url('pos/invoice/');?>";
    var sites_primary   = "<?php echo site_url('pos/add/');?>";
	var sites_attribute = "<?php echo site_url('pos/attribute/');?>";
	var source = "<?php echo $source;?>";
    
    var url  = "<?php echo $graph;?>";
	
</script>

          <div class="row"> 
          
            <div class="col-md-12 col-sm-12 col-xs-12">
                                  
              <!--  batas xtitle 2  -->    
                
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
           
           <!-- searching form -->
           
           <form id="searchform" class="form-inline">
             
            <div class="form-group">
                <label class="control-label labelx"> Payment : </label> <br>  
                <?php $js = "class='select2_single form-control' id='cpayment' tabindex='-1' style='min-width:200px;' "; 
			    echo form_dropdown('cpayment', $payment, isset($default['payment']) ? $default['payment'] : '', $js); ?>
                &nbsp;
            </div>  
               
            <div class="form-group">
               <label> Dates : </label> <br>
               <input type="text" title="Date" class="form-control" id="ds1" name="tdates" /> 
            </div>
              
          <div class="form-group">
           <br>   
           <div class="btn-group">      
           <button type="submit" class="btn btn-primary button_inline"> Filter </button>
           <button type="reset" onClick="" class="btn btn-success button_inline"> Clear </button>
           <button type="button" onClick="load_data();" class="btn btn-danger button_inline"> Reset </button>
           </div>
          </div>
          </form> <br>

         <!-- searching form -->
           
              
<form class="form-inline" id="cekallform" method="post" action="<?php echo ! empty($form_action_del) ? $form_action_del : ''; ?>">
      <!-- table -->

      <div class="table-responsive">
        <?php echo ! empty($table) ? $table : ''; ?>            
      </div>

</form>       
             </div>

            <div class="btn-group">
            <a class="btn btn-primary" href="<?php echo site_url('pos/add'); ?>"> <i class="fa fa-plus"></i>&nbsp;Add New </a>

               <!-- links -->
               <?php if (!empty($link)){foreach($link as $links){echo $links . '';}} ?>
               <!-- links -->      
            </div>
            </div>
          </div>  
    
      <!-- Modal - Add Form -->
      <div class="modal fade" id="myModal" role="dialog">
         <?php //$this->load->view('sales_confirmation'); ?>      
      </div>
      <!-- Modal - Add Form -->
      
      <!-- Modal Attribute -->
      <div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	     
		 <?php //$this->load->view('product_attribute_frame'); ?> 
      </div>
      <!-- Modal Attribute -->
      
      
      <!-- Modal - Report Form -->
      <div class="modal fade" id="myModal3" role="dialog">subl
         <?php //$this->load->view('sales_report_panel'); ?>    
      </div>
      <!-- Modal - Report Form -->
      
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
