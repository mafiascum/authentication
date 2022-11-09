<?php
/**
 *
 * @package phpBB Extension - Mafiascum Authentication
 * @copyright (c) 2013 phpBB Group
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace mafiascum\authentication\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * Event listener
 */
class main_listener implements EventSubscriberInterface
{
    /* phpbb\config\config */
    protected $config;

    /* @var \phpbb\controller\helper */
    protected $helper;

    /* @var \phpbb\template\template */
    protected $template;

    /* @var \phpbb\request\request */
    protected $request;

    /* @var \phpbb\db\driver\driver */
	protected $db;

    /* @var \phpbb\user */
    protected $user;

    /* @var \phpbb\user_loader */
    protected $user_loader;

    /* @var \phpbb\auth\auth */
    protected $auth;

    /* phpbb\language\language */
    protected $language;

    static public function getSubscribedEvents()
    {
        return array(
            'core.user_setup' => 'load_language_on_setup',
            'core.ucp_profile_reg_details_data' => 'inject_alts_template_data',
            'core.ucp_profile_reg_details_validate' => 'validate_alt_payload',
			'core.ucp_register_user_row_after' => 'ucp_register_user_row_after',
			'core.ucp_profile_reg_details_sql_ary' => 'ucp_profile_reg_details_sql_ary',
			'core.acp_users_overview_modify_data' => 'acp_users_overview_modify_data',
			'core.acp_users_overview_before' => 'acp_users_overview_before',
			'core.user_setup_after' => 'user_setup_after',
			'core.acp_users_mode_add' => 'acp_users_mode_add',
			'core.memberlist_view_profile' => 'memberlist_view_profile',
			'core.viewonline_modify_sql' => 'viewonline_modify_sql',
            'core.acp_users_display_overview' => 'inject_acp_alt_overview_data',
            'core.user_add_after' => 'check_user_conflicts',
            'core.viewforum_get_topic_data' => 'disable_anonymous_new_topics',
            'core.viewtopic_get_post_data' => 'append_vla_select_cols',
            'core.viewtopic_cache_user_data' => 'cache_addl_user_data',
            'core.viewtopic_modify_post_row' => 'assoc_addl_user_data',
        );
    }

