<?php

namespace mafiascum\authentication\acp;

class alts_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $language, $template, $request, $config, $db;
		global $phpbb_root_path, $phpbb_admin_path, $phpEx;
		$table_prefix = "phpbb_";
		$alt_table_name = $table_prefix . "alts";
        $this->page_title = "Manage Alts"; //$language->lang('ALT_MANAGEMENT');
		$user_id	= $request->variable('u', 0);
		$username	= utf8_normalize_nfc($request->variable('username',''));
		$action		= $request->variable('action', "");
		$select 	= false;
		if (!$username && !$user_id)
		{
			$select = true;
		} else if (!$user_id) {
			$sql = 'SELECT user_id
				FROM ' . USERS_TABLE . "
				WHERE username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'";
			$result = $db->sql_query($sql);
			$user_id = (int) $db->sql_fetchfield('user_id');
			$db->sql_freeresult($result);
			if (!$user_id)
			{
				trigger_error('No user found.' . adm_back_link($this->u_action), E_USER_WARNING);
			}
		}
		add_form_key('ms_authentication_alts_alts');
		switch ($mode){
			case "manage" :
				$this->tpl_name = 'acp_alt_manage_body';
				if(!$select){
					$sql = 'SELECT u.*, s.*
					FROM ' . USERS_TABLE . ' u
						LEFT JOIN ' . SESSIONS_TABLE . ' s ON (s.session_user_id = u.user_id)
					WHERE u.user_id = ' . $user_id . '
					ORDER BY s.session_time DESC';
					$result = $db->sql_query_limit($sql, 1);
					$user_row = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
					$userAltData = \mafiascum\authentication\includes\AltManager::getAlts($table_prefix, $user_row['user_id']);
					if($action == 'removealt') {

						$alt_user_id = $request->variable('alt_user_id', 0);

						$sql = 'DELETE FROM ' . $alt_table_name . '
								WHERE main_user_id=' . $user_row['user_id'] . '
								AND alt_user_id=' . $alt_user_id;

						$db->sql_query($sql);
					} else if ($request->is_set_post('submituser')) {
						if (!check_form_key('ms_authentication_alts_alts'))
						{
							 trigger_error('FORM_INVALID', E_USER_WARNING);
						}
						if($action == 'addalt'){
							$alt_username = utf8_clean_string($request->variable('alt_add_username', ''));

							$sql = 'SELECT user_id
									FROM ' . USERS_TABLE . "
									WHERE username_clean='" . $db->sql_escape($alt_username) . "'";

							$resultSet = $db->sql_query($sql);

							if(!($row = $db->sql_fetchrow($resultSet))) {
								trigger_error('No user found.' . adm_back_link($this->u_action), E_USER_WARNING);
							}

							$alt_user_id = $row['user_id'];

							$db->sql_freeresult($resultSet);

							if($userAltData->isHydra()) {

								trigger_error('Cannot add an alt to a hydra. You must add the hydra as an alt of another user.'. adm_back_link($this->u_action), E_USER_WARNING);
							}

							if($userAltData->hasAlt($alt_user_id) || $userAltData->hasMain($alt_user_id)) {

								trigger_error("Alt already added.". adm_back_link($this->u_action), E_USER_WARNING);
							}

							$sql = 'INSERT INTO ' . $alt_table_name
								 . $db->sql_build_array('INSERT', Array(
								 'main_user_id'		=> $userAltData->getSingleMainUserId(),
								 'alt_user_id'		=> $alt_user_id,
								 ));

							$db->sql_query($sql);
						}
					} 
					//User alt table
					$userAltData = \mafiascum\authentication\includes\AltManager::getAlts($table_prefix, $user_row['user_id']);
					$alts = $userAltData->getAllAlts();

					$userAltData->loadAltUserData($table_prefix);

					$index = 0;
					$username = "";
					while($index < count($alts)) {
						$alt_user_id = $alts[ $index ];
						$row = $userAltData->getAltUserData($alt_user_id);
						if ($user_id == $row['user_id']){
							$username = $row['username'];
						}
						$template->assign_block_vars('useraltrow', array(
							'USERNAME'		=> ($row['user_id'] == ANONYMOUS) ? $user->lang['GUEST'] : $row['username'],
							'U_PROFILE'		=> append_sid("{$phpbb_admin_path}index.$phpEx", "i=users&amp;mode=overview&amp;u={$row['user_id']}"),
							'ACCOUNT_TYPE'	=> ($userAltData->hasMain($alt_user_id) ? 'Main' : ($row['is_hydra'] ? 'Hydra' : 'Alt')),
							'ACCOUNT_LINK'	=> $this->u_action . '&amp;u=' . $row['user_id'],
							'REMOVE_URL'	=> $this->u_action . '&amp;u=' . $user_id . '&amp;action=removealt&amp;alt_user_id=' . $row['user_id'],
							'CAN_REMOVE'	=> ($userAltData->isMain() && !$userAltData->hasMain($alt_user_id) ? 1 : 0),
						));

						++$index;
					}
					$template->assign_vars(array(
							'ACCOUNT_TYPE'		=> $userAltData->getAccountType(),
							'S_CAN_ADD_ALT'		 => ($userAltData->isMain() ? true : false),
							'U_ACTION'          => $this->u_action,
							'S_SELECT_USER'		=> false,
							'U_USER_ID'			=> $user_row['user_id'],
							'U_USERNAME'	=> $username
					));
					//Create all necessary regex for IP and email matching.
					$ip_set = explode('.', $user_row['user_ip']);
					$ip_exp = ($user_row['user_ip'] == '') ? '^$' : '^'.$ip_set[0]. '.' . $ip_set[1] . '.' . $ip_set[2] . '.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$';
					$email_set = explode('@', $user_row['user_email']);
					$email_exp = '^'.$email_set[0].'@';
					// Get other users who've posted under this IP or Email
					$sql = 'SELECT user_id, user_email, username, user_ip
						FROM ' . USERS_TABLE . '
						WHERE (user_ip != "" AND user_ip REGEXP "' . $ip_exp .'")
						OR user_email REGEXP "' . $email_exp .'"';
					$result = $db->sql_query($sql);
					//Create table of associated accounts.
					while ($row = $db->sql_fetchrow($result))
					{
						//Make sure the master isn't associated with itself.
						if($row['user_id'] != $user_row['user_id'])
						{
							$template->assign_block_vars('userrow', array(
								'USERNAME'        => ($row['user_id'] == ANONYMOUS) ? $user->lang['GUEST'] : $row['username'],
								'EMAIL'        =>     (preg_match('/'.$email_exp.'/', $row['user_email'])) ? '<span style="color:red;">' .$row['user_email']. '</span>' : $row['user_email'],
								'IP'            =>     (preg_match('/'.$ip_exp.'/', $row['user_ip'])) ? '<span style="color:red;">' .$row['user_ip']. '</span>' : $row['user_ip'],
								'U_PROFILE'        => append_sid("{$phpbb_admin_path}index.$phpEx", "i=users&amp;mode=overview&amp;u={$row['user_id']}")
							));
						}
					}
				} else {
					$template->assign_vars(array(
							'S_SELECT_USER'		=> true,
							'U_ACTION'          => $this->u_action,
					));
				}
			break;
        }
    }
}