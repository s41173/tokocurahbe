<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="UTF-8">
	
	<title> POS - <?php echo $orderid; ?> </title>
	
	<style>
		body {
			margin: 0 auto;
		}
		
		table {
			margin: 0 auto;
			border-bottom: 1px dotted black;
			font-family: 'verdana', sans-serif;
			font-size: 10pt;
            line-height: 11px;
		}
		td, th {
			text-align: left;
			padding: 5px 12px;
		}
		th {
			border-top: 1px dotted black;
			border-bottom: 1px dotted black;
		}
		p {
			text-align: center;
			margin: 0 auto;
		}
		.qty {
			text-align: center;
		}
		.sometxt {
			margin: 0 auto;
			font-family: 'verdana', sans-serif;
		}
		.sometxt p {
			text-align: center;
			font-size: 10pt;
		}
        .price{ text-align: right;}
	</style>
    
    <script type="text/javascript">
    
    function closeWindow() {
    setTimeout(function() {
    window.close();
    }, 15000);
    }

    </script> 
    
</head>
<body onload="window.print(); closeWindow();">
<div class="sometxt">
	<p>
		<?php echo $b_name; ?> <br>
		<?php echo $b_address; ?> <br>
		Telp. <?php echo $b_phone1; ?> Kota <?php echo $b_city; ?> Indonesia
	</p>
</div>
<table>
	<tr>
		<th>Nama</th>
		<th class="qty">Qty</th>
		<th>Harga</th>
		<th>Total</th>
	</tr>
<!---------------------------------->
<!--
	<tr>
		<td>Eskulin Mist Col 12</td>
		<td class="qty">5</td>
		<td class="price">10.300</td>
		<td class="price">51.500</td>
	</tr>
-->
    
<?php

if ($items){
    
    $tot = 0;
    $price = 0;
    $discount = 0;
    $tax = 0;
    $product = $this->load->library('product_lib');
    
    foreach ($items as $res){
        
        echo "
  	<tr>
		<td> ".ucfirst($product->get_name($res->product_id))." </td>
		<td class=\"qty\">".$res->qty."</td>
		<td class=\"price\">".idr_format($res->price).",-</td>
        <td class=\"price\">".idr_format(floatval($res->qty*$res->price)).",-</td>
	</tr>      
        ";
        $tot++;
        $price = $price + ($res->qty*$res->price);
        $discount = $discount + ($res->qty*$res->discount);
        $tax = $tax + $res->tax;
        
    }
    
}

?>
	
<!-------------TOTAL-------------->
	<tr>
		<td colspan="3"> Harga Jual --<?php echo $tot; ?>--Item(s) </td>
		<td class="price"> <?php echo idr_format($price); ?>,-</td>
	</tr>
    
    <tr>
		<td colspan="3"> Discount </td>
		<td class="price"> <?php echo idr_format($discount); ?>,-</td>
	</tr>
    
    <tr>
		<td colspan="3"> Tax / Ppn </td>
		<td class="price"> <?php echo idr_format($tax); ?>,-</td>
	</tr>
    
        <tr>
		<td colspan="3"><b> Total </b></td>
		<td class="price"> <b> <?php echo idr_format($total); ?>,- </b> </td>
	</tr>
<!--------------------------------->
</table>
<div class="sometxt">
    <p>
            <?php echo $orderid; ?> <br>
            <?php echo $date.' '.waktuindo(); ?>  Opr: <?php echo $user; ?> <br>
            Terima Kasih Atas Kunjungan Anda <br>
            Periksa Barang sebelum dibeli <br>
            Barang yang sudah dibeli tidak bisa ditukar atau dikembalikan
    </p>
</div>
</body>
</html>