<?php

if ( !class_exists('scbOptionsPage_07') )
	require_once(dirname(__FILE__) . '/inc/scbOptionsPage.php');

class extraFeedLinkAdmin extends scbOptionsPage_07 {
	protected function setup() {
		$this->options = $GLOBALS['EFL_options'];

		$this->defaults = array(
			'home' => array(FALSE, '%site_title% Comments'),
			'comments' => array(TRUE, 'Comments: %title%'),
			'category' => array(TRUE, 'Category: %title%'),
			'tag' => array(TRUE, 'Tag: %title%'),
			'author' => array(TRUE, 'Author: %title%'),
			'search' => array(TRUE, 'Search: %title%')
		);

		$this->args = array(
			'page_title' => 'Extra Feed Links',
			'short_title' => 'Extra Feed Links',
			'page_slug' => 'extra-feed-links'
		);

		$this->nonce = 'efl-settings';
	}

	protected function form_handler() {
		if ( empty($_POST['action']) )
			return false;

		check_admin_referer($this->nonce);

		// Update options
		if ( 'Save Changes' == $_POST['action'] ) {
			foreach (array_keys($this->options->get()) as $name) {
				$new_format[$name][0] = $_POST['show-' . $name];
				$new_format[$name][1] = $_POST['format-' . $name];
			}

			$this->options->update($new_format);
			$this->admin_msg('Settings <strong>saved</strong>.');
		}

		// Reset options
		if ( 'Reset' == $_POST['action'] ) {
			$this->options->reset();
			$this->admin_msg('Settings <strong>reset</strong>.');
		}
	}

	public function page_head() {
?>
<style type="text/css">
table input.widefat {width:250px !important}
</style>
<?php
	}

	public function page_content() {
		echo $this->page_header();
?>
<p>The table below allows you to select which page types get an extra header link and the format of the link text.</p>

<div class="alignleft" style="width:auto">
<form method="post" action="">
	<?php wp_nonce_field($this->nonce); ?>
	<table class="widefat" style="width:auto">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" /></th>
			<th scope="col">Page type</th>
			<th scope="col">Text format</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach($this->options->get() as $name => $value) { ?>
		<tr>
			<th scope='row' class='check-column'><?php 
				echo $this->input(array(
					'type' => 'checkbox',
					'names' => 'show-'.$name,
					'desc_pos' => 'none'
				), array('show-'.$name => $value[0]));
			?></th>
			<td><?php echo ucfirst($name) ?></td>
			<td><?php 
				echo $this->input(array(
					'type' => 'text',
					'names' => 'format-'.$name,
					'desc_pos' => 'none'
				), array('format-'.$name => $value[1]));
			?></td>
		</tr>
		<?php } ?>
		</tbody>
		</table>

	<div class="tablenav" style="width:auto">
		<div class="alignleft">
			<input name="action" type="submit" class="button-primary button" value="Save Changes" />
			<input name="action" type="submit" class="button-secondary" onClick="return confirm('Are you sure you want to reset to defaults?')" value="Reset" />
		</div>
	</div>

	</form>
</div>

<div style="float:left; margin-left: 50px">
	<p>Available substitution tags:</p>
	<ul>
		<li><em>%title%</em> - displays the corresponding title for each page type</li>
		<li><em>%site_title%</em> - displays the title of the site</li>
	</ul>
</div>
<?php	
		echo $this->page_footer();
	}
}

