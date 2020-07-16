<?php

/*
	Extra Posting
	https://github.com/JacksiroKe
	Add extra postingfield(s) on the question form
	
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	require_once QA_INCLUDE_DIR . 'db/admin.php';
	require_once QA_INCLUDE_DIR . 'db/maxima.php';
	require_once QA_INCLUDE_DIR . 'db/selects.php';
	require_once QA_INCLUDE_DIR . 'app/options.php';
	require_once QA_INCLUDE_DIR . 'app/admin.php';
	require_once QA_INCLUDE_DIR . 'qa-theme-base.php';
	require_once QA_INCLUDE_DIR . 'qa-app-blobs.php';
	require_once QA_PLUGIN_DIR . 'q2a-extra-posting/extra-posting.php';

	class qa_html_theme_layer extends qa_html_theme_base {
		var $plugin_directory;
		var $plugin_url;
		
		public function __construct($template, $content, $rooturl, $request)
		{
			global $qa_layers;
			$this->plugin_directory = $qa_layers['Extra Posting Admin']['directory'];
			$this->plugin_url = $qa_layers['Extra Posting Admin']['urltoroot'];
			qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
		}
		
		function doctype()
		{
			global $qa_request;
			$adminsection = strtolower(qa_request_part(1));
			$errors = array();
			$securityexpired = false;
			
			if (strtolower(qa_request_part(1)) == 'extra_posting') {
				$this->template = $adminsection;
				$this->extra_posting_navigation($adminsection);
				$this->content['suggest_next']="";
				$this->content['error'] = $securityexpired ? qa_lang_html('admin/form_security_expired') : qa_admin_page_error();
				$this->content['title'] = qa_lang_html('admin/admin_title') . ' - ' . qa_lang(extra_posting::lang.'/'.extra_posting::plugin.'_nav');
				$this->content = $this->extra_posting_admin();
			}
			qa_html_theme_base::doctype();
		}
		
		function nav_list($extra_posting_navigation, $class, $level=null)
		{
			if($this->template=='admin') {
				if ($class == 'nav-sub') {
					$extra_posting_navigation['extra_posting'] = array(
						'label' => qa_lang(extra_posting::lang.'/'.extra_posting::plugin.'_nav'),
						'url' => qa_path_html('admin/extra_posting'),
					);
				}
				if ( $this->request == 'admin/extra_posting' ) $extra_posting_navigation = array_merge(qa_admin_sub_navigation(), $extra_posting_navigation);
			}
			if(count($extra_posting_navigation) > 1 ) 
				qa_html_theme_base::nav_list($extra_posting_navigation, $class, $level=null);	
		}
		
		function extra_posting_navigation($request)
		{
			$this->content['navigation']['sub'] = qa_admin_sub_navigation();
			$this->content['navigation']['sub']['extra_posting'] = array(
				'label' => qa_lang(extra_posting::lang.'/'.extra_posting::plugin.'_nav'),	
				'url' => qa_path_html('admin/extra_posting'),  
				'selected' => ($request == 'extra_posting' ) ? 'selected' : '',
			);
			return 	$this->content['navigation']['sub'];
		}
		
		function extra_posting_admin()
		{
			$extra_posting = new extra_posting;
			$saved = '';
			$error = false;
			$error_active = array();
			$error_prompt = array();
			$error_note = array();
			$error_type = array();
			$error_attr = array();
			$error_option = array();
			$error_default = array();
			$error_form_pos = array();
			$error_display = array();
			$error_label = array();
			$error_page_pos = array();
			$error_hide_blank = array();
			$error_required = array();
			
			for($key=1; $key<=extra_posting::field_count_max; $key++) {
				$error_active[$key] = $error_prompt[$key] = $error_note[$key] = $error_type[$key] = $error_attr[$key] = $error_option[$key] = $error_default[$key] = $error_form_pos[$key] = $error_display[$key] = $error_label[$key] = $error_page_pos[$key] = $error_hide_blank[$key] = $error_required[$key] = '';
			}
			if (qa_clicked(extra_posting::save_button)) {
				qa_opt(extra_posting::field_count, qa_post_text(extra_posting::field_count.'_field'));
				qa_opt(extra_posting::maxfile_size, qa_post_text(extra_posting::maxfile_size.'_field'));
				qa_opt(extra_posting::only_image, (int)qa_post_text(extra_posting::only_image.'_field'));
				qa_opt(extra_posting::image_maxwidth, qa_post_text(extra_posting::image_maxwidth.'_field'));
				qa_opt(extra_posting::image_maxheight, qa_post_text(extra_posting::image_maxheight.'_field'));
				qa_opt(extra_posting::thumb_size, qa_post_text(extra_posting::thumb_size.'_field'));
				qa_opt(extra_posting::lightbox_effect, (int)qa_post_text(extra_posting::lightbox_effect.'_field'));
				$extra_posting->init_extra_fields(qa_post_text(extra_posting::field_count.'_field'));
				foreach ($extra_posting->extra_fields as $key => $extra_field) {
					if (trim(qa_post_text(extra_posting::field_prompt.'_field'.$key)) == '') {
						$error_prompt[$key] = qa_lang(extra_posting::lang.'/'.extra_posting::field_prompt.'_error');
						$error = true;
					}
					if (qa_post_text(extra_posting::field_type.'_field'.$key) != extra_posting::field_type_text
					&& qa_post_text(extra_posting::field_type.'_field'.$key) != extra_posting::field_type_textarea
					&& qa_post_text(extra_posting::field_type.'_field'.$key) != extra_posting::field_type_file
					&& trim(qa_post_text(extra_posting::field_option.'_field'.$key)) == '') {
						$error_option[$key] = qa_lang(extra_posting::lang.'/'.extra_posting::field_option.'_error');
						$error = true;
					}
					/*
					if ((bool)qa_post_text(extra_posting::field_display.'_field'.$key) && trim(qa_post_text(extra_posting::field_label.'_field'.$key)) == '') {
						$error_label[$key] = qa_lang(extra_posting::lang.'/'.extra_posting::field_label.'_error');
						$error = true;
					}
					*/
				}
				foreach ($extra_posting->extra_fields as $key => $extra_field) {
					qa_opt(extra_posting::field_active.$key, (int)qa_post_text(extra_posting::field_active.'_field'.$key));
					qa_opt(extra_posting::field_prompt.$key, qa_post_text(extra_posting::field_prompt.'_field'.$key));
					qa_opt(extra_posting::field_note.$key, qa_post_text(extra_posting::field_note.'_field'.$key));
					qa_opt(extra_posting::field_type.$key, qa_post_text(extra_posting::field_type.'_field'.$key));
					qa_opt(extra_posting::field_option.$key, qa_post_text(extra_posting::field_option.'_field'.$key));
					qa_opt(extra_posting::field_attr.$key, qa_post_text(extra_posting::field_attr.'_field'.$key));
					qa_opt(extra_posting::field_default.$key, qa_post_text(extra_posting::field_default.'_field'.$key));
					qa_opt(extra_posting::field_form_pos.$key, qa_post_text(extra_posting::field_form_pos.'_field'.$key));
					qa_opt(extra_posting::field_display.$key, (int)qa_post_text(extra_posting::field_display.'_field'.$key));
					qa_opt(extra_posting::field_label.$key, qa_post_text(extra_posting::field_label.'_field'.$key));
					qa_opt(extra_posting::field_page_pos.$key, qa_post_text(extra_posting::field_page_pos.'_field'.$key));
					qa_opt(extra_posting::field_hide_blank.$key, (int)qa_post_text(extra_posting::field_hide_blank.'_field'.$key));
					qa_opt(extra_posting::field_required.$key, (int)qa_post_text(extra_posting::field_required.'_field'.$key));
				}
				$saved = qa_lang_html(extra_posting::lang.'/'.extra_posting::saved_message);
			}
			if (qa_clicked(extra_posting::dfl_button)) {
				$extra_posting->init_extra_fields(extra_posting::field_count_max);
				foreach ($extra_posting->extra_fields as $key => $extra_field) {
					qa_opt(extra_posting::field_active.$key, (int)$extra_field['active']);
					qa_opt(extra_posting::field_prompt.$key, $extra_field['prompt']);
					qa_opt(extra_posting::field_note.$key, $extra_field['note']);
					qa_opt(extra_posting::field_type.$key, $extra_field['type']);
					qa_opt(extra_posting::field_option.$key, $extra_field['option']);
					qa_opt(extra_posting::field_attr.$key, $extra_field['attr']);
					qa_opt(extra_posting::field_default.$key, $extra_field['default']);
					qa_opt(extra_posting::field_form_pos.$key, $extra_field['form_pos']);
					qa_opt(extra_posting::field_display.$key, (int)$extra_field['display']);
					qa_opt(extra_posting::field_label.$key, $extra_field['label']);
					qa_opt(extra_posting::field_page_pos.$key, $extra_field['page_pos']);
					qa_opt(extra_posting::field_hide_blank.$key, (int)$extra_field['displayblank']);
					qa_opt(extra_posting::field_required.$key, (int)$extra_field['required']);
				}
				$extra_posting->extra_posting_count = extra_posting::field_count_dfl;
				qa_opt(extra_posting::field_count,$extra_posting->extra_posting_count);
				$extra_posting->init_extra_fields($extra_posting->extra_posting_count);
				$extra_posting->extra_posting_maxfile_size = extra_posting::maxfile_size_dfl;
				$extra_posting->extra_posting_only_image = extra_posting::only_image_dfl;
				$extra_posting->extra_posting_image_maxwidth = extra_posting::image_maxwidth_dfl;
				$extra_posting->extra_posting_image_maxheight = extra_posting::image_maxheight_dfl;
				$extra_posting->extra_posting_thumb_size = extra_posting::thumb_size_dfl;
				$extra_posting->extra_posting_lightbox_effect = extra_posting::lightbox_effect_dfl;
				qa_opt(extra_posting::thumb_size,$extra_posting->extra_posting_thumb_size);
				$saved = qa_lang_html(extra_posting::lang.'/'.extra_posting::reset_message);
			}
			if ($saved == '' && !$error) {
				$extra_posting->extra_posting_count = qa_opt(extra_posting::field_count);
				if(!is_numeric($extra_posting->extra_posting_count))
					$extra_posting->extra_posting_count = extra_posting::field_count_dfl;
				$extra_posting->init_extra_fields($extra_posting->extra_posting_count);
				$extra_posting->extra_posting_maxfile_size = qa_opt(extra_posting::maxfile_size);
				if(!is_numeric($extra_posting->extra_posting_maxfile_size))
					$extra_posting->extra_posting_maxfile_size = extra_posting::maxfile_size_dfl;
				$extra_posting->extra_posting_image_maxwidth = qa_opt(extra_posting::image_maxwidth);
				if(!is_numeric($extra_posting->extra_posting_image_maxwidth))
					$extra_posting->extra_posting_image_maxwidth = extra_posting::image_maxwidth_dfl;
				$extra_posting->extra_posting_image_maxheight = qa_opt(extra_posting::image_maxheight);
				if(!is_numeric($extra_posting->extra_posting_image_maxheight))
					$extra_posting->extra_posting_image_maxheight = extra_posting::image_maxheight_dfl;
				$extra_posting->extra_posting_thumb_size = qa_opt(extra_posting::thumb_size);
				if(!is_numeric($extra_posting->extra_posting_thumb_size))
					$extra_posting->extra_posting_thumb_size = extra_posting::thumb_size_dfl;
			}
			$rules = array();
			foreach ($extra_posting->extra_fields as $key => $extra_field) {
				$rules[extra_posting::field_prompt.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_note.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_type.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_option.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_attr.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_default.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_form_pos.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_display.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_label.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_page_pos.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_hide_blank.$key] = extra_posting::field_active.'_field'.$key;
				$rules[extra_posting::field_required.$key] = extra_posting::field_active.'_field'.$key;
			}
			qa_set_display_rules($qa_content, $rules);

			$ret = array();
			if($saved != '' && !$error)
				$this->content['form']['ok'] = $saved;

			$fields = array();
			$fieldoption = array();
			for($i=extra_posting::field_count_dfl;$i<=extra_posting::field_count_max;$i++) {
				$fieldoption[(string)$i] = (string)$i;
			}
			$fields[] = array(
				'id' => extra_posting::field_count,
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_count.'_label'),
				'value' => qa_opt(extra_posting::field_count),
				'tags' => 'name="'.extra_posting::field_count.'_field"',
				'type' => 'select',
				'options' => $fieldoption,
				'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_count.'_note'),
			);
			
			$fields[] = array(
				'id' => extra_posting::maxfile_size,
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::maxfile_size.'_label'),
				'value' => qa_opt(extra_posting::maxfile_size),
				'tags' => 'name="'.extra_posting::maxfile_size.'_field"',
				'type' => 'number',
				'suffix' => 'bytes',
				'note' => qa_lang(extra_posting::lang.'/'.extra_posting::maxfile_size.'_note'),
			);
			$fields[] = array(
				'id' => extra_posting::only_image,
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::only_image.'_label'),
				'type' => 'checkbox',
				'value' => (int)qa_opt(extra_posting::only_image),
				'tags' => 'name="'.extra_posting::only_image.'_field"',
			);
			$fields[] = array(
				'id' => extra_posting::image_maxwidth,
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::image_maxwidth.'_label'),
				'value' => qa_opt(extra_posting::image_maxwidth),
				'tags' => 'name="'.extra_posting::image_maxwidth.'_field"',
				'type' => 'number',
				'suffix' => qa_lang_html('admin/pixels'),
				'note' => qa_lang(extra_posting::lang.'/'.extra_posting::image_maxwidth.'_note'),
			);
			$fields[] = array(
				'id' => extra_posting::image_maxheight,
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::image_maxheight.'_label'),
				'value' => qa_opt(extra_posting::image_maxheight),
				'tags' => 'name="'.extra_posting::image_maxheight.'_field"',
				'type' => 'number',
				'suffix' => qa_lang_html('admin/pixels'),
				'note' => qa_lang(extra_posting::lang.'/'.extra_posting::image_maxheight.'_note'),
			);
			$fields[] = array(
				'id' => extra_posting::thumb_size,
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::thumb_size.'_label'),
				'value' => qa_opt(extra_posting::thumb_size),
				'tags' => 'name="'.extra_posting::thumb_size.'_field"',
				'type' => 'number',
				'suffix' => qa_lang_html('admin/pixels'),
				'note' => qa_lang(extra_posting::lang.'/'.extra_posting::thumb_size.'_note'),
			);
			$fields[] = array(
				'id' => extra_posting::lightbox_effect,
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::lightbox_effect.'_label'),
				'type' => 'checkbox',
				'value' => (int)qa_opt(extra_posting::lightbox_effect),
				'tags' => 'name="'.extra_posting::lightbox_effect.'_field"',
			);
			$type = array(extra_posting::field_type_text => qa_lang_html(
				extra_posting::lang.'/'.extra_posting::field_type_text_label),
				extra_posting::field_type_textarea => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_type_textarea_label), 
				extra_posting::field_type_check => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_type_check_label), extra_posting::field_type_select => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_type_select_label),
				extra_posting::field_type_radio => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_type_radio_label), 
				extra_posting::field_type_file => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_type_file_label),
				extra_posting::field_type_files => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_type_files_label),
				extra_posting::field_type_record => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_type_record_label )
			);

			$form_pos = array();
			$form_pos[extra_posting::field_form_pos_top] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_top_label);
			if(qa_opt('show_custom_ask'))
				$form_pos[extra_posting::field_form_pos_custom] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_custom_label);
			$form_pos[extra_posting::field_form_pos_title] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_title_label);
			if (qa_using_categories())
				$form_pos[extra_posting::field_form_pos_category] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_category_label);
			$form_pos[extra_posting::field_form_pos_content] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_content_label);
			if (qa_opt('extra_posting_active'))
				$form_pos[extra_posting::field_form_pos_extra] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_extra_label);
			if (qa_using_tags())
				$form_pos[extra_posting::field_form_pos_tags] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_tags_label);
			$form_pos[extra_posting::field_form_pos_notify] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_notify_label);
			$form_pos[extra_posting::field_form_pos_bottom] = qa_lang_html(extra_posting::lang.'/'.extra_posting::field_form_pos_bottom_label);

			$page_pos = array(extra_posting::field_page_pos_upper => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_page_pos_upper_label)
							, extra_posting::field_page_pos_inside => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_page_pos_inside_label)
							, extra_posting::field_page_pos_below => qa_lang_html(extra_posting::lang.'/'.extra_posting::field_page_pos_below_label)
			);
			
			foreach ($extra_posting->extra_fields as $key => $extra_field) {
				$fields[] = array(
					'id' => extra_posting::field_title.$key,
					'type' => 'custom',
					'html' => '<h2>' . qa_html(qa_opt(extra_posting::field_prompt.$key)) . '</h2>',
				);
				$fields[] = array(
					'id' => extra_posting::field_active.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_active.'_label',$key),
					'type' => 'checkbox',
					'value' => (int)qa_opt(extra_posting::field_active.$key),
					'tags' => 'name="'.extra_posting::field_active.'_field'.$key.'" id="'.extra_posting::field_active.'_field'.$key.'"',
					'error' => $error_active[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_prompt.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_prompt.'_label',$key),
					'value' => qa_html(qa_opt(extra_posting::field_prompt.$key)),
					'tags' => 'name="'.extra_posting::field_prompt.'_field'.$key.'" id="'.extra_posting::field_prompt.'_field'.$key.'"',
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_prompt.'_note',$key),
					'error' => $error_prompt[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_note.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_note.'_label',$key),
					'type' => 'textarea',
					'value' => qa_opt(extra_posting::field_note.$key),
					'tags' => 'name="'.extra_posting::field_note.'_field'.$key.'" id="'.extra_posting::field_note.'_field'.$key.'"',
					'rows' => $extra_posting->extra_posting_note_height,
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_note.'_note',$key),
					'error' => $error_note[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_type.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_type.'_label',$key),
					'tags' => 'name="'.extra_posting::field_type.'_field'.$key.'" id="'.extra_posting::field_type.'_field'.$key.'"',
					'type' => 'select',
					'options' => $type,
					'value' => @$type[qa_opt(extra_posting::field_type.$key)],
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_type.'_note',$key),
					'error' => $error_type[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_option.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_option.'_label',$key),
					'type' => 'textarea',
					'value' => qa_opt(extra_posting::field_option.$key),
					'tags' => 'name="'.extra_posting::field_option.'_field'.$key.'" id="'.extra_posting::field_option.'_field'.$key.'"',
					'rows' => $extra_posting->extra_posting_option_height,
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_option.'_note',$key),
					'error' => $error_option[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_attr.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_attr.'_label',$key),
					'value' => qa_html(qa_opt(extra_posting::field_attr.$key)),
					'tags' => 'name="'.extra_posting::field_attr.'_field'.$key.'" id="'.extra_posting::field_attr.'_field'.$key.'"',
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_attr.'_note',$key),
					'error' => $error_attr[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_default.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_default.'_label',$key),
					'value' => qa_html(qa_opt(extra_posting::field_default.$key)),
					'tags' => 'name="'.extra_posting::field_default.'_field'.$key.'" id="'.extra_posting::field_default.'_field'.$key.'"',
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_default.'_note',$key),
					'error' => $error_default[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_form_pos.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_form_pos.'_label',$key),
					'tags' => 'name="'.extra_posting::field_form_pos.'_field'.$key.'" id="'.extra_posting::field_form_pos.'_field'.$key.'"',
					'type' => 'select',
					'options' => $form_pos,
					'value' => @$form_pos[qa_opt(extra_posting::field_form_pos.$key)],
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_form_pos.'_note',$key),
					'error' => $error_form_pos[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_display.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_display.'_label',$key),
					'type' => 'checkbox',
					'value' => (int)qa_opt(extra_posting::field_display.$key),
					'tags' => 'name="'.extra_posting::field_display.'_field'.$key.'" id="'.extra_posting::field_display.'_field'.$key.'"',
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_display.'_note',$key),
					'error' => $error_display[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_label.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_label.'_label',$key),
					'value' => qa_html(qa_opt(extra_posting::field_label.$key)),
					'tags' => 'name="'.extra_posting::field_label.'_field'.$key.'" id="'.extra_posting::field_label.'_field'.$key.'"',
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_label.'_note',$key),
					'error' => $error_label[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_page_pos.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_page_pos.'_label',$key),
					'tags' => 'name="'.extra_posting::field_page_pos.'_field'.$key.'" id="'.extra_posting::field_page_pos.'_field'.$key.'"',
					'type' => 'select',
					'options' => $page_pos,
					'value' => @$page_pos[qa_opt(extra_posting::field_page_pos.$key)],
					'note' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_page_pos.'_note',str_replace('^', $key, extra_posting::field_page_pos_hook)),
					'error' => $error_page_pos[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_hide_blank.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_hide_blank.'_label',$key),
					'type' => 'checkbox',
					'value' => (int)qa_opt(extra_posting::field_hide_blank.$key),
					'tags' => 'name="'.extra_posting::field_hide_blank.'_field'.$key.'" id="'.extra_posting::field_hide_blank.'_field'.$key.'"',
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_hide_blank.'_note',$key),
					'error' => $error_hide_blank[$key],
				);
				$fields[] = array(
					'id' => extra_posting::field_required.$key,
					'label' => qa_lang_html_sub(extra_posting::lang.'/'.extra_posting::field_required.'_label',$key),
					'type' => 'checkbox',
					'value' => (int)qa_opt(extra_posting::field_required.$key),
					'tags' => 'name="'.extra_posting::field_required.'_field'.$key.'" id="'.extra_posting::field_required.'_field'.$key.'"',
					'note' => qa_lang(extra_posting::lang.'/'.extra_posting::field_required.'_note',$key),
					'error' => $error_required[$key],
				);
			}
			
			$buttons = array();
			$buttons['save'] = array(
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::save_button),
				'tags' => 'name="'.extra_posting::save_button.'" id="'.extra_posting::save_button.'"',
			);
			$buttons['reset'] = array(
				'label' => qa_lang_html(extra_posting::lang.'/'.extra_posting::dfl_button),
				'tags' => 'name="'.extra_posting::dfl_button.'" id="'.extra_posting::dfl_button.'"',
			);
			
			$this->content['form'] = array(
				'tags' => 'method="post" action="'.qa_path_html(qa_request()).'"',
				'style' => 'wide',
				'fields' => $fields,
				'buttons' => $buttons
			);
			
			if($saved != '' && !$error)
				$this->content['form']['ok'] = $saved;

			return $this->content;
		}
		
		
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
