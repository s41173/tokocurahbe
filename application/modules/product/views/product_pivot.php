<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="shortcut icon" href="<?php echo base_url().'images/fav_icon.png';?>" >
<title> <?php echo isset($title) ? $title : ''; ?>  </title>
<style media="all">
	table{ font-family:"Arial", Times, serif; font-size:11px;}
	h4{ font-family:"Arial", Times, serif; font-size:14px; font-weight:600;}
	.clear{clear:both;}
	table th{ background-color:#EFEFEF; padding:4px 0px 4px 0px; border-top:1px solid #000000; border-bottom:1px solid #000000;}
    p{ font-family:"Arial", Times, serif; font-size:12px; margin:0; padding:0;}
	legend{font-family:"Arial", Times, serif; font-size:13px; margin:0; padding:0; font-weight:600;}
	.tablesum{ font-size:13px;}
	.strongs{ font-weight:normal; font-size:12px; border-top:1px dotted #000000; }
	.poder{ border-bottom:0px solid #000000; color:#0000FF; font-size:9pt;}
	.red{ border-bottom:0px solid #000000; color:#900; font-size:10pt;}
</style>

    <link rel="stylesheet" type="text/css" href="<?php echo base_url().'js-old/pivot/' ?>pivot.css">
	  <script type="text/javascript" src="<?php echo base_url().'js-old/pivot/' ?>jquery-1.8.3.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/pivot/' ?>jquery-ui-1.9.2.custom.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/pivot/' ?>jquery.ui.touch-punch.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url().'js-old/pivot/' ?>pivot.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {

			var input = $("#input")
			$("#output").pivotUI(input);
			$("#input").hide();
        });
    </script>

</head>

<body>

<div style="width:100%; border:0px solid blue; font-family:Arial, Helvetica, sans-serif; font-size:12px;">

	<div style="border:0px solid red; float:left;">
		<table border="0">
            <tr> <td> Manufacture </td> <td> : </td> <td> <?php echo $manufacture; ?> </td> </tr>
            <tr> <td> Category </td> <td> : </td> <td> <?php echo $category; ?> </td> </tr>
			<tr> <td> Run Date </td> <td> : </td> <td> <?php echo $rundate; ?> </td> </tr>
			<tr> <td> Log </td> <td> : </td> <td> <?php echo $log; ?> </td> </tr>
		</table>
	</div>

	<center>
	   <div style="border:0px solid green; width:230px;">
	      <h4> <?php echo isset($company) ? $company : ''; ?> <br> Product - Report (Pivot Table) </h4>
	   </div>
	</center>

	<div class="clear"></div>

	<div style="width:100%; border:0px solid brown; margin-top:20px; border-bottom:0px dotted #000000; ">

    	<div id='jqxWidget'>
        <div style='margin-top: 10px;' id="output"> </div>
        </div>

		<table id="input" border="0" width="100%" style="visibility:hidden;">
		 <thead>
           <tr>
 	       <th> No </th> <th> Category </th> <th> Manufacture </th> <th> Name </th> <th> Model </th> <th> Currency </th>
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

</body>
</html>
