<?php
/**
*
* @package phpBB Extension - MafiaScum Authentication
* @copyright (c) 2017 mafiascum.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
    "ALT_OF"         => "Main account / Hydra members",
    "ALT_OF_EXPLAIN" => "This account may act as an alt or hydra of the following users. These accounts will receive PMs via which to verify this status.",
    "ADD_USER"       => "Add User",
    "ALT_REQUEST_PM_SUBJECT" => "Request to flag this account as alias of %s",
    "ALT_REQUEST_PM_BODY" => 'Hello,

The account "%s" would like to add you as a main or alias. Please [url="app.php/verify_alt_request?alt_request_id=%s&token=%s"]click here[/url] to confirm this request. If this action was performed in error, you may reply to this PM or ignore this request.',
    'ALT_REQUEST_PENDING' => " (Pending)",
	'ERROR_CANNOT_ADD_SELF_AS_MAIN_OR_ALIAS' => 'You may not add yourself as a main or alias.',
    'OLD_EMAILS' => 'Old emails',
    'VERIFICATION_REQUEST_CONFIRMED' => 'This verification request has been successfully confirmed!',
	'VERIFICATION_REQUEST_DOES_NOT_EXIST' => 'This verification request does not exist, has already been verified, or is not associated with this user.',
	'WIKI_PAGE' => 'Wiki page',
));
