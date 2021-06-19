<?php

function references_bbc(array &$bbc_tags) {
	global $scripturl;

	$bbc_tags[] = array(
		'tag' => 'referencemember',
		'type' => 'unparsed_equals',
		'before' => '<a href="' . $scripturl . '?action=profile;u=$1" class="reference">',
		'after' => '</a>',
	);
}

function references_permissions(array &$permissionGroups, array &$permissionList, array &$leftPermissionGroups, array &$hiddenPermissions, array &$relabelPermissions) {
	loadLanguage('References');
	$permissionList['membergroup']['reference_member'] = array(false, 'general', 'view_basic_info');
}

function references_process_post(&$msgOptions, &$topicOptions, &$posterOptions){
    global $smcFunc, $user_info;

	if (!allowedTo('reference_member')){
        return;
    }
    
	$body = htmlspecialchars_decode(preg_replace('~<br\s*/?\>~', "\n", str_replace('&nbsp;', ' ', $msgOptions['body'])), ENT_QUOTES);

	$matches = array();
	$string = str_split($body);
	$depth = 0;
	foreach ($string as $char) {
		if ($char == '$') {
			$depth++;
			$matches[] = array();
		} elseif ($char == "\n"){
            $depth = 0;
        }

		for ($i = $depth; $i > 0; $i--) {
			if (count($matches[count($matches) - $i]) > 60) {
				$depth--;
				break;
			}
			$matches[count($matches) - $i][] = $char;
		}
	}

	foreach ($matches as $k => $match){
        $matches[$k] = substr(implode('', $match), 1);
    }
    
	if (empty($matches)){
        return;
    }
    
	$names = array();
	foreach ($matches as $match){
		$match = preg_split('/([^\w])/', $match, -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($i = 1; $i <= count($match); $i++){
            $names[] = implode('', array_slice($match, 0, $i));
        }
	}

    $names = array_unique(array_map('trim', $names));
    
	$request = $smcFunc['db_query']('', '
		SELECT id_member, real_name
		FROM {db_prefix}members
		WHERE real_name IN ({array_string:names})
		ORDER BY LENGTH(real_name) DESC
		LIMIT {int:count}',
		array(
			'names' => $names,
			'count' => count($names),
		)
	);
	$members = array();
	while ($row = $smcFunc['db_fetch_assoc']($request)){
		$members[$row['id_member']] = array(
			'id' => $row['id_member'],
			'real_name' => $row['real_name']
        );
    }
	$smcFunc['db_free_result']($request);

	if (empty($members)){
        return;
    }
        
	foreach ($members as $member) {
		if (stripos($msgOptions['body'], '$' . $member['real_name']) === false){
            continue;
        }

		$msgOptions['body'] = str_ireplace('$' . $member['real_name'], '[referencemember=' . $member['id'] . ']' . $member['id'] . '[/referencemember]', $msgOptions['body']);
	}
}

function references_post_scripts(){
	global $settings, $context;

	if (!allowedTo('reference_member')){
        return;
    }

	$context['insert_after_template'] .= '
		<script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/references.js"></script>';
}