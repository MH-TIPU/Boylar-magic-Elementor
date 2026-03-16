<?php
/**
 * Plugin Name: Boylar magic Elementor
 * Description: Elementor addon that generates fully editable Elementor sections using AI providers (OpenAI-compatible). Default uses 100% free Gemini API.
 * Version: 0.2.0
 * Author: Boylar
 * Text Domain: boylar-magic-elementor
 */
if (!defined('ABSPATH'))
	exit;

define('BOYLAR_ME_VERSION', '0.2.0');
define('BOYLAR_ME_PLUGIN_FILE', __FILE__);
define('BOYLAR_ME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BOYLAR_ME_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once BOYLAR_ME_PLUGIN_DIR . 'includes/class-boylar-elementor-code-generator.php';

add_action('plugins_loaded', function () {
	// Elementor is required only for the editor integration.
	if (!did_action('elementor/loaded')) {
		return;
	}

	new Boylar_Elementor_Code_Generator();
});

