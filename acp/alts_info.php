<?php

namespace mafiascum\authentication\acp;

class alts_info
{
    public function module()
    {
        return array(
            'filename'  => '\mafiascum\authentication\acp\alts_module',
            'title'     => 'ALTS_MANAGEMENT_TITLE',
            'modes'    => array(
				'manage'  => array(
                    'title' => 'ACP_ALT_MANAGE',
                    'auth'  => 'acl_a_user',
                    'cat'   => array('ALTS_MANAGEMENT_TITLE'),
                ),
            ),
        );
    }
}