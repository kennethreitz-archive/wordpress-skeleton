<?php

// Handles the_title and the_content fields
class FEE_Field_Post extends FEE_Field_Base {

	protected $field;

	protected function setup() {
		$this->field = str_replace('the_', 'post_', $this->get_filter());
	}

	static function get_object_type() {
		return 'post';
	}

	function wrap($content, $post_id = 0) {
		if ( ! $post_id = $this->_get_id($post_id) )
			return $content;

		return parent::wrap($content, $post_id);
	}

	protected function _get_id($post_id = 0, $in_loop = true ) {
		if ( $in_loop && !in_the_loop() )
			return false;

		if ( ! $post_id )
			$post_id = get_the_ID();

		if ( ! $post_id || ! $this->check($post_id) )
			return false;

		return $post_id;
	}

	function get($post_id) {
		return get_post_field($this->field, $post_id);
	}

	function save($post_id, $content) {
		$postdata = array(
			'ID' => $post_id,
			$this->field => $content
		);

		if ( FEE_Core::$options->reset_date ) {
			$postdata['post_date'] = current_time('mysql');
			$postdata['post_date_gmt'] = current_time('mysql', 1);
		}

		// reset slug
		if ( $this->field == 'post_title' ) {
			$current_slug = get_post_field('post_name', $post_id);
			$current_title = get_post_field('post_title', $post_id);

			// update only if not explicitly set
			if ( empty($current_slug) || $current_slug == sanitize_title_with_dashes($current_title) ) {
				$new_slug = sanitize_title_with_dashes($content);
				$postdata['post_name'] = $new_slug;
			}
		}

		wp_update_post((object) $postdata);

		$this->set_post_global($post_id);

		return $content;
	}

	function check($post_id = 0) {
		return current_user_can('edit_' . get_post_type($post_id), $post_id);
	}

	protected function set_post_global($post_id) {
		$GLOBALS['post'] = get_post($post_id);
	}
}

// Handles <p> tags in the_content
class FEE_Field_Chunks extends FEE_Field_Post {

	const delim = "\n\n";

	function wrap($content, $post_id = 0) {
		if ( ! $post_id = $this->_get_id($post_id) )
			return $content;

		$chunks = $this->split($content);

		foreach ( $chunks as $i => $chunk )
			$content = str_replace($chunk, FEE_Field_Base::wrap($chunk, "$post_id#$i", true), $content);

		return $content;
	}

	function get($post_id) {
		list($post_id, $chunk_id) = explode('#', $post_id);

		$field = get_post_field('post_content', $post_id);

		$chunks = $this->split($field, true);

		return @$chunks[$chunk_id];
	}

	function save($post_id, $chunk_content) {
		list($post_id, $chunk_id) = explode('#', $post_id);

		$content = get_post_field('post_content', $post_id);

		$chunks = $this->split($content, true);

		$chunk_content = trim($chunk_content);

		$content = str_replace($chunks[$chunk_id], $chunk_content, $content);

		$postdata = array(
			'ID' => $post_id,
			'post_content' => $content
		);

		wp_update_post((object) $postdata);

		$this->set_post_global($post_id);

		// Refresh the page if a new chunk is added
		if ( empty($chunk_content) || FALSE !== strpos($chunk_content, self::delim) )
			$this->force_refresh();

		die($chunk_content);
	}

	// Split content into chunks
	protected function split($content, $autop = false) {
		if ( $autop )
			$content = wpautop($content);

		preg_match_all("#<p>(.*?)</p>#", $content, $matches);

		return $matches[1];
	}

	protected function force_refresh() {
		die("<script language='javascript'>location.reload(true)</script>");
	}
}

// Handles the_excerpt field
class FEE_Field_Excerpt extends FEE_Field_Post {

	function get($post_id) {
		$post = get_post($post_id);

		$excerpt = $post->post_excerpt;

		if ( empty($excerpt) ) {
			$this->set_post_global($post_id);
			$excerpt = $this->trim_excerpt($post->post_content);
		}

		return $excerpt;
	}

	function save($post_id, $excerpt) {
		$default_excerpt = $this->get($post_id);

		if ( $excerpt == $default_excerpt )
			return $excerpt;

		$postdata = array(
			'ID' => $post_id,
			'post_excerpt' => $excerpt
		);

		wp_update_post((object) $postdata);

		$this->set_post_global($post_id);

		if ( empty($excerpt) )
			return $default_excerpt;

		return $excerpt;
	}

