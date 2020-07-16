<?php
/*
	Extra Posting by JacksiroKe
	https://www.github.com/JacksiroKe
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

	$plugin_dir = dirname( __FILE__ ) . '/';
	$plugin_url = qa_path_to_root().'qa-plugin/q2a-extra-posting';
	qa_register_layer('extra-posting-admin.php', 'Extra Posting Admin', $plugin_dir, $plugin_url );
	
	qa_register_plugin_phrases('langs/extra-posting-lang-*.php', 'extra_posting_lang');
	qa_register_plugin_module('module', 'extra-posting.php', 'extra_posting', 'Extra Posting');
	qa_register_plugin_module('event', 'extra-posting-event.php', 'extra_posting_event', 'Extra Posting');
	qa_register_plugin_layer('extra-posting-layer.php', 'Extra Posting');
	qa_register_plugin_module('filter', 'extra-posting-filter.php', 'extra_posting_filter', 'Extra Posting');
	qa_register_plugin_overrides('extra-posting-overrides.php');
/*
	Omit PHP closing tag to help avoid accidental output
*/