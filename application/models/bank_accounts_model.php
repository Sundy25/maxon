<?php
class Bank_Accounts_model extends CI_Model {

private $primary_key='bank_account_number';
private $table_name='bank_accounts';

function __construct(){
	parent::__construct();
}
	function get_paged_list($limit=10,$offset=0,
	$order_column='',$order_type='asc')
	{
                $nama='';
                if(isset($_GET['nama'])){
                    $nama=$_GET['nama'];
                }
                if($nama!='')$this->db->where("category like '%$nama%'");

		if (empty($order_column)||empty($order_type))
		$this->db->order_by($this->primary_key,'asc');
		else
		$this->db->order_by($order_column,$order_type);
		return $this->db->get($this->table_name,$limit,$offset);
	}
	function count_all(){
		return $this->db->count_all($this->table_name);
	}
	function get_by_id($id){
		$this->db->where($this->primary_key,$id);
		return $this->db->get($this->table_name);
	}
	function save($data){
		$this->db->insert($this->table_name,$data);
		return $this->db->insert_id();
	}
	function update($id,$data){
		$this->db->where($this->primary_key,$id);
		$this->db->update($this->table_name,$data);
	}
	function delete($id){
		$this->db->where($this->primary_key,$id);
		$this->db->delete($this->table_name);
	}
    function account_number_list(){
        $query=$this->db->query("select bank_account_number,bank_name 
            from bank_accounts");
        $ret=array();
        $ret['']='- Select -';
        foreach ($query->result() as $row)
        {
                $ret[$row->bank_account_number]=$row->bank_account_number.' - '.$row->bank_name;
        }		 
        return $ret;
	}
	function saldo_rekening()
	{
		$sql="select b.bank_account_number,b.bank_name,sum(cw.deposit_amount-cw.payment_amount) as sum_amount 
		from bank_accounts b left join check_writer cw on cw.account_number=b.bank_account_number
		group by b.bank_account_number,b.bank_name
		order by sum(cw.deposit_amount-cw.payment_amount)  desc
		limit 0,10";
		$query=$this->db->query($sql);
		foreach($query->result() as $row){
			$item=$row->bank_account_number;	//. ' - '.$row->bank_name;
			if($item=="")$item="Unknown";
			$qty=$row->sum_amount;
			if($qty==null)$qty=0;
			$data[]=array(substr($item,0,10),$qty);
		}
		return $data;
	}
	function saldo_rekening_old()
	{
		$sql="select b.bank_account_number,b.bank_name,sum(cw.deposit_amount-cw.payment_amount) as sum_amount 
		from bank_accounts b left join check_writer cw on cw.account_number=b.bank_account_number
		group by b.bank_account_number,b.bank_name
		order by sum(cw.deposit_amount-cw.payment_amount)  desc
		limit 0,10";
		$query=$this->db->query($sql);
		foreach($query->result() as $row){
			$item=$row->bank_account_number;	//. ' - '.$row->bank_name;
			if($item=="")$item="Unknown";
			$qty=$row->sum_amount;
			if($qty==null)$qty=0;
			$data[$item]=$qty;
		}
		return $data;
	}
	
}