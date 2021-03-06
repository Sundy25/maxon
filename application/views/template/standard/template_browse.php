<script type="text/javascript">
	CI_ROOT = "<?=base_url()?>index.php/";
	CI_BASE = "<?=base_url()?>"; 		
</script>

<div id='__section_left_content' class="col-md-9">
<?
date_default_timezone_set("Asia/Jakarta");

echo $library_src;
echo $script_head;

$width=isset($width)?$width." px":"auto";
$height=isset($height)?$height." px":"auto";
$caption=isset($caption)?$caption:$controller;
$offset=0;
$limit=100;
$def_col_width=80; 
$table_head="<thead><tr>";
for($i=0;$i<count($fields);$i++){
	$aFld=$fields[$i];
	if(is_string($aFld)){
		$fld_name=$fields[$i];
		$fld_caption=$fields_caption[$i];
	} else {
		$fld_name=$fields[$i]['name'];
		$fld_caption=$fields[$i]['caption'];
	}
    $table_head.="<th data-options='field:\"$fld_name\" ";
	if(isset($col_width[$fld_name])){
		$width=$col_width[$fld_name];
	} else {
		$width=$def_col_width;
	}
	$table_head.=", width:\"$width\" ";
	
	if(isset($fields_format_numeric)){
		if(is_num_format($fld_name,$fields_format_numeric)){
			$table_head.=",align:\"right\",editor:\"numberbox\", 
			formatter: function(value,row,index){
				if(isNumber(value)){
					return number_format(value,2,\".\",\",\");
					return value;
				} else {
					return value;
				}
			}";
		}
	} 
	$table_head.="'";
	$table_head.=">".$fld_caption."</th>";
}
$table_head.="</tr></thead>";

if(isset($_form)){
	echo load_view($_form,array('mode'=>'add'));
}

$controller_name=str_replace("/","_",$controller);

?>
<script type="text/javascript">    
    CI_CONTROL='<?=$controller?>';
    FIELD_KEY='<?=$field_key?>';
    CI_CAPTION='<?=$caption?>';
    CI_WIDTH='<?=$width?>';
    CI_HEIGHT='<?=$height?>';
	
</script>
<div class='thumbnail box-gradient' style='min-height:600px'>
<table id="dg_<?=$controller_name?>" class="easyui-datagrid", title="<?=$caption?>"
      style="height:'<?=$height?>';width:'<?=$width?>'", 
      data-options="rownumbers:true,pagination:true,pageSize:100,fitColumns:true,
      loadFilter:pagerFilter_<?=$controller_name?>,
      singleSelect:true,collapsible:true,
      url:'<?=base_url()?>index.php/<?=$controller?>/browse_data',
      toolbar:'#tb_<?=$controller_name?>'">
      
      <?=$table_head?>
      
</table>
</div>       
 <div id="tb_<?=$controller_name?>" style="padding:5px;height:auto" class='thumbnail box-gradient'>
		<div class='thumbnail box-gradient' style='font-weight:900'>
			<?=link_button("Add", "addnew_$controller_name();return false;","add","true");?>
			<?=link_button("Edit", "edit_$controller_name();return false;","edit","true");?>
			<?=link_button("Del", "del_row_$controller_name();return false;","remove","true");?>
			
			<? 
			if(isset($posting_visible)){
				echo link_button('Posting','posting_'.$controller_name."();return false;",'save');
			};
			if(isset($list_info_visible)){
				echo link_button('Info','cari_info_'.$controller_name."();return false;",'form');
			};
			if(isset($import_visible)){
				echo link_button('Import','import_'.$controller_name."();return false;",'csv');			
			}
			?>
			<?
			if(isset($export_visible)){
				echo link_button('Export','export_'.$controller_name."();return false;",'xls');
			}	
			echo "<td>".link_button('Cari','cari_'.$controller_name."();return false;",'search')."</td>";
			?>
			
		</div>
		<div class='col-md-10'>
		 
		<form id='frmSearch_<?=$controller_name?>' class='form-inline'>
			<?
			$i=0;
			$s="";
			foreach($criteria as $fa){
				$type="text";
				$val="";
				if($fa->field_class=="easyui-datetimebox"){
					$val=date("Y-m-d 00:00:00");
					if(strpos($fa->field_id,"date_to"))$val=date("Y-m-d 23:59:59");
					$s.="<div class='form-group'>";
					$s.="&nbsp<label for='$fa->field_id'>$fa->caption</label>&nbsp";
					$s.="<input type='$type' value='$val' id='$fa->field_id'  
					name='$fa->field_id' 
					class='$fa->field_class form-control' style='width:150px'>";
					$s.= "</div>";
				} else if($fa->field_class=="checkbox"){
					$s.="<div class='form-group'>";
					$s.="&nbsp<label for='$fa->field_id'>$fa->caption</label>&nbsp";
					$s .=  "<input type='checkbox' value=$val id='$fa->field_id'  
					name='$fa->field_id'>";
					$s.= "</div>";
				} else {
					$style=" ";
					$class="form-control";
					$fa->field_class=$class;
					if($fa->field_style!="")$style=$fa->field_style;
					$s.="<div class='form-group'>";
					$s.="&nbsp<label for='$fa->field_id'>$fa->caption</label>&nbsp";
					$s .=  "<input type='$type' value='$val' id='$fa->field_id'
					name='$fa->field_id'  placeholder='$fa->caption' >";
					$s.= "</div>";
				}
			}
			echo $s;
			?>
		</form>
		</div>
		<div style="font-size:9px">
			<i>***Apabila data tidak tampil ditabel ini, silahkan persempit pencarian (isi kriteria) dan tekan tombol search.</i>
		</div>
		
</div>
	<?
		if(isset($other_menu)){
			$this->load->view($other_menu);
		}
	?>
</div>

<?
function is_num_format($fld_name,$fld_fmt){
	for($i=0;$i<count($fld_fmt);$i++){
		if($fld_name==$fld_fmt[$i]){
			return true;
		}
	}
}
?>
	
<script type="text/javascript">
    function pagerFilter_<?=$controller_name?>(data){
            if (typeof data.length == 'number' && typeof data.splice == 'function'){	// is array
                    data = {
                            total: data.length,
                            rows: data,
                            search: $('#search_<?=$controller_name?>').val()
                    }
            }
            var dg = $(this);
            var opts = dg.datagrid('options');
            var pager = dg.datagrid('getPager');
            pager.pagination({
                    onSelectPage:function(pageNum, pageSize){
                            opts.pageNumber = pageNum;
                            opts.pageSize = pageSize;
                            pager.pagination('refresh',{
                                    pageNumber:pageNum,
                                    pageSize:pageSize
                            });
                            dg.datagrid('loadData',data);
                    }
            });
            if (!data.originalRows){
                    data.originalRows = (data.rows);
            }
            var start = (opts.pageNumber-1)*parseInt(opts.pageSize);
            var end = start + parseInt(opts.pageSize);
			if(data.originalRows){
				data.rows = (data.originalRows.slice(start, end));
			}
            return data;
    }
    function addnew_<?=$controller_name?>(){
        xurl=CI_ROOT+CI_CONTROL+'/add';
        window.open(xurl,"_self");
    };
    function edit_<?=$controller_name?>(){
        var row = $('#dg_<?=$controller_name?>').datagrid('getSelected');
        if (row){
            xurl=CI_ROOT+CI_CONTROL+'/view/'+row[FIELD_KEY];
	        window.open(xurl,"_self");
        }
    }
    function del_row_<?=$controller_name?>(){
			var row = $('#dg_<?=$controller_name?>').datagrid('getSelected');
			if (row){
				$.messager.confirm('Confirm','Are you sure you want to remove this line?',function(r){
					if(!r)return false;
	                xurl=CI_ROOT+CI_CONTROL+'/delete/'+row[FIELD_KEY];                             
	                xparam='';
	                $.ajax({
	                        type: "GET",
	                        url: xurl,
	                        param: xparam,
	                        success: function(result){
							try {
									var result = eval('('+result+')');
									if(result.success){
										$.messager.show({
											title:'Success',msg:result.msg
										});
										$('#dg_<?=$controller_name?>').datagrid('reload');	 
									} else {
										$.messager.show({
											title:'Error',msg:result.msg
										});
										log_err(result.msg);
									};
								} catch (exception) {		
									 
									// reload kalau output bukan json
									$('#dg_<?=$controller_name?>').datagrid('reload');	 
								}
	                        },
	                        error: function(msg){$.messager.alert('Info',"Tidak bisa dihapus baris ini !");}
	                });         
				});
		}
	}
    function cari_<?=$controller_name?>(){
    	xsearch=$('#frmSearch_<?=$controller_name?>').serialize();
	    xurl=CI_ROOT+CI_CONTROL+'/browse_data?'+xsearch;
        $('#dg_<?=$controller_name?>').datagrid({url:xurl});
        //$('#dg_<?=$controller_name?>').datagrid('reload');
    }
    function posting_<?=$controller_name?>(){
    	xsearch=$('#frmSearch_<?=$controller_name?>').serialize();
	    xurl=CI_ROOT+CI_CONTROL+'/posting_all?'+xsearch;
		$.messager.confirm('Confirm','Are you sure you want to posting all date ?',function(r){
	        window.open(xurl,"_self");
		});
    }
    function cari_info_<?=$controller_name?>(){
    	xsearch=$('#frmSearch_<?=$controller_name?>').serialize();
	    xurl=CI_ROOT+CI_CONTROL+'/list_info?'+xsearch;
		window.open(xurl,"_self");
	}
	function export_<?=$controller_name?>(){
    	xsearch=$('#frmSearch_<?=$controller_name?>').serialize();
	    xurl=CI_ROOT+CI_CONTROL+'/export_xls?'+xsearch;
        window.open(xurl,"_self");		
	}
	function import_<?=$controller_name?>(){
	    xurl=CI_ROOT+CI_CONTROL+'/import_<?=$controller_name?>';
        window.open(xurl,"_self");		
	}
	
</script>