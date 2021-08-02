<?php

namespace mafiascum\authentication\migrations;

class authentication_alt_requests extends \phpbb\db\migration\migration
{

    public function effectively_installed()
    {	
        return $this->db_tools->sql_table_exists($this->table_prefix . 'alt_requests');
    }
    
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v31x\v314');
    }
	
    public function update_schema()
    {
        return array(
            'add_tables'    => array(
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
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables'    => array(
                $this->table_prefix . 'alt_requests',
            ),
        );
    }
}
?>