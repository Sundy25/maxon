<LEGEND>FORMULIR PENGAJUAN KREDIT</LEGEND>
<div class="thumbnail box-gradient">
	<?
	if(!isset($show_tool))$show_tool="true";
	$show=$show_tool=="true"?true:false;
	if($show) echo link_button('Save', 'save()','save');		
	echo link_button('Print', 'print_item()','print');		
	if($show) echo link_button('Add','','add','true',base_url().'index.php/leasing/app_master/add');		
	echo link_button('Search','','search','true',base_url().'index.php/leasing/app_master');		
	if($mode=="view") echo link_button('Refresh','','reload','true',base_url().'index.php/leasing/app_master/view/'.$app_id);		
	if($show) echo link_button('Delete', 'delete_tour()','remove');		
	echo link_button('Help', 'load_help()','help');	
	$mode=="view"?$readonly=" readonly":$readonly="";
	$mode=="view"?$disable=" disable":$disable="";
	
	?>
	<a href="#" class="easyui-splitbutton" data-options="menu:'#mmOptions',iconCls:'icon-tip'">Options</a>
	<div id="mmOptions" style="width:200px;">
		<div onclick="load_help()">Help</div>
		<div>Update</div>
		<div>MaxOn Forum</div>
		<div>About</div>
	</div>
</div>
<div class="thumbnail" >	
	<form id="frmMain" method="post">
		<div class='thumbnail box-gradient'>
			<table style="width:100%"><tr>
			<td>	<?=form_checkbox('verified','',$verified,'disabled')?> Verified </td>
			<td> 	Score <?=form_input('score_value',$score_value,' style="width:40px" disabled');?></td> 
			<!-- <td> 	<?=form_dropdown('score_value',array('0'=>'None','1'=>'Tidak Sesuai','2'=>'Sesuai'),$score,'disabled');?></td>--> 
			<td>	<?=form_checkbox('confirmed','',$confirmed,'disabled')?> Confirmed </td>
			<!-- <td>	<?=form_checkbox('gm_approved','','','disabled')?> GM Approved </td> -->
			<td>	<?=form_checkbox('survey','',$surveyed,'disabled')?> Survey </td>
			<td>	<?=form_checkbox('risk_approved','',$risk_approved,'disabled')?> Risk Approved </td>
			<td>	<?=form_checkbox('recomended','',$approved,'disabled')?> Recomended</td> 		
			<td style='color:red;font-size:small'><? 
			echo $status;
			if($status=="Finish"){
				echo "</br>Nomor Kontrak: ".anchor(base_url()."index.php/leasing/loan/view/".$loan_id,$loan_id);	
			}
			?></td>
			</tr></table>
		</div>
		<table style='width:100%'>
			<tr><td colspan='4'>
			
			</td></tr>
			<tr><td>Nomor Bukti</td><td><?=form_input('app_id',$app_id,"id='app_id'")?></td>
			<td>Tanggal</td><td><?=form_input('app_date',$app_date,"class='easyui-datetimebox'")?></td></tr>
			<tr><td>Pilih Debitur</td>
				<td colspan='6'><? 
					echo form_input('cust_id',$cust_id,"id='cust_id'");
					echo link_button('','dlgFindCust_Show()','search');
					echo form_input('cust_name',$cust_name,"id='cust_name' style='width:300px' disabled");
					echo link_button('View Debitur','CustomerShow()','reload');
				?></td>
			<tr><td>Counter </td><td colspan='6'><? 
				echo form_input('counter_id',$counter_id,"id='counter_id'");
				echo link_button('','dlgFindCounter_Show()','search');
				echo form_input('counter_name',$counter_name,"id='counter_name' style='width:400px' disabled");
				?></td>
			</tr>
			<tr><td>Create By</td><td><?
			
			echo form_hidden('create_by',$create_by);
			echo $username;
			?></td>
				<td>Create Date</td><td><?=form_input('create_date',$create_date, " readonly")?></td>
			</tr>
			
		</table>
		<div class="easyui-tabsx" >
			<div title="ITEMS"> <? include_once "items.php" ?></div>
			<div title='PERHITUNGAN'><? include_once "angsuran.php" ?></div>
		</div>
		<input type='hidden' name='mode' id='mode'	value='<?=$mode?>'>
	</form>
</div>
<? 
include_once "item_form.php";
include_once "item_list.php";
include_once "cust_master_list.php";
include_once "counter_list.php";
?>
