<?php

if (!defined('SMF'))
	require_once('SSI.php');

global $smcFunc, $modSettings;

db_extend('Packages');
db_extend('Extra');

$hooks = array(
	'integrate_pre_include' => '$sourcedir/References.php',
	'integrate_load_permissions' => 'references_permissions',
	'integrate_bbc_codes' => 'references_bbc'
);

foreach ($hooks as $hook => $function)
	add_integration_function($hook, $function);