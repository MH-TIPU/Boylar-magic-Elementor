# Boylar magic Elementor

Elementor addon that generates **fully editable Elementor sections** (native widgets) from a prompt and/or reference image.

## For site owners (setup)

### Requirements
- WordPress 6.x+
- Elementor (Free or Pro) installed and active

### Install
1. Upload the plugin folder to `wp-content/plugins/`
2. Activate **Boylar magic Elementor**

### Configure AI provider
Go to **WP Admin → Settings → Boylar magic Elementor**:
- **API Base URL**: an *OpenAI-compatible* endpoint (defaults to OpenAI)
- **API Key**: required for hosted providers
- **Auth header**: choose `Authorization: Bearer` or `x-api-key` depending on your provider
- **Model (text)** and **Model (vision)**: defaults are `gpt-4o-mini` and `gpt-4o`
- **Auto-remove generator widget**: enabled by default
- **Cache TTL**: caches repeated prompts briefly to reduce costs
- **Rate limit**: limits requests per user per minute
- **Request timeout** and **Max output tokens**: control request behavior/cost
- **Test connection**: validates settings against the provider `/models` endpoint

> No external user login/registration is required. The site owner configures the provider once.

### Use in Elementor
1. Open any page in **Elementor Editor**
2. Find the widget: **Magic AI Generator** (in “General”)
3. Drag it into the page
4. Enter a prompt (optional: select an image)
5. Click **Generate Section**
6. The plugin injects a new, fully editable section; the generator widget is removed automatically (if enabled)

## For end users (content editors)
- You can click any generated Heading/Text/Image/Button and edit it normally.
- Use the prompt to specify layout, colors, spacing, and CTA text.
- If you provide a reference image, the model will try to match the layout.

## For developers

### Architecture
- **Elementor widget** renders a simple “Generate” button and stores prompt/image in widget settings
- **Editor JS** calls WP AJAX and injects the returned element tree via `elementor.addSection()`
- **PHP AJAX handler** calls the configured AI provider and returns a validated Elementor section object

### Main files
- `boylar-elementor-code-generator.php`: plugin bootstrap (inside `boylar-magic-elementor/` folder)
- `includes/class-boylar-elementor-code-generator.php`: hooks, settings page, AJAX
- `includes/widgets/class-boylar-magic-ai-widget.php`: Elementor widget
- `assets/editor/magic-ai.js`: editor-only generation + injection + auto-remove

### AJAX endpoint
- Action: `wp_ajax_boylar_magic_ai_generate`
- Nonce: `boylar_magic_ai_nonce` (sent as `BoylarMagicAI.nonce`)

### AI provider compatibility
This plugin uses an **OpenAI Chat Completions compatible** endpoint:
- Request: `POST {base_url}/chat/completions`
- Uses `response_format: { type: "json_object" }` and expects `{ "section": { ... } }`

You can point **API Base URL** to any compatible service or a self-hosted gateway.

## “Is there an absolutely free API?”
There is **no reliable “forever free” hosted API** for quality generative output at scale.

Your practical options:
- **Hosted providers** (paid): easiest and fastest to set up.
- **Self-hosted (local) models** (no per-request fees): you run your own server (e.g., an OpenAI-compatible gateway). This is “free” in API cost, but not free in compute.

## Security notes
- Only logged-in users with editor permissions can generate (requires Elementor editor access).
- Requests are protected with WordPress nonces.
- Images are loaded server-side from Media Library attachment IDs (no raw public file reads).
- Rate limiting, prompt length limits, and optional caching reduce abuse and cost spikes.

## Privacy
When you click **Generate Section**, your prompt (and optional reference image) is sent to the configured AI provider endpoint for processing. Do not include sensitive personal data in prompts or screenshots.

## License
GPLv2 or later.

