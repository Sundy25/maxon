<?php
class Aktiva_model extends CI_Model {

private $primary_key='id';
private $table_name='fa_asset';

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
                if($nama!='')$this->db->where("description like '%$nama%'");

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
		$data['warranty_date']= date('Y-m-d H:i:s', strtotime($data['warranty_date']));
		$data['acquisition_date']= date('Y-m-d H:i:s', strtotime($data['acquisition_date']));
		$this->db->insert($this->table_name,$data);
		return $this->db->insert_id();
	}
	function update($id,$data){
		$data['warranty_date']= date('Y-m-d H:i:s', strtotime($data['warranty_date']));
		$data['acquisition_date']= date('Y-m-d H:i:s', strtotime($data['acquisition_date']));
		$this->db->where($this->primary_key,$id);
		$this->db->update($this->table_name,$data);
	}
	function delete($id){
		$this->db->where($this->primary_key,$id);
		$this->db->delete($this->table_name);
	}
	function load_all() {
		//load all aset active
//		$this->db->where("active",true);
		return $this->db->get($this->table_name);
	}
	function loadlist() {
		//load all aset active
//		$this->db->where("active",true);
		$rows=null;
		if($q=$this->db->get($this->table_name)){
			foreach($q->result() as $r) {
				$rows[]=$r;
			}
		}
		return $rows;
	}
 
}