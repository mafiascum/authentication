<?php

namespace mafiascum\authentication\controller;

class verifyAltRequest
{
    /* @var \phpbb\request\request */
    protected $request;

    /* @var \phpbb\user */
    protected $user;

    /* @var \phpbb\db\driver\driver */
	protected $db;

    /* phpbb\language\language */
    protected $language;

    /* @var \phpbb\template\template */
    protected $template;

    /* @var \phpbb\controller\helper */
    protected $helper;
    
    public function __construct(\phpbb\request\request $request, \phpbb\user $user, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\template\template $template, \phpbb\controller\helper $helper)
    {
        $this->request = $request;
        $this->user = $user;
        $this->db = $db;
        $this->language = $language;
        $this->template = $template;
        $this->helper = $helper;
    }

    public function handle()
    {
        global $table_prefix;
        
        $alt_request_id = $this->request->variable('alt_request_id', 0);
        $token = $this->db->sql_escape($this->request->variable('token', ''));

        $main_user_id = $this->user->data['user_id'];
            
        $sql = "select alt_user_id from " . $table_prefix . "alt_requests";
        $sql = $sql . " where alt_request_id = " . $alt_request_id;
        $sql = $sql . " and main_user_id = " . $main_user_id;
        $sql = $sql . " and token = '" . $token . "'";
            
        $result = $this->db->sql_query($sql);
            
        if ($row = $this->db->sql_fetchrow($result)) {
            $sql = "delete from " . $table_prefix . "alt_requests";
            $sql = $sql . " where alt_request_id = " . $alt_request_id;
            $this->db->sql_query($sql);
                
            $sql = "insert into " . $table_prefix . "alts";
            $sql = $sql . " (alt_user_id, main_user_id) ";
            $sql = $sql . " values (" . $row['alt_user_id'] . "," . $main_user_id . ")";
            $this->db->sql_query($sql);
                
            $confirmation_response = $this->language->lang('VERIFICATION_REQUEST_CONFIRMED');
        } else {
            $confirmation_response = $this->language->lang('VERIFICATION_REQUEST_DOES_NOT_EXIST');
        }

        trigger_error($confirmation_response);
    }
}
?>