    public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\request\request $request, \phpbb\db\driver\driver_interface $db,  \phpbb\user $user, \phpbb\user_loader $user_loader, \phpbb\language\language $language, \phpbb\auth\auth $auth, $table_prefix)
    {
        $this->config = $config;
        $this->helper = $helper;
        $this->template = $template;
        $this->request = $request;
        $this->db = $db;
        $this->user = $user;
        $this->user_loader = $user_loader;
        $this->language = $language;
        $this->auth = $auth;
        $this->table_prefix = $table_prefix;
	}
	
	public function check_user_conflicts($event){
		$user_row = $event["user_row"];
		$user_id = $event["user_id"];

		$sql = " SELECT user.user_id USER_ID, IF(alts.main_user_id IS NOT NULL, 1, 0) IS_MAIN"
	         . " FROM " . USERS_TABLE . " user"
	         . " LEFT JOIN " . $this->table_prefix . "alts alts ON(alts.main_user_id=user.user_id)"
			 . " WHERE user.user_email='" . $this->db->sql_escape($user_row['user_email']) . "'"
			 . " AND user.user_id != " . $user_id
	         . " GROUP BY user.user_id"
	         . " ORDER BY 2 DESC, user.user_id ASC"
	         . " LIMIT 1";

		$result = $this->db->sql_query($sql);

		if ($row = $this->db->sql_fetchrow($result))
		{
			$main_user_id = $row['USER_ID'];
			$this->db->sql_freeresult($result);

            $sql = ' INSERT INTO ' . $this->table_prefix . 'alts'
                 . ' (alt_user_id, main_user_id)'
                 . ' VALUES (' . $user_id . ',' . $main_user_id . ')';
            $this->db->sql_query($sql);
		}
	}

	public function inject_acp_alt_overview_data($event){
		global $phpbb_admin_path, $phpEx;
		$user_row = $event["user_row"];
		$user_id = $user_row["user_id"];
		$userAltData = \mafiascum\authentication\includes\AltManager::getAlts($this->table_prefix, $user_id);
		
		$accountType = "<a href=" . append_sid("{$phpbb_admin_path}index.$phpEx", "i=-mafiascum-authentication-acp-alts_module&amp;mode=manage&amp;u={$user_id}") . ">" . $userAltData->getAccountType() . "</a>";
		$this->template->assign_vars(array(
			'ACCOUNT_TYPE'       => $accountType,
		));
	}
    private function send_alt_request_pm($main_user_id, $alt_request_id, $token) {
        global $phpEx, $phpbb_root_path;

        include_once($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
		include_once($phpbb_root_path . 'includes/message_parser.' . $phpEx);
        
        $link_hash = generate_link_hash('alt_request_' . $alt_request_id);

        $message_parser = new \parse_message();
		$message_parser->message = $this->language->lang("ALT_REQUEST_PM_BODY", $this->user->data['username'], $alt_request_id, $token);
		$message_parser->parse(true, true, true, false, false, true, true);

        $pm_data = array(
			'from_user_id'			=> $this->user->data['user_id'],
			'from_user_ip'			=> $this->user->ip,
			'from_username'			=> $this->user->data['username'],
			'enable_sig'			=> false,
			'enable_bbcode'			=> true,
			'enable_smilies'		=> true,
			'enable_urls'			=> true,
			'icon_id'				=> 0,
			'bbcode_bitfield'		=> $message_parser->bbcode_bitfield,
			'bbcode_uid'			=> $message_parser->bbcode_uid,
			'message'				=> $message_parser->message,
			'address_list'			=> array('u' => array($main_user_id => 'to')),
		);

        submit_pm('post', $this->language->lang("ALT_REQUEST_PM_SUBJECT", $this->user->data['username']), $pm_data, false);
    }

    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = array(
            'ext_name' => 'mafiascum/authentication',
            'lang_set' => 'common',
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }

    private function get_mains_for_alt($alt_user_id) {
        $alt_table_name = $this->table_prefix . "alts";
        $alt_request_table_name = $this->table_prefix . "alt_requests";
        
        $mains = array();
        $sql = "SELECT main_user_id, 'confirmed' as status FROM " . $alt_table_name . " WHERE alt_user_id = " . $alt_user_id;
        $sql = $sql . " UNION";
        $sql = $sql . " SELECT main_user_id, 'pending' as status FROM " . $alt_request_table_name . " WHERE alt_user_id = " . $alt_user_id;
        
        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result)) {
            $mains[] = array(
                'user_id' => $row['main_user_id'],
                'status' => $row['status'],
            );
        }
        $this->db->sql_freeresult($result);

        return $mains;
    }

    public function create_delete_pending_alt_requests($event) {
        $alt_table_name = $this->table_prefix . "alts";
        $alt_request_table_name = $this->table_prefix . "alt_requests";

        $alt_user_id = $this->user->data['user_id'];
        
        $new_mains = $this->request->variable("main_users", array(""));
        
        $current_mains = array_column($this->get_mains_for_alt($alt_user_id), 'user_id');

        $mains_add = array_diff($new_mains, $current_mains);
        $mains_remove = array_diff($current_mains, $new_mains);

        if (sizeof($mains_remove)) {
            $sql = "DELETE FROM " . $alt_table_name;
            $sql = $sql . " WHERE alt_user_id = " . $alt_user_id;
            $sql = $sql . " AND main_user_id IN (" . implode(",", $mains_remove) . ")";
            $this->db->sql_query($sql);

            $sql = "DELETE FROM " . $alt_request_table_name;
            $sql = $sql . " WHERE alt_user_id = " . $alt_user_id;
            $sql = $sql . " AND main_user_id IN (" . implode(",", $mains_remove) . ")";
            $this->db->sql_query($sql);
        }
        if (sizeof($mains_add)) {
            foreach ($mains_add as $main_user_id) {
                $token = bin2hex(random_bytes(16));
                
                $sql = "INSERT INTO " . $alt_request_table_name . " (alt_user_id, main_user_id, token) ";
                $sql = $sql . " VALUES (" . $alt_user_id . ", " . $main_user_id . ", '" . $token . "')";

                $this->db->sql_query($sql);

                $sql = 'select last_insert_id() as id';
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $alt_request_id = $row['id'];

                $this->send_alt_request_pm($main_user_id, $alt_request_id, $token);
            }
        }
    }

    public function validate_alt_payload($event) {
        $alt_user_id = $this->user->data['user_id'];

        $error = $event['error'];

        $new_mains = $this->request->variable("main_users", array(""));

        if (in_array($alt_user_id, $new_mains)) {
            $error[] = 'ERROR_CANNOT_ADD_SELF_AS_MAIN_OR_ALIAS';
        }

        $event['error'] = $error;
    }

    public function inject_alts_template_data($event) {
        $alt_user_id = $this->user->data['user_id'];
            
        $mains = $this->get_mains_for_alt($alt_user_id);
            
        foreach ($mains as $main_user) {
            $this->user_loader->load_users(array($main_user['user_id']));
            $username_formatted = $this->user_loader->get_username($main_user['user_id'], 'username');
            $username_profile = $this->user_loader->get_username($main_user['user_id'], 'profile');
                
            $this->template->assign_block_vars('MAIN_USERS', array(
                'USER_ID'       => $main_user['user_id'],
                'USERNAME'      => $username_formatted,
                'PROFILE'       => $username_profile,
                'PENDING'       => $main_user['status'] == 'pending',
            ));
        }
    }

    function ucp_register_user_row_after($event) {
        //Disable display email option by default when registering.
        $user_row = $event['user_row'];
        
        $user_row['user_allow_viewemail'] = 0;
        
        $event['user_row'] = $user_row;
	}
	
	function ucp_profile_reg_details_sql_ary($event) {
		$existing_email = $this->user->data['user_email'];
		$submitted_email = $event['data']['email'];

		if(strcmp($existing_email, $submitted_email)) {
			$this->user->data['user_old_emails'] = $this->get_updated_old_emails_field($this->user->data['user_old_emails'], $existing_email);
			
			$sql_ary = $event['sql_ary'];

			$sql_ary['user_old_emails'] = $this->user->data['user_old_emails'];

			$event['sql_ary'] = $sql_ary;
		}

		$this->create_delete_pending_alt_requests($event);
	}

	function acp_users_overview_modify_data($event) {
		$user_row = $event['user_row'];
		$data = $event['data'];
		$sql_ary = $event['sql_ary'];

		$existing_email = $user_row['user_email'];
		$submitted_email = $data['email'];

		if(strcmp($existing_email, $submitted_email)) {
			$user_row['user_old_emails'] = $this->get_updated_old_emails_field($user_row['user_old_emails'], $existing_email);

			$sql_ary = $event['sql_ary'];

			$sql_ary['user_old_emails'] = $user_row['user_old_emails'];

			$event['sql_ary'] = $sql_ary;
		}
	}

	function get_updated_old_emails_field($old_emails, $existing_email) {
		return  $old_emails
				. (strlen($old_emails) > 0 ? "\n" : "")
				. $existing_email;
	}

	function acp_users_overview_before($event) {
		$user_row = $event['user_row'];
		$old_emails = explode("\n", $user_row['user_old_emails']);

		foreach ($old_emails as $old_email)
		{
			$this->template->assign_block_vars('old_emails', array(
				'OLD_EMAIL'        => ($old_email)
			));
		}
	}
	
	function user_setup_after($event) {
		$iVar = $this->request->variable('i', '');

		if(strcasecmp($iVar, 'acp_database') == 0)
			exit;
	}

	function acp_users_mode_add($event) {
		echo $event['mode'];
		exit;
	}

	function memberlist_view_profile($event) {

		$member = $event['member'];
		$username = $member['username'];
		
		$this->template->assign_vars(array(
			'WIKI_NAME' => $username,
			'WIKI_URL' => $this->get_user_wiki_url($username)
		));
	}

	function get_user_wiki_url($username) {
		return "https://wiki.mafiascum.net/index.php?title=" . urlencode($username);
	}

	function viewonline_modify_sql($event) {
		//Disable the viewonline list.
		$sql_ary = $event['sql_ary'];
		$where = $sql_ary['WHERE'];

		$where .= ' AND 1 = 0';

		$sql_ary['WHERE'] = $where;
		$event['sql_ary'] = $sql_ary;
    }
    
    function disable_anonymous_new_topics($event) {
        if ($this->user->data['user_id'] == ANONYMOUS) {
            $this->template->assign_vars(array(
                'S_DISPLAY_POST_INFO'       => false,
            ));
        }
    }

    function append_vla_select_cols($event) {
        $sql_ary = $event['sql_ary'];
        $select = $sql_ary['SELECT'];
        $select .= ', u.user_vla_start, u.user_vla_till';
        $sql_ary['SELECT'] = $select;
        $event['sql_ary'] = $sql_ary;
    }

    private static function get_vla_start_time($vlaStartField){
        $vlaStartDateArray = explode('-', $vlaStartField);
        return count($vlaStartDateArray) < 3 ? NULL : mktime(0, 0, 0, $vlaStartDateArray[1], $vlaStartDateArray[0], $vlaStartDateArray[2]);
    }

    private static function get_vla_end_time($vlaEndField) {
        $vlaEndDateArray = explode('-', $vlaEndField);
        return count($vlaEndDateArray) < 3 ? NULL : mktime(23, 59, 59, $vlaEndDateArray[1], $vlaEndDateArray[0], $vlaEndDateArray[2]);
    }

    private static function is_user_vla($vlaStartField, $vlaEndField) {
        $vlaStartTime = self::get_vla_start_time($vlaStartField);
        $vlaEndTime = self::get_vla_end_time($vlaEndField);
        
        if(is_null($vlaStartTime) || is_null($vlaEndTime))
            return false;
        
        $currentTime = time();
        
        return ($currentTime >= $vlaStartTime && $currentTime <= $vlaEndTime);
    }

    function cache_addl_user_data($event) {
        $user_cache_data = $event['user_cache_data'];
        $poster_id = $event['poster_id'];
        $row = $event['row'];

        $now = $this->user->create_datetime();
        $day = (int) $now->format('d');
        $month = (int) $now->format('m');

        //birthday
        $user_cache_data['user_birthday'] = false;
        if ($this->config['allow_birthdays'] && !empty($row['user_birthday'])) {
            list($bday_day, $bday_month) = array_map('intval', explode('-', $row['user_birthday']));

            if ($bday_day === $day && $bday_month === $month)
            {
                $user_cache_data['user_birthday'] = true;
            }
        }

        // scumday
        $user_cache_data['user_scumday'] = false;
        if ($this->config['allow_birthdays'] && !empty($row['user_regdate'])) {
            $scumday = $this->user->create_datetime();
            $scumday->setTimestamp($row['user_regdate']);
            $sday_day = (int) $scumday->format('d');
            $sday_month = (int) $scumday->format('m');

            if ($sday_day === $day && $sday_month === $month) {
                $user_cache_data['user_scumday'] = true; 
            }
        }

        $is_vla = self::is_user_vla($row['user_vla_start'], $row['user_vla_till']);

        $user_cache_data['vla'] = (bool) $is_vla;
        $user_cache_data['vla_start'] = ($row['user_vla_start'] != '') ? $row['user_vla_start'] : '';
        $user_cache_data['vla_end'] = ($row['user_vla_till'] != '') ? $row['user_vla_till'] : '';
        $user_cache_data['vla_display'] = $is_vla ? strftime("%A, %B %d %Y") : "";

        $event['user_cache_data'] = $user_cache_data;
    }

    function assoc_addl_user_data($event) {
        $user_cache = $event['user_cache'];
        $poster_id = $event['poster_id'];
        $post_row = $event['post_row'];

        $post_row['S_BIRTHDAY'] = $user_cache[$poster_id]['user_birthday'];
        $post_row['S_SCUMDAY'] = $user_cache[$poster_id]['user_scumday'];
        $post_row['S_VLA'] = $user_cache[$poster_id]['vla'];
		$post_row['S_VLA_END'] = $user_cache[$poster_id]['vla_display'];
        $post_row['S_VLA_UNTIL'] = $this->language->lang('VLA_UNTIL', $user_cache[$poster_id]['vla_display']);

        $event['post_row'] = $post_row;
    }
}
?>