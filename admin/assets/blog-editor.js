/* BlueDesk article editor: Word-like formatting with safe rich-text paste. */
(function () {
  'use strict';

  const allowedTags = new Set([
    'A', 'B', 'BLOCKQUOTE', 'BR', 'CAPTION', 'CODE', 'DEL', 'DIV', 'EM', 'FIGCAPTION', 'FIGURE', 'FONT',
    'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'HR', 'I', 'IMG', 'LI', 'MARK', 'OL', 'P', 'PRE', 'S', 'SMALL',
    'SPAN', 'STRIKE', 'STRONG', 'SUB', 'SUP', 'TABLE', 'TBODY', 'TD', 'TFOOT', 'TH', 'THEAD', 'TR', 'U', 'UL'
  ]);
  const dropTags = new Set(['APPLET', 'EMBED', 'FORM', 'IFRAME', 'MATH', 'OBJECT', 'SCRIPT', 'STYLE', 'SVG', 'TEMPLATE']);
  const colors = new Set(['black', 'blue', 'brown', 'gray', 'green', 'grey', 'maroon', 'navy', 'olive', 'orange', 'purple', 'red', 'silver', 'teal', 'white', 'yellow', 'transparent']);
  let pastedImageSequence = 0;

  function safeStyle(value) {
    const probe = document.createElement('span');
    probe.style.cssText = value;
    const output = [];
    const colorValue = raw => {
      const normalized = String(raw || '').trim().toLowerCase();
      return colors.has(normalized) || /^#[0-9a-f]{3,8}$/i.test(normalized) || /^rgba?\(\s*[0-9.%,\s]+\)$/i.test(normalized) ? normalized : '';
    };
    const allowedFamilies = ['Arial', 'Aptos', 'Calibri', 'Cambria', 'Cairo', 'Candara', 'Consolas', 'Courier New', 'Garamond', 'Georgia', 'Helvetica', 'Lato', 'Montserrat', 'Open Sans', 'Poppins', 'Roboto', 'sans-serif', 'serif', 'monospace', 'system-ui', 'Segoe UI', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'];
    const numeric = (raw, max, units) => {
      const match = String(raw || '').trim().match(/^([0-9]+(?:\.[0-9]+)?)(px|pt|em|rem|%)$/i);
      if (!match || Number(match[1]) < 8 || Number(match[1]) > max || !units.includes(match[2].toLowerCase())) return '';
      return match[1] + match[2].toLowerCase();
    };
    for (const property of Array.from(probe.style)) {
      const raw = probe.style.getPropertyValue(property).trim();
      if (!raw || /url\s*\(|expression\s*\(|javascript\s*:/i.test(raw)) continue;
      let cleaned = '';
      if (property === 'color' || property === 'background-color') cleaned = colorValue(raw);
      else if (property === 'text-align' && /^(left|right|center|justify|start|end)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'direction' && /^(ltr|rtl)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'font-weight' && /^(normal|bold|[1-9]00)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'font-style' && /^(normal|italic|oblique)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'text-decoration' && /^(none|underline|line-through|overline)(\s+(underline|line-through|overline))*$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'vertical-align' && /^(baseline|sub|super|middle|top|bottom)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'font-family') {
        const familyList = raw.split(',').map(family => {
          const normalized = family.trim().replace(/^['"]|['"]$/g, '').toLowerCase();
          return allowedFamilies.find(name => name.toLowerCase() === normalized) || '';
        });
        if (familyList.length && familyList.every(Boolean)) cleaned = familyList.join(', ');
      } else if (property === 'font-size') cleaned = numeric(raw, /pt/i.test(raw) ? 36 : (/%/.test(raw) ? 300 : 48), ['px', 'pt', 'em', 'rem', '%']);
      else if (property === 'line-height' && /^(normal|[1-3](?:\.[0-9]+)?|[8-9][0-9](?:\.[0-9]+)%)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (/^(margin|margin-(top|bottom|left|right)|padding|padding-(top|bottom|left|right))$/.test(property) && /^(0|[0-9]{1,2}(?:\.[0-9]+)?(?:px|pt|em|rem)|auto)(\s+(0|[0-9]{1,2}(?:\.[0-9]+)?(?:px|pt|em|rem)|auto)){0,3}$/i.test(raw)) cleaned = raw.toLowerCase();
      else if ((property === 'width' || property === 'max-width') && /^(100|[1-9]?[0-9])%$|^(auto|[0-9]{1,3}(?:px|pt))$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'white-space' && /^(normal|pre|pre-wrap|nowrap)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'list-style-type' && /^(disc|circle|square|decimal|lower-alpha|upper-alpha|lower-roman|upper-roman|none)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'border-collapse' && /^(collapse|separate)$/i.test(raw)) cleaned = raw.toLowerCase();
      else if (property === 'border' && /^(0|[1-3]px\s+(solid|dashed|dotted)\s+#[0-9a-f]{3,8})$/i.test(raw)) cleaned = raw.toLowerCase();
      if (cleaned) output.push(property + ':' + cleaned);
    }
    return output.join(';');
  }

  function allowedUrl(value, kind) {
    const raw = String(value || '').trim();
    if (!raw || raw.startsWith('//') || /[\u0000-\u0020\\]/.test(raw)) return false;
    const scheme = raw.match(/^([a-z][a-z0-9+.-]*):/i)?.[1]?.toLowerCase();
    if (scheme) return kind === 'link'
      ? ['http', 'https', 'mailto', 'tel'].includes(scheme)
      : ['http', 'https'].includes(scheme);
    return raw.startsWith('/') || raw.startsWith('#') || raw.startsWith('?') || raw.startsWith('./') || raw.startsWith('../') || !raw.startsWith(':');
  }

  function sanitizeHtml(html) {
    const parsed = new DOMParser().parseFromString('<body>' + String(html || '') + '</body>', 'text/html');
    const clean = parent => {
      Array.from(parent.childNodes).forEach(node => {
        if (node.nodeType === Node.COMMENT_NODE) {
          node.remove();
          return;
        }
        if (node.nodeType !== Node.ELEMENT_NODE) return;
        const tag = node.tagName.toUpperCase();
        if (dropTags.has(tag)) {
          node.remove();
          return;
        }
        if (!allowedTags.has(tag)) {
          clean(node);
          node.replaceWith(...Array.from(node.childNodes));
          return;
        }
        Array.from(node.attributes).forEach(attribute => {
          const name = attribute.name.toLowerCase();
          const value = attribute.value;
          let keep = ['title', 'style', 'dir', 'align'].includes(name);
          if (tag === 'A' && name === 'href' && allowedUrl(value, 'link')) keep = true;
          if (tag === 'IMG' && ['src', 'alt', 'width', 'height'].includes(name)) keep = true;
          if (tag === 'IMG' && name === 'data-editor-paste-image') keep = true;
          if (['TD', 'TH'].includes(tag) && ['colspan', 'rowspan', 'scope'].includes(name)) keep = true;
          if (tag === 'OL' && ['start', 'type'].includes(name)) keep = true;
          if (tag === 'LI' && name === 'value') keep = true;
          if (tag === 'FONT' && ['color', 'face', 'size'].includes(name)) keep = true;
          if (name === 'style') {
            const style = safeStyle(value);
            if (style) node.setAttribute('style', style);
            else node.removeAttribute('style');
          } else if (name === 'align') {
            if (/^(left|right|center|justify)$/i.test(value)) {
              node.setAttribute('style', safeStyle((node.getAttribute('style') || '') + ';text-align:' + value));
            }
            node.removeAttribute('align');
          } else if (name === 'dir') {
            if (!/^(ltr|rtl|auto)$/i.test(value)) node.removeAttribute(name);
          } else if (name === 'href' || name === 'src') {
            if (!allowedUrl(value, name === 'href' ? 'link' : 'image')) node.removeAttribute(name);
          } else if (['width', 'height'].includes(name)) {
            if (!/^[1-9][0-9]{0,3}$/.test(value)) node.removeAttribute(name);
          } else if (['colspan', 'rowspan', 'start', 'value'].includes(name)) {
            if (!/^[1-9][0-9]{0,2}$/.test(value)) node.removeAttribute(name);
          } else if (tag === 'OL' && name === 'type') {
            if (!/^(1|a|A|i|I)$/.test(value)) node.removeAttribute(name);
          } else if (['scope'].includes(name)) {
            if (!['row', 'col', 'rowgroup', 'colgroup'].includes(value.toLowerCase())) node.removeAttribute(name);
          } else if (tag === 'FONT' && name === 'color') {
            if (!safeStyle('color:' + value)) node.removeAttribute(name);
          } else if (tag === 'FONT' && name === 'size') {
            if (!/^[1-7]$/.test(value)) node.removeAttribute(name);
          } else if (!keep) {
            node.removeAttribute(name);
          }
        });
        if (tag === 'IMG' && !node.hasAttribute('src') && !node.hasAttribute('data-editor-paste-image')) {
          node.remove();
          return;
        }
        clean(node);
      });
    };
    clean(parsed.body);
    return parsed.body.innerHTML;
  }

  function uploadImage(file, editor, placeholder) {
    const form = editor.closest('form');
    const formData = new FormData();
    formData.append('_token', editor.dataset.csrf || '');
    formData.append('image', file, file.name || 'pasted-image.png');
    const message = editor.querySelector('[data-editor-message]');
    if (message) {
      message.textContent = editor.dataset.language === 'en' ? 'Uploading image…' : 'جارٍ رفع الصورة…';
      message.classList.remove('is-error');
    }
    const task = fetch(editor.dataset.uploadUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
      .then(async response => {
        const result = await response.json();
        if (!response.ok || !result.url) throw new Error(result.error || 'Image upload failed.');
        if (placeholder && placeholder.isConnected) {
          placeholder.src = result.url;
          placeholder.removeAttribute('data-editor-paste-image');
          placeholder.classList.remove('is-uploading');
        } else if (form) {
          insertImage(editor, result.url, file.name || '');
        }
        return result.url;
      })
      .catch(error => {
        if (placeholder && placeholder.isConnected) placeholder.remove();
        if (message) {
          message.textContent = (editor.dataset.language === 'en' ? 'Could not upload image: ' : 'تعذر رفع الصورة: ') + error.message;
          message.classList.add('is-error');
        }
        return null;
      });
    const pending = editor._pendingUploads || (editor._pendingUploads = new Set());
    pending.add(task);
    task.finally(() => pending.delete(task));
    return task;
  }

  function uploadFromDataUrl(dataUrl, editor, placeholder) {
    const task = fetch(dataUrl)
      .then(response => response.blob())
      .then(blob => {
        const extension = (blob.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
        return uploadImage(new File([blob], 'pasted-image.' + extension, { type: blob.type }), editor, placeholder);
      })
      .catch(error => {
        if (placeholder && placeholder.isConnected) placeholder.remove();
        showMessage(editor, (editor.dataset.language === 'en' ? 'Could not upload pasted image: ' : 'تعذر رفع الصورة الملصقة: ') + error.message, true);
        return null;
      });
    const pending = editor._pendingUploads || (editor._pendingUploads = new Set());
    pending.add(task);
    task.finally(() => pending.delete(task));
    return task;
  }

  function rememberSelection(editor) {
    const selection = window.getSelection();
    if (selection && selection.rangeCount && editor.contains(selection.anchorNode)) {
      editor._savedRange = selection.getRangeAt(0).cloneRange();
    }
  }

  function restoreSelection(editor) {
    editor.focus();
    const selection = window.getSelection();
    if (!selection) return;
    selection.removeAllRanges();
    if (editor._savedRange && editor.contains(editor._savedRange.commonAncestorContainer)) {
      selection.addRange(editor._savedRange);
    } else {
      const range = document.createRange();
      range.selectNodeContents(editor);
      range.collapse(false);
      selection.addRange(range);
    }
  }

  function insertHtml(editor, html) {
    restoreSelection(editor);
    document.execCommand('insertHTML', false, html);
    rememberSelection(editor);
  }

  function insertImage(editor, url, alt) {
    if (!allowedUrl(url, 'image')) return;
    const safeAlt = String(alt || '').replace(/[<>]/g, '').slice(0, 255);
    insertHtml(editor, '<img src="' + url.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="' + safeAlt.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '">');
  }

  function showMessage(editor, message, isError) {
    const target = editor.querySelector('[data-editor-message]');
    if (!target) return;
    target.textContent = message;
    target.classList.toggle('is-error', Boolean(isError));
  }

  function updateWordCount(editor) {
    const count = (editor.innerText || '').trim().split(/\s+/u).filter(Boolean).length;
    const target = editor.closest('.rich-editor')?.querySelector('[data-editor-count]');
    if (target) target.textContent = editor.dataset.language === 'en' ? count + ' words' : count + ' كلمة';
  }

  function initEditor(root) {
    const form = root.closest('form');
    const source = document.getElementById(root.dataset.sourceId);
    const surface = root.querySelector('[data-editor-surface]');
    if (!form || !source || !surface) return;
    root.dataset.language = surface.lang || 'ar';
    surface.dataset.placeholder = source.getAttribute('placeholder') || '';
    surface.innerHTML = sanitizeHtml(source.value);
    surface.hidden = false;
    source.classList.add('is-enhanced');
    source.required = false;
    updateWordCount(surface);
    root.closest('.blog-editor-field')?.querySelector('label[for="' + CSS.escape(source.id) + '"]')?.addEventListener('click', event => {
      event.preventDefault();
      surface.focus();
    });

    surface.addEventListener('keyup', () => rememberSelection(surface));
    surface.addEventListener('mouseup', () => rememberSelection(surface));
    document.addEventListener('selectionchange', () => rememberSelection(surface));

    root.querySelectorAll('[data-editor-command]').forEach(button => {
      button.addEventListener('mousedown', event => event.preventDefault());
      button.addEventListener('click', () => {
        const command = button.dataset.editorCommand;
        restoreSelection(surface);
        if (command === 'createLink') {
          let href = window.prompt(root.dataset.language === 'en' ? 'Enter the link URL' : 'اكتب رابط الصفحة');
          if (href) {
            href = href.trim();
            if (!/^[a-z][a-z0-9+.-]*:/i.test(href) && !/^(\/|#|\?|\.\/|\.\.\/)/.test(href)) href = 'https://' + href;
            if (allowedUrl(href, 'link')) {
              restoreSelection(surface);
              document.execCommand('createLink', false, href);
            }
          }
        } else {
          document.execCommand(command, false, null);
        }
        rememberSelection(surface);
        surface.dispatchEvent(new Event('input', { bubbles: true }));
      });
    });

    const blockSelect = root.querySelector('[data-editor-block]');
    blockSelect?.addEventListener('change', () => {
      restoreSelection(surface);
      document.execCommand('formatBlock', false, blockSelect.value);
      rememberSelection(surface);
      surface.dispatchEvent(new Event('input', { bubbles: true }));
    });
    root.querySelector('[data-editor-font]')?.addEventListener('change', event => {
      restoreSelection(surface);
      document.execCommand('fontName', false, event.currentTarget.value);
      rememberSelection(surface);
      surface.dispatchEvent(new Event('input', { bubbles: true }));
    });
    root.querySelector('[data-editor-size]')?.addEventListener('change', event => {
      restoreSelection(surface);
      document.execCommand('fontSize', false, event.currentTarget.value);
      rememberSelection(surface);
      surface.dispatchEvent(new Event('input', { bubbles: true }));
    });
    root.querySelector('[data-editor-color]')?.addEventListener('input', event => {
      restoreSelection(surface);
      document.execCommand('foreColor', false, event.currentTarget.value);
      rememberSelection(surface);
      surface.dispatchEvent(new Event('input', { bubbles: true }));
    });
    root.querySelector('[data-editor-highlight]')?.addEventListener('input', event => {
      restoreSelection(surface);
      document.execCommand('hiliteColor', false, event.currentTarget.value);
      rememberSelection(surface);
      surface.dispatchEvent(new Event('input', { bubbles: true }));
    });
    root.querySelector('[data-editor-image-url]')?.addEventListener('mousedown', event => event.preventDefault());
    root.querySelector('[data-editor-image-url]')?.addEventListener('click', () => {
      const url = window.prompt(root.dataset.language === 'en' ? 'Paste an image URL' : 'الصق رابط الصورة');
      if (!url) return;
      const alt = window.prompt(root.dataset.language === 'en' ? 'Describe the image' : 'اكتب وصف الصورة (اختياري)') || '';
      insertImage(surface, url.trim(), alt);
      surface.dispatchEvent(new Event('input', { bubbles: true }));
    });
    root.querySelector('[data-editor-upload-image]')?.addEventListener('mousedown', event => event.preventDefault());
    root.querySelector('[data-editor-upload-image]')?.addEventListener('click', () => root.querySelector('[data-editor-file]')?.click());
    root.querySelector('[data-editor-file]')?.addEventListener('change', event => {
      const file = event.currentTarget.files?.[0];
      if (file) uploadImage(file, root, null).then(() => surface.dispatchEvent(new Event('input', { bubbles: true })));
      event.currentTarget.value = '';
    });
    root.querySelector('[data-editor-table]')?.addEventListener('mousedown', event => event.preventDefault());
    root.querySelector('[data-editor-table]')?.addEventListener('click', () => {
      const labels = root.dataset.language === 'en' ? ['Rows?', 'Columns?'] : ['عدد الصفوف؟', 'عدد الأعمدة؟'];
      const rows = Math.max(1, Math.min(20, parseInt(window.prompt(labels[0], '3'), 10) || 0));
      const columns = Math.max(1, Math.min(12, parseInt(window.prompt(labels[1], '3'), 10) || 0));
      if (!rows || !columns) return;
      const table = document.createElement('table');
      table.style.borderCollapse = 'collapse';
      for (let rowIndex = 0; rowIndex < rows; rowIndex += 1) {
        const row = table.insertRow();
        for (let columnIndex = 0; columnIndex < columns; columnIndex += 1) {
          const cell = rowIndex === 0 ? document.createElement('th') : document.createElement('td');
          cell.textContent = rowIndex === 0
            ? (root.dataset.language === 'en' ? 'Heading' : 'عنوان')
            : (root.dataset.language === 'en' ? 'Text' : 'نص');
          cell.style.border = '1px solid #cbd3df';
          cell.style.padding = '.45rem .6rem';
          row.appendChild(cell);
        }
      }
      insertHtml(surface, table.outerHTML);
      surface.dispatchEvent(new Event('input', { bubbles: true }));
    });

    surface.addEventListener('paste', event => {
      const clipboard = event.clipboardData;
      if (!clipboard) return;
      const html = clipboard.getData('text/html');
      if (!html) return;
      event.preventDefault();
      const parsed = new DOMParser().parseFromString(html, 'text/html');
      const clipboardImages = Array.from(clipboard.items || [])
        .filter(item => item.kind === 'file' && item.type.startsWith('image/'))
        .map(item => item.getAsFile())
        .filter(Boolean);
      let fileIndex = 0;
      const pendingSourceImages = [];
      parsed.querySelectorAll('img').forEach(image => {
        const src = image.getAttribute('src') || '';
        let uploadSource = null;
        if (/^data:image\//i.test(src)) uploadSource = src;
        else if (!allowedUrl(src, 'image') && clipboardImages[fileIndex]) uploadSource = clipboardImages[fileIndex++];
        if (uploadSource) {
          const marker = 'paste-' + (++pastedImageSequence);
          pendingSourceImages.push({ marker, source: uploadSource });
          image.removeAttribute('src');
          image.setAttribute('data-editor-paste-image', marker);
          image.classList.add('is-uploading');
        }
      });
      const safeHtml = sanitizeHtml(parsed.body.innerHTML);
      insertHtml(surface, safeHtml);
      pendingSourceImages.forEach(item => {
        const placeholder = Array.from(surface.querySelectorAll('[data-editor-paste-image]'))
          .find(image => image.getAttribute('data-editor-paste-image') === item.marker);
        if (!placeholder) return;
        if (typeof item.source === 'string') uploadFromDataUrl(item.source, root, placeholder);
        else uploadImage(item.source, root, placeholder);
      });
      surface.dispatchEvent(new Event('input', { bubbles: true }));
    });

    if (!form._richEditorBound) {
      form._richEditorBound = true;
      let resumeAfterUpload = false;
      form.addEventListener('submit', async event => {
        const editors = Array.from(form.querySelectorAll('[data-rich-editor]'));
        editors.forEach(editorRoot => {
          const field = document.getElementById(editorRoot.dataset.sourceId);
          const editorSurface = editorRoot.querySelector('[data-editor-surface]');
          if (field && editorSurface) field.value = editorSurface.innerHTML;
        });
        if (resumeAfterUpload) {
          resumeAfterUpload = false;
          return;
        }
        const emptyEditor = editors.find(editorRoot => {
          const editorSurface = editorRoot.querySelector('[data-editor-surface]');
          return !editorSurface || !(editorSurface.innerText || '').trim();
        });
        if (emptyEditor) {
          event.preventDefault();
          showMessage(emptyEditor, emptyEditor.dataset.language === 'en' ? 'Write some article content before saving.' : 'اكتب محتوى المقال قبل الحفظ.', true);
          emptyEditor.querySelector('[data-editor-surface]')?.focus();
          return;
        }
        const pending = editors.flatMap(editorRoot => Array.from(editorRoot._pendingUploads || []));
        if (!pending.length) return;
        event.preventDefault();
        showMessage(root, root.dataset.language === 'en' ? 'Waiting for pasted images to finish uploading…' : 'انتظر حتى يكتمل رفع الصور الملصقة…', false);
        await Promise.all(pending);
        editors.forEach(editorRoot => {
          const field = document.getElementById(editorRoot.dataset.sourceId);
          const editorSurface = editorRoot.querySelector('[data-editor-surface]');
          if (field && editorSurface) field.value = editorSurface.innerHTML;
        });
        resumeAfterUpload = true;
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
      });
    }
  }

  document.querySelectorAll('[data-rich-editor]').forEach(initEditor);
})();
