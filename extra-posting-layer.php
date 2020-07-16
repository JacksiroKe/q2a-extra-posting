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

require_once QA_INCLUDE_DIR.'qa-theme-base.php';
require_once QA_INCLUDE_DIR.'qa-app-blobs.php';
require_once QA_PLUGIN_DIR.'q2a-extra-posting/extra-posting.php';

class qa_html_theme_layer extends qa_html_theme_base {

	private $extradata;
	private $pluginurl;
	
	function doctype() {
		qa_html_theme_base::doctype();
		$this->pluginurl = qa_opt('site_url').'qa-plugin/q2a-extra-posting/';
		if($this->template == 'question') {
			if(isset($this->content['q_view']['raw']['postid']))
				$this->extradata = $this->extra_posting_get_extradata($this->content['q_view']['raw']['postid']);
		}
	}

	function head_script() {
		qa_html_theme_base::head_script();
		if ($this->template == 'ask') {
			$this->output('<script type="text/javascript" src="'.$this->pluginurl.'js/extra-posting.js"></script>');
			$this->output('<script>');
			$this->output('$(function () {');
			$this->output('	$(\'.input-files-1\').fileUploader();');
			$this->output('});');
			$this->output('</script>');
		}
		if(count((array)$this->extradata) && $this->extra_posting_file_exist() && qa_opt(extra_posting::lightbox_effect)) {
			$this->output('<script type="text/javascript" src="'.$this->pluginurl.'js/jquery.magnific-popup.min.js"></script>');
			$this->output('<script type="text/javascript">');
			$this->output('$(function(){');
			$this->output('	$(".qa-q-view-extra-upper-img, .qa-q-view-extra-inside-img, .qa-q-view-extra-img").magnificpopup({');
			$this->output('		type: \'image\',');
			$this->output('		terror: \'<a href="%url%">the image</a> could not be loaded.\',');
			$this->output('		image: {');
			$this->output('			titlesrc: \'title\'');
			$this->output('		},');
			$this->output('		gallery: {');
			$this->output('			enabled: true');
			$this->output('		},');
			$this->output('		callbacks: {');
			$this->output('			elementparse: function(item) {');
			$this->output('				console.log(item);');
			$this->output('			}');
			$this->output('		}');
			$this->output('	});');
			$this->output('});');
			$this->output('</script>');
		}
	}
	
	function head_css() {
		qa_html_theme_base::head_css();
		if(count((array)$this->extradata) && $this->extra_posting_file_exist() && qa_opt(extra_posting::lightbox_effect)) {
			$this->output('<link rel="stylesheet" type="text/css" href="'.$this->pluginurl.'css/magnific-popup.css"/>');
		}
		if ($this->template == 'ask') {
			$this->output('<link href="' . $this->pluginurl . 'css/extra-posting.css" rel="stylesheet" type="text/css">');
		}
	}

	function main() {
		if($this->template == 'ask') {
			if(isset($this->content['form']['fields']))
				$this->extra_posting_add_field(null, $this->content['form']['fields'], $this->content['form']);
		} else if(isset($this->content['form_q_edit']['fields'])) {
				$this->extra_posting_add_field($this->content['q_view']['raw']['postid'], $this->content['form_q_edit']['fields'], $this->content['form_q_edit']);
		}
		qa_html_theme_base::main();
	}

	function q_view_content($q_view) {
		if(!isset($this->content['form_q_edit'])) {
			$this->extra_posting_output($q_view, extra_posting::field_page_pos_upper);
			$this->extra_posting_output($q_view, extra_posting::field_page_pos_inside);
			$this->extra_posting_clearhook($q_view);
		}
		qa_html_theme_base::q_view_content($q_view);
	}

	function q_view_extra($q_view) {
		qa_html_theme_base::q_view_extra($q_view);
		if(!isset($this->content['form_q_edit'])) {
			$this->extra_posting_output($q_view, extra_posting::field_page_pos_below);
		}
	}
	
