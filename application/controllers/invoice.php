<?php if(!defined('BASEPATH')) exit('No direct script access allowd');

class Invoice extends CI_Controller {
    private $limit=10;
    private $sql="select i.invoice_number,i.invoice_date,i.amount,i.posted, 
            i.sold_to_customer,c.company,i.salesman,c.city,i.warehouse_code
            from invoice i
            left join customers c on c.customer_number=i.sold_to_customer
            where  invoice_type='i' ";
    private $controller='invoice';
    private $primary_key='invoice_number';
    private $file_view='sales/invoice';
    private $table_name='invoice';
	function __construct()
	{
		parent::__construct();
		if(!$this->access->is_login())redirect(base_url());
 		$this->load->helper(array('url','form','browse_select','mylib_helper'));
        $this->load->library('sysvar');
        $this->load->library('javascript');
        $this->load->library('template');
		$this->load->library('form_validation');
		$this->load->model('invoice_model');
		$this->load->model('customer_model');
        $this->load->model('inventory_model');
        $this->load->model('type_of_payment_model');
		$this->load->model('salesman_model');
	}
	function nomor_bukti($add=false)
	{
		$key="Invoice Numbering";
		if($add){
		  	$this->sysvar->autonumber_inc($key);
		} else {			
			$no=$this->sysvar->autonumber($key,0,'!FJ~$00001');
			for($i=0;$i<100;$i++){			
				$no=$this->sysvar->autonumber($key,0,'!FJ~$00001');
				$rst=$this->invoice_model->get_by_id($no)->row();
				if($rst){
				  	$this->sysvar->autonumber_inc($key);
				} else {
					break;					
				}
			}
			return $no;
					}
	}

