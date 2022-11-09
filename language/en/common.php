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
	'ALT_MANAGEMENT' => 'Manage Alts',
	'ALT_MANAGE' => 'Manage Alts',
	'ALTS_MANAGE' => 'Manage Alts',
	'ASSOCIATED_ACCOUNTS' => 'Associated Accounts',
	'OTHER_ASSOC' => 'Other associations',
	'ADD_ALT' => 'Add Alt',
	'ACCOUNT_TYPE' => 'Account Type',
	'ALTS_MANAGEMENT_TITLE' => 'ALT MANAGEMENT',
	'ACP_ALT_MANAGE' => 'Manage Alts',
	'SELECT_USER' => 'Select a user',
	'USER_ADMIN_EXPLAIN' => 'search for a user to manage',
	'NO_MATCHES_FOUND' => 'No matches found',
	'L_ACCOUNT_LINK' => 'Account Alts',
	'NO_USER' => 'No user found',
	'ACTIVATE_VLA'					=> 'Declare this account on V/LA',
	'CLEAR_VLA'						=> 'Clear V/LA',
	'VLA_SET_START_DATE'			=> 'V/LA Status Start Date',
	'VLA_SET_END_DATE'				=> 'V/LA Status End Date',
	'NO_VLA_DATA'				=> 'No V/LA start or end dates were specified. Ensure that these fields are correctly entered.',
	'MISMATCHED_VLA_DATE'		=> 'You cannot end a V/LA before it starts.',
	'VLA_DATE_PRIOR'			=> 'You cannot start or end a V/LA in the past.',
	'VLA_TOO_SMALL'				=> 'Your V/LA must be at least three days in length.',
	'VLA_TOO_LARGE'				=> 'Your V/LA must be no longer than two months.',
	'UCP_VLA_SETTINGS_TITLE'    => 'Vacation / Limited Access',
	'UCP_VLA_TITLE'             => 'Vacation / Limited Access',
	'VLA_UNTIL'                 => 'On Vacation/Limited Access Until %s',
	'MAFIA_WIKI'                => 'Mafia wiki',
	'RULES'                     => 'Rules',
	'VIEWTOPIC_BIRTHDAY'        => 'Happy Birthday!',
	'VIEWTOPIC_SCUMDAY'        => 'Happy Scumday!',
));
