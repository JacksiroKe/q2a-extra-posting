<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for ask a question page


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


require_once QA_INCLUDE_DIR.'app/format.php';
require_once QA_INCLUDE_DIR.'app/limits.php';
require_once QA_INCLUDE_DIR.'db/selects.php';
require_once QA_INCLUDE_DIR.'util/sort.php';


// Check whether this is a follow-on question and get some info we need from the database

$in = array();

$followpostid = qa_get('follow');
$in['categoryid'] = qa_clicked('doask') ? qa_get_category_field_value('category') : qa_get('cat');
$userid = qa_get_logged_in_userid();

list($categories, $followanswer, $completetags) = qa_db_select_with_pending(
	qa_db_category_nav_selectspec($in['categoryid'], true),
	isset($followpostid) ? qa_db_full_post_selectspec($userid, $followpostid) : null,
	qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS)
);

if (!isset($categories[$in['categoryid']])) {
	$in['categoryid'] = null;
}

if (@$followanswer['basetype'] != 'A') {
	$followanswer = null;
}


// Check for permission error

$permiterror = qa_user_maximum_permit_error('permit_post_q', QA_LIMIT_QUESTIONS);

if ($permiterror) {
	$qa_content = qa_content_prepare();

	// The 'approve', 'login', 'confirm', 'limit', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other option ('level') prevents the menu option being shown, in qa_content_prepare(...)

	switch ($permiterror) {
		case 'login':
			$qa_content['error'] = qa_insert_login_links(qa_lang_html('question/ask_must_login'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
			break;

		case 'confirm':
			$qa_content['error'] = qa_insert_login_links(qa_lang_html('question/ask_must_confirm'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
			break;

		case 'limit':
			$qa_content['error'] = qa_lang_html('question/ask_limit');
			break;

		case 'approve':
			$qa_content['error'] = strtr(qa_lang_html('question/ask_must_be_approved'), array(
				'^1' => '<a href="' . qa_path_html('account') . '">',
				'^2' => '</a>',
			));
			break;

		default:
			$qa_content['error'] = qa_lang_html('users/no_permission');
			break;
	}

	return $qa_content;
}


// Process input

$captchareason = qa_user_captcha_reason();

$in['title'] = qa_get_post_title('title'); // allow title and tags to be posted by an external form
$in['extra'] = qa_opt('extra_field_active') ? qa_post_text('extra') : null;

if (qa_using_tags()) {
	$in['tags'] = qa_get_tags_field_value('tags');
}

if (qa_clicked('doask')) {
	require_once QA_INCLUDE_DIR.'app/post-create.php';
	require_once QA_INCLUDE_DIR.'util/string.php';

	$categoryids = array_keys(qa_category_path($categories, @$in['categoryid']));
	$userlevel = qa_user_level_for_categories($categoryids);

	$in['name'] = qa_opt('allow_anonymous_naming') ? qa_post_text('name') : null;
	$in['notify'] = strlen(qa_post_text('notify')) > 0;
	$in['content'] = qa_post_text('content');
	$in['email'] = qa_post_text('email');
	$in['queued'] = qa_user_moderation_reason($userlevel) !== false;
	$in['format'] = 'html';
	$in['text'] = '';

	$errors = array();

	if (!qa_check_form_security_code('ask', qa_post_text('code'))) {
		$errors['page'] = qa_lang_html('misc/form_security_again');
	}
	else {
		$filtermodules = qa_load_modules_with('filter', 'filter_question');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_question($in, $errors, null);
			qa_update_post_text($in, $oldin);
		}

		if (qa_using_categories() && count($categories) && (!qa_opt('allow_no_category')) && !isset($in['categoryid'])) {
			// check this here because we need to know count($categories)
			$errors['categoryid'] = qa_lang_html('question/category_required');
		}
		elseif (qa_user_permit_error('permit_post_q', null, $userlevel)) {
			$errors['categoryid'] = qa_lang_html('question/category_ask_not_allowed');
		}

		if ($captchareason) {
			require_once QA_INCLUDE_DIR.'app/captcha.php';
			qa_captcha_validate_post($errors);
		}

		if (empty($errors)) {
			// check if the question is already posted
			$testTitleWords = implode(' ', qa_string_to_words($in['title']));
			$testContentWords = implode(' ', qa_string_to_words($in['content']));
			$recentQuestions = qa_db_select_with_pending(qa_db_qs_selectspec(null, 'created', 0, null, null, false, true, 5));

			foreach ($recentQuestions as $question) {
				if (!$question['hidden']) {
					$qTitleWords = implode(' ', qa_string_to_words($question['title']));
					$qContentWords = implode(' ', qa_string_to_words($question['content']));

					if ($qTitleWords == $testTitleWords && $qContentWords == $testContentWords) {
						$errors['page'] = qa_lang_html('question/duplicate_content');
						break;
					}
				}
			}
		}

		if (empty($errors)) {
			$cookieid = isset($userid) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary

			$questionid = qa_question_create($followanswer, $userid, qa_get_logged_in_handle(), $cookieid,
				$in['title'], $in['content'], $in['format'], $in['text'], isset($in['tags']) ? qa_tags_to_tagstring($in['tags']) : '',
				$in['notify'], $in['email'], $in['categoryid'], $in['extra'], $in['queued'], $in['name']);

			qa_redirect(qa_q_request($questionid, $in['title'])); // our work is done here
		}
	}
}


// Prepare content for theme

$qa_content = qa_content_prepare(false, array_keys(qa_category_path($categories, @$in['categoryid'])));

$qa_content['title'] = qa_lang_html(isset($followanswer) ? 'question/ask_follow_title' : 'question/ask_title');
$qa_content['error'] = @$errors['page'];

$custom = qa_opt('show_custom_ask') ? trim(qa_opt('custom_ask')) : '';

$qa_content['form'] = array(
	'tags' => 'name="ask" method="post" action="'.qa_self_html().'"',

	'style' => 'tall',

	'fields' => array(
		'custom' => array(
			'type' => 'custom',
			'note' => $custom,
		),

		'title' => array(
			'label' => qa_lang_html('question/q_title_label'),
			'tags' => 'name="title" id="title" autocomplete="off"',
			'value' => qa_html(@$in['title']),
			'error' => qa_html(@$errors['title']),
		),

		'similar' => array(
			'type' => 'custom',
			'html' => '<span id="similar"></span>',
		),

		'content' => array(
			'type' => 'textarea',
			'rows' => 5,
			'label' => qa_lang_html('question/q_content_label'),
			'tags' => 'name="content" id="content" autocomplete="off"',
			'value' => qa_html(@$in['content']),
			'error' => qa_html(@$errors['content']),
		),

	),

	'buttons' => array(
		'ask' => array(
			'tags' => 'onclick="qa_show_waiting_after(this, false);',
			'label' => qa_lang_html('question/ask_button'),
		),
	),

	'hidden' => array(
		'code' => qa_get_form_security_code('ask'),
		'doask' => '1',
	),
);

if (!strlen($custom)) {
	unset($qa_content['form']['fields']['custom']);
}

if (qa_opt('do_ask_check_qs') || qa_opt('do_example_tags')) {
	$qa_content['form']['fields']['title']['tags'] .= ' onchange="qa_title_change(this.value);"';

	if (strlen(@$in['title'])) {
		$qa_content['script_onloads'][] = 'qa_title_change('.qa_js($in['title']).');';
	}
}

if (isset($followanswer)) {
	$viewer = qa_load_viewer($followanswer['content'], $followanswer['format']);

	$field = array(
		'type' => 'static',
		'label' => qa_lang_html('question/ask_follow_from_a'),
		'value' => $viewer->get_html($followanswer['content'], $followanswer['format'], array('blockwordspreg' => qa_get_block_words_preg())),
	);

	qa_array_insert($qa_content['form']['fields'], 'title', array('follows' => $field));
}

if (qa_using_categories() && count($categories)) {
	$field = array(
		'label' => qa_lang_html('question/q_category_label'),
		'error' => qa_html(@$errors['categoryid']),
	);

	qa_set_up_category_field($qa_content, $field, 'category', $categories, $in['categoryid'], true, qa_opt('allow_no_sub_category'));

	if (!qa_opt('allow_no_category')) // don't auto-select a category even though one is required
		$field['options'][''] = '';

	qa_array_insert($qa_content['form']['fields'], 'content', array('category' => $field));
}

if (qa_opt('extra_field_active')) {
	$field = array(
		'label' => qa_html(qa_opt('extra_field_prompt')),
		'tags' => 'name="extra"',
		'value' => qa_html(@$in['extra']),
		'error' => qa_html(@$errors['extra']),
	);

	qa_array_insert($qa_content['form']['fields'], null, array('extra' => $field));
}

if (qa_using_tags()) {
	$field = array(
		'error' => qa_html(@$errors['tags']),
	);

	qa_set_up_tag_field($qa_content, $field, 'tags', isset($in['tags']) ? $in['tags'] : array(), array(),
		qa_opt('do_complete_tags') ? array_keys($completetags) : array(), qa_opt('page_size_ask_tags'));

	qa_array_insert($qa_content['form']['fields'], null, array('tags' => $field));
}

if (!isset($userid) && qa_opt('allow_anonymous_naming')) {
	qa_set_up_name_field($qa_content, $qa_content['form']['fields'], @$in['name']);
}

qa_set_up_notify_fields($qa_content, $qa_content['form']['fields'], 'Q', qa_get_logged_in_email(),
	isset($in['notify']) ? $in['notify'] : qa_opt('notify_users_default'), @$in['email'], @$errors['email']);

if ($captchareason) {
	require_once QA_INCLUDE_DIR.'app/captcha.php';
	qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors, qa_captcha_reason_note($captchareason));
}

$qa_content['focusid'] = 'title';


return $qa_content;
