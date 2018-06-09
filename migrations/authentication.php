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
    
	public function update_data()
    {
        return array(

            // Add a parent module ALTS_MANAGEMENT to the Extensions tab (ACP_CAT_DOT_MODS)
            array('module.add', array(
                'acp',
                'ACP_CAT_USERGROUP',
                'ALTS_MANAGEMENT_TITLE'
            )),

            // Add our main_module to the parent module (ACP_DEMO_TITLE)
            array('module.add', array(
                'acp',
                'ALTS_MANAGEMENT_TITLE',
                array(
                    'module_basename'       => '\mafiascum\authentication\acp\alts_module',
                    'modes'                         => array('manage'),
                ),
            )),
        );
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
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables'    => array(
                $this->table_prefix . 'alts',
                $this->table_prefix . 'alt_requests',
            ),
        );
    }
}
?>