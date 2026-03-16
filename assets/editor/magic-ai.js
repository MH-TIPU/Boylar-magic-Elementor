/* global elementor, jQuery, BoylarMagicAI */
(function ($) {
  "use strict";

  function ensureElementorReady() {
    if (!window.elementor) {
      throw new Error("Elementor editor not available.");
    }
  }

  async function boylarMagicAIAddSection(sectionJson) {
    ensureElementorReady();

    if (!sectionJson || sectionJson.elType !== "section") {
      throw new Error("Invalid AI JSON: expected a 'section' object.");
    }

    const addedModel = elementor.addSection(sectionJson);

    try {
      if (addedModel && elementor.selection) {
        elementor.selection.select(addedModel);
      }
    } catch (e) { }

    return addedModel;
  }

  function showNotice(type, message) {
    // Prefer Elementor notifications; fallback to alert.
    try {
      if (elementor && elementor.notifications && elementor.notifications.showToast) {
        elementor.notifications.showToast({ message, type });
        return;
      }
    } catch (e) { }
    if (type === "error") {
      window.alert(message);
    }
  }

  function addSectionNearElement(sectionJson, elementId) {
    // Best-effort: insert near the generator widget.
    // MUST be inserted at the root level, NOT as a child widget sibling.
    let topLevelId = null;
    try {
      if (elementId && elementor && elementor.documents && elementor.documents.getCurrent) {
        const doc = elementor.documents.getCurrent();
        if (doc && typeof doc.getElementsById === "function") {
          let model = doc.getElementsById(elementId);
          while (model && model.get("parent")) {
            let parent = model.get("parent");
            let parentType = parent.get("elType") || parent.get("type");
            if (parentType === "document" || parentType === "root" || !parentType) {
              topLevelId = model.get("id");
              break;
            }
            model = parent;
          }
        }
      }
    } catch (e) { }

    // Prefer Elementor command stack (undo-friendly) if available.
    try {
      if (elementor && elementor.commands && typeof elementor.commands.run === "function") {
        elementor.commands.run("document/elements/create", {
          model: sectionJson,
          options: topLevelId ? { at: "after", targetId: topLevelId } : {},
        });
        return true;
      }
    } catch (e) { }

    // Fallback
    elementor.addSection(sectionJson);
    return true;
  }

  async function boylarMagicAIGenerateAndInsert({ prompt, screenshotBase64 = "" }) {
    const res = await $.ajax({
      url: BoylarMagicAI.ajaxUrl,
      method: "POST",
      dataType: "json",
      data: {
        action: "boylar_magic_ai_generate",
        nonce: BoylarMagicAI.nonce,
        prompt,
        screenshot: screenshotBase64,
        image_id: 0,
        post_id: BoylarMagicAI.postId,
      },
    });

    if (!res || !res.success) {
      throw new Error((res && res.data && res.data.message) || "AI generation failed.");
    }

    return boylarMagicAIAddSection(res.data.section);
  }

  async function generateFromWidget($widgetRoot) {
    const promptFromAttr = ($widgetRoot.attr("data-boylar-prompt") || "").trim();
    const imageId = parseInt($widgetRoot.attr("data-boylar-image-id") || "0", 10) || 0;
    const elementIdFromAttr = ($widgetRoot.attr("data-boylar-element-id") || "").trim();
    const elementId =
      elementIdFromAttr ||
      ($widgetRoot.closest(".elementor-element").attr("data-id") || "").trim();

    let prompt = promptFromAttr;
    if (!prompt) {
      prompt = (window.prompt(
        "Describe the section you want (e.g., 'Hero with headline, subtext, button, blue background')."
      ) || "").trim();
    }
    if (!prompt) return;

    const $btn = $widgetRoot.find(".boylar-magic-ai-generate").first();
    $btn.prop("disabled", true).text("Generating...");

    try {
      const res = await $.ajax({
        url: BoylarMagicAI.ajaxUrl,
        method: "POST",
        dataType: "json",
        data: {
          action: "boylar_magic_ai_generate",
          nonce: BoylarMagicAI.nonce,
          prompt,
          image_id: imageId,
          post_id: BoylarMagicAI.postId,
        },
      });

      if (!res || !res.success) {
        throw new Error((res && res.data && res.data.message) || "AI generation failed.");
      }

      // Prefer inserting near the widget if possible.
      try {
        addSectionNearElement(res.data.section, elementId);
      } catch (e) {
        await boylarMagicAIAddSection(res.data.section);
      }
      $btn.text("Generated");

      if (BoylarMagicAI.autoRemoveWidget && elementId) {
        removeElementByIdBestEffort(elementId);
      }
      showNotice("success", "Section generated and inserted.");
    } catch (err) {
      $btn.text("Generate Section");
      showNotice("error", err && err.message ? err.message : String(err));
    } finally {
      $btn.prop("disabled", false);
    }
  }

  function bindWidgetButtons() {
    // 1. The main editor document (handles clicks if widget is somehow in the main doc)
    $(document).on("click", ".boylar-magic-ai-widget .boylar-magic-ai-generate", function (e) {
      e.preventDefault();
      const $root = $(this).closest(".boylar-magic-ai-widget");
      generateFromWidget($root);
    });

    // 2. Elementor hooks (when a specific widget is opened)
    if (window.elementor && window.elementor.hooks) {
      window.elementor.hooks.addAction('panel/open_editor/widget/boylar_magic_ai_generator', function (panel, model, view) {
        bindIframeEvents();
      });
    }

    // 3. Listen to preview iframe loads/reloads
    if (window.elementor) {
      window.elementor.on("preview:loaded", bindIframeEvents);
      window.elementor.on("document:loaded", bindIframeEvents);
    }

    // 4. Try an immediate bind as well
    bindIframeEvents();

    // 5. Fallback: occasionally check and bind if the iframe body was replaced
    setInterval(bindIframeEvents, 3000);
  }

  function bindIframeEvents() {
    try {
      if (elementor && elementor.$previewContents) {
        // Unbind first to prevent multiple firings
        elementor.$previewContents.find('body').off("click.boylarMagic")
          .on("click.boylarMagic", ".boylar-magic-ai-widget .boylar-magic-ai-generate", function (e) {
            e.preventDefault();
            const $root = $(this).closest(".boylar-magic-ai-widget");
            generateFromWidget($root);
          });
      }
    } catch (e) {
      console.error("Boylar Magic Elementor: Failed to bind iframe events", e);
    }
  }

  function removeElementByIdBestEffort(elementId) {
    // Elementor internal APIs differ across versions.
    // We attempt multiple safe paths; if all fail, we just leave the widget in place.
    try {
      if (elementor && elementor.commands && typeof elementor.commands.run === "function") {
        elementor.commands.run("document/elements/delete", { id: elementId });
        return true;
      }
    } catch (e) { }

    try {
      if (elementor.documents && elementor.documents.getCurrent) {
        const doc = elementor.documents.getCurrent();
        if (doc && typeof doc.getElementsById === "function") {
          const model = doc.getElementsById(elementId);
          if (model && typeof model.destroy === "function") {
            model.destroy();
            return true;
          }
        }
        if (doc && typeof doc.getElements === "function") {
          const rootCollection = doc.getElements();
          const model = findModelRecursive(rootCollection, elementId);
          if (model && typeof model.destroy === "function") {
            model.destroy();
            return true;
          }
        }
      }
    } catch (e) { }

    try {
      if (elementor.elements && elementor.elements.models) {
        const model = findModelRecursive(elementor.elements, elementId);
        if (model && typeof model.destroy === "function") {
          model.destroy();
          return true;
        }
      }
    } catch (e) { }

    return false;
  }

  function findModelRecursive(collectionOrModel, elementId) {
    if (!collectionOrModel) return null;

    // If it's a model with id.
    if (collectionOrModel.get && typeof collectionOrModel.get === "function") {
      const id = collectionOrModel.get("id");
      if (id === elementId) return collectionOrModel;
      const children = collectionOrModel.get("elements");
      if (children) return findModelRecursive(children, elementId);
      return null;
    }

    // If it's a Backbone collection-like.
    const models = collectionOrModel.models || collectionOrModel;
    if (!models || !models.length) return null;
    for (let i = 0; i < models.length; i++) {
      const m = models[i];
      const found = findModelRecursive(m, elementId);
      if (found) return found;
    }
    return null;
  }

  function boot() {
    try {
      ensureElementorReady();
    } catch (e) {
      return;
    }
    bindWidgetButtons();
  }

  $(window).on("elementor:init", boot);
  $(boot);

  window.BoylarMagicAIEditor = {
    addSection: boylarMagicAIAddSection,
    generateAndInsert: boylarMagicAIGenerateAndInsert,
  };
})(jQuery);

