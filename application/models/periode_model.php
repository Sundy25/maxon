<?php
class Periode_model extends CI_Model {

private $primary_key='period';
private $table_name='financial_periods';

function __construct(){
	parent::__construct();
	$this->check_this_year();
}
function check_this_year(){
	$year=date("Y");
	for($bln=1;$bln<=12;$bln++){
		$period=$year."-".strzero($bln,2);
		if(!$this->exist($period)){
			$last_date=date("Y-m-t", strtotime($year."-".strzero($bln,2)."-01"));	//lastday
			$this->save(array(
				"period"=>$period,"year_id"=>$year,"sequence"=>$bln,
				"startdate"=>$year."-".strzero($bln,2)."-01 00:00:00",
				"enddate"=>$last_date." 23:59:59",
				"closed"=>0,"month_name"=>date("M",strtotime($last_date))			
			));
		}
	}
} 
function count_all(){
	return $this->db->count_all($this->table_name);
}
function get_by_id($id){
	$this->db->where($this->primary_key,$id);
	return $this->db->get($this->table_name);
}

function exist($id){
   return $this->db->count_all($this->table_name." where period='".$id."'")>0;
}
function save($data){
	if(isset($data['startdate']))$data['startdate']= date('Y-m-d H:i:s', strtotime($data['startdate']));
	if(isset($data['enddate']))$data['enddate']= date('Y-m-d H:i:s', strtotime($data['enddate']));
	$this->db->insert($this->table_name,$data);
	return $this->db->insert_id();
}
function update($id,$data){
	if(isset($data['startdate']))$data['startdate']= date('Y-m-d H:i:s', strtotime($data['startdate']));
	if(isset($data['enddate']))$data['enddate']= date('Y-m-d H:i:s', strtotime($data['enddate']));
	$this->db->where($this->primary_key,$id);
	$this->db->update($this->table_name,$data);
}
function delete($id){
	$this->db->where($this->primary_key,$id);
	$this->db->delete($this->table_name);
}
function dropdown(){
        $query=$this->db->query("select period from ".$this->table_name);
        $ret=array();
        $ret['']='- Select -';
        foreach ($query->result() as $row) {
                $ret[$row->period]=$row->period;
        }		 
        return $ret;
}
function current_periode(){
	$this->db->where("closed=0");
	$this->db->order_by("period","desc");
	$q=$this->db->get($this->table_name);
	if($q){
		return $q->row()->period;
	} else {
		return '';
	}
}
	function closed($date_trans)
	{
		$retval=false;
		$sql="select closed from financial_periods where '".$date_trans."' 
			between startdate and enddate";
		 
		
		$q=$this->db->query($sql);
		if($q){
			if($q->row())$retval=$q->row()->closed;
		}
		return $retval;
	}
	function loadlist() {
		$rows=null;
		$this->db->order_by("period");
		if($q=$this->db->get($this->table_name)){
			foreach($q->result() as $r) {
				$rows[]=$r;
			}
		}
		return $rows;
	}
	
}
