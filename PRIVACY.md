## Privacy (Boylar magic Elementor)

### What this plugin does
This plugin generates Elementor sections from a user prompt and optional reference image.

### Data sent to external services
When a user clicks **Generate Section** in the Elementor editor, the plugin sends:
- The **prompt text** entered in the widget
- The **reference image** (optional), selected from the WordPress Media Library (sent as an encoded image payload)

This data is sent to the **AI provider endpoint configured by the site owner** in:
**WP Admin → Settings → Boylar magic Elementor** (default is OpenAI, but it can be any OpenAI-compatible endpoint).

### When data is sent
Data is sent **only** when the user clicks **Generate Section**. The plugin does not send data in the background.

### Where data is stored
- The plugin stores provider settings in WordPress options.
- The plugin may store short-lived cached results (if caching is enabled in settings) using WordPress transients to reduce repeated requests/costs.

### What you should not include
Do not include sensitive personal data (passwords, payment details, private IDs) in prompts or screenshots.

### Site owner responsibilities
You are responsible for:
- Choosing the AI provider endpoint
- Reviewing your provider’s privacy policy/terms
- Updating your site privacy policy accordingly

