<?php
namespace mafiascum\authentication\task;

class mafiascum_authentication_autoRemove extends \phpbb\cron\task\base {
    protected $config;
    
    /* @var \phpbb\db\driver\driver */
    protected $db;

    public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db) {
        $this->config = $config;
        $this->db = $db;
    }

    public function run() {
        global $phpEx, $phpbb_root_path;
        include_once($phpbb_root_path . 'common.' . $phpEx);
        include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);

        $sql = " SELECT"
             . "   group_id,"
             . "   user_id"
             . " FROM"
             . " " . USER_GROUP_TABLE
             . " WHERE auto_remove_time != 0"
             . " AND auto_remove_time <= UNIX_TIMESTAMP(NOW())";

        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result)) {
            group_user_del($row['group_id'], array($row['user_id']));
        }
        $this->db->sql_freeresult($result);

        $this->config->set('mafiascum_authentication_autoRemove_last_gc', time());
    }

    public function should_run() {
        return $this->config['mafiascum_authentication_autoRemove_last_gc'] < time() - $this->config['mafiascum_authentication_autoRemove_gc'];
    }

}
?>