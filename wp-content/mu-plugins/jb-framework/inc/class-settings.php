<?php
//--------------------------------------------------------------------------
// Namespace
//--------------------------------------------------------------------------
Namespace JB\Framework;

if ($_SERVER['SCRIPT_FILENAME'] == __FILE__) {	// check for direct file access
	header('Location: /');						// redirect to website root
	die();										// kill the page if the redirection fails
}

//--------------------------------------------------------------------------
// Settings Class
//--------------------------------------------------------------------------
//
// 
//
//

abstract class Settings {

	function __construct(){
		//Framwork specific actions
		add_action('admin_init', 							array($this,'settings'));
		add_action('admin_menu', 							array($this,'admin_add_page'));

		//Framework specific filters
		add_filter('pre_update_option_category_base',		array($this,'base_options_permalink'),	1);
		add_filter('pre_update_option_tag_base',			array($this,'base_options_permalink'),	1);
		add_filter('pre_update_option_permalink_structure',	array($this,'base_options_permalink'),	1);
		add_filter('init', 									array($this,'rewrites'));
	}

	//--------------------------------------------------------------------------
	// Remove the /blog in the permalink structure
	//--------------------------------------------------------------------------
	public function base_options_permalink($permalink){
		$permalink = preg_replace("/^\/blog\//","/",$permalink);
		return $permalink;
	}

	//--------------------------------------------------------------------------
	// Change the author rewrite rule | specify our own base for author archives
	//--------------------------------------------------------------------------
	public function rewrites(){
		global $wp_rewrite;
		$wp_rewrite->author_base = get_option('jb_base_author', 'author');
	}

	//--------------------------------------------------------------------------
	// Settings API
	//--------------------------------------------------------------------------
	// We create a settings page where all those options are regroup
	public function settings(array $settings_fields = array()) {
		//--------------------------------------------------------------------------
		// Why Permalink rewrite here ? We cannot put them on the permalink page
		//--------------------------------------------------------------------------
		// see : http://core.trac.wordpress.org/ticket/9296
		add_settings_section('jb_general', __('General', 'jb'), '', 'jb_settings');
		add_settings_section('jb_wp_permalinks', __('Advanced permalinks', 'jb'), '', 'jb_settings');

		//See http://codex.wordpress.org/Function_Reference/add_settings_field
		$framework_settings_fields = array(
			array(
				'id' 					=> 'jb_limit_excerpt',
				'title'					=> '<label for="jb_limit_excerpt">'.__('Excerpt length', 'jb').'</label>', 
				'callback'				=> array($this,'option_form_render'),
				'page'					=> 'jb_settings',
				'section'				=> 'jb_general',
				'args'					=> array('id'=>'jb_limit_excerpt', 'default'=>'140'),
				'sanitize_reg_callback' => 'intval'
			),
			array(
				'id' 					=> 'jb_base_404',
				'title'					=> '<label for="jb_base_404">'.__('404 Error base', 'jb').'</label>', 
				'callback'				=> array($this,'option_form_render'),
				'page'					=> 'jb_settings',
				'section'				=> 'jb_wp_permalinks',
				'args'					=> array('id'=>'jb_base_404', 'default'=>'error-404'),
				'sanitize_reg_callback' => 'sanitize_text_field'
			),
			array(
				'id' 					=> 'jb_base_author',
				'title'					=> '<label for="jb_base_author">'.__('Author base', 'jb').'</label>', 
				'callback'				=> array($this,'option_form_render'),
				'page'					=> 'jb_settings',
				'section'				=> 'jb_wp_permalinks',
				'args'					=> array('id'=>'jb_base_author', 'default'=>'author'),
				'sanitize_reg_callback' => 'sanitize_text_field' //Third arg from register_settings function
			)
		);
		
		//We simplify the settings section
		$this->construct_settings_fields($framework_settings_fields + $settings_fields);

	}

	private function construct_settings_fields(array $settings_fields){
		foreach ($settings_fields as $field):
			add_settings_field(
				$field['id'],
				$field['title'],
				$field['callback'],
				$field['page'],
				$field['section'],
				$field['args']
			);
			register_setting($field['page'], $field['id'], $field['sanitize_reg_callback']);
		endforeach;
	}

	public function option_form_render($args) {
		echo '<input name="'.$args['id'].'" id="'.$args['id'].'" type="text" value="' . get_option($args['id'], $args['default']) . '" class="regular-text" />';
	}

	//--------------------------------------------------------------------------
	// Add Theme Settings page
	//--------------------------------------------------------------------------
	public function admin_add_page() {
		add_options_page(__('Theme Settings', 'jb'), __('Theme Settings', 'jb'), 'manage_options', 'jb_settings', array($this,'options_page'));
	}

	public function options_page(){
		if(isset($_GET['settings-updated']) && $_GET['settings-updated'] && isset($_GET['page']) && $_GET['page'] == 'jb_settings')
				flush_rewrite_rules();
		?>
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h2><?php _e('Theme Settings', 'jb'); ?></h2>
			<form class="clear" action="options.php" method="post">
				<?php settings_fields('jb_settings'); ?>
				<?php do_settings_sections('jb_settings'); ?>
			 
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
				</p>
			</form>
		</div>
		<?php
	}


}