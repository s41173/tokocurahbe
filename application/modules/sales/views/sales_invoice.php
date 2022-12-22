<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="shortcut icon" href="<?php echo base_url().'images/fav_icon.png';?>" >
<title> Sales Order - <?php echo isset($pono) ? $pono : ''; ?></title>
<style media="all">

	#logo { margin:0 10px 0 5px;}
	#logotext{ font-size:1em; text-align:center; margin:5px; }
	p { margin:0; padding:0; font-size:1.05em;}
	#pono{ font-size:1.3em; padding:0; margin:0 5px 10px 0; text-align:left;}
	
	table.product
	{ border-collapse:collapse; width:100%; margin-bottom:10px; }
	
	table.product,table.product th
	{	border: 1px solid black; font-size:1.05em; font-weight:bold; padding:3px 0 3px 0; }
	
	table.product,table.product td
	{	border: 1px solid black; font-size:1.05em; font-weight:normal; padding:3px 0 3px 0; text-align:center; }
	
	table.product td.left { text-align:left; padding:3px 5px 3px 10px; }
	table.product td.right { text-align:right; padding:3px 10px 3px 5px; }
	
	#container{ width:20.5cm; font-family:Arial, Helvetica, sans-serif; font-size:12px; border:0px solid red;  }
	
</style>
</head>
    
<script type="text/javascript">
    
    function closeWindow() {
        setTimeout(function() {
        window.close();
        }, 300000);
    }
    
</script>      

<body onLoad="closeWindow()">

<div id="container">

	<div style="border:0px solid #000; width:12.8cm; height:3.6cm; float:left;">
		<img id="logo" align="left" width="120" src="<?php echo isset($logo) ? $logo : ''; ?>"> <br>
		<p id="logotext"> 
		  <?php echo $paddress; ?> <br> Kotamadya Medan - <?php echo $p_zip; ?> &nbsp; Telp. <?php echo $p_phone1; ?>
		  <br> E-Mail : <?php echo $p_email; ?> <br> Website : <?php echo $p_sitename; ?>
		</p>
		<p style="float:left; margin:0; padding:10px 0 0 85px; font-weight:bold;"> NPWP : <?php echo $p_npwp; ?> </p>
	</div>
	
	<div style="border:0px solid; float:right;">
		
		<h4 id="pono"> No : <?php echo $pono; ?> &nbsp; &nbsp; <?php echo 'IDR'; ?> </h4>
		<p> Medan, &nbsp; <?php echo isset($podate) ? $podate : ''; ?> </p> <br>
		<p> To, </p> 
		<p style="margin:8px 0 0 0;"> <b> <?php echo isset($customer) ? $customer : ''; ?> </b> </p>
		<p> <?php echo isset($address) ? $address : ''; ?> - <?php echo isset($city) ? $city : ''; ?> </p> 
		<p> <?php echo isset($phone) ? $phone : ''; ?> </p>
		
	</div>
	
	<div style="clear:both; "></div>
	
	<h2 style="font-size:1.4em; font-weight:normal; text-align:center; margin:0px 0px 10px 0px; padding:0 0 0 25px;"> SALES ORDER </h2> <div style="clear:both; "></div> 

	
	<div style="clear:both; "></div>
	
	<div style="margin:10px; border-bottom:1px dotted #000;">
		<?php //echo ! empty($table) ? $table : ''; ?>
		
		<table class="product">

		 <tr> 
			<th> No </th> <th> Product </th> <th> Qty </th> <th> Price </th> <th> Discount </th> <th> Tax </th> <th> Total Amount </th>
		 </tr>
		 
		 <?php
		 	
			$product = $this->load->library('product_lib');
			
			if ($items)
			{
				$i=1;
				foreach ($items as $res)
				{
					echo "
					
					 <tr> 
						<td> ".$i." </td>
						<td class=\"left\"> ".strtoupper($product->get_name($res->product_id))." </td> 
						<td> ".$res->qty.' '.$product->get_unit($res->product_id)." </td> 
						<td class=\"right\"> ".number_format($res->price).",- </td>
						<td class=\"right\"> ".number_format($res->discount).",- </td> 
						<td class=\"right\"> ".number_format($res->tax).",- </td> 
						<td class=\"right\"> ".number_format($res->amount).",- </td>   
					 </tr>
					
					"; $i++;
				}
			}
			
		 ?>
		 
		 <tr> <td></td> <td class="left"> Landed Cost </td> <td colspan="4"></td>   <td class="right"> <?php echo number_format($cost); ?>,- </td> </tr>
		 <tr> <td></td> <td class="left"> Down Payment </td> <td colspan="4"></td>   <td class="right"> <?php echo number_format($p1); ?>,- </td> </tr>
		 <tr> <td colspan="6"></td>  <td class="right"> <b> <?php echo number_format($p2); ?>,- </b> </td> </tr>
			
		</table>
		
		<div style="float:left; width:7.5cm; border:0px solid #000;">  
			<p style="margin:0; padding:5px 0 0 0;"> Description : <?php echo $desc; ?> </p>
			<p style="margin:0; padding:5px 0 0 0;"> Status / Log : <?php echo $approve.'/'.$status.'/'.$log; ?> </p>
		</div>
		
		<div style="float:right;">
			
			<table>
				<p> &nbsp; &nbsp; Ordered By, &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Approved By, </p> <br> <br> <br> <br>
				<p style="text-align:right;"> ( <?php echo $user; ?> ) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (_______________) </p>
				<p> &nbsp; &nbsp; &nbsp; Sales Dept  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  Manager </p>
			</table>
			<br>
		</div>
		
		<div style="clear:both; ">
		
	</div>	
	
</div>

</body>
</html>
