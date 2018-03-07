<?php

namespace mafiascum\authentication\migrations;

class authentication extends \phpbb\db\migration\migration
{

    public function effectively_installed()
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'alts');
    }
    
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v31x\v314');
    }
    
    public function update_schema()
    {
        return array(
            'add_tables'    => array(
                $this->table_prefix . 'alts' => array(
                    'COLUMNS' => array(
                        'main_user_id'             => array('UINT', 0),
                        'alt_user_id'              => array('UINT', 0),
                    ),
                    'KEYS' => array(
                        'main_user_id' => array('UNIQUE', 'main_user_id', 'alt_user_id'),
                    ),
                ),
                $this->table_prefix . 'alt_requests' => array(
                    'COLUMNS' => array(
                        'alt_request_id'           => array('UINT', NULL, 'auto_increment'),
                        'main_user_id'             => array('UINT', 0),
                        'alt_user_id'              => array('UINT', 0),
                        'token'                    => array('VCHAR:255', ''),
                    ),
                    'PRIMARY_KEY' => 'alt_request_id',
                    'KEYS' => array(
                        'main_user_id' => array('UNIQUE', 'main_user_id', 'alt_user_id'),
                    ),
                ),
            ),
            'add_columns' => array(
                $this->table_prefix . 'user_group' => array(
                    'auto_remove_time' => array('UINT:11', 0),
				),
				$this->table_prefix . 'users' => array(
					'user_old_emails' => array('TEXT', '')
				)
			),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables'    => array(
                $this->table_prefix . 'alts',
                $this->table_prefix . 'alt_requests',
            ),
            'drop_columns'   => array(
                $this->table_prefix . 'user_group' => array(
                    'auto_remove_time',
                ),
            ),
        );
    }
}
?>