<div class='thumbnail box-gradient'>
<?=link_button("Help","load_help()","help")?>
</div>
<legend>PROSES REKOMENDASI HASIL SURVEY</legend>
<p>Dibawah ini adalah daftar aplikasi permohonan kredit yang sudah disurvey
silahkan contreng rekomendasi nomor aplikasi tersebut untuk diteruskan 
ke GM RISK untuk direview dan diproses approval kredit.</p>
<p>Tekan tombol <strong>SIMPAN</strong> untuk mulai menyimpan data</p>

<div class='thumbnail'>
	<button type="button" onclick="save()" class="btn btn-info">Recomend</button>
	<button type="button" onclick="not_recomend()" class="btn btn-warning">Not Recomend</button>
</div>	
<? 
echo form_open('',array("action"=>"","name"=>"frmMain","id"=>"frmMain"));
echo $surveyed;
echo form_close();
?>
<p>&nbsp</p>
<div id="divButton" style="display:none">
	<button type="button" onclick="view_survey();return false;" class="btn btn-info">View Survey</button>
	<button type="button" onclick="view_score();return false;" class="btn btn-info">View Score</button>
</div>
<div id='divSpk'> 

</div>

<script language="javascript">
	var m_app_id="";
  	function save(){
		if(!confirm('Data sudah benar ? Yakin mau disimpan ?')) return false;
		url='<?=base_url()?>index.php/leasing/risk/save';
		$('#frmMain').form('submit',{
			url: url, onSubmit: function(){	return $(this).form('validate'); },
			success: function(result){
				var result = eval('('+result+')');
				if (result.success){
					log_msg('Data sudah tersimpan.');
					window.parent.location.reload(); 
				} else {
					log_err(result.msg);
				}
			}
		});
  	}
  	function not_recomend(){
		if(!confirm('Data sudah benar ? Yakin mau disimpan ?')) return false;
		url='<?=base_url()?>index.php/leasing/risk/not_recomend';
		$('#frmMain').form('submit',{
			url: url, onSubmit: function(){	return $(this).form('validate'); },
			success: function(result){
				var result = eval('('+result+')');
				if (result.success){
					log_msg('Data sudah tersimpan.');
					window.parent.location.reload(); 
				} else {
					log_err(result.msg);
				}
			}
		});
  	}
	function view_spk(app_id){
		m_app_id=app_id;
		view_survey();
	}
	function view_survey(){
		$("#divButton").fadeIn("fast");
		var url="<?=base_url()?>index.php/leasing/risk/view/"+m_app_id;
		add_tab_parent("survey_"+m_app_id,url);
		$("#divSpk").fadeIn('slow');
	}
	function view_score(){
		var url="<?=base_url()?>index.php/leasing/scoring/view_result/"+m_app_id;
		add_tab_parent("scoring_"+m_app_id,url);
	}
	function load_help() {
		window.parent.$("#help").load("<?=base_url()?>index.php/help/load/risk");
	}
	
</script>