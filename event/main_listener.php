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
            'core.ucp_profile_reg_details_sql_ary' => 'create_delete_pending_alt_requests',
            'core.ucp_profile_reg_details_validate' => 'validate_alt_payload',
            'core.ucp_register_user_row_after' => 'ucp_register_user_row_after',
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
}
?>