	// Copy-paste from wp_trim_excerpt()
	private function trim_excerpt($text) {
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = apply_filters('excerpt_length', 55);
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '[...]');
			$text = implode(' ', $words);
		}

		return apply_filters('get_the_excerpt', $text);
	}
}

// Handles the_terms field
class FEE_Field_Terms extends FEE_Field_Post {

	function wrap($content, $taxonomy, $before, $sep, $after) {
		if ( ! $post_id = $this->_get_id() )
			return $content;

		$content = $this->placehold(str_replace(array($before, $after), '', $content));

		$id = implode('#', array($post_id, $taxonomy));

		return $before . FEE_Field_Base::wrap($content, $id) . $after;
	}

	function get($id) {
		list($post_id, $taxonomy) = explode('#', $id);

		$tags = get_terms_to_edit($post_id, $taxonomy);
		$tags = str_replace(',', ', ', $tags);

		return $tags;
	}

	function save($id, $terms) {
		list($post_id, $taxonomy) = explode('#', $id);

		wp_set_post_terms($post_id, $terms, $taxonomy);

		$response = get_the_term_list($post_id, $taxonomy, '', ', ');	// todo: store $sep somehow

		return $this->placehold($response);
	}
}

// Handles the_tags field
class FEE_Field_Tags extends FEE_Field_Terms {

	function wrap($content, $before, $sep, $after) {
		return parent::wrap($content, 'post_tag', $before, $sep, $after);
	}
}

// Handles the_category field
class FEE_Field_Category extends FEE_Field_Terms {

	function wrap($content, $sep, $parents) {
		return parent::wrap($content, 'category', '', $sep, '');
	}

	function save($id, $categories) {
		list($post_id, $taxonomy) = explode('#', $id);

		$cat_ids = array();
		foreach ( explode(',', $categories) as $cat_name ) {
			if ( ! $cat = get_cat_ID(trim($cat_name)) ) {
				$args = wp_insert_term($cat_name, $taxonomy);

				if ( is_wp_error($args) )
					continue;

				$cat = $args['term_id'];
			}

			$cat_ids[] = $cat;
		}

		wp_set_post_categories($post_id, $cat_ids);

		$response = get_the_term_list($post_id, $taxonomy, '', ', ');

		return $this->placehold($response);
	}
}

// Handles the post thumbnail
class FEE_Field_Thumbnail extends FEE_Field_Post {

	function wrap($html, $post_id, $post_thumbnail_id, $size) {
		if ( ! $post_id = $this->_get_id($post_id, false) )
			return $content;

		$id = implode('#', array($post_id, $size));

		return FEE_Field_Base::wrap($html, $id);
	}

	function get($id) {
		list($post_id, $size) = explode('#', $id);

		return get_post_thumbnail_id($post_id);
	}

	function save($id, $thumbnail_id) {
		list($post_id, $size) = explode('#', $id);

		if ( -1 == $thumbnail_id ) {
			delete_post_meta($post_id, '_thumbnail_id');
			return -1;
		}

		update_post_meta($post_id, '_thumbnail_id', $thumbnail_id);

		list($url) = image_downsize($thumbnail_id, $size);

		return $url;
	}
}

// Handles post_meta field
class FEE_Field_Meta extends FEE_Field_Post {

	function wrap($data, $post_id, $key, $type, $single) {
		if ( $this->check($post_id) ) {
			if ( $single )
				$data = array($this->placehold($data));

			$r = array();
			foreach ( $data as $i => $val ) {
				$id = implode('#', array($post_id, $key, $type, $i));
				$r[$i] = FEE_Field_Base::wrap($val, $id);
			}
		}
		else {
			$r = (array) $data;
		}

		if ( $single )
			return $r[0];

		return $r;
	}

	function get($id) {
		list($post_id, $key, $type, $i) = explode('#', $id);

		$data = get_post_meta($post_id, $key);

		return @$data[$i];
	}

	function save($id, $new_value) {
		list($post_id, $key, $type, $i) = explode('#', $id);

		$data = get_post_meta($post_id, $key);

		$old_value = @$data[$i];

		update_post_meta($post_id, $key, $new_value, $old_value);

		return $new_value;
	}
}

function editable_post_meta($post_id, $key, $type = 'input', $echo = true) {
	$data = get_editable_post_meta($post_id, $key, $type, true);

	if ( ! $echo )
		return $data;

	echo $data;
}

function get_editable_post_meta($post_id, $key, $type = 'input', $single = false) {
	$data = get_post_meta($post_id, $key, $single);

	return apply_filters('post_meta', $data, $post_id, $key, $type, $single);
}

