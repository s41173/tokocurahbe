<div class="modal-dialog">
        
<!-- Modal content-->
<div class="modal-content">
<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal">&times;</button>
  <h4 class="modal-title"> Sales Report </h4>
</div>
<div class="modal-body">
 
 <!-- form add -->
<div class="x_panel" >
<div class="x_title">
  
  <div class="clearfix"></div> 
</div>
<div class="x_content">
    
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

<form id="" data-parsley-validate class="form-horizontal form-label-left" method="POST" 
action="<?php echo $form_action_report; ?>" enctype="multipart/form-data">
    
    
    <div class="form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Customer </label>
        <div class="col-md-8 col-sm-12 col-xs-12">     
         <?php $js = "class='select2_single form-control' id='ccustomer' tabindex='-1' style='width:250px;' "; 
	     echo form_dropdown('ccustomer', $customer, isset($default['customer']) ? $default['customer'] : '', $js); ?>
        </div>
    </div>
    
    <div class="form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Branch </label>
        <div class="col-md-8 col-sm-12 col-xs-12">     
         <?php $js = "class='select2_single form-control' id='cbranch' tabindex='-1' style='width:250px;' "; 
	     echo form_dropdown('cbranch', $branch_combo, isset($default['branch']) ? $default['branch'] : '', $js); ?>
        </div>
    </div>
    
    <div class="form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Product </label>
        <div class="col-md-5 col-sm-4 col-xs-12">     
        <table>
         <tr>
             <td> <input id="titems" class="form-control" type="text" readonly name="cproduct">   </td>
             <td> <?php echo anchor_popup(site_url("product/get_list/titems/".$branch), '[ ... ]', $atts2); ?> </td>
         </tr>    
        </table>    
        </div>
    </div>
    
     <div class="form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Period </label>
        <div class="col-md-9 col-sm-9 col-xs-12">     
<input type="text" readonly style="width: 200px" name="reservation" id="d1" class="form-control active" value=""> 
        </div>
    </div>
    
    <div class="form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Paid </label>
        <div class="col-md-3 col-sm-12 col-xs-12">     
<select name="cpaid" class="form-control">
    <option value=""> -- </option>
    <option value="1"> Paid </option>
    <option value="0"> Unpaid </option>
</select>
        </div>
    </div>
    
    <div class="form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Confirmation </label>
        <div class="col-md-3 col-sm-9 col-xs-12">     
<select name="cconfirm" class="form-control">
    <option value=""> -- </option>
    <option value="1"> Confirmed </option>
    <option value="0"> Unconfirmed </option>
</select>
        </div>
    </div>
    
    <div class="form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12"> Type </label>
        <div class="col-md-5 col-sm-9 col-xs-12">     
			<select name="ctype" class="form-control">
              <option value="0"> Summary </option>
              <option value="1"> Pivottable </option>
              <option value="2"> Sales Item </option>
              <option value="3"> Pivottable Sales Item </option>
            </select>
        </div>
    </div>

      <div class="ln_solid"></div>
      <div class="form-group">
          <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3 btn-group">      
          <button type="submit" class="btn btn-primary">Post</button>
          <button type="reset" class="btn btn-success"> Reset </button>
          <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
          </div>
      </div>
  </form> 
  <div id="err"></div>
</div>
</div>
<!-- form add -->

</div>
    <div class="modal-footer">
      
    </div>
  </div>
  
</div>