	function set_defaults($record=NULL){
		$data=data_table($this->table_name,$record);
		 
        $data['library_src'] = $this->jquery->script();
        $data['script_head'] = $this->jquery->_compile();
		$data['mode']='';
		$data['message']='';
        $data['warehouse_code']=$this->access->cid;
		if($record==NULL)$data['invoice_date']= date("Y-m-d");
		if($record==NULL)$data['invoice_number']=$this->nomor_bukti();
        $data['invoice_type']='I';
		 $data['summary_info']='';
		$data['customer_info']='';
		$data['discount_amount']=0;
		$data['tax_amount']=0;
		return $data;
	}
	function index()
	{          
		if(!$this->access->is_login()){
		    redirect(base_url());
			exit;
		}				
        $this->browse();
	}
	function get_posts(){
            $data=data_table_post($this->table_name);
            return $data;
	}
	function add()
	{
		 $data=$this->set_defaults();
		 $this->_set_rules();
		 if ($this->form_validation->run()=== TRUE){
			$data=$this->get_posts(); 
			$data['invoice_number']=$this->nomor_bukti();
			$data['invoice_type']='I';
			$this->invoice_model->save($data);
			$this->nomor_bukti(true);
			$id=$data['invoice_number'];
            $this->view($id,'Finish');
   		} else {
			$this->load->model('invoice_lineitems_model');                       
			$data['mode']='add';
			$data['message']='';
            $data['sold_to_customer']=$this->input->post('sold_to_customer');
//            $data['customer_list']=$this->customer_model->select_list();
			$data['salesman_list']=$this->salesman_model->select_list();
            $data['amount']=$this->input->post('amount');
            $data['payment_terms_list']=$this->type_of_payment_model->select_list();
			$this->template->display_form_input($this->file_view,$data,'');			
		}
	}
	function save()
	{
		$mode=$this->input->post('mode');
		if($mode=="add"){
	        $id=$this->nomor_bukti();
		} else {
			$id=$this->input->post('invoice_number');			
		}
		$data['invoice_date']=$this->input->post('invoice_date');
		$data['sold_to_customer']=$this->input->post('sold_to_customer');
		$data['salesman']=$this->input->post('salesman');
		$data['payment_terms']=$this->input->post('payment_terms');
		$data['due_date']=$this->input->post('due_date');
		$data['comments']=$this->input->post('comments');			
		$data['sales_order_number']=$this->input->post('sales_order_number');
		$data['invoice_number']=$id;
		$data['invoice_type']='I';
		$data['type_of_invoice']='Simple';
        $this->session->set_userdata('invoice_number',$id);
		 
		if($mode=="add"){
			$ok=$this->invoice_model->save($data);
		} else {
			$ok=$this->invoice_model->update($id,$data);			
		}
		if ($ok){
			if($mode=="add")$this->nomor_bukti(true);
			echo json_encode(array('success'=>true,'invoice_number'=>$id));
		} else {
			echo json_encode(array('msg'=>'Some errors occured.'));
		}
	}
	function update()
	{
		 $data=$this->set_defaults();              
		 $this->_set_rules();
 		 $id=$this->input->post('invoice_number');
		 if ($this->form_validation->run()=== TRUE){
			$data=$this->get_posts();
			$data['invoice_type']='I'; 
			$this->invoice_model->update($id,$data);
            $message='Update Success';
		} else {
			$message='Error Update';
		}
                
 		$this->view($id,$message);		
	}
	function add_item(){
        	$nomor=$this->input->get('invoice_number');            
            if(!$nomor){
                $data['message']='Nomor faktur tidak diisi.!';
				echo $data['message'];
				return false;
            }
            $data['invoice_number']=$nomor;
            $data['item_lookup']=$this->inventory_model->item_list();
            $this->load->view('sales/invoice_add_item',$data);
   }
    function save_item(){
        $item_no=$this->input->post('item_number');
		$faktur=$this->input->post('invoice_number_item');
        if(!($item_no||$faktur)){
        	$msg='Kode barang atau nomor faktur tidak diisi !';
        }

		$id=$this->input->post('line_number');
		if($id!='')$data['line_number']=$id;

        $data['invoice_number']=$faktur;
        $data['item_number']=$item_no;
        $data['quantity']=$this->input->post('quantity');
        $data['unit']=$this->input->post('unit');
        $data['price']=$this->input->post('price');
        $data['cost']=$this->input->post('cost');			
        $data['discount']=$this->input->post('discount');			

        $item=$this->inventory_model->get_by_id($data['item_number'])->row();
		if($item){
            $data['description']=$item->description;
		}
		if($data['cost']==0)$data['cost']=$item->cost;
        $gross=$data['quantity']*$data['price'];
		$disc_amount=$data['discount']*$gross;
		$data['amount']=$gross-$disc_amount;
	
        $this->load->model('invoice_lineitems_model');
		
		if($id!=''){
			$ok=$this->invoice_lineitems_model->update($id,$data);
		} else {
        	$ok=$this->invoice_lineitems_model->save($data);
		}
//		$msg=var_dump($data);
		//$this->invoice_model->recalc($faktur);
		 
		if ($ok){
			echo json_encode(array('success'=>true));
		} else {
			echo json_encode(array('msg'=>'Some errors occured.'));
		}
    }        
    function delete_item($id=0){
		$id=urldecode($id);
    	if($id==0)$id=$this->input->post('line_number');
        $this->load->model('invoice_lineitems_model');
        if($this->invoice_lineitems_model->delete($id)) {
			echo json_encode(array('success'=>true));
		} else {
			echo json_encode(array('msg'=>'Some errors occured.'));
		}
    }        
	function view($id,$message=null){
		$id=urldecode($id);
		 $data['id']=$id;
		 $this->invoice_model->recalc($id);
		 $model=$this->invoice_model->get_by_id($id)->row();
		 $data=$this->set_defaults($model);
		 $data['mode']='view';
         $data['message']=$message;
         $data['customer_list']=$this->customer_model->select_list();  
         $data['customer_info']=$this->customer_model->info($data['sold_to_customer']);
		 $data['salesman_list']=$this->salesman_model->select_list();
		 $data['payment_amount']=$this->invoice_model->amount_paid;
		 $data['retur_amount']=$this->invoice_model->retur_amount;
		 $data['crdb_amount']=$this->invoice_model->crdb_amount;
		 $data['saldo']=$this->invoice_model->saldo;
		 $data['amount']=$model->amount;
		 $data['subtotal']=$model->subtotal;
		 $data['discount']=$model->discount;
			
		$data['salesman_list']=$this->salesman_model->select_list();
        $data['payment_terms_list']=$this->type_of_payment_model->select_list();
		$data['summary_info']=$this->summary($id);
			
         $menu='sales/menu_invoice';
		 $this->session->set_userdata('_right_menu',$menu);
         $this->session->set_userdata('invoice_number',$id);
         $this->template->display('sales/invoice',$data);                 
	}
	 // validation rules
	function _set_rules(){	
		 $this->form_validation->set_rules('invoice_number','Nomor Faktur', 'required|trim');
		 $this->form_validation->set_rules('invoice_date','Tanggal','callback_valid_date');
		 $this->form_validation->set_rules('sold_to_customer','Pelanggan', 'required|trim');
	}
	 // date_validation callback
	function valid_date($str)
	{
	 if(!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$str))
	 {
		 $this->form_validation->set_message('valid_date','Format tanggal salah, seharusnya yyyy-mm-dd');
		 return false;
	 } else {
	 	return true;
	 }
	}
	function search()
	{
		$this->browse();
		
	}
	
    function browse($offset=0,$limit=50,$order_column='sales_order_number',$order_type='asc'){

	
		$data['controller']=$this->controller;
		$data['fields_caption']=array('Nomor Faktur','Tanggal','Jumlah','Posted','Kode Cust','Nama Customer',
			'Salesman','Kota','Gudang');
		$data['fields']=array('invoice_number','invoice_date','amount','posted', 
            'sold_to_customer','company','salesman','city','warehouse_code');
		$data['field_key']='invoice_number';
		$data['caption']='DAFTAR FAKTUR PENJUALAN';
		$data['posting_visible']=true;

		$this->load->library('search_criteria');
		
		$faa[]=criteria("Dari","sid_date_from","easyui-datetimebox");
		$faa[]=criteria("S/d","sid_date_to","easyui-datetimebox");
		$faa[]=criteria("Nomor Faktur","sid_number");
		$faa[]=criteria("Pelanggan","sid_cust");
		$faa[]=criteria("Salesman","sid_salesman");
		$faa[]=criteria("Posted","sid_posted");
		
		$data['criteria']=$faa;
        $this->template->display_browse2($data);            
    }
    function browse_data($offset=0,$limit=100,$nama=''){
    	$nama=$this->input->get('sid_cust');
		$no=$this->input->get('sid_number');
		$d1= date( 'Y-m-d H:i:s', strtotime($this->input->get('sid_date_from')));
		$d2= date( 'Y-m-d H:i:s', strtotime($this->input->get('sid_date_to')));
        $sql=$this->sql;
		if($no!=''){
			$sql.=" and invoice_number='".$no."'";
		} else {
			$sql.=" and invoice_date between '$d1' and '$d2'";
			if($nama!='')$sql.=" and company like '$nama%'";	
			if($this->input->get('sid_salesman')!='')$sql.=" and salesman like '".$this->input->get('salesman')."%'";
			if($this->input->get('sid_posted')!=''){
				if($this->input->get('sid_posted')=='1'){
					$sql.=" and posted=true";
				} else {
					$sql.=" and posted=false";				
				}
			}
		}
        $sql.=" limit $offset,$limit";
        echo datasource($sql);
    }	 
	function amount_paid($faktur){
			$faktur=urldecode($faktur);
			return $this->invoice_model->paid_amont($faktur);}
	function amount_retur($faktur){
			$faktur=urldecode($faktur);
			return $this->invoice_model->retur_amount($faktur);}
	function amount_crdb($faktur){
			$faktur=urldecode($faktur);
			return $this->invoice_model->crdb_amount($faktur);}
	
	function delete($id){
		$id=urldecode($id);
		$this->load->model("periode_model");
		$this->load->model("invoice_model");
		$q=$this->invoice_model->get_by_id($id);
		if($this->periode_model->closed($q->row()->invoice_date)){
				$message="Periode sudah ditutup tidak bisa dihapus !";
				$this->view($id,$message);
				return false;
		}
		$this->load->model('jurnal_model');
		 
		if($this->jurnal_model->get_by_gl_id($id)->row()) {
			$message="Sudah dijurnal tidak bisa dihapus !";
			$this->view($id,$message);
			return false;
		}
		
		$cnt_pay=$this->db->query("select count(1) as cnt from payments where invoice_number='$id'")->row()->cnt;
		if($cnt_pay){
			$message="Faktur ini sudah ada pembayaran tidak bisa dihapus !";
			$this->view($id,$message);
			return false;
		}

		if ($this->amount_retur($id)>0){
			$message="Faktur ini sudah ada retur tidak bisa dihapus !";
			$this->view($id,$message);
			return false;
		}
		if ($this->amount_crdb($id)>0){
			$message="Faktur ini sudah ada credit memo tidak bisa dihapus !";
			$this->view($id,$message);
			return false;
		}
	 	$this->invoice_model->delete($id);
        $this->browse();
	}
    function detail(){
        $data['invoice_date']=isset($_GET['invoice_date'])?$_GET['invoice_date']:'';
        $data['sold_to_customer']=isset($_GET['sold_to_customer'])?$_GET['sold_to_customer']:'';
        $data['payment_terms']=isset($_GET['payment_terms'])?$_GET['payment_terms']:'';
        $data['comments']=isset($_GET['comments'])?$_GET['comments']:'';
		$data['salesman']=isset($_GET['salesman'])?$_GET['salesman']:'';
		$data['invoice_number']=$this->nomor_bukti();	// ambil nomor terbaru
        $this->invoice_model->save($data);
        $this->nomor_bukti(true);
		header("location: ".base_url()."index.php/invoice/view/".$data['invoice_number']);
    }
	function view_detail($nomor){
		$nomor=urldecode($nomor);
        $sql="select p.item_number,i.description,p.quantity 
        ,p.unit,p.price,p.amount,p.line_number
        from invoice_lineitems p
        left join inventory i on i.item_number=p.item_number
        where invoice_number='$nomor'";
        $table=browse_simple($sql);
		$btn=link_button("Addnew", "addnew_item()","edit");
		$btn.=link_button("Remove", "remove_item()","remove");
		$btn.=link_button("Refresh", "refresh_items()","ok");
		$scr="
			<script src=\"".base_url()."js/jquery/jquery-1.8.0.min.js\"></script>
			<script src=\"".base_url()."js/jquery-ui/jquery.easyui.min.js\"></script>
		";
		echo $btn.$table;
		
   }
    function print_faktur($nomor){
		$nomor=urldecode($nomor);
        $invoice=$this->invoice_model->get_by_id($nomor)->row();
		$saldo=$this->invoice_model->recalc($nomor);
		$data['invoice_number']=$invoice->invoice_number;
		$data['invoice_date']=$invoice->invoice_date;
		$data['sold_to_customer']=$invoice->sold_to_customer;
		$data['comments']=$invoice->comments;
		$data['sales_order_number']=$invoice->sales_order_number;
		$data['due_date']=$invoice->due_date;
		$data['amount']=$invoice->amount;
		$data['sub_total']=$invoice->subtotal;
		$data['discount']=$invoice->discount;
		$data['disc_amount']=$invoice->subtotal*$invoice->discount;
		$data['freight']=$invoice->freight;
		$data['others']=$invoice->other;
		$data['tax']=$invoice->sales_tax_percent;
		$data['tax_amount']=$invoice->sales_tax_percent*($data['sub_total']-$data['disc_amount']);
        $this->load->view('sales/rpt/print_faktur',$data);    	
    }
	function select_list(){
		
		$q=$this->input->get('q');
		$cst=$this->input->get('cust');
		if($q){
			if($q=='not_paid'){				
				$sql="select invoice_number,invoice_date,due_date,amount,payment_terms
				from invoice 
				where invoice_type='I' and (paid=false or isnull(paid))
				and sold_to_customer='$cst'";
				$query=$this->db->query($sql);
				$i=0;
				$this->load->model('invoice_model');
				$data='';
				foreach($query->result() as $row){
					$saldo=$this->invoice_model->recalc($row->invoice_number);
					$data[$i][]=$row->invoice_number;
					$data[$i][]=$row->invoice_date;
					$data[$i][]=$row->due_date;
					$data[$i][]=$row->payment_terms;
					$data[$i][]=$row->amount;
					$data[$i][]=$saldo;
					$data[$i][]=form_input('bayar[]');
					$data[$i][]=form_hidden('faktur[]',$row->invoice_number);
					$i++;
				}
				
				$this->load->library('browse');
				$header=array('Faktur','Tanggal','Jth Tempo','Termin','Jumlah','Saldo','Bayar');
				$this->browse->set_header($header);
				$this->browse->data($data);
//				$this->browse->add_row(array($row->invoice_number,
//					$row->invoice_date,$row->due_date,$row->payment_terms));
				echo $this->browse->refresh();
			}
		}
	}
	function invoice_not_paid($customer_number){
		$customer_number=urldecode($customer_number);
		$this->load->model('invoice_model');
		$sql="select invoice_number,invoice_date,due_date,amount,payment_terms 
		from invoice
		where invoice_type='I' and (paid=false or isnull(paid))
		and sold_to_customer='$customer_number'";
 
		$query=$this->db->query($sql);
		$i=0;
		$rows[0]='';
		if($query){ 
			foreach($query->result_array() as $row){
				$nomor=$row['invoice_number'];
				$saldo=$this->invoice_model->recalc($nomor);
				if($saldo!=0){
					$row['amount']=number_format($row['amount']);
					$row['saldo']=number_format($saldo);
					$row['bayar']=form_input("bayar[]","","style='width:100px'");
					$row['invoice_number']=$nomor.form_hidden("faktur[]",$nomor);
					$rows[$i++]=$row;
				}
			};
		}
		$data['total']=$i;
		$data['rows']=$rows;
					
		echo json_encode($data);
	}
		
	function payment($cmd,$faktur){
		$faktur=urldecode($faktur);
		if($cmd=="list"){
	        $sql="select p.no_bukti,p.date_paid,p.how_paid,p.amount_paid 
	        from payments p
	        where p.invoice_number='$faktur'";
	        $table=browse_simple($sql,'Daftar Pembayaran',600,600,'dgPay'); 
			$btn=link_button("Addnew", "addnew_payment()","edit");
			$btn.=link_button("Remove", "remove_payment()","remove");
			$btn.=link_button("Refresh", "refresh_payment()","ok");
			$scr="
				<script src=\"".base_url()."js/jquery/jquery-1.8.0.min.js\"></script>
				<script src=\"".base_url()."js/jquery-ui/jquery.easyui.min.js\"></script>
			";
			echo $btn.$table;
		}
			
	}
	function returx($cmd,$faktur){
		$faktur=urldecode($faktur);
		if($cmd=="list"){
	        $sql="select i.invoice_number as no_retur,i.invoice_date as tanggal,il.item_number,il.description,
	        il.quantity,il.unit,il.line_number
	        from invoice i left join invoice_lineitems il on il.invoice_number=i.invoice_number
	        where i.invoice_type='R' and i.sales_order_number='$faktur'";
	        $table=browse_simple($sql,'Daftar Retur',600,600,'dgRetur'); 
			$btn=link_button("Addnew", "addnew_retur()","edit");
			$btn.=link_button("Remove", "remove_retur()","remove");
			$btn.=link_button("Refresh", "refresh_retur()","ok");
			$scr="
				<script src=\"".base_url()."js/jquery/jquery-1.8.0.min.js\"></script>
				<script src=\"".base_url()."js/jquery-ui/jquery.easyui.min.js\"></script>
			";
			echo $btn.$table;
		}
	}
	function retur($faktur) {
		$faktur=urldecode($faktur);
		$sql="select i.invoice_number,invoice_date,item_number,description,quantity,unit,price,i.amount,il.warehouse_code 
		from invoice i left join invoice_lineitems il on il.invoice_number=i.invoice_number where invoice_type='R'
		and i.your_order__='$faktur' 
		order by i.invoice_number,invoice_date";
		echo datasource($sql);
	}
	function crdb($cmd,$faktur){
		$faktur=urldecode($faktur);
		if($cmd=="list"){
	        $sql="select c.kodecrdb,c.tanggal,c.transtype,c.amount,c.keterangan
	        from crdb_memo c
	        where c.docnumber='$faktur'";
	        $table=browse_simple($sql,'Daftar Cr Dr Memo',600,600,'dgCrDb'); 
			$btn=link_button("Addnew", "addnew_crdb()","edit");
			$btn.=link_button("Remove", "remove_crdb()","remove");
			$btn.=link_button("Refresh", "refresh_crdb()","ok");
			$scr="
				<script src=\"".base_url()."js/jquery/jquery-1.8.0.min.js\"></script>
				<script src=\"".base_url()."js/jquery-ui/jquery.easyui.min.js\"></script>
			";
			echo $btn.$table;
		}
	}
	function jurnal($cmd,$faktur){
		$faktur=urldecode($faktur);
		echo "cmd=".$cmd." faktur=".$faktur;	
	}
	function summary($faktur){
		$faktur=urldecode($faktur);
		$this->load->model('invoice_model');
		$this->invoice_model->recalc($faktur);
		return "<table><tr><td>Invoice Amount: </td><td>".number_format($this->invoice_model->amount)."</td></tr>"
			."<tr><td>Payment Amount: </td><td>".number_format($this->invoice_model->amount_paid)."</td></tr>"
			."<tr><td>Retur Amount: </td><td>".number_format($this->invoice_model->retur_amount)."</td></tr>"
			."<tr><td>CrDb Amount: </td><td>".number_format($this->invoice_model->crdb_amount)."</td></tr>"
			."<tr><td>Balance Amount: </td><td>".number_format($this->invoice_model->saldo)."</td></tr></table>"; 
	}
	function grafik_penjualan(){
		header('Content-type: application/json');
		$data['label']="Sales By Month";
		$data['data']=$this->trend_penjualan();
		echo json_encode($data);
	}
	function trend_penjualan()
	{
		$sql="select DATE_FORMAT(`invoice_date`,'%Y-%m') as prd,
		sum(p.amount) as sum_amount 
		from invoice p
		where invoice_type='I' and year(p.invoice_date)=".date('Y')."
		group by DATE_FORMAT(`invoice_date`,'%Y-%m')
		order by p.invoice_date asc
		limit 0,10";
		$query=$this->db->query($sql);
		$data[0]=0;
		foreach($query->result() as $row){
			$prd=$row->prd;
			if($prd=="")$prd="00-00";
			$amount=$row->sum_amount;
			if($amount==null)$amount=0;
			if($amount>0)$amount=round($amount/1000);
			$data[]=array(substr($prd,0,10),$amount);
		}
		return $data;
	}
	function daftar_saldo_faktur()
	{
		$sql="select invoice_number,invoice_date,amount,company,due_date  
		from invoice i left join customers c on c.customer_number=i.sold_to_customer
		where invoice_type='I' and due_date>=".date('Y-m-d')."  
		order by invoice_date   		
		limit 5
		";
		echo datasource($sql);
	}
	function omzet_salesman() {
		$sql="select salesman,sum(amount) as jumlah 
		from invoice where invoice_type='I' and year(invoice_date)=".date('Y')." 
		group by salesman";
		echo datasource($sql);
	}
	function items($nomor,$type='')
	{
		$nomor=urldecode($nomor);
		$sql="select p.item_number,i.description,p.quantity,p.cost 
		,p.unit,p.price,p.discount,p.amount,p.line_number,p.revenue_acct_id,coa.account,coa.account_description
		from invoice_lineitems p
		left join inventory i on i.item_number=p.item_number
		left join chart_of_accounts coa on coa.id=p.revenue_acct_id
		where invoice_number='$nomor'";
		 
		echo datasource($sql);
	}
	function recalc($nomor=''){
		$nomor=urldecode($nomor);
		if($nomor!=''){
			if(($_GET['discount'])){
				$sql="update invoice set discount=".$_GET['discount'].",sales_tax_percent=".$_GET['tax']
				.",freight=".$_GET['freight'].",other=".$_GET['others']." where invoice_number='$nomor'";
				$rs=mysql_query($sql);
			}
			
			$saldo=$this->invoice_model->recalc($nomor);
			
			$sub_total=$this->invoice_model->sub_total;
			$data=array('sub_total'=>$sub_total,'amount'=>$this->invoice_model->amount,
			"retur"=>$this->invoice_model->retur_amount,"crdb"=>$this->invoice_model->crdb_amount,
			"payment"=>$this->invoice_model->amount_paid,"saldo"=>$this->invoice_model->saldo,
			"disc_amount_1"=>$this->invoice_model->disc_amount_1,
			"tax"=>$this->invoice_model->tax);
			echo json_encode($data);				
			
		}
	}
	function customer($cust){
		$cust=urldecode($cust);
		$sql="select invoice_number,invoice_date,salesman,payment_terms,amount
		from invoice where invoice_type='I' and sold_to_customer='$cust'";
		echo datasource($sql);
	}
	function select($customer=''){
		$customer=urldecode($customer);
		$s="select invoice_number,invoice_date,payment_terms from invoice 
		where invoice_type='I'";
		if($customer!="")$s.=" and sold_to_customer='".$customer."'";
	 
		echo datasource($s);
	}
	function list_item($faktur) {
		$faktur=urldecode($faktur);
		$s="select item_number,description,quantity,unit 
		from invoice_lineitems 
		where invoice_number='$faktur'";
		echo datasource($s);
	}
	function unposting($nomor) {
		$nomor=urldecode($nomor);
		$message=$this->invoice_model->unposting($nomor);		
		$this->view($nomor);
	}
	function posting($nomor)
	{
		$nomor=urldecode($nomor);
		$message=$this->invoice_model->posting($nomor);
		$this->view($nomor);
	}
	function find($nomor){
		$nomor=urldecode($nomor);
		$this->load->model('invoice_model');
		$saldo=$this->invoice_model->recalc($nomor);
		$query=$this->invoice_model->get_by_id($nomor)->row();
		$data['invoice_date']=$query->invoice_date;
		$data['amount']=number_format($query->amount);
		$data['saldo']=number_format($saldo);
		
		echo json_encode($data);
		
	}
	function list_crdb($faktur)
	{
		$nomor=urldecode($nomor);
		$sql="select kodecrdb as nomor,tanggal, amount 
			from crdb_memo i
			where docnumber='$faktur'";
		echo datasource($sql);				
			
	}
	function posting_all() {
		$d1= date( 'Y-m-d H:i:s', strtotime($this->input->get('sid_date_from')));
		$d2= date( 'Y-m-d H:i:s', strtotime($this->input->get('sid_date_to')));
		$sql="select distinct invoice_number from invoice"; 
		$sql.=" where invoice_type in ('I') and (posted is null or posted=false) and invoice_date between '$d1' and '$d2'";
		
		if($q=$this->db->query($sql)){
			foreach($q->result() as $r){
				echo "<p>Posting..
				<a href=".base_url()."index.php/invoice/view/".$r->invoice_number."
				class='info_link'>".$r->invoice_number."</a> : ";
				$message=$this->invoice_model->posting($r->invoice_number);
				if($message!=''){
					echo ': '.$message;
				}
				echo "</p>";
			}
		}
		echo "<p>Finish.</p>";
	}	
	function save_pos()
	{
		$data=$this->input->get();
        $id=$this->nomor_bukti();
		$data_head['invoice_number']=$id;
		$data_head['invoice_date']=date("Y-m-d H:i:s");
		$data_head['sold_to_customer']="CASH";
		$data_head['salesman']=user_id();
		$data_head['payment_terms']="CASH";
		$data_head['invoice_type']='I';
		$data_head['type_of_invoice']='Simple';
		$data_head['due_date']=$data_head['invoice_date'];
		 
		$ok=$this->invoice_model->save($data_head);
		if($ok){
			$this->nomor_bukti(true);
			$arItems=$data['items'];
			//arItem.push([td[0],td[1],td[2],q,p,t]);
			$total=0;
			for($i=0;$i<count($arItems);$i++){
				$detail=$arItems[$i];
				$data_detail['invoice_number']=$id;
				$data_detail['item_number']=$detail[0];
				$data_detail['description']=$detail[1];
				$data_detail['unit']=$detail[2];
				$data_detail['quantity']=$detail[3];
				$data_detail['price']=$detail[4];
				$data_detail['amount']=$detail[5];
				$this->db->insert("invoice_lineitems",$data_detail);
				$total=$total+$detail[5];
			}
			$this->db->update("invoice",array("paid"=>1,"amount"=>$total));
			$payment=$data['payment'];
			$cash=$payment['cash'];
			$card=$payment['card'];
			$kembali=$cash-$total;
			if($kembali>0){	
				$cash=$cash-$kembali;
			}
			if($cash>0){
				$this->db->insert("payments",array("invoice_number"=>$id,
					"date_paid"=>date("Y-m-d H:i:s"),"how_paid"=>"CASH",
					"amount_paid"=>$cash,"amount_alloc"=>$kembali));
			}
			if($payment['card']>0){
				$this->db->insert("payments",array("invoice_number"=>$id,
					"date_paid"=>date("Y-m-d H:i:s"),"how_paid"=>"CARD",
					"amount_paid"=>$card,"amount_alloc"=>$kembali));
			
			}
			echo json_encode(array('success'=>true,'invoice_number'=>$id));
		} else {
			echo json_encode(array('msg'=>'Some errors occured.'.mysql_error()));
		}
	}	
}
