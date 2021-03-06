<?php
class Invoice_model extends CI_Model {

private $primary_key='invoice_number';
private $table_name='invoice';

public $amount_paid=0;
public $retur_amount=0;
public $crdb_amount=0;
public $saldo=0;
public $amount=0;
public $sub_total=0;
public $warehouse_code='';
public $disc_amount_1=0;
public $tax=0;
function __construct(){
	parent::__construct();
}
function recalc($nomor){
	
    $this->load->model('payment_model');
	$this->load->model('crdb_model');
	$this->load->model('invoice_lineitems_model');
	if($nomor=='undefined')$nomor=$this->session->userdata('invoice_number');
	
    $inv=$this->get_by_id($nomor)->row();
    if($inv) {
		$this->invoice_lineitems_model->check_revenue_acct($nomor,$inv->invoice_type);
	    $this->sub_total=$this->invoice_lineitems_model->sum_total_price($nomor);
		if($inv->discount=='')$inv->discount=0;
		if($inv->sales_tax_percent=='')$inv->sales_tax_percent=0;
		
		if($inv->discount>1)$inv->discount=$inv->discount/100;
		$this->disc_amount_1=$inv->discount*$this->sub_total;
		
	    $this->amount=$this->sub_total-$this->disc_amount_1;
		
		if($inv->sales_tax_percent>1)$inv->sales_tax_percent=$inv->sales_tax_percent/100;
		$this->tax=$inv->sales_tax_percent*$this->amount;
		
		$this->amount=$this->amount+$this->tax;
		
		$this->amount=$this->amount+$inv->freight;
		$this->amount=$this->amount+$inv->other;
	
	    $this->amount_paid=$this->payment_model->total_amount($nomor);
		$this->retur_amount=$this->total_retur($nomor);
		$this->crdb_amount=$this->crdb_model->total_by_invoice($nomor);
		
	    $this->saldo=$inv->amount-$this->amount_paid
			-$this->retur_amount
			+$this->crdb_amount;
		
		$sql="update invoice set paid=";
		if($this->saldo==0){
			$sql.="true";
		} else {
			$sql.="false";
		}
		$sql.=",amount=".$this->amount.",subtotal=".$this->sub_total
		.",saldo_invoice=".$this->saldo.",disc_amount_1='".$this->disc_amount_1."',
		discount='".$inv->discount."',sales_tax_percent='".$inv->sales_tax_percent."',
		disc_amount='".$this->disc_amount_1."',tax='".$this->tax."' 
			where invoice_number='$nomor'";
		//var_dump($sql);
		
		$this->db->query($sql);

	}
    return $this->saldo;
}
	function total_retur($nomor)
	{
		$q=$this->db->query("select sum(amount) as sum_amt from invoice where invoice_type='R' 
			and your_order__='$nomor'")->row();
		if($q){
			return $q->sum_amt;
		} else {
			return 0;
		}
	}
	function paid_amont($faktur){
		$this->load->model('payment');
		return $this->payment_model->total_amount($faktur);
	}
	function retur_amount($faktur){
		return $this->total_retur($faktur);
	}
	function crdb_amount($faktur){
		$this->load->model('crdb_model');
		return $this->crdb_model->total_by_invoice($faktur);
	}
	
function get_paged_list($limit=10,$offset=0,
$order_column='',$order_type='asc')
{
    $nama='';
    if(isset($_GET['nama'])){
        $nama=$_GET['nama'];
    }
    $this->db->select('i.invoice_number,i.invoice_date,i.sold_to_customer,
        c.company,i.amount');
    $this->db->join('customers c','c.customer_number=i.sold_to_customer','left');
    $this->db->from('invoice i');
    if($nama!='') $this->db->where("c.company like '%$nama%' 
            or i.[invoice number] like '%$nama%'
            ");
    if (empty($order_column)||empty($order_type))
    { 
        $this->db->order_by($this->primary_key,'asc');
    } else {
        $this->db->order_by($order_column,$order_type);
    }
    return $this->db->get('',$limit,$offset);
}
function count_all(){
	return $this->db->count_all($this->table_name);
}

function get_by_id($id){
	 
	$this->db->where($this->primary_key,$id);
	if($row=$this->db->get($this->table_name)->row()){
		$r_item=$this->db->query("select warehouse_code from invoice_lineitems 
			where invoice_number='$id' limit 1")->row();
		if($r_item)	$this->warehouse_code=$r_item->warehouse_code;
		$terms=$row->payment_terms;
		$due_date=$row->due_date;
		if($t=$this->db->query("select days from type_of_payment where type_of_payment='$terms'")){
			if($t=$t->row())$due_date=add_date($row->invoice_date,$t->days);
		}
		$data['warehouse_code']=$this->warehouse_code;
		$data['due_date']=$due_date;
		$this->update($id,$data);
	}
	$this->db->where($this->primary_key,$id);
	return $this->db->get($this->table_name);
}
function save($data){
	$data['invoice_date']= date('Y-m-d H:i:s', strtotime($data['invoice_date']));
	$data['due_date']= date('Y-m-d H:i:s', strtotime($data['due_date']));
//	$this->db->insert($this->table_name,$data);
//	echo $this->db->_error_message();
//	return $this->db->insert_id();
	return $this->db->insert($this->table_name,$data);
}
function update($id,$data){
	
	if(isset($data['warehouse_code'])){
		$gudang=$data['warehouse_code'];	
		$this->db->query("update invoice_lineitems set warehouse_code='$gudang' 
		where invoice_number='$id'");			
		unset($data['warehouse_code']);
	}
	if(isset($data['invoice_date']))$data['invoice_date']= date('Y-m-d H:i:s', strtotime($data['invoice_date']));
	if(isset($data['due_date']))$data['due_date']= date( 'Y-m-d H:i:s', strtotime($data['due_date']));
	$this->db->where($this->primary_key,$id);
	return $this->db->update($this->table_name,$data);
}
function delete($id){
   	$this->db->where($this->primary_key,$id);
	$this->db->delete('invoice_lineitems');
	        
	$this->db->where($this->primary_key,$id);
	$this->db->delete($this->table_name);
}

    function add_item($id,$item,$qty){
        $sql="select description,retail,cost,unit_of_measure
            from inventory
            where item_number='".$item."'";
        
        $query=$this->db->query($sql);
        $row = $query->row_array(); 
         
        $data = array('invoice_number' => $id, 'item_number' => $item, 
            'quantity' => $qty,'description'=>$row['description'],
            'price' => $row['retail'],'amount'=>$row['retail']*$qty,
            'unit'=>$row['unit_of_measure']
            );
        $str = $this->db->insert_string('invoice_lineitems', $data);
        $query=$this->db->query($str);
    }
    function del_item($line){
        $query=$this->db->query("delete from invoice_lineitems
            where line_number=".$line);
    }
	function save_from_so_items($faktur,$qty_order,$from_so_line,$gudang,$ship_date){
		$this->load->model('sales_order_lineitems_model');
		$this->load->model('inventory_model');
		$this->load->model('invoice_lineitems_model');
		for($i=0;$i<=count($qty_order)-1;$i++){
			$line_number=$from_so_line[$i];
			$qty_do=$qty_order[$i];
			
			if($line_number>0){
				if($qty_do>0) {
					$so=$this->sales_order_lineitems_model->get_by_id($line_number)->row();
					$item=$this->inventory_model->get_by_id($so->item_number)->row();
					
					$data['invoice_number']=$faktur;
					$data['item_number']=$so->item_number;
					$data['description']=$so->description;
					$data['unit']=$so->unit;
					if($data['unit']=='')$data['unit']=$item->unit_of_measure;
					$data['quantity']=$qty_do;
					$data['price']=$so->price;
					$data['discount']=$so->discount;
					
					$data['amount']=$data['quantity']*$data['price'];
					$data['warehouse_code']=$gudang;	
					$data['from_line_number']=$line_number;
					$data['from_line_doc']=$so->sales_order_number;
					$data['from_line_type']="SO";
					$data['ship_date']=date('Y-m-d H:i:s', strtotime($ship_date));
					$this->invoice_lineitems_model->save($data);
					$this->db->query("update sales_order_lineitems set ship_date='".$data['ship_date']."' 
					 where line_number='".$so->line_number."'");
				 }
			}
		}
	}
	function unposting($nomor) {
		$saldo=$this->invoice_model->recalc($nomor);
		$faktur=$this->invoice_model->get_by_id($nomor)->row();

		$this->load->model("periode_model");
		if($this->periode_model->closed($faktur->invoice_date)){
			echo "ERR_PERIOD";
			return false;
		}
		// validate jurnal
		$this->load->model('jurnal_model');
		if($this->jurnal_model->del_jurnal($nomor)) {
			$data['posted']=false;
		} else {
			$data['posted']=true;
		}
		$this->invoice_model->update($nomor,$data);
	
	}
	function unposting_rang_date($date_from,$date_to){
		$this->load->model('jurnal_model');
		$date_from=date('Y-m-d H:i:s', strtotime($date_from));
		$date_to=date('Y-m-d H:i:s', strtotime($date_to));
		$s="select invoice_number 
		from invoice where invoice_type='I' 
		and invoice_date between '$date_from' and '$date_to' and ifnull(posted,false)=false 
		order by invoice_number";
		$rst_inv_hdr=$this->db->query($s);
		if($rst_inv_hdr){
			foreach ($rst_inv_hdr->result() as $r_inv_hdr) {
				$this->unposting($r_inv_hdr->invoice_number);
				echo "<br>Delete Jurnal: ".$r_inv_hdr->invoice_number;
			}
		}
		echo "<br>Finish. Please back when ready."; 
	}
	function posting($nomor) {
		$saldo=$this->recalc($nomor);
		$faktur=$this->get_by_id($nomor)->row();
		$message="";
		$this->load->model("periode_model");
		if($this->periode_model->closed($faktur->invoice_date)){
			$message="Tidak bisa posting karena periode sudah ditutup.<br>";
			return $message;
		}
		$this->load->model('jurnal_model');
		$this->load->model('chart_of_accounts_model');
		$this->load->model('company_model');
		$this->load->model('invoice_lineitems_model');

		$cid=$this->access->cid;
		$set=$this->company_model->get_by_id($cid)->row();

		$coa_tax=$set->so_tax;
		$coa_freight=$set->so_freight;
		$coa_other=$set->so_other;
		$coa_ar=$set->accounts_receivable;
		$coa_disc=$set->so_discounts_given;

		$detail=$this->invoice_lineitems_model->get_by_nomor($nomor);
		foreach($detail->result() as $item) {
			//-- posting invoice_lineitems
			//-- ambil akun dari master barang
			$r_stok=$this->db->query("select sales_account,inventory_account,cogs_account,cost,cost_from_mfg 
				from inventory where item_number='".$item->item_number."'")->row();
			if($r_stok){
				$coa_sales=$item->revenue_acct_id>0?$item->revenue_acct_id:$r_stok->sales_account;
				if($coa_sales=="" or $coa_sales=="0")	$coa_sales=$set->inventory_sales;
				$coa_stock=$r_stok->inventory_account>0?$r_stok->inventory_account:$set->inventory;
				$coa_hpp=$r_stok->cogs_account>0?$r_stok->cogs_account:$set->inventory_cogs;
				if($item->cost==0){
					$item->cost=$r_stok->cost;
					$this->db->query("update invoice_lineitems set cost=".$item->cost." where line_number=".$item->line_number);
				}
				if($item->cost==0){
					$item->cost=$r_stok->cost_from_mfg;
					$this->db->query("update invoice_lineitems set cost=".$item->cost." where line_number=".$item->line_number);
				}
			}
			
			$sales_amt=$item->price*$item->quantity;
			$disc_amt=$item->discount*$sales_amt;
			$hpp_amt=$item->cost*$item->quantity;
			if($hpp_amt>0){
				//-- posting persediaan
				$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_stock, 
					$faktur->invoice_date,0,$hpp_amt,"Inventory",$faktur->comments,$cid,$item->item_number);
				//-- posting hpp
				$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_hpp, 
					$faktur->invoice_date,$hpp_amt,0,"Cogs",$faktur->comments,$cid,$item->item_number);
			}
			//-- posting penjualan
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_sales, 
					$faktur->invoice_date,0,$sales_amt,"Sales",$faktur->comments,$cid,$item->item_number);

			if($disc_amt>0){
			//-- posting discount item
				$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_disc, 
					$faktur->invoice_date,$disc_amt,0,"Sales Discount",$faktur->comments,$cid,$item->item_number);
			} 
		}
		//-- posting piutang
		$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_ar, 
			$faktur->invoice_date,$faktur->amount,0,"Account Receivable",$faktur->comments,$cid,$faktur->sold_to_customer);
		if($faktur->disc_amount!=0){
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_disc, 
				$faktur->invoice_date,$faktur->disc_amount,0,"Sales Discount",$faktur->comments,$cid,$faktur->sold_to_customer);
		}
		if($faktur->tax!=0){
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_tax, 
				$faktur->invoice_date,0,$faktur->tax,"Sales Tax",$faktur->comments,$cid,$faktur->sold_to_customer);					
		}
		if($faktur->freight!=0){
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_freight, 
				$faktur->invoice_date,0,$faktur->freight,"Sales Freight",$faktur->comments,$cid,$faktur->sold_to_customer);					
		}
		if($faktur->other!=0){
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_other, 
				$faktur->invoice_date,0,$faktur->other,"Sales Other",$faktur->comments,$cid,$faktur->sold_to_customer);					
		}
		// validate jurnal
		if($this->jurnal_model->validate($nomor)) {	$data['posted']=true;	} else {$data['posted']=false;}
		$this->invoice_model->update($nomor,$data);
		
	
	}
	function posting_range_date($date_from,$date_to){
		$this->load->model('jurnal_model');
		$this->load->model('chart_of_accounts_model');
		$this->load->model('company_model');
		$date_from=date('Y-m-d H:i:s', strtotime($date_from));
		$date_to=date('Y-m-d H:i:s', strtotime($date_to));
		$s="select invoice_number 
		from invoice where invoice_type='I' 
		and invoice_date between '$date_from' and '$date_to' and ifnull(posted,false)=false 
		order by invoice_number";
		$rst_inv_hdr=$this->db->query($s);
		if($rst_inv_hdr){
			foreach ($rst_inv_hdr->result() as $r_inv_hdr) {
				
				echo "<br>Posting...".$r_inv_hdr->invoice_number;
				$this->posting($rst_inv_hdr->invoice_number);
						
			} // foreach rst_inv_hdr
		} // if rst_inv_hdr
		echo "<br>Finish. Please back when ready."; 
			
	} // posting
	function posting_retur($nomor) {
		$saldo=$this->recalc($nomor);
		$faktur=$this->get_by_id($nomor)->row();
		$message="";
		$this->load->model("periode_model");
		if($this->periode_model->closed($faktur->invoice_date)){
			$message="Tidak bisa posting karena periode sudah ditutup.<br>";
			return $message;
		}
		$this->load->model('jurnal_model');
		$this->load->model('chart_of_accounts_model');
		$this->load->model('company_model');
		$this->load->model('invoice_lineitems_model');

		$cid=$this->access->cid;
		$set=$this->company_model->get_by_id($cid)->row();

		$coa_tax=$set->so_tax;
		$coa_freight=$set->so_freight;
		$coa_other=$set->so_other;
		$coa_ar=$set->accounts_receivable;
		$coa_disc=$set->so_discounts_given;

		$detail=$this->invoice_lineitems_model->get_by_nomor($nomor);
		foreach($detail->result() as $item) {
			//-- posting invoice_lineitems
			//-- ambil akun dari master barang
			$r_stok=$this->db->query("select sales_account,inventory_account,cogs_account,cost,cost_from_mfg 
				from inventory where item_number='".$item->item_number."'")->row();
			if($r_stok){
				$coa_sales=$item->revenue_acct_id>0?$item->revenue_acct_id:$r_stok->sales_account;
				if($coa_sales=="" or $coa_sales=="0")	$coa_sales=$set->inventory_sales;
				$coa_stock=$r_stok->inventory_account>0?$r_stok->inventory_account:$set->inventory;
				$coa_hpp=$r_stok->cogs_account>0?$r_stok->cogs_account:$set->inventory_cogs;
				if($item->cost==0){
					$item->cost=$r_stok->cost;
					$this->db->query("update invoice_lineitems set cost=".$item->cost." where line_number=".$item->line_number);
				}
				if($item->cost==0){
					$item->cost=$r_stok->cost_from_mfg;
					$this->db->query("update invoice_lineitems set cost=".$item->cost." where line_number=".$item->line_number);
				}
			}
			
			$sales_amt=$item->price*$item->quantity;
			$disc_amt=$item->discount*$sales_amt;
			$hpp_amt=$item->cost*$item->quantity;
			if($hpp_amt>0){
				//-- posting persediaan
				$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_stock, 
					$faktur->invoice_date,$hpp_amt,0,"Inventory",$faktur->comments,$cid,$item->item_number);
				//-- posting hpp
				$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_hpp, 
					$faktur->invoice_date,0,$hpp_amt,"Cogs",$faktur->comments,$cid,$item->item_number);
			}
			//-- posting penjualan
			if($sales_amt>0){
				$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_sales, 
					$faktur->invoice_date,$sales_amt,0,"Sales",$faktur->comments,$cid,$item->item_number);
			}
			//-- posting discount item
			if($disc_amt>0){
				$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_disc, 
					$faktur->invoice_date,0,$disc_amt,"Sales Discount",$faktur->comments,$cid,$item->item_number);
			}						
		}
		//-- posting piutang
		$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_ar, 
			$faktur->invoice_date,0,$faktur->amount,"Account Receivable",$faktur->comments,$cid,$faktur->sold_to_customer);
		if($faktur->disc_amount!=0){
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_disc, 
				$faktur->invoice_date,0,$faktur->disc_amount,"Sales Discount",$faktur->comments,$cid,$faktur->sold_to_customer);
		}
		if($faktur->tax!=0){
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_tax, 
				$faktur->invoice_date,0,$faktur->tax,"Sales Tax",$faktur->comments,$cid,$faktur->sold_to_customer);					
		}
		if($faktur->freight!=0){
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_freight, 
				$faktur->invoice_date,$faktur->freight,0,"Sales Freight",$faktur->comments,$cid,$faktur->sold_to_customer);					
		}
		if($faktur->other!=0){
			$this->jurnal_model->add_jurnal($faktur->invoice_number,$coa_other, 
				$faktur->invoice_date,$faktur->other,0,"Sales Other",$faktur->comments,$cid,$faktur->sold_to_customer);					
		}
		// validate jurnal
		if($this->jurnal_model->validate($nomor)) {	$data['posted']=true;	} else {$data['posted']=false;}
		$this->invoice_model->update($nomor,$data);
		
	
	}	
}