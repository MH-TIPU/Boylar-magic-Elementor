## Contributing

### Development setup
- WordPress + Elementor installed locally
- Activate **Boylar magic Elementor**
- Configure **Settings → Boylar magic Elementor**

### Coding standards
- PHP: WordPress Coding Standards style
- JS: keep editor code defensive (Elementor APIs vary by version)
- Security: sanitize all inputs, validate permissions, protect requests with nonces

### What to test before PR
- Prompt-only generation works
- Vision (image) generation works (if enabled)
- Generated section is editable (Heading/Text/Image/Button)
- Auto-remove generator widget works
- Settings page saves/loads correctly

