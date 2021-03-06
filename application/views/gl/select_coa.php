<!-- DIALOG KODE PERKIRAAN -->
<div id='dlgCoa' class="easyui-dialog"  
style="width:600px;height:380px;padding:5px 5px"
closed="true" toolbar="#tbCoa">
		<table id="dgCoa" class="easyui-datagrid"  width='100%'
		data-options="toolbar: '', singleSelect: true, fitColumns: true, 
			url: '' ">
			<thead>
				<tr>
					<th data-options="field:'account',width:80">Kode Akun</th>
					<th data-options="field:'account_description',width:250">Nama Perkiraan</th>
					<th data-options="field:'id',width:30">ID</th>
				</tr>
			</thead>
		</table>
</div>
<div id="tbCoa" class='box-gradient'>
	Enter Text: <input  id="search_coa" style='width:180' name="search_coa">
	<a href="#" class="easyui-linkbutton" iconCls="icon-search"  
	onclick="lookup_coa();return false;">Filter</a>        
	<a href="#" class="easyui-linkbutton" iconCls="icon-ok" onclick="select_coa();return false;">Select</a>
</div>
<script type="text/javascript">
	function lookup_coa() {
		$('#dlgCoa').dialog('open').dialog('setTitle','Cari kode perkiraan');
		coa=$('#search_coa').val();
		$('#dgCoa').datagrid({url:'<?=base_url()?>index.php/coa/select/'+coa});
		$('#dgCoa').datagrid('reload');
	}
	function select_coa() {
		var row = $('#dgCoa').datagrid('getSelected');
		if (row){
			$('#account').val(row.account);
			$('#description').val(row.account_description);
			$('#dlgCoa').dialog('close');
		}			
	}
</script>
<!-- END DIALOG KODE PERKIRAAN -->
