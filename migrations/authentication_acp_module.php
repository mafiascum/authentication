<?php

namespace mafiascum\authentication\migrations;

class authentication_acp_module extends \phpbb\db\migration\migration
{

    public function effectively_installed()
    {	
        $sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
			AND module_basename = 'ALTS_MANAGEMENT_TITLE'";
		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return $module_id !== false;
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
}
?>