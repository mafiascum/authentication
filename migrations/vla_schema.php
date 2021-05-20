<?php 

namespace mafiascum\authentication\migrations;

class vla_schema extends \phpbb\db\migration\migration {
    public function effectively_installed() {
		return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_vla_start');
	}

    public function update_schema()
	{
		return array(
			'add_columns'        => array(
				$this->table_prefix . 'users'    => array(
					'user_vla_start' => array('VCHAR:10', ''),
					'user_vla_till' => array('VCHAR:10', ''),
				),
			),
		);
	}

	public function revert_schema() {
		return array(
			'drop_columns'        => array(
				$this->table_prefix . 'users'        => array(
					'user_vla_start',
					'user_vla_till',
				),
			),
		);
	}
}