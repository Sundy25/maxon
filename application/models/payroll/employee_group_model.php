<?php
class Employee_group_model extends CI_Model {

private $primary_key='kode';
private $table_name='employee_group';

function __construct(){
	parent::__construct();
}
	function save($data){
		return $this->db->insert($this->table_name,$data);
	}
	function update($id,$data){
		$this->db->where($this->primary_key,$id);
		return $this->db->update($this->table_name,$data);
	}
	function delete($kode){
		$this->db->where($this->primary_key,$kode);
		return $this->db->delete($this->table_name);
	}
	function lookup(){
		$query=$this->db->query("select kode,keterangan from ".$this->table_name);
		$ret=array();$ret['']='- Select -';
 		foreach ($query->result() as $row){$ret[$row->kode]=$row->keterangan;}		 
		return $ret;
	}

}
