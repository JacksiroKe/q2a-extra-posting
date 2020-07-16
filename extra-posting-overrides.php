<?php

/*
	Extra Posting
	https://github.com/JacksiroKe
	Add extra postingfield(s) on the question form
	
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

	/**
	 * Return an array of the default Q2A requests and which /qa-include/pages/*.php file implements them
	 * If the key of an element ends in /, it should be used for any request with that key as its prefix
	 */
	function qa_page_routing()
	{
		return array(
			'account' => 'pages/account.php',
			'activity/' => 'pages/activity.php',
			'admin/' => 'pages/admin/admin-default.php',
			'admin/approve' => 'pages/admin/admin-approve.php',
			'admin/categories' => 'pages/admin/admin-categories.php',
			'admin/flagged' => 'pages/admin/admin-flagged.php',
			'admin/hidden' => 'pages/admin/admin-hidden.php',
			'admin/layoutwidgets' => 'pages/admin/admin-widgets.php',
			'admin/moderate' => 'pages/admin/admin-moderate.php',
			'admin/pages' => 'pages/admin/admin-pages.php',
			'admin/plugins' => 'pages/admin/admin-plugins.php',
			'admin/points' => 'pages/admin/admin-points.php',
			'admin/recalc' => 'pages/admin/admin-recalc.php',
			'admin/stats' => 'pages/admin/admin-stats.php',
			'admin/userfields' => 'pages/admin/admin-userfields.php',
			'admin/usertitles' => 'pages/admin/admin-usertitles.php',
			'answers/' => 'pages/answers.php',
			//'ask' => 'pages/ask.php',
			'categories/' => 'pages/categories.php',
			'comments/' => 'pages/comments.php',
			'confirm' => 'pages/confirm.php',
			'favorites' => 'pages/favorites.php',
			'favorites/questions' => 'pages/favorites-list.php',
			'favorites/users' => 'pages/favorites-list.php',
			'favorites/tags' => 'pages/favorites-list.php',
			'feedback' => 'pages/feedback.php',
			'forgot' => 'pages/forgot.php',
			'hot/' => 'pages/hot.php',
			'ip/' => 'pages/ip.php',
			'login' => 'pages/login.php',
			'logout' => 'pages/logout.php',
			'messages/' => 'pages/messages.php',
			'message/' => 'pages/message.php',
			'questions/' => 'pages/questions.php',
			'register' => 'pages/register.php',
			'reset' => 'pages/reset.php',
			'search' => 'pages/search.php',
			'tag/' => 'pages/tag.php',
			'tags' => 'pages/tags.php',
			'unanswered/' => 'pages/unanswered.php',
			'unsubscribe' => 'pages/unsubscribe.php',
			'updates' => 'pages/updates.php',
			'user/' => 'pages/user.php',
			'users' => 'pages/users.php',
			'users/blocked' => 'pages/users-blocked.php',
			'users/new' => 'pages/users-newest.php',
			'users/special' => 'pages/users-special.php',
		);
	}

	/**
	 *	Run the appropriate /qa-include/pages/*.php file for this request and return back the $qa_content it passed
	*/
	function qa_get_request_content()
	{
		$requestlower = strtolower(qa_request());
		$requestparts = qa_request_parts();
		$firstlower = strtolower($requestparts[0]);
		$routing = qa_page_routing();

		if (isset($routing[$requestlower])) {
			qa_set_template($firstlower);
			$qa_content = require QA_INCLUDE_DIR . $routing[$requestlower];

		} elseif (isset($routing[$firstlower . '/'])) {
			qa_set_template($firstlower);
			$qa_content = require QA_INCLUDE_DIR . $routing[$firstlower . '/'];

		} elseif (is_numeric($requestparts[0])) {
			qa_set_template('question');
			$qa_content = require QA_PLUGIN_DIR . 'q2a-extra-posting/pages/question.php';

		} else {
			qa_set_template(strlen($firstlower) ? $firstlower : 'qa'); // will be changed later
			$qa_content = require QA_INCLUDE_DIR . 'pages/default.php'; // handles many other pages, including custom pages and page modules
		}

		if ($firstlower == 'ask') {
			qa_set_template($firstlower);
			$qa_content = require QA_PLUGIN_DIR . 'q2a-extra-posting/pages/ask.php';
		}

		if ($firstlower == 'admin') {
			$_COOKIE['qa_admin_last'] = $requestlower; // for navigation tab now...
			setcookie('qa_admin_last', $_COOKIE['qa_admin_last'], 0, '/', QA_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true); // ...and in future
		}

		if (isset($qa_content))
			qa_set_form_security_key();

		return $qa_content;
	}

