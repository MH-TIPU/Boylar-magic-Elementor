=== Boylar magic Elementor ===
Contributors: boylar
Tags: elementor, ai, generator, openai, page builder
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate fully editable Elementor sections using AI (prompt and optional reference image).

== Description ==

Boylar magic Elementor adds a drag-and-drop Elementor widget that generates a brand new, fully editable Elementor section (native widgets like Heading, Text Editor, Image, Button) from:

- A text prompt (required)
- An optional reference image/screenshot (vision)

The generated section is inserted into your layout using Elementor’s internal APIs, so you can edit every element normally (text, colors, spacing, typography).

### Privacy
When you click **Generate Section**, your prompt (and optional reference image) is sent to the configured AI provider endpoint for processing.

Do not include sensitive personal data in prompts or screenshots.

This plugin lets the site owner configure the provider endpoint (default is OpenAI). You are responsible for ensuring your use complies with your provider’s terms and privacy policy.

### Cost & abuse controls
Site owners can configure rate limiting and short-term caching to reduce costs and prevent repeated requests.

### No registration for editors
Editors do not need any external signup or login. The site owner configures an AI provider once.

### AI Provider
This plugin supports **OpenAI-compatible** Chat Completions endpoints. You can use:
- OpenAI (default)
- Any compatible gateway/provider
- A self-hosted OpenAI-compatible server (no per-request API fees, but you run the compute)

== Installation ==

1. Upload the `boylar-magic-elementor` folder to `/wp-content/plugins/`
2. Activate **Boylar magic Elementor** from **Plugins**
3. Go to **Settings → Boylar magic Elementor** and configure:
   - API Base URL
   - API Key
   - Auth header
   - Model (text) / Model (vision)
   - (Optional) Rate limit / Cache TTL / Limits

== How to use ==

1. Open a page in the **Elementor Editor**
2. Drag **Boylar Magic Elementor** widget to the canvas
3. Enter a prompt (optional: select a reference image)
4. Click **Generate Section**
5. The plugin inserts a new section with native Elementor widgets
6. (Optional) The generator widget auto-removes itself after successful generation (toggle in settings)

== Frequently Asked Questions ==

= Is it free? =
The plugin is free. AI providers are typically paid services. You can point the plugin at a self-hosted OpenAI-compatible endpoint if you want “no per-request API fees”, but you still pay for hosting/compute.

= Do my editors need to register or log in anywhere? =
No external registration. Only users who can edit in Elementor (logged-in WordPress users) can generate sections.

= Does it work without Elementor Pro? =
Yes, it targets native Elementor widgets available in Elementor Free.

= Where is the API key stored? =
In WordPress options (Settings page). For best security you can define `BOYLAR_OPENAI_API_KEY` in `wp-config.php`.

== Screenshots ==

1. Boylar Magic Elementor widget in Elementor panel
2. Prompt + optional reference image controls
3. Generated section inserted and fully editable

== Changelog ==

= 0.1.0 =
* Initial release: Elementor widget + editor injection + OpenAI-compatible provider support + settings page.

