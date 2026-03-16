<?php
if (!defined('ABSPATH'))
	exit;

class Boylar_Elementor_Code_Generator
{
	const NONCE_ACTION = 'boylar_magic_ai_nonce';
	const AJAX_ACTION = 'boylar_magic_ai_generate';
	const AJAX_TEST = 'boylar_magic_ai_test_connection';
	const OPT_GROUP = 'boylar_magic_elementor';
	const OPT_KEY = 'boylar_magic_elementor_settings';

	public function __construct()
	{
		add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_editor_assets']);
		add_action('elementor/widgets/register', [$this, 'register_widgets']);

		add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'ajax_generate']);
		add_action('wp_ajax_' . self::AJAX_TEST, [$this, 'ajax_test_connection']);

		add_action('admin_menu', [$this, 'register_settings_page']);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
	}

	public function register_widgets($widgets_manager)
	{
		require_once BOYLAR_ME_PLUGIN_DIR . 'includes/widgets/class-boylar-magic-ai-widget.php';
		$widgets_manager->register(new \Boylar_Magic_AI_Generator_Widget());
	}

	public function enqueue_editor_assets()
	{
		$handle = 'boylar-magic-ai-editor';
		$src = BOYLAR_ME_PLUGIN_URL . 'assets/editor/magic-ai.js';
		$path = BOYLAR_ME_PLUGIN_DIR . 'assets/editor/magic-ai.js';
		$ver = file_exists($path) ? (string) filemtime($path) : BOYLAR_ME_VERSION;

		wp_register_script(
			$handle,
			$src,
			['jquery'],
			$ver,
			true
		);

		wp_enqueue_script($handle);

		$settings = $this->get_settings();
		wp_localize_script($handle, 'BoylarMagicAI', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce(self::NONCE_ACTION),
			'postId' => get_the_ID(),
			'autoRemoveWidget' => !empty($settings['auto_remove_widget']),
			'enableVision' => !empty($settings['enable_vision']),
		]);
	}

	public function register_settings_page()
	{
		add_menu_page(
			__('Boylar Magic Elementor', 'boylar-magic-elementor'),
			__('Boylar Magic Elementor', 'boylar-magic-elementor'),
			'manage_options',
			'boylar-magic-elementor',
			[$this, 'render_settings_page'],
			'dashicons-superhero',
			58
		);
	}

	public function enqueue_admin_assets($hook_suffix)
	{
		// Settings page: Top-level Toplevel Page
		if ($hook_suffix !== 'toplevel_page_boylar-magic-elementor')
			return;

		$handle = 'boylar-magic-elementor-settings';
		$src = BOYLAR_ME_PLUGIN_URL . 'assets/admin/settings.js';
		$path = BOYLAR_ME_PLUGIN_DIR . 'assets/admin/settings.js';
		$ver = file_exists($path) ? (string) filemtime($path) : BOYLAR_ME_VERSION;

		wp_register_script($handle, $src, ['jquery'], $ver, true);
		wp_enqueue_script($handle);
		wp_localize_script($handle, 'BoylarMagicElementorSettings', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce(self::NONCE_ACTION),
		]);
	}

	public function register_settings()
	{
		register_setting(
			self::OPT_GROUP,
			self::OPT_KEY,
			[
				'type' => 'array',
				'sanitize_callback' => [$this, 'sanitize_settings'],
				'default' => $this->default_settings(),
			]
		);

		add_settings_section(
			'boylar_magic_main',
			__('AI Provider', 'boylar-magic-elementor'),
			function () {
				echo '<p>' . esc_html__('Configure an OpenAI-compatible API endpoint. We recommend using the 100% Free Google Gemini API. Get your free key at: https://aistudio.google.com/app/apikey', 'boylar-magic-elementor') . '</p>';
			},
			'boylar-magic-elementor'
		);

		add_settings_field(
			'api_base_url',
			__('API Base URL', 'boylar-magic-elementor'),
			[$this, 'field_api_base_url'],
			'boylar-magic-elementor',
			'boylar_magic_main'
		);

		add_settings_field(
			'api_key',
			__('API Key', 'boylar-magic-elementor'),
			[$this, 'field_api_key'],
			'boylar-magic-elementor',
			'boylar_magic_main'
		);

		add_settings_field(
			'auth_header_mode',
			__('Auth header', 'boylar-magic-elementor'),
			[$this, 'field_auth_header_mode'],
			'boylar-magic-elementor',
			'boylar_magic_main'
		);

		add_settings_field(
			'test_connection',
			__('Test connection', 'boylar-magic-elementor'),
			[$this, 'field_test_connection'],
			'boylar-magic-elementor',
			'boylar_magic_main'
		);

		add_settings_field(
			'model_text',
			__('Model (text)', 'boylar-magic-elementor'),
			[$this, 'field_model_text'],
			'boylar-magic-elementor',
			'boylar_magic_main'
		);

		add_settings_field(
			'model_vision',
			__('Model (vision)', 'boylar-magic-elementor'),
			[$this, 'field_model_vision'],
			'boylar-magic-elementor',
			'boylar_magic_main'
		);

		add_settings_section(
			'boylar_magic_behavior',
			__('Behavior', 'boylar-magic-elementor'),
			function () {
				echo '<p>' . esc_html__('Editor behavior settings.', 'boylar-magic-elementor') . '</p>';
			},
			'boylar-magic-elementor'
		);

		add_settings_field(
			'auto_remove_widget',
			__('Auto-remove generator widget', 'boylar-magic-elementor'),
			[$this, 'field_auto_remove_widget'],
			'boylar-magic-elementor',
			'boylar_magic_behavior'
		);

		add_settings_field(
			'enable_vision',
			__('Enable vision (image input)', 'boylar-magic-elementor'),
			[$this, 'field_enable_vision'],
			'boylar-magic-elementor',
			'boylar_magic_behavior'
		);

		add_settings_field(
			'max_prompt_chars',
			__('Max prompt length', 'boylar-magic-elementor'),
			[$this, 'field_max_prompt_chars'],
			'boylar-magic-elementor',
			'boylar_magic_behavior'
		);

		add_settings_field(
			'cache_ttl_seconds',
			__('Cache TTL (seconds)', 'boylar-magic-elementor'),
			[$this, 'field_cache_ttl_seconds'],
			'boylar-magic-elementor',
			'boylar_magic_behavior'
		);

		add_settings_field(
			'request_timeout',
			__('Request timeout (seconds)', 'boylar-magic-elementor'),
			[$this, 'field_request_timeout'],
			'boylar-magic-elementor',
			'boylar_magic_behavior'
		);

		add_settings_field(
			'max_output_tokens',
			__('Max output tokens', 'boylar-magic-elementor'),
			[$this, 'field_max_output_tokens'],
			'boylar-magic-elementor',
			'boylar_magic_behavior'
		);

		add_settings_field(
			'rate_limit_per_minute',
			__('Rate limit (per minute)', 'boylar-magic-elementor'),
			[$this, 'field_rate_limit_per_minute'],
			'boylar-magic-elementor',
			'boylar_magic_behavior'
		);

		add_settings_field(
			'keep_data_on_uninstall',
			__('Keep settings on uninstall', 'boylar-magic-elementor'),
			[$this, 'field_keep_data_on_uninstall'],
			'boylar-magic-elementor',
			'boylar_magic_behavior'
		);
	}

	public function render_settings_page()
	{
		if (!current_user_can('manage_options'))
			return;
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__('Boylar Magic Elementor', 'boylar-magic-elementor') . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields(self::OPT_GROUP);
		do_settings_sections('boylar-magic-elementor');
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	public function sanitize_settings($input)
	{
		$defaults = $this->default_settings();
		$input = is_array($input) ? $input : [];
		$out = [];

		$out['api_base_url'] = isset($input['api_base_url']) ? esc_url_raw(trim((string) $input['api_base_url'])) : $defaults['api_base_url'];
		if ($out['api_base_url'] === '') {
			$out['api_base_url'] = $defaults['api_base_url'];
		}

		$out['api_key'] = isset($input['api_key']) ? sanitize_text_field((string) $input['api_key']) : '';
		$allowed_auth = ['bearer', 'x_api_key'];
		$out['auth_header_mode'] = isset($input['auth_header_mode']) ? sanitize_text_field((string) $input['auth_header_mode']) : $defaults['auth_header_mode'];
		if (!in_array($out['auth_header_mode'], $allowed_auth, true))
			$out['auth_header_mode'] = $defaults['auth_header_mode'];

		$out['model_text'] = isset($input['model_text']) ? sanitize_text_field((string) $input['model_text']) : $defaults['model_text'];
		$out['model_vision'] = isset($input['model_vision']) ? sanitize_text_field((string) $input['model_vision']) : $defaults['model_vision'];
		$out['auto_remove_widget'] = !empty($input['auto_remove_widget']) ? 1 : 0;
		$out['keep_data_on_uninstall'] = !empty($input['keep_data_on_uninstall']) ? 1 : 0;
		$out['enable_vision'] = !empty($input['enable_vision']) ? 1 : 0;

		$out['max_prompt_chars'] = isset($input['max_prompt_chars']) ? absint($input['max_prompt_chars']) : $defaults['max_prompt_chars'];
		if ($out['max_prompt_chars'] < 200)
			$out['max_prompt_chars'] = 200;
		if ($out['max_prompt_chars'] > 6000)
			$out['max_prompt_chars'] = 6000;

		$out['max_image_bytes'] = isset($input['max_image_bytes']) ? absint($input['max_image_bytes']) : $defaults['max_image_bytes'];
		if ($out['max_image_bytes'] < 200000)
			$out['max_image_bytes'] = 200000;
		if ($out['max_image_bytes'] > 8000000)
			$out['max_image_bytes'] = 8000000;

		$out['rate_limit_per_minute'] = isset($input['rate_limit_per_minute']) ? absint($input['rate_limit_per_minute']) : $defaults['rate_limit_per_minute'];
		if ($out['rate_limit_per_minute'] < 1)
			$out['rate_limit_per_minute'] = 1;
		if ($out['rate_limit_per_minute'] > 60)
			$out['rate_limit_per_minute'] = 60;

		$out['cache_ttl_seconds'] = isset($input['cache_ttl_seconds']) ? absint($input['cache_ttl_seconds']) : $defaults['cache_ttl_seconds'];
		if ($out['cache_ttl_seconds'] < 0)
			$out['cache_ttl_seconds'] = 0;
		if ($out['cache_ttl_seconds'] > 86400)
			$out['cache_ttl_seconds'] = 86400;

		$out['request_timeout'] = isset($input['request_timeout']) ? absint($input['request_timeout']) : $defaults['request_timeout'];
		if ($out['request_timeout'] < 10)
			$out['request_timeout'] = 10;
		if ($out['request_timeout'] > 120)
			$out['request_timeout'] = 120;

		$out['max_output_tokens'] = isset($input['max_output_tokens']) ? absint($input['max_output_tokens']) : $defaults['max_output_tokens'];
		if ($out['max_output_tokens'] < 200)
			$out['max_output_tokens'] = 200;
		if ($out['max_output_tokens'] > 4000)
			$out['max_output_tokens'] = 4000;

		return $out;
	}

	private function default_settings()
	{
		return [
			'api_base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai/',
			'api_key' => '',
			'auth_header_mode' => 'bearer',
			'model_text' => 'gemini-2.5-flash',
			'model_vision' => 'gemini-2.5-flash',
			'auto_remove_widget' => 1,
			'keep_data_on_uninstall' => 0,
			'enable_vision' => 1,
			'max_prompt_chars' => 1200,
			'max_image_bytes' => 2000000,
			'rate_limit_per_minute' => 6,
			'cache_ttl_seconds' => 60,
			'request_timeout' => 60,
			'max_output_tokens' => 1200,
		];
	}

	private function get_settings()
	{
		$defaults = $this->default_settings();
		$opt = get_option(self::OPT_KEY, []);
		return wp_parse_args(is_array($opt) ? $opt : [], $defaults);
	}

	public function field_api_base_url()
	{
		$s = $this->get_settings();
		printf(
			'<input type="url" class="regular-text" style="width: 100%%; max-width: 500px;" name="%s[api_base_url]" value="%s" />',
			esc_attr(self::OPT_KEY),
			esc_attr($s['api_base_url'])
		);
		echo '<p class="description">' . wp_kses_post(__('Default: <code>https://generativelanguage.googleapis.com/v1beta/openai/</code> (100% Free Gemini API)', 'boylar-magic-elementor')) . '</p>';
	}

	public function field_api_key()
	{
		$s = $this->get_settings();
		$using_constant = defined('BOYLAR_OPENAI_API_KEY') && BOYLAR_OPENAI_API_KEY;
		printf(
			'<input type="password" class="regular-text" name="%s[api_key]" value="%s" autocomplete="off" />',
			esc_attr(self::OPT_KEY),
			esc_attr($s['api_key'])
		);
		if ($using_constant) {
			echo '<p class="description">' . esc_html__('API key is currently provided by BOYLAR_OPENAI_API_KEY in wp-config.php. The field above can be left blank.', 'boylar-magic-elementor') . '</p>';
		} else {
			echo '<p class="description">' . wp_kses_post(__('Stored securely in WP options.<br><strong>Get a Free Gemini key here: <a href="https://aistudio.google.com/app/apikey" target="_blank">https://aistudio.google.com/app/apikey</a></strong>', 'boylar-magic-elementor')) . '</p>';
		}
	}

	public function field_auth_header_mode()
	{
		$s = $this->get_settings();
		printf(
			'<select name="%s[auth_header_mode]">
				<option value="bearer" %s>%s</option>
				<option value="x_api_key" %s>%s</option>
			</select>',
			esc_attr(self::OPT_KEY),
			selected($s['auth_header_mode'], 'bearer', false),
			esc_html__('Authorization: Bearer <key> (OpenAI default)', 'boylar-magic-elementor'),
			selected($s['auth_header_mode'], 'x_api_key', false),
			esc_html__('x-api-key: <key>', 'boylar-magic-elementor')
		);
	}

	public function field_test_connection()
	{
		echo '<button type="button" class="button" id="boylar-magic-test-connection">' . esc_html__('Test connection', 'boylar-magic-elementor') . '</button>';
		echo '<span id="boylar-magic-test-connection-result" style="margin-left:10px;"></span>';
	}

	public function field_model_text()
	{
		$s = $this->get_settings();
		printf(
			'<input type="text" class="regular-text" name="%s[model_text]" value="%s" />',
			esc_attr(self::OPT_KEY),
			esc_attr($s['model_text'])
		);
	}

	public function field_model_vision()
	{
		$s = $this->get_settings();
		printf(
			'<input type="text" class="regular-text" name="%s[model_vision]" value="%s" />',
			esc_attr(self::OPT_KEY),
			esc_attr($s['model_vision'])
		);
	}

	public function field_auto_remove_widget()
	{
		$s = $this->get_settings();
		printf(
			'<label><input type="checkbox" name="%s[auto_remove_widget]" value="1" %s /> %s</label>',
			esc_attr(self::OPT_KEY),
			checked(!empty($s['auto_remove_widget']), true, false),
			esc_html__('After inserting the generated section, delete the generator widget automatically.', 'boylar-magic-elementor')
		);
	}

	public function field_enable_vision()
	{
		$s = $this->get_settings();
		printf(
			'<label><input type="checkbox" name="%s[enable_vision]" value="1" %s /> %s</label>',
			esc_attr(self::OPT_KEY),
			checked(!empty($s['enable_vision']), true, false),
			esc_html__('Allow using a reference image (vision model).', 'boylar-magic-elementor')
		);
	}

	public function field_max_prompt_chars()
	{
		$s = $this->get_settings();
		printf(
			'<input type="number" min="200" max="6000" name="%s[max_prompt_chars]" value="%s" />',
			esc_attr(self::OPT_KEY),
			esc_attr((string) $s['max_prompt_chars'])
		);
	}

	public function field_rate_limit_per_minute()
	{
		$s = $this->get_settings();
		printf(
			'<input type="number" min="1" max="60" name="%s[rate_limit_per_minute]" value="%s" />',
			esc_attr(self::OPT_KEY),
			esc_attr((string) $s['rate_limit_per_minute'])
		);
		echo '<p class="description">' . esc_html__('Limits generation requests per user per minute to control costs.', 'boylar-magic-elementor') . '</p>';
	}

	public function field_cache_ttl_seconds()
	{
		$s = $this->get_settings();
		printf(
			'<input type="number" min="0" max="86400" name="%s[cache_ttl_seconds]" value="%s" />',
			esc_attr(self::OPT_KEY),
			esc_attr((string) $s['cache_ttl_seconds'])
		);
		echo '<p class="description">' . esc_html__('Cache generated results per user/prompt for this many seconds. Set 0 to disable.', 'boylar-magic-elementor') . '</p>';
	}

	public function field_request_timeout()
	{
		$s = $this->get_settings();
		printf(
			'<input type="number" min="10" max="120" name="%s[request_timeout]" value="%s" />',
			esc_attr(self::OPT_KEY),
			esc_attr((string) $s['request_timeout'])
		);
	}

	public function field_max_output_tokens()
	{
		$s = $this->get_settings();
		printf(
			'<input type="number" min="200" max="4000" name="%s[max_output_tokens]" value="%s" />',
			esc_attr(self::OPT_KEY),
			esc_attr((string) $s['max_output_tokens'])
		);
	}


	public function field_keep_data_on_uninstall()
	{
		$s = $this->get_settings();
		printf(
			'<label><input type="checkbox" name="%s[keep_data_on_uninstall]" value="1" %s /> %s</label>',
			esc_attr(self::OPT_KEY),
			checked(!empty($s['keep_data_on_uninstall']), true, false),
			esc_html__('Keep plugin settings when uninstalling.', 'boylar-magic-elementor')
		);
	}

	public function ajax_generate()
	{
		if (!current_user_can('edit_posts')) {
			wp_send_json_error(['message' => 'Forbidden'], 403);
		}

		check_ajax_referer(self::NONCE_ACTION, 'nonce');

		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		if (!$post_id) {
			wp_send_json_error(['message' => 'Missing post_id.'], 400);
		}
		if ($post_id && !current_user_can('edit_post', $post_id)) {
			wp_send_json_error(['message' => 'Forbidden'], 403);
		}

		$prompt = isset($_POST['prompt']) ? wp_unslash((string) $_POST['prompt']) : '';
		$prompt = trim($prompt);
		if ($prompt === '') {
			wp_send_json_error(['message' => 'Prompt is required.'], 400);
		}

		$screenshot = isset($_POST['screenshot']) ? wp_unslash((string) $_POST['screenshot']) : '';
		$screenshot = trim($screenshot);
		$image_id = isset($_POST['image_id']) ? absint($_POST['image_id']) : 0;

		$settings = $this->get_settings();

		// Cache (per user, per prompt+image+model) to reduce costs.
		$cache_key = $this->cache_key($settings, $prompt, $image_id, $post_id);
		$ttl = isset($settings['cache_ttl_seconds']) ? (int) $settings['cache_ttl_seconds'] : 0;
		if ($ttl > 0) {
			$cached = get_transient($cache_key);
			if (is_array($cached) && !empty($cached['section'])) {
				wp_send_json_success(['section' => $cached['section'], 'cached' => true]);
			}
		}
		if (function_exists('mb_strlen')) {
			if (mb_strlen($prompt) > (int) $settings['max_prompt_chars']) {
				wp_send_json_error(['message' => 'Prompt is too long.'], 400);
			}
		} else {
			if (strlen($prompt) > (int) $settings['max_prompt_chars']) {
				wp_send_json_error(['message' => 'Prompt is too long.'], 400);
			}
		}

		if (!$this->rate_limit_ok($settings)) {
			wp_send_json_error(['message' => 'Rate limit exceeded. Please wait a moment and try again.'], 429);
		}

		$api_key = $this->get_openai_api_key($settings);
		if (!$api_key) {
			wp_send_json_error(['message' => 'API key missing. Configure it in Settings → Boylar magic Elementor (or define BOYLAR_OPENAI_API_KEY).'], 500);
		}

		if ($image_id && !empty($settings['enable_vision'])) {
			$screenshot = $this->attachment_to_data_url($image_id);
			if (is_wp_error($screenshot)) {
				wp_send_json_error(['message' => $screenshot->get_error_message()], 400);
			}
		} elseif ($image_id && empty($settings['enable_vision'])) {
			wp_send_json_error(['message' => 'Vision is disabled in settings.'], 400);
		}

		$result = $this->call_openai_for_elementor_section([
			'api_key' => $api_key,
			'base_url' => $settings['api_base_url'],
			'model_text' => $settings['model_text'],
			'model_vision' => $settings['model_vision'],
			'auth_header_mode' => $settings['auth_header_mode'],
			'timeout' => $settings['request_timeout'],
			'max_output_tokens' => $settings['max_output_tokens'],
			'prompt' => $prompt,
			'screenshot' => $screenshot,
		]);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()], 500);
		}

		$section = $this->coerce_elementor_section_shape($result);
		if (is_wp_error($section)) {
			wp_send_json_error(['message' => $section->get_error_message()], 500);
		}

		if ($ttl > 0) {
			set_transient($cache_key, ['section' => $section], $ttl);
		}
		wp_send_json_success(['section' => $section, 'cached' => false]);
	}

	public function ajax_test_connection()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Forbidden'], 403);
		}

		check_ajax_referer(self::NONCE_ACTION, 'nonce');

		$settings = $this->get_settings();
		$api_key = $this->get_openai_api_key($settings);
		if (!$api_key) {
			wp_send_json_error(['message' => 'API key is missing.'], 400);
		}

		$base_url = rtrim((string) $settings['api_base_url'], '/');
		$url = $base_url . '/models';
		$headers = $this->build_auth_headers($settings, $api_key);

		$resp = wp_remote_get($url, [
			'timeout' => (int) $settings['request_timeout'],
			'headers' => $headers,
		]);

		if (is_wp_error($resp)) {
			wp_send_json_error(['message' => $resp->get_error_message()], 500);
		}
		$code = (int) wp_remote_retrieve_response_code($resp);
		$body = (string) wp_remote_retrieve_body($resp);
		if ($code < 200 || $code >= 300) {
			wp_send_json_error(['message' => 'HTTP ' . $code . ' - ' . $body], 500);
		}

		wp_send_json_success(['message' => 'Connection OK (models endpoint reachable).']);
	}

	private function cache_key(array $settings, $prompt, $image_id, $post_id)
	{
		$user_id = (int) get_current_user_id();
		$pieces = [
			'user' => $user_id,
			'post' => (int) $post_id,
			'prompt' => (string) $prompt,
			'image_id' => (int) $image_id,
			'base' => (string) $settings['api_base_url'],
			'mt' => (string) $settings['model_text'],
			'mv' => (string) $settings['model_vision'],
		];
		return 'boylar_magic_cache_' . md5(wp_json_encode($pieces));
	}

	private function attachment_to_data_url($attachment_id)
	{
		$attachment_id = absint($attachment_id);
		if (!$attachment_id) {
			return new WP_Error('bad_image', 'Invalid image.');
		}

		$path = get_attached_file($attachment_id);
		if (!$path || !file_exists($path)) {
			return new WP_Error('bad_image', 'Image file not found.');
		}

		$mime = get_post_mime_type($attachment_id);
		if (!$mime || strpos($mime, 'image/') !== 0) {
			return new WP_Error('bad_image', 'Attachment must be an image.');
		}

		$settings = $this->get_settings();
		$max = isset($settings['max_image_bytes']) ? (int) $settings['max_image_bytes'] : 2000000;
		$size = @filesize($path);
		if ($size && $size > $max) {
			return new WP_Error('bad_image', 'Image is too large.');
		}

		$bytes = file_get_contents($path);
		if ($bytes === false) {
			return new WP_Error('bad_image', 'Could not read image.');
		}

		$base64 = base64_encode($bytes);
		return 'data:' . $mime . ';base64,' . $base64;
	}

	private function rate_limit_ok(array $settings)
	{
		$limit = isset($settings['rate_limit_per_minute']) ? (int) $settings['rate_limit_per_minute'] : 6;
		if ($limit <= 0)
			return true;

		$user_id = (int) get_current_user_id();
		$ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		$key = 'boylar_magic_rl_' . md5($user_id . '|' . $ip);

		$state = get_transient($key);
		$state = is_array($state) ? $state : ['count' => 0];
		$count = isset($state['count']) ? (int) $state['count'] : 0;
		if ($count >= $limit)
			return false;

		$state['count'] = $count + 1;
		set_transient($key, $state, MINUTE_IN_SECONDS);
		return true;
	}

	private function get_openai_api_key(array $settings)
	{
		if (defined('BOYLAR_OPENAI_API_KEY') && BOYLAR_OPENAI_API_KEY) {
			return (string) BOYLAR_OPENAI_API_KEY;
		}
		return !empty($settings['api_key']) ? (string) $settings['api_key'] : '';
	}

	private function call_openai_for_elementor_section(array $args)
	{
		$api_key = (string) $args['api_key'];
		$base_url = rtrim((string) ($args['base_url'] ?? 'https://api.openai.com/v1'), '/');
		$model_text = (string) ($args['model_text'] ?? 'gpt-4o-mini');
		$model_vision = (string) ($args['model_vision'] ?? 'gpt-4o');
		$timeout = isset($args['timeout']) ? (int) $args['timeout'] : 60;
		$max_output_tokens = isset($args['max_output_tokens']) ? (int) $args['max_output_tokens'] : 1200;
		$user_text = (string) $args['prompt'];
		$screenshot = (string) $args['screenshot'];

		$system = $this->openai_system_prompt();
		$user_content = [];

		$user_content[] = [
			'type' => 'text',
			'text' => "User prompt:\n" . $user_text,
		];

		// Optional vision input (base64 data URL or raw base64).
		if ($screenshot !== '') {
			$image_url = $screenshot;
			if (strpos($image_url, 'data:image/') !== 0) {
				// Assume raw base64 PNG if not a data URL.
				$image_url = 'data:image/png;base64,' . $image_url;
			}
			$user_content[] = [
				'type' => 'image_url',
				'image_url' => ['url' => $image_url],
			];
		}

		$body = [
			'model' => $screenshot !== '' ? $model_vision : $model_text,
			'temperature' => 0.4,
			'response_format' => ['type' => 'json_object'],
			'max_tokens' => $max_output_tokens,
			'messages' => [
				['role' => 'system', 'content' => $system],
				['role' => 'user', 'content' => $user_content],
			],
		];

		$settings_for_headers = [
			'auth_header_mode' => $args['auth_header_mode'] ?? 'bearer',
		];
		$headers = $this->build_auth_headers($settings_for_headers, $api_key);
		$headers['Content-Type'] = 'application/json';

		$resp = wp_remote_post(
			$base_url . '/chat/completions',
			[
				'timeout' => $timeout,
				'headers' => $headers,
				'body' => wp_json_encode($body),
			]
		);

		if (is_wp_error($resp)) {
			return $resp;
		}

		$code = (int) wp_remote_retrieve_response_code($resp);
		$raw = (string) wp_remote_retrieve_body($resp);
		if ($code < 200 || $code >= 300) {
			return new WP_Error('openai_http_error', 'OpenAI request failed: HTTP ' . $code . ' - ' . $raw);
		}

		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			return new WP_Error('openai_bad_json', 'OpenAI response was not valid JSON.');
		}

		$content = $decoded['choices'][0]['message']['content'] ?? '';
		if (!is_string($content) || $content === '') {
			return new WP_Error('openai_empty', 'OpenAI returned empty content.');
		}

		$payload = json_decode($content, true);
		if (!is_array($payload)) {
			return new WP_Error('openai_payload_bad_json', 'OpenAI payload was not valid JSON.');
		}

		// Expect { "section": { ...elementor section... } }
		return $payload['section'] ?? $payload;
	}

	private function build_auth_headers(array $settings, $api_key)
	{
		$mode = isset($settings['auth_header_mode']) ? (string) $settings['auth_header_mode'] : 'bearer';
		if ($mode === 'x_api_key') {
			return ['x-api-key' => $api_key];
		}
		return ['Authorization' => 'Bearer ' . $api_key];
	}

	private function openai_system_prompt()
	{
		return <<<PROMPT
You are an expert Elementor designer and WordPress developer.

Return ONLY JSON (no markdown). The JSON MUST be an object with a single key "section".

The value of "section" MUST be a single Elementor Section element object compatible with elementor.addSection(section).

### Required shape
- section.elType = "section"
- section.isInner = false
- section.settings = object (Elementor section settings; keep to common keys)
- section.elements = array of Columns (each column is elType "column", with its own settings + elements array)
- Widgets inside columns MUST be native Elementor widgets only:
  - heading (widgetType "heading")
  - text-editor (widgetType "text-editor")
  - image (widgetType "image")
  - button (widgetType "button")

### Widget schema
Each widget MUST include:
- elType: "widget"
- widgetType: one of the allowed widget types above
- settings: object containing realistic Elementor control values

### Visual-to-controls mapping rules (use these keys)
When the user asks for colors/spacing/typography, map them to these typical Elementor settings:
- Section background: section.settings.background_background = "classic", section.settings.background_color = "#RRGGBB"
- Section padding: section.settings.padding = { "unit":"px","top":..,"right":..,"bottom":..,"left":..,"isLinked":false }
- Column padding: column.settings.padding = same structure
- Heading text: heading.settings.title, heading.settings.size ("xl","xxl","large","medium","small"), heading.settings.align ("left","center","right")
- Heading color: heading.settings.title_color = "#RRGGBB"
- Text editor: text-editor.settings.editor (HTML string), text-editor.settings.align
- Button: button.settings.text, button.settings.link = { "url":"https://example.com" }, button.settings.align, button.settings.button_text_color, button.settings.background_color, button.settings.size ("sm","md","lg")
- Image: image.settings.image = { "url":"https://via.placeholder.com/800x500", "id":"", "size":"" }, image.settings.image_size = "full"

### Constraints
- Keep it to ONE section, 1-2 columns, and 3-6 widgets total.
- Use modern defaults: generous spacing, clear hierarchy, accessible contrast.
- DO NOT include any explanations. Output JSON only.
PROMPT;
	}

	private function generate_elementor_id()
	{
		return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 7);
	}

	private function coerce_elementor_section_shape($section)
	{
		if (!is_array($section)) {
			return new WP_Error('invalid_section', 'AI did not return a section object.');
		}

		$section['elType'] = 'section';
		$section['id'] = $this->generate_elementor_id();
		$section['isInner'] = false;
		if (!isset($section['settings']) || !is_array($section['settings'])) {
			$section['settings'] = [];
		}
		if (!isset($section['elements']) || !is_array($section['elements'])) {
			$section['elements'] = [];
		}

		// Guardrail: strict clamp + sanitize to safe subset.
		$section['settings'] = $this->sanitize_section_settings($section['settings']);

		foreach ($section['elements'] as $ci => $col) {
			if (!is_array($col)) {
				unset($section['elements'][$ci]);
				continue;
			}
			$col['elType'] = 'column';
			$col['id'] = $this->generate_elementor_id();
			if (!isset($col['settings']) || !is_array($col['settings'])) {
				$col['settings'] = [];
			}
			if (!isset($col['elements']) || !is_array($col['elements'])) {
				$col['elements'] = [];
			}

			$col['settings'] = $this->sanitize_column_settings($col['settings']);

			foreach ($col['elements'] as $wi => $w) {
				if (!is_array($w)) {
					unset($col['elements'][$wi]);
					continue;
				}
				$w['elType'] = 'widget';
				$w['id'] = $this->generate_elementor_id();
				$allowed = ['heading', 'text-editor', 'image', 'button'];
				if (empty($w['widgetType']) || !in_array($w['widgetType'], $allowed, true)) {
					$w['widgetType'] = 'text-editor';
				}
				if (!isset($w['settings']) || !is_array($w['settings'])) {
					$w['settings'] = [];
				}
				$w['settings'] = $this->sanitize_widget_settings($w['widgetType'], $w['settings']);
				$col['elements'][$wi] = $w;
			}

			$section['elements'][$ci] = $col;
		}

		$section['elements'] = array_values($section['elements']);

		// Minimal fallback if AI returns nothing usable.
		if (empty($section['elements'])) {
			$section['elements'] = [
				[
					'elType' => 'column',
					'id' => $this->generate_elementor_id(),
					'settings' => [],
					'elements' => [
						[
							'elType' => 'widget',
							'id' => $this->generate_elementor_id(),
							'widgetType' => 'heading',
							'settings' => [
								'title' => 'Generated Section',
								'size' => 'xl',
								'align' => 'left',
							],
						],
					],
				],
			];
		}

		return $section;
	}

	private function sanitize_section_settings(array $settings)
	{
		$out = [];
		if (isset($settings['background_background'])) {
			$out['background_background'] = sanitize_text_field((string) $settings['background_background']);
		}
		if (isset($settings['background_color'])) {
			$out['background_color'] = $this->sanitize_color((string) $settings['background_color']);
		}
		if (isset($settings['padding']) && is_array($settings['padding'])) {
			$out['padding'] = $this->sanitize_box($settings['padding']);
		}
		return $out;
	}

	private function sanitize_column_settings(array $settings)
	{
		$out = [];
		if (isset($settings['padding']) && is_array($settings['padding'])) {
			$out['padding'] = $this->sanitize_box($settings['padding']);
		}
		return $out;
	}

	private function sanitize_widget_settings($widget_type, array $settings)
	{
		switch ($widget_type) {
			case 'heading':
				return [
					'title' => isset($settings['title']) ? sanitize_text_field((string) $settings['title']) : '',
					'size' => isset($settings['size']) ? sanitize_text_field((string) $settings['size']) : '',
					'align' => isset($settings['align']) ? sanitize_text_field((string) $settings['align']) : '',
					'title_color' => isset($settings['title_color']) ? $this->sanitize_color((string) $settings['title_color']) : '',
				];
			case 'text-editor':
				return [
					'editor' => isset($settings['editor']) ? wp_kses_post((string) $settings['editor']) : '',
					'align' => isset($settings['align']) ? sanitize_text_field((string) $settings['align']) : '',
				];
			case 'button':
				$link = [];
				if (isset($settings['link']) && is_array($settings['link']) && isset($settings['link']['url'])) {
					$link['url'] = esc_url_raw((string) $settings['link']['url']);
				}
				return [
					'text' => isset($settings['text']) ? sanitize_text_field((string) $settings['text']) : '',
					'link' => $link,
					'align' => isset($settings['align']) ? sanitize_text_field((string) $settings['align']) : '',
					'size' => isset($settings['size']) ? sanitize_text_field((string) $settings['size']) : '',
					'button_text_color' => isset($settings['button_text_color']) ? $this->sanitize_color((string) $settings['button_text_color']) : '',
					'background_color' => isset($settings['background_color']) ? $this->sanitize_color((string) $settings['background_color']) : '',
				];
			case 'image':
				$image = ['url' => ''];
				if (isset($settings['image']) && is_array($settings['image'])) {
					if (isset($settings['image']['url'])) {
						$image['url'] = esc_url_raw((string) $settings['image']['url']);
					}
					if (isset($settings['image']['id'])) {
						$image['id'] = sanitize_text_field((string) $settings['image']['id']);
					}
					if (isset($settings['image']['size'])) {
						$image['size'] = sanitize_text_field((string) $settings['image']['size']);
					}
				}
				return [
					'image' => $image,
					'image_size' => isset($settings['image_size']) ? sanitize_text_field((string) $settings['image_size']) : 'full',
				];
		}
		return [];
	}

	private function sanitize_color($value)
	{
		$value = trim((string) $value);
		if ($value === '')
			return '';
		$hex = sanitize_hex_color($value);
		return $hex ? $hex : '';
	}

	private function sanitize_box(array $box)
	{
		$unit = isset($box['unit']) ? sanitize_text_field((string) $box['unit']) : 'px';
		if (!in_array($unit, ['px', '%', 'em', 'rem', 'vh', 'vw'], true))
			$unit = 'px';

		$clamp = function ($n) {
			$n = is_numeric($n) ? (float) $n : 0;
			if ($n < 0)
				$n = 0;
			if ($n > 200)
				$n = 200;
			return (string) (int) round($n);
		};

		return [
			'unit' => $unit,
			'top' => $clamp($box['top'] ?? 0),
			'right' => $clamp($box['right'] ?? 0),
			'bottom' => $clamp($box['bottom'] ?? 0),
			'left' => $clamp($box['left'] ?? 0),
			'isLinked' => !empty($box['isLinked']),
		];
	}
}

