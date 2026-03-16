<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Boylar_Magic_AI_Generator_Widget extends Widget_Base {
	public function get_name() {
		return 'boylar_magic_ai_generator';
	}

	public function get_title() {
		return __( 'Magic AI Generator', 'boylar-magic-elementor' );
	}

	public function get_icon() {
		return 'eicon-ai';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_keywords() {
		return [ 'ai', 'generator', 'openai', 'section', 'layout' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'boylar_section',
			[
				'label' => __( 'Magic AI', 'boylar-magic-elementor' ),
			]
		);

		$this->add_control(
			'prompt',
			[
				'label' => __( 'Prompt', 'boylar-magic-elementor' ),
				'type' => Controls_Manager::TEXTAREA,
				'rows' => 6,
				'placeholder' => __( 'Example: Hero section with headline, subtext, CTA button, blue background.', 'boylar-magic-elementor' ),
				'default' => '',
			]
		);

		$this->add_control(
			'image',
			[
				'label' => __( 'Reference Image (optional)', 'boylar-magic-elementor' ),
				'type' => Controls_Manager::MEDIA,
				'description' => __( 'Optional screenshot/reference. If provided, AI will use it to infer layout.', 'boylar-magic-elementor' ),
				'default' => [
					'url' => '',
				],
			]
		);

		$this->add_control(
			'generate_note',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => '<strong>' . esc_html__( 'Tip:', 'boylar-magic-elementor' ) . '</strong> ' .
					esc_html__( 'Use this widget only to generate. After generation you can delete this widget.', 'boylar-magic-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$prompt   = isset( $settings['prompt'] ) ? (string) $settings['prompt'] : '';
		$image_id = 0;
		if ( ! empty( $settings['image']['id'] ) ) {
			$image_id = absint( $settings['image']['id'] );
		}

		// Canvas button. JS (editor-only) will bind click handler and call AJAX.
		$element_id = method_exists( $this, 'get_id' ) ? (string) $this->get_id() : '';
		echo '<div class="boylar-magic-ai-widget" data-boylar-prompt="' . esc_attr( $prompt ) . '" data-boylar-image-id="' . esc_attr( (string) $image_id ) . '" data-boylar-element-id="' . esc_attr( $element_id ) . '">';
		echo '<button type="button" class="boylar-magic-ai-generate elementor-button elementor-size-sm elementor-button-success">';
		echo esc_html__( 'Generate Section', 'boylar-magic-elementor' );
		echo '</button>';
		echo '</div>';
	}

	protected function content_template() {
		?>
		<#
		var prompt = settings.prompt || '';
		var imageId = (settings.image && settings.image.id) ? settings.image.id : '';
		var elementId = view.getID ? view.getID() : '';
		#>
		<div class="boylar-magic-ai-widget" data-boylar-prompt="{{ prompt }}" data-boylar-image-id="{{ imageId }}" data-boylar-element-id="{{ elementId }}">
			<button type="button" class="boylar-magic-ai-generate elementor-button elementor-size-sm elementor-button-success">
				<?php echo esc_html__( 'Generate Section', 'boylar-magic-elementor' ); ?>
			</button>
		</div>
		<?php
	}
}

