<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_category(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `category_list` set {$data} ";
		}else{
			$sql = "UPDATE `category_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `category_list` where `name` = '{$name}' ".(is_numeric($id) && $id > 0 ? " and id != '{$id}'" : "")." ")->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = 'Category Name already exists.';
			
		}else{
			$save = $this->conn->query($sql);
			if($save){
				$rid = !empty($id) ? $id : $this->conn->insert_id;
				$resp['id'] = $rid;
				$resp['status'] = 'success';
				if(empty($id))
					$resp['msg'] = "Category has successfully added.";
				else
					$resp['msg'] = "Category details has been updated successfully.";
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured.";
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_category(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `category_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Category has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_price(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `price_list` set {$data} ";
		}else{
			$sql = "UPDATE `price_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `price_list` where `size` = '{$size}' and `category_id` = '{$category_id}' ".(is_numeric($id) && $id > 0 ? " and id != '{$id}'" : "")." ")->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = ' Size already exists on the selected category.';
			
		}else{
			$save = $this->conn->query($sql);
			if($save){
				$rid = !empty($id) ? $id : $this->conn->insert_id;
				$resp['id'] = $rid;
				$resp['status'] = 'success';
				if(empty($id))
					$resp['msg'] = " Price has successfully added.";
				else
					$resp['msg'] = " Price details has been updated successfully.";
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured.";
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_price(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `price_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Price has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_transaction(){
		if(empty($_POST['id'])){
			$pref = date('Ym-');
			$code = sprintf("%'.05d",1);
			while(true){
				$check = $this->conn->query("SELECT * FROM `transaction_list` where `code` = '{$pref}{$code}'")->num_rows;
				if($check > 0){
					$code = sprintf("%'.05d",abs($code) + 1);
				}else{
					break;
				}
			}
			$_POST['code'] = $pref.$code;
		}
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(in_array($k,array('code','client_name','client_contact','client_address','total_amount','paid_amount','balance','payment_status','status'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `transaction_list` set {$data} ";
		}else{
			$sql = "UPDATE `transaction_list` set {$data} where id = '{$id}' ";
		}
		
		$save = $this->conn->query($sql);
		if($save){
			$tid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['tid'] = $tid;
			$resp['status'] = 'success';
			$total = 0;
			$this->conn->query("DELETE FROM `transaction_items` where transaction_id = '{$tid}'");
			$data = "";
			foreach($price_id as $k => $v){
				if(!empty($data)) $data .=", ";
				$_total = $price[$k] * $quantity[$k];
				$total += $_total;
				$data .= "('{$tid}','{$v}','{$price[$k]}','{$quantity[$k]}','{$_total}')";
			}
			if(!empty($data)){
				$sql2 = "INSERT INTO `transaction_items` (`transaction_id`,`price_id`,`price`,`quantity`,`total`) VALUES {$data}";
			}
			$save2= false;
			if(isset($sql2))
			$save2 = $this->conn->query($sql2);
			if($save2){
				$this->conn->query("UPDATE `transaction_list` set total_amount = '{$total}' where id = '{$tid}'");
				if(isset($amount)){
					if(empty($id))
						$save3 = $this->conn->query("INSERT INTO payment_history (`transaction_id`,`amount`) VALUES ('{$tid}','{$amount}')");
					else{
						$save3 = $this->conn->query("UPDATE payment_history set `amount` = '{$amount}' where transaction_id = '{$tid}' order by unix_timestamp(date_created) asc limit 1");
					}
					if($save3){
						$total_paid = $this->conn->query("SELECT SUM(amount) from payment_history where transaction_id = '{$tid}'")->fetch_array()[0];
						$total_paid = $total_paid > 0 ? $total_paid : 0;
						$pstatus = $total_paid > 0 ? ($total_paid == $total) ? 2 : 1 : 0;
						$balance = $total - $total_paid;
						$this->conn->query("UPDATE `transaction_list` set paid_amount = '{$total_paid}', payment_status = '{$pstatus}', `balance` ='{$balance}' where id = '{$tid}'");
						if(empty($id))
							$resp['msg'] = " Transaction has successfully added.";
						else
							$resp['msg'] = " Transaction details has been updated successfully.";
					}else{
						$resp['status'] = 'failed';
						$resp['msg'] = " Transaction Items has failed to save.";
						$resp['err'] = $this->conn->error;
						if(empty($id))
							$this->conn->query("DELETE FROM `transaction_list` where id = '{$tid}' ");
					}
				}

			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = " Transaction Items has failed to save.";
				$resp['err'] = $this->conn->error."[{$sql2}]";
				if(empty($id))
					$this->conn->query("DELETE FROM `transaction_list` where id = '{$tid}' ");
			}
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_transaction(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `transaction_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Transaction has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_payment(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `payment_history` set {$data} ";
		}else{
			$sql = "UPDATE `payment_history` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = " Payment has successfully added.";
			else
				$resp['msg'] = " Payment details has been updated successfully.";
			$total = $this->conn->query("SELECT total_amount FROM `transaction_list` where id = '{$transaction_id}'")->fetch_array()[0];
			$total = $total > 0 ? $total : 0;
			$total_paid = $this->conn->query("SELECT SUM(amount) from payment_history where transaction_id = '{$transaction_id}'")->fetch_array()[0];
			$total_paid = $total_paid > 0 ? $total_paid : 0;
			$pstatus = $total_paid > 0 ? ($total_paid == $total) ? 2 : 1 : 0;
			$balance = $total - $total_paid;
			$this->conn->query("UPDATE `transaction_list` set paid_amount = '{$total_paid}', payment_status = '{$pstatus}', `balance` ='{$balance}' where id = '{$transaction_id}'");
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_payment(){
		extract($_POST);
		$get = $this->conn->query("SELECT * FROM `payment_history` where id = '{$id}'");
		if($get->num_rows > 0){
			$res = $get->fetch_array();
		}
		$del = $this->conn->query("DELETE FROM `payment_history` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Payment has been deleted successfully.");
			if(isset($res['transaction_id'])){
				$total = $this->conn->query("SELECT total_amount FROM `transaction_list` where id = '{$res['transaction_id']}'")->fetch_array()[0];
				$total = $total > 0 ? $total : 0;
				$total_paid = $this->conn->query("SELECT SUM(amount) from payment_history where transaction_id = '{$res['transaction_id']}'")->fetch_array()[0];
				$total_paid = $total_paid > 0 ? $total_paid : 0;
				$pstatus = $total_paid > 0 ? ($total_paid == $total) ? 2 : 1 : 0;
				$balance = $total - $total_paid;
				$this->conn->query("UPDATE `transaction_list` set paid_amount = '{$total_paid}', payment_status = '{$pstatus}', `balance` ='{$balance}' where id = '{$res['transaction_id']}'");
			}
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_transaction_status(){
		extract($_POST);
		
		$update = $this->conn->query("UPDATE `transaction_list` set status = '{$status}' where id = '{$id}'");
		if($update){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Transaction's Status has been updated successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_category':
		echo $Master->save_category();
	break;
	case 'delete_category':
		echo $Master->delete_category();
	break;
	case 'save_price':
		echo $Master->save_price();
	break;
	case 'delete_price':
		echo $Master->delete_price();
	break;
	case 'save_transaction':
		echo $Master->save_transaction();
	break;
	case 'delete_transaction':
		echo $Master->delete_transaction();
	break;
	case 'save_payment':
		echo $Master->save_payment();
	break;
	case 'delete_payment':
		echo $Master->delete_payment();
	break;
	case 'update_transaction_status':
		echo $Master->update_transaction_status();
	break;
	default:
		// echo $sysset->index();
		break;
}