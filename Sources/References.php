<?php

function references_bbc(array &$bbc_tags) {
	global $scripturl;

	$bbc_tags[] = array(
		'tag' => 'referencemember',
		'type' => 'unparsed_equals',
		'before' => '<a href="' . $scripturl . '?action=profile;u=$1" class="reference">$',
		'after' => '</a>',
	);
}

function references_permissions(array &$permissionGroups, array &$permissionList, array &$leftPermissionGroups, array &$hiddenPermissions, array &$relabelPermissions) {
	loadLanguage('References');
	$permissionList['membergroup']['reference_member'] = array(false, 'general', 'view_basic_info');
}

function references_process_post(&$msgOptions, &$topicOptions, &$posterOptions){
    global $smcFunc, $user_info;
    
	$body = htmlspecialchars_decode(preg_replace('~<br\s*/?\>~', "\n", str_replace('&nbsp;', ' ', $msgOptions['body'])), ENT_QUOTES);

	$matches = array();
	$string = str_split($body);
	$depth = 0;
	foreach ($string as $char)
	{
		if ($char == '$')
		{
			$depth++;
			$matches[] = array();
		}
		elseif ($char == "\n")
			$depth = 0;

		for ($i = $depth; $i > 0; $i--)
		{
			if (count($matches[count($matches) - $i]) > 60)
			{
				$depth--;
				break;
			}
			$matches[count($matches) - $i][] = $char;
		}
	}

	foreach ($matches as $k => $match)
		$matches[$k] = substr(implode('', $match), 1);

	// Names can have spaces, or they can't...we try to match every possible
	if (empty($matches) || !allowedTo('mention_member'))
		return;

	// Names can have spaces, other breaks, or they can't...we try to match every possible
	// combination.
	$names = array();
	foreach ($matches as $match)
	{
		$match = preg_split('/([^\w])/', $match, -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($i = 1; $i <= count($match); $i++)
			$names[] = implode('', array_slice($match, 0, $i));
	}

	$names = array_unique(array_map('trim', $names));

	// Get the membergroups this message can be seen by
	$request = $smcFunc['db_query']('', '
		SELECT b.member_groups
		FROM {db_prefix}boards AS b
		WHERE id_board = {int:board}',
		array(
			'board' => $topicOptions['board'],
		)
	);
	list ($member_groups) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);
	$member_groups = explode(',', $member_groups);
	foreach ($member_groups as $k => $group)
		// Dunno why
		if (strlen($group) == 0)
			unset($member_groups[$k]);

	// Attempt to fetch all the valid usernames along with their required metadata
	$request = $smcFunc['db_query']('', '
		SELECT id_member, real_name, email_mentions, email_address, unread_mentions, id_group, id_post_group, additional_groups
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
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$members[$row['id_member']] = array(
			'id' => $row['id_member'],
			'real_name' => $row['real_name'],
			'email_mentions' => $row['email_mentions'],
			'email_address' => $row['email_address'],
			'unread_mentions' => $row['unread_mentions'],
			'groups' => array_unique(array_merge(array($row['id_group'], $row['id_post_group']), explode(',', $row['additional_groups']))),
		);
	$smcFunc['db_free_result']($request);

	if (empty($members))
		return;

	// Replace all the tags with BBCode ([member=<id>]<username>[/member])
	$msgOptions['mentions'] = array();
	foreach ($members as $member)
	{
		if (stripos($msgOptions['body'], '@' . $member['real_name']) === false
			|| (!in_array(1, $member['groups']) && count(array_intersect($member['groups'], $member_groups)) == 0))
			continue;

		$msgOptions['body'] = str_ireplace('@' . $member['real_name'], '[member=' . $member['id'] . ']' . $member['real_name'] . '[/member]', $msgOptions['body']);

		// Why would an idiot mention themselves?
		if ($user_info['id'] == $member['id'])
			continue;

		$msgOptions['mentions'][] = $member;
	}
}

function references_post_scripts()
{
	global $settings, $context;

	if (!allowedTo('reference_member'))
		return;

	$context['insert_after_template'] .= '
		<script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/references.js"></script>';
}