<?php
if (!defined('SMF'))
	require_once('SSI.php');

$hooks = array(
	'integrate_pre_include' => '$sourcedir/References.php',
	'integrate_profile_areas' => 'references_profile_areas',
	'integrate_load_permissions' => 'references_permissions',
	'integrate_bbc_codes' => 'references_bbc',
	'integrate_menu_buttons' => 'references_menu',
	'integrate_register' => 'references_register',
);

foreach ($hooks as $hook => $function)
	remove_integration_function($hook, $function);