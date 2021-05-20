<?php

namespace mafiascum\authentication\ucp;

class vla_info
{
    public function module()
    {
        return array(
            'filename'  => '\mafiascum\authentication\ucp\vla_module',
            'title'     => 'UCP_VLA_TITLE',
            'modes'    => array(
				'settings'  => array(
                    'title' => 'UCP_VLA_SETTINGS_TITLE',
                    'cat'   => array('UCP_VLA_TITLE'),
                ),
            ),
        );
    }
}