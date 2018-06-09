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
<<<<<<< HEAD
			'core.ucp_register_user_row_after' => 'ucp_register_user_row_after',
			'core.ucp_profile_reg_details_sql_ary' => 'ucp_profile_reg_details_sql_ary',
			'core.acp_users_overview_modify_data' => 'acp_users_overview_modify_data',
			'core.acp_users_overview_before' => 'acp_users_overview_before',
			'core.user_setup_after' => 'user_setup_after',
			'core.acp_users_mode_add' => 'acp_users_mode_add',
			'core.memberlist_view_profile' => 'memberlist_view_profile'
=======
			'core.acp_users_display_overview' => 'inject_acp_alt_overview_data'
>>>>>>> master
        );
    }

    public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\request\request $request, \phpbb\db\driver\driver_interface $db,  \phpbb\user $user, \phpbb\user_loader $user_loader, \phpbb\language\language $language, \phpbb\auth\auth $auth, $table_prefix)
    {
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
}
?>