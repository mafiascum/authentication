<?php 

namespace mafiascum\authentication\migrations;

class vla_module extends \phpbb\db\migration\migration {
    public function effectively_installed() {
        $sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'ucp'
			AND module_basename = 'UCP_VLA_TITLE'";
		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return $module_id !== false;
    }

    public function update_data() {
        return array(
			// Add the UCP vla category, a top level category
			array('module.add', array(
			   'ucp',
			   false,
			   'UCP_VLA_TITLE',
			)),
			// Add the four UCP digest modules
			array('module.add', array(
				'ucp', 
				'UCP_VLA_TITLE', 
				array(
					'module_basename'   => '\mafiascum\authentication\ucp\vla_module',
					'modes' => array('settings'),
				),
			)),

		);
    }
}