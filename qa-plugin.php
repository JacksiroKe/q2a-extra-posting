<?php
/*
	Plugin Name: Extra Question Field
	Plugin URI: https://github.com/JacksiroKe/q2a-extra-question
	Plugin Description: Add extra field(s) on the question form
	Plugin Version: 2.1
	Plugin Date: 2015-02-04
	Plugin Author: JacksiroKe
	Plugin Author URI: https://github.com/JacksiroKe
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Update Check URI:
*/
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

	$plugin_dir = dirname( __FILE__ ) . '/';
	$plugin_url = qa_path_to_root().'qa-plugin/q2a-extra-question-field';
	qa_register_layer('qa-eqf-admin.php', 'Extra Question Field Admin', $plugin_dir, $plugin_url );
	
	qa_register_plugin_phrases('langs/qa-eqf-lang-*.php', 'eqf_lang');
	qa_register_plugin_module('module', 'qa-eqf.php', 'qa_eqf', 'Extra Question Field');
	qa_register_plugin_module('event', 'qa-eqf-event.php', 'qa_eqf_event', 'Extra Question Field');
	qa_register_plugin_layer('qa-eqf-layer.php', 'Extra Question Field');
	qa_register_plugin_module('filter', 'qa-eqf-filter.php', 'qa_eqf_filter', 'Extra Question Field');
/*
	Omit PHP closing tag to help avoid accidental output
*/