	function extra_posting_add_field($postid, &$fields, &$form) {
		global $qa_extra_question_fields;
		$multipart = false;
		for($key=extra_posting::field_count_max; $key>=1; $key--) {
			if((bool)qa_opt(extra_posting::field_active.$key)) {
				$field = array();
				$name = extra_posting::field_base_name.$key;
				$field['label'] = qa_opt(extra_posting::field_prompt.$key);
				$type = qa_opt(extra_posting::field_type.$key);
				switch ($type) {
					case extra_posting::field_type_file:
						$field['type'] = 'custom';
						$value = qa_db_single_select(qa_db_post_meta_selectspec($postid, 'qa_q_'.$name));
						$original = '';
						if(!empty($value)) {
							$blob = qa_read_blob($value);
							$format = $blob['format'];
							$bloburl = qa_get_blob_url($value);
							$imageurl = str_replace('qa=blob', 'qa=image', $bloburl);
							$filename = $blob['filename'];
							$original = $filename;
							$width = $this->extra_posting_get_image_width($blob['content']);
							if($width > qa_opt(extra_posting::thumb_size))
								$width = qa_opt(extra_posting::thumb_size);
							if($format == 'jpg' || $format == 'jpeg' || $format == 'png' || $format == 'gif')
								$original = '<img src="'.$imageurl.'&qa_size='.$width.'" alt="'.$filename.'" id="'.$name.'-thumb" class="'.extra_posting::field_base_name.'-thumb"/>';
							$original = '<a href="'.$imageurl.'" target="_blank" id="'.$name.'-link" class="'.extra_posting::field_base_name.'-link">' . $original . '</a>';
							$original .= '<input type="checkbox" name="'.$name.'-remove" id="'.$name.'-remove" class="'.extra_posting::field_base_name.'-remove"/><label for="'.$name.'-remove">'.qa_lang(extra_posting::plugin.'/extra_field_remove').'</label><br>';
							$original .= '<input type="hidden" name="'.$name.'-old" id="'.$name.'-old" value="'.$value.'"/>';
						}
						$field['html'] = $original.'<input type="file" class="qa-form-tall-'.$type.'" name="'.$name.'">';
						$multipart = true;
						break;

					case extra_posting::field_type_files:
						$field['type'] = 'custom';
						$value = qa_db_single_select(qa_db_post_meta_selectspec($postid, 'qa_q_'.$name));
						$original = '';
						if(!empty($value)) {
							$blob = qa_read_blob($value);
							$format = $blob['format'];
							$bloburl = qa_get_blob_url($value);
							$imageurl = str_replace('qa=blob', 'qa=image', $bloburl);
							$filename = $blob['filename'];
							$original = $filename;
							$width = $this->extra_posting_get_image_width($blob['content']);
							if($width > qa_opt(extra_posting::thumb_size))
								$width = qa_opt(extra_posting::thumb_size);
							if($format == 'jpg' || $format == 'jpeg' || $format == 'png' || $format == 'gif')
								$original = '<img src="'.$imageurl.'&qa_size='.$width.'" alt="'.$filename.'" id="'.$name.'-thumb" class="'.extra_posting::field_base_name.'-thumb"/>';
							$original = '<a href="'.$imageurl.'" target="_blank" id="'.$name.'-link" class="'.extra_posting::field_base_name.'-link">' . $original . '</a>';
							$original .= '<input type="checkbox" name="'.$name.'-remove" id="'.$name.'-remove" class="'.extra_posting::field_base_name.'-remove"/><label for="'.$name.'-remove">'.qa_lang(extra_posting::plugin.'/extra_field_remove').'</label><br>';
							$original .= '<input type="hidden" name="'.$name.'-old" id="'.$name.'-old" value="'.$value.'"/>';
						}
						//$field['html'] = $original.'<input type="file" multiple="" class="qa-form-tall-'.$type.'" id="'.$name.'" name="'.$name.'[]">';
						/*$field['html'] = $original.'
						<div class="input-files-1" style="padding-top: .5rem;">
							<div class="file-uploader">
								<input type="file" id="'.$name.'" name="'.$name.'[]" multiple="multiple">
								<div class="uploaded"></div>
								<div class="upload-text">
									<span>Drag &amp; Drop files here or click to browse</span>
								</div>
							</div>
						</div>';*/
						$field['html'] = $original.'<div class="input-files-1" style="padding-top: .5rem;"></div>';

						$multipart = true;
						break;
					
					case extra_posting::field_type_record:
						$field['type'] = 'custom';
						$value = qa_db_single_select(qa_db_post_meta_selectspec($postid, 'qa_q_'.$name));
						$original = '';
						if(!empty($value)) {
							$blob = qa_read_blob($value);
							$format = $blob['format'];
							$bloburl = qa_get_blob_url($value);
							$imageurl = str_replace('qa=blob', 'qa=image', $bloburl);
							$filename = $blob['filename'];
							$original = $filename;
							$width = $this->extra_posting_get_image_width($blob['content']);
							if($width > qa_opt(extra_posting::thumb_size))
								$width = qa_opt(extra_posting::thumb_size);
							if($format == 'jpg' || $format == 'jpeg' || $format == 'png' || $format == 'gif')
								$original = '<img src="'.$imageurl.'&qa_size='.$width.'" alt="'.$filename.'" id="'.$name.'-thumb" class="'.extra_posting::field_base_name.'-thumb"/>';
							$original = '<a href="'.$imageurl.'" target="_blank" id="'.$name.'-link" class="'.extra_posting::field_base_name.'-link">' . $original . '</a>';
							$original .= '<input type="checkbox" name="'.$name.'-remove" id="'.$name.'-remove" class="'.extra_posting::field_base_name.'-remove"/><label for="'.$name.'-remove">'.qa_lang(extra_posting::plugin.'/extra_field_remove').'</label><br>';
							$original .= '<input type="hidden" name="'.$name.'-old" id="'.$name.'-old" value="'.$value.'"/>';
						}
						$field['html'] = $original.'<input type="file" class="qa-form-tall-'.$type.'" name="'.$name.'">';
						$multipart = true;
						break;

					default:
						$field['type'] = qa_opt(extra_posting::field_type.$key);
						$field['tags'] = 'name="'.$name.'"';
						$options = $this->extra_posting_options(qa_opt(extra_posting::field_option.$key));
						if (qa_opt(extra_posting::field_attr.$key) != '')
							$field['tags'] .= ' '.qa_opt(extra_posting::field_attr.$key);
						if ($field['type'] != extra_posting::field_type_text && $field['type'] != extra_posting::field_type_textarea)
							$field['options'] = $options;
						if(is_null($postid))
							$field['value'] = qa_opt(extra_posting::field_default.$key);
						else
							$field['value'] = qa_db_single_select(qa_db_post_meta_selectspec($postid, 'qa_q_'.$name));
						if ($field['type'] != extra_posting::field_type_text && $field['type'] != extra_posting::field_type_textarea && is_array($field['options'])) {
							if($field['type'] == extra_posting::field_type_check) {
								if($field['value'] == 0)
									$field['value'] = '';
							} else
								$field['value'] = @$field['options'][$field['value']];
						}
						if ($field['type'] == extra_posting::field_type_textarea) {
							if(isset($options[0]))
								$field['rows'] = $options[0];
							if(empty($field['rows']))
								$field['rows'] = extra_posting::field_option_rows_dfl;
						}
						break;
				}
				$field['note'] = nl2br(qa_opt(extra_posting::field_note.$key));
				if(isset($qa_extra_question_fields[$name]['error']))
					$field['error'] = $qa_extra_question_fields[$name]['error'];
				$this->extra_posting_insert_array($fields, $field, $name, qa_opt(extra_posting::field_form_pos.$key));
			}
		}
		if($multipart) {
			$form['tags'] .= ' enctype="multipart/form-data"';
		}
	}
	function extra_posting_insert_array(&$items, $insertitem, $insertkey, $findkey) {
		$newitems = array();
		if($findkey == extra_posting::field_form_pos_top) {
			$newitems[$insertkey] = $insertitem;
			foreach($items as $key => $item)
				$newitems[$key] = $item;
		} elseif($findkey == extra_posting::field_form_pos_bottom) {
			foreach($items as $key => $item)
				$newitems[$key] = $item;
			$newitems[$insertkey] = $insertitem;
		} else {
			if(!array_key_exists($findkey, $items))
				$findkey = extra_posting::field_form_pos_dfl;
			foreach($items as $key => $item) {
				$newitems[$key] = $item;
				if($key == $findkey)
					$newitems[$insertkey] = $insertitem;
			}
		}
		$items = $newitems;
	}
	function extra_posting_options($optionstr) {
		if(stripos($optionstr, '@eval') !== false)
			$optionstr = eval(str_ireplace('@eval', '', $optionstr));
		if(stripos($optionstr, '||') !== false)
			$items = explode('||',$optionstr);
		else
			$items = array($optionstr);
		$options = array();
		foreach($items as $item) {
			if(strstr($item,'==')) {
				$nameval = explode('==',$item);
				$options[$nameval[1]] = $nameval[0];
			} else
				$options[$item] = $item;
		}
		return $options;
	}
	function extra_posting_output(&$q_view, $position) {
		$output = '';
		$isoutput = false;
		foreach($this->extradata as $key => $item) {
			if($item['position'] == $position) {
				$name = $item['name'];
				$type = $item['type'];
				$value = $item['value'];
				
				if ($type == extra_posting::field_type_textarea)
					$value = nl2br($value);
				else if ($type == extra_posting::field_type_check)
					if ($value == '')
						$value = 0;
				if ($type != extra_posting::field_type_text && $type != extra_posting::field_type_textarea && $type != extra_posting::field_type_file) {
					$options = $this->extra_posting_options(qa_opt(extra_posting::field_option.$key));
					if(is_array($options))
						$value = @$options[$value];
				}
				
				if($value == '' && qa_opt(extra_posting::field_hide_blank.$key))
					continue;
				
				switch ($position) {
				case extra_posting::field_page_pos_upper:
					$outerclass = 'qa-q-view-extra-upper qa-q-view-extra-upper'.$key;
					$innertclass = 'qa-q-view-extra-upper-title qa-q-view-extra-upper-title'.$key;
					$innervclass = 'qa-q-view-extra-upper-content qa-q-view-extra-upper-content'.$key;
					$inneraclass = 'qa-q-view-extra-upper-link qa-q-view-extra-upper-link'.$key;
					$innericlass = 'qa-q-view-extra-upper-img qa-q-view-extra-upper-img'.$key;
					break;
				case extra_posting::field_page_pos_inside:
					$outerclass = 'qa-q-view-extra-inside qa-q-view-extra-inside'.$key;
					$innertclass = 'qa-q-view-extra-inside-title qa-q-view-extra-inside-title'.$key;
					$innervclass = 'qa-q-view-extra-inside-content qa-q-view-extra-inside-content'.$key;
					$inneraclass = 'qa-q-view-extra-inside-link qa-q-view-extra-inside-link'.$key;
					$innericlass = 'qa-q-view-extra-inside-img qa-q-view-extra-inside-img'.$key;
					break;
				case extra_posting::field_page_pos_below:
					$outerclass = 'qa-q-view-extra qa-q-view-extra'.$key;
					$innertclass = 'qa-q-view-extra-title qa-q-view-extra-title'.$key;
					$innervclass = 'qa-q-view-extra-content qa-q-view-extra-content'.$key;
					$inneraclass = 'qa-q-view-extra-link qa-q-view-extra-link'.$key;
					$innericlass = 'qa-q-view-extra-img qa-q-view-extra-img'.$key;
					break;
				}
				$title = qa_opt(extra_posting::field_label.$key);
				if ($type == extra_posting::field_type_file && $value != '') {
					if(qa_blob_exists($value)) {
						$blob = qa_read_blob($value);
						$format = $blob['format'];
						$bloburl = qa_get_blob_url($value);
						$imageurl = str_replace('qa=blob', 'qa=image', $bloburl);
						$filename = $blob['filename'];
						$width = $this->extra_posting_get_image_width($blob['content']);
						if($width > qa_opt(extra_posting::thumb_size))
							$width = qa_opt(extra_posting::thumb_size);
						$value = $filename;
						if($format == 'jpg' || $format == 'jpeg' || $format == 'png' || $format == 'gif') {
							$value = '<img src="'.$imageurl.'&qa_size='.$width.'" alt="'.$filename.'" target="_blank"/>';
							$value = '<a href="'.$imageurl.'" class="'.$inneraclass.' '.$innericlass.'" title="'.$title.'">' . $value . '</a>';
						} else
							$value = '<a href="'.$bloburl.'" class="'.$inneraclass.'" title="'.$title.'">' . $value . '</a>';
					} else
						$value = '';
				}
				$output .= '<div class="'.$outerclass.'">';
				$output .= '<div class="'.$innertclass.'">'.$title.'</div>';
				$output .= '<div class="'.$innervclass.'">'.$value.'</div>';
				$output .= '</div>';
				
				if(qa_opt(extra_posting::field_page_pos.$key) != extra_posting::field_page_pos_inside)
					$this->output($output);
				else {
					if(isset($q_view['content'])) {
						$hook = str_replace('^', $key, extra_posting::field_page_pos_hook);
						$q_view['content'] = str_replace($hook, $output, $q_view['content']);
					}
				}
				$isoutput = true;
			}
			$output = '';
		}
		if($isoutput)
			$this->output('<div style="clear:both;"></div>');
	}
	function extra_posting_get_extradata($postid) {
		$extradata = array();
		for($key=1; $key<=extra_posting::field_count_max; $key++) {
			if((bool)qa_opt(extra_posting::field_active.$key) && (bool)qa_opt(extra_posting::field_display.$key)) {
				$name = extra_posting::field_base_name.$key;
				$value = qa_db_single_select(qa_db_post_meta_selectspec($postid, 'qa_q_'.$name));
				if($value == '' && qa_opt(extra_posting::field_hide_blank.$key))
					continue;
				$extradata[$key] = array(
					'name'=>$name,
					'type'=>qa_opt(extra_posting::field_type.$key),
					'position'=>qa_opt(extra_posting::field_page_pos.$key),
					'value'=>$value,
				);
			}
		}
		return $extradata;
	}
	function extra_posting_file_exist() {
		$fileexist = false;
		foreach($this->extradata as $key => $item) {
			if ($item['type'] == extra_posting::field_type_file)
				$fileexist = true;
		}
		return $fileexist;
	}
	function extra_posting_clearhook(&$q_view) {
		for($key=1; $key<=extra_posting::field_count_max; $key++) {
			if(isset($q_view['content'])) {
				$hook = str_replace('^', $key, extra_posting::field_page_pos_hook);
				$q_view['content'] = str_replace($hook, '', $q_view['content']);
			}
		}
	}
	function extra_posting_get_image_width($content) {
		$image=@imagecreatefromstring($content);
		if (is_resource($image))
			return imagesx($image);
		else
			return null;
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/