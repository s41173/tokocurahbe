<div class="modal-dialog">
        
<!-- Modal content-->
<div class="modal-content">
<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal">&times;</button>
  <h4 class="modal-title"> Export CSV - Product </h4>
</div>
<div class="modal-body">
 
 <!-- error div -->
 <div class="alert alert-success success"> </div>
 <div class="alert alert-warning warning"> </div>
 <div class="alert alert-error error"> </div>
 
 <!-- form add -->
<div class="x_panel" >
<div class="x_title">
  
  <div class="clearfix"></div> 
</div>
<div class="x_content">

<form id="" data-parsley-validate class="form-horizontal form-label-left" method="POST"
      action="<?php echo site_url('product/export_csv'); ?>" enctype="multipart/form-data" >
             
    <div class="form-group">
      <label for="middle-name" class="control-label col-md-3 col-sm-3 col-xs-12"> SKU </label>
      <div class="col-md-6 col-sm-6 col-xs-12">
         <table>
             <tr> 
             <td> <input type="text" class="form-control" name="tsku" placeholder="Sku" style="width:150px;" required /> </td>
             <td> <input type="number" class="form-control" name="tqty" placeholder="Qty" style="width:85px;" required /> </td>
             </tr>
         </table>
          
      </div>
    </div>

      <div class="ln_solid"></div>
      <div class="form-group">
          <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3 btn-group">
          <button type="submit" class="btn btn-primary" id="button">Export</button>
          <button type="button" id="bclose" class="btn btn-danger" data-dismiss="modal">Close</button>
          </div>
      </div>
  </form> 

</div>
</div>
<!-- form add -->

</div>
    <div class="modal-footer"> </div>
</div>
  
</div>