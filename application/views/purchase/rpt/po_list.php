<?
//var_dump($_POST);
?>
<?
     $CI =& get_instance();
     $CI->load->model('company_model');
     $model=$CI->company_model->get_by_id($CI->access->cid)->row();
	$date1= date('Y-m-d H:i:s', strtotime($CI->input->post('txtDateFrom')));
	$date2= date('Y-m-d H:i:s', strtotime($CI->input->post('txtDateTo')));
	$supplier= $CI->input->post('text1');
?>
<link href="<?php echo base_url();?>/themes/standard/style_print.css" rel="stylesheet">
<table cellspacing="0" cellpadding="1" border="0" width='800px'> 
     <tr>
     	<td colspan='2'><h2><?=$model->company_name?></h2></td><td colspan='2'><h2>PURCHASE ORDER SUMMARY</h2></td>     	
     </tr>
     <tr>
     	<td colspan='2'><?=$model->street?></td><td></td>     	
     </tr>
     <tr>
     	<td colspan='2'><?=$model->suite?></td>     	
     </tr>
     <tr>
     	<td>
     		<?=$model->city_state_zip_code?> - Phone: <?=$model->phone_number?>
     	</td>
     </tr>
     <tr>
     	<td>
     		Criteria: Dari Tanggal: <?=$date1?> s/d : <?=$date2?> Supplier: <?=$supplier?>
     	</td>
     </tr>
     <tr><td colspan=4 style='border-bottom: black solid 1px'></td></tr>
     <tr>
     	<td colspan="8">
 		<table class='titem'>
 		<thead>
 			<tr><td>Nomor PO</td><td>Tanggal</td><td>Termin</td><td>Due</td>
 				<td>Kode Supplier</td><td>Nama Supplier</td><td>Kota</td>
 				<td>Phone</td><td>Jumlah</td><td>Received?</td>
 			</tr>
 		</thead>
 		<tbody>
     			<?
	 		       $sql="select p.purchase_order_number,p.po_date,p.terms,p.supplier_number,
	 		        s.supplier_name,p.amount,p.received,s.city,s.phone,p.due_date   
	                from purchase_order p
	                left join suppliers s on s.supplier_number=p.supplier_number
	                where p.potype='O' and p.po_date between '$date1' and '$date2'";
					if($supplier!="")$sql.=" and p.supplier_number='$supplier'"; 
	                $sql.=" order by p.purchase_order_number";
			        $query=$CI->db->query($sql);
	
	     			$tbl="";
	                 foreach($query->result() as $row){
	                    $tbl.="<tr>";
	                    $tbl.="<td>".$row->purchase_order_number."</td>";
	                    $tbl.="<td>".$row->po_date."</td>";
	                    $tbl.="<td>".($row->terms)."</td>";
	                    $tbl.="<td>".($row->due_date)."</td>";
	                    $tbl.="<td>".$row->supplier_number."</td>";
	                    $tbl.="<td>".$row->supplier_name."</td>";
	                    $tbl.="<td>".$row->city."</td>";
	                    $tbl.="<td>".$row->phone."</td>";
	                    $tbl.="<td align='right'>".number_format($row->amount)."</td>";
	                    $tbl.="<td>".$row->received."</td>";
	                    $tbl.="</tr>";
	               };
				   echo $tbl;
    			?>
     	

   		</tbody>
   		</table>
     	
     	</td>
     </tr>
</table>
