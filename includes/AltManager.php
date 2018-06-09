<?php

namespace mafiascum\authentication\includes;

class AltManager {

	private $main_user_ids;
	private $alt_user_ids;
	private $user_data;
	private $user_id;
	private $alt;
	private $hydra;
	private $main;
	
	public function loadAltUserData($table_prefix) {
		
		global $db;
		
		$alt_table_name = $table_prefix . "alts";
		
		$sql = 'SELECT *
				FROM ' . USERS_TABLE . '
				WHERE ' . $db->sql_in_set('user_id', $this->getAllAlts());
		
		$resultSet = $db->sql_query($sql);
		
		$this->user_data = Array();
		
		while($row = $db->sql_fetchrow($resultSet)) {

			if($this->hasMain($row['user_id'])) {
				
				$row['is_hydra'] = 0;
			}
			else {
			
				$sql = 'SELECT COUNT(*) AS cnt
						FROM ' . $alt_table_name . '
						WHERE alt_user_id=' . $row['user_id'];
				
				$resultSet2 = $db->sql_query($sql);
				
				$row2 = $db->sql_fetchrow($resultSet2);
				
				if((int)$row2['cnt'] > 1) { 
                         
                         $row['is_hydra'] = 1;
                    }
                    else {
                         
                         $row['is_hydra'] = 0;
                    }
				
				$db->sql_freeresult($resultSet2);
				
			}
			
			$this->user_data[ $row['user_id'] ] = $row;
		}
		
		$db->sql_freeresult($resultSet);
	}
	
	public function getAltUserData($user_id) {
	
		return $this->user_data[ $user_id ];
	}
	
	public function getAllAlts() {
		
		return array_merge($this->main_user_ids, $this->alt_user_ids);
	}
	
	public function hasAlt($user_id) {
		
		return in_array($user_id, $this->alt_user_ids);
	}
	
	public function hasMain($user_id) {
		
		return in_array($user_id, $this->main_user_ids);
	}
	
	public function isAlt() {
		
		return $this->alt;
	}
	
	public function isHydra() {
		
		return $this->hydra;
	}
	
	public function isMain() {
		
		return $this->main;
	}
	
	public function getAccountType() {
		
		if($this->isHydra())
			return "Hydra";
		else if($this->isAlt())
			return "Alt";
		else
			return "Main";
	}
	
	public function getSingleMainUserId() {
		
		return $this->main_user_ids[ 0 ];
	}
	
	public static function getAlts($table_prefix, $user_id) {
		$alt_table_name = $table_prefix . "alts";
		$userAltData = new AltManager();
		
		$userAltData->user_id = $user_id;
		
		global $db;
		
		$sql = 'SELECT main_user_id
				FROM ' . $alt_table_name . '
				WHERE alt_user_id=' . $user_id;
		$resultSet = $db->sql_query($sql);
		
		$rows = $db->sql_fetchrowset($resultSet);
		
		$records = count($rows);
		
		$userAltData->main_user_ids = Array();
		
		$userAltData->alt_user_ids = Array();
		
		$index = 0;
		while($index < $records) {
			
			$userAltData->main_user_ids[] = $rows[$index]['main_user_id'];
			
			++$index;
		}
		
		if($records > 1) {
			
			$userAltData->hydra = true;
			$userAltData->main = false;
			$userAltData->alt = false;
			
			return $userAltData;
		}
		else if($records == 0) {//This is the main account.
			$userAltData->hydra = false;
			$userAltData->main = true;
			$userAltData->alt = false;
			$mainUserId = $user_id;
			
			$userAltData->main_user_ids[] = $mainUserId;
		}
		else {
			
			$userAltData->main = false;
			$userAltData->hydra = false;
			$userAltData->alt = true;
			$row = $rows[0];	
		
			$mainUserId = $row['main_user_id'];	
		}
		
		$db->sql_freeresult($resultSet);
		
		$sql = 'SELECT alt_user_id
				FROM ' . $alt_table_name . '
				WHERE main_user_id=' . $mainUserId;
				
		$resultSet = $db->sql_query($sql);
		
		while($row = $db->sql_fetchrow($resultSet)) {
			
			$userAltData->alt_user_ids[] = (int)$row['alt_user_id'];
		}
		
		$db->sql_freeresult($resultSet);
		
		return $userAltData;
	}
}
?>