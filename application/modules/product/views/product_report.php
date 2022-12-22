<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="shortcut icon" href="<?php echo base_url().'images/fav_icon.png';?>" >
<title> <?php echo isset($title) ? $title : ''; ?>  </title>
<style media="all">
	table{ font-family:"Tahoma", Times, serif; font-size:11px;}
	h4{ font-family:"Tahoma", Times, serif; font-size:14px; font-weight:600;}
	.clear{clear:both;}
	table th{ background-color:#EFEFEF; padding:4px 0px 4px 0px; border-top:1px solid #000000; border-bottom:1px solid #000000;}
    p{ font-family:"Tahoma", Times, serif; font-size:12px; margin:0; padding:0;}
	legend{font-family:"Tahoma", Times, serif; font-size:13px; margin:0; padding:0; font-weight:600;}
	.tablesum{ font-size:13px;}
	.strongs{ font-weight:normal; font-size:12px; border-top:1px dotted #000000; }
	.poder{ border-bottom:0px solid #000000; color:#0000FF;}
    .img_product{ height: 50px; align-content: center;}
</style>

<link rel="stylesheet" href="<?php echo base_url().'js-old/jxgrid/' ?>css/jqx.base.css" type="text/css" />
    
	<script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxcore.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxdata.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxbuttons.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxcheckbox.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxscrollbar.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxlistbox.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxdropdownlist.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxmenu.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.sort.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.filter.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.columnsresize.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.columnsreorder.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.selection.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.pager.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.aggregates.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxdata.export.js"></script>
	<script type="text/javascript" src="<?php echo base_url().'js-old/jxgrid/' ?>js/jqxgrid.export.js"></script>
	
    <script type="text/javascript">
	
        $(document).ready(function () {
          
			var rows = $("#table tbody tr");
                // select columns.
                var columns = $("#table thead th");
                var data = [];
                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    var datarow = {};
                    for (var j = 0; j < columns.length; j++) {
                        // get column's title.
                        var columnName = $.trim($(columns[j]).text());
                        // select cell.
                        var cell = $(row).find('td:eq(' + j + ')');
                        datarow[columnName] = $.trim(cell.text());
                    }
                    data[data.length] = datarow;
                }
                var source = {
                    localdata: data,
                    datatype: "array",
                    datafields:
                    [
                        { name: "No", type: "string" },
                        { name: "Sku", type: "string" },
						{ name: "Category", type: "string" },
						{ name: "Manufacture", type: "string" },
						{ name: "Name", type: "string" },
						{ name: "Model", type: "string" },
						{ name: "Currency", type: "string" },
                        { name: "Qty", type: "number" },
                        { name: "Min Order", type: "string" },
                        { name: "Price", type: "number" },
                        { name: "Discount (%)", type: "string" },
                        { name: "Disc Price", type: "number" },
                        { name: "Image", type: "string" },
                        { name: "Dimension", type: "string" },
                        { name: "Weight", type: "string" },
                        { name: "Publish", type: "string" }
                    ]
                };
			
            var dataAdapter = new $.jqx.dataAdapter(source);
            $("#jqxgrid").jqxGrid(
            {
                width: '100%',
				source: dataAdapter,
				sortable: true,
				filterable: true,
				pageable: true,
				altrows: true,
				enabletooltips: true,
				filtermode: 'excel',
				autoheight: true,
				columnsresize: true,
				columnsreorder: true,
				showstatusbar: true,
				statusbarheight: 30,
				showaggregates: true,
				autoshowfiltericon: false,
                columns: [
                  { text: 'No', dataField: 'No', width: 50 },
				  { text: 'Sku', dataField: 'Sku', width : 250 },
                  { text: 'Category', dataField: 'Category', width : 250 },
				  { text: 'Manufacture', dataField: 'Manufacture', width : 200 },
  				  { text: 'Name', dataField: 'Name', width : 250 },
				  { text: 'Model', dataField: 'Model', width : 250 },
  				  { text: 'Currency', dataField: 'Currency', width : 100 },
    { text: 'Qty', datafield: 'Qty', width: 100, cellsalign: 'right', cellsformat: 'number', aggregates: ['sum'] },
                  { text: 'Min Order', dataField: 'Min Order', width : 100 },
    { text: 'Price', dataField: 'Price', width : 150, cellsalign: 'right', cellsformat: 'number' },
                  { text: 'Discount (%)', dataField: 'Discount (%)', width : 130 },
    { text: 'Disc Price', dataField: 'Disc Price', width : 120, cellsalign: 'right', cellsformat: 'number', aggregates: ['sum'] },
                  { text: 'Dimension', dataField: 'Dimension', width : 200 },
                  { text: 'Weight', dataField: 'Weight', width : 75 },
                  { text: 'Publish', dataField: 'Publish', width : 70 }
                ]
            });
			
			$('#jqxgrid').jqxGrid({ pagesizeoptions: ['1000', '2000', '3000', '5000', '10000', '15000']}); 
			
			$("#bexport").click(function() {
				
				var type = $("#crtype").val();	
				if (type == 0){ $("#jqxgrid").jqxGrid('exportdata', 'html', 'Product-Summary'); }
				else if (type == 1){ $("#jqxgrid").jqxGrid('exportdata', 'xls', 'Product-Summary'); }
				else if (type == 2){ $("#jqxgrid").jqxGrid('exportdata', 'pdf', 'Product-Summary'); }
				else if (type == 3){ $("#jqxgrid").jqxGrid('exportdata', 'csv', 'Product-Summary'); }
			});
			
			$('#jqxgrid').on('celldoubleclick', function (event) {
     	  		var col = args.datafield;
				var value = args.value;
				var res;
			
				if (col == 'Code')
				{ 			
				   res = value.split("CD-00");
				   openwindow(res[1]);
				}
 			});
			
			function openwindow(val)
			{
				var site = "<?php echo site_url('ap_payment/print_invoice/');?>";
				window.open(site+"/"+val, "", "width=800, height=600"); 
				//alert(site+"/"+val);
			}
			
			$("#table").hide();
			
		// end jquery	
        });
    </script>
</head>

<body>

<div style="width:100%; border:0px solid blue; font-family:Arial, Helvetica, sans-serif; font-size:12px;">
	
	<div style="border:0px solid red; float:left;">
		<table border="0">
			<tr> <td> Manufacture </td> <td> : </td> <td> <?php echo $manufacture; ?> </td> </tr>
            <tr> <td> Category </td> <td> : </td> <td> <?php echo $category; ?> </td> </tr>
            <tr> <td> Branch / Outlet </td> <td> : </td> <td> <?php echo $branch; ?> </td> </tr>
            <tr> <td> Period </td> <td> : </td> <td> <?php echo $month.'-'.$year; ?> </td> </tr>
			<tr> <td> Run Date </td> <td> : </td> <td> <?php echo $rundate; ?> </td> </tr>
			<tr> <td> Log </td> <td> : </td> <td> <?php echo $log; ?> </td> </tr>
		</table>
	</div>

	<center>
	   <div style="border:0px solid green; width:230px;">	
	       <h4> <?php echo isset($company) ? $company : ''; ?> <br> Product Report </h4>
	   </div>
	</center>
	
	<div class="clear"></div>
	
	<div style="width:100%; border:0px solid brown; margin-top:20px; border-bottom:1px dotted #000000; ">
	
    <div id='jqxWidget'>
        <div style='margin-top: 10px;' id="jqxgrid"> </div>
        
        <table style="float:right; margin:5px;">
        <tr>
        <td> <input type="button" id="bexport" value="Export"> - </td>
        <td> 
        <select id="crtype"> <option value="0"> HTML </option> <option value="1"> XLS </option>  <option value="2"> PDF </option> 
        <option value="3"> CSV </option> 
        </select>
        </td>
        </tr>
        </table>
        
    </div>
    
		<table id="table" border="0" width="100%">
		   <thead>
           <tr>
 	       <th> No </th> <th> Sku </th> <th> Category </th> <th> Manufacture </th> <th> Name </th> <th> Model </th> <th> Currency </th>
           <th> Qty </th> <th> Min Order </th> <th> Price </th> <th> Discount (%) </th> <th> Disc Price </th> <th> Image </th> <th> Dimension </th> <th> Weight </th> <th> Publish </th>
		   </tr>
           </thead>
		  
          <tbody> 
		  <?php 
		      
              function manufacture($val)
              {
                  $res = new Manufacture_lib(); 
                  return strtoupper($res->get_name($val));
              } 
              
              function category($val)
              {
                  $res = new Categoryproduct_lib(); 
                  return strtoupper($res->get_name($val));
              } 
              
              function pstatus($val){ if ($val == 0){ return 'N'; }else{ return 'Y'; } }
              
              function qty($pid,$branch,$month,$year){
                  
                $st = new Stock_ledger_lib();  
                return $st->get_qty($pid, $branch, $month, $year);  
              }
			  		  
		      $i=1; 
			  if ($reports)
			  {
				foreach ($reports as $res)
				{	
				   echo " 
				   <tr> 
				       <td class=\"strongs\">".$i."</td> 
					   <td class=\"strongs\">".$res->sku."</td>
                       <td class=\"strongs\">".category($res->category)."</td>
                       <td class=\"strongs\">".manufacture($res->manufacture)."</td>
                       <td class=\"strongs\">".strtoupper($res->name)."</td>
                       <td class=\"strongs\">".strtoupper($res->model)."</td>
                       <td class=\"strongs\">".$res->currency."</td>
                       <td class=\"strongs\">".qty($res->id, $branch_id, $month, $year)."</td>
                       <td class=\"strongs\">".$res->min_order."</td>
                       <td class=\"strongs\">".$res->price."</td>
                       <td class=\"strongs\">".@intval($res->discount/$res->price*100)."</td>
                       <td class=\"strongs\">".intval($res->price-$res->discount)."</td>
                <td class=\"strongs\"> <img class=\"img_product\" src=\"".base_url().'images/product/'.$res->image."\"> </td>
					   <td class=\"strongs\">".$res->dimension.' '.$res->dimension_class."</td> 
					   <td class=\"strongs\">".$res->weight."</td>
                       <td class=\"strongs\">".pstatus($res->publish)."</td>
				   </tr>";
				   $i++;
				}
			 }  
		  ?>
		</tbody>      
		</table>
        
        </div>
        
        <a style="float:left; margin:10px;" title="Back" href="<?php echo site_url('product'); ?>"> 
          <img src="<?php echo base_url().'images/back.png'; ?>"> 
        </a>
        
	</div>
	
</div>

</body>
</html>
