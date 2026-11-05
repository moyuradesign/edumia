// Edumia Blog Editor: Markdown formatting with safe server preview and private media picker.
export function initBlogEditor() {
  const root = document.querySelector('[data-blog-editor]');
  if (!root) return;

  const body = root.querySelector('[data-blog-body]');
  const title = root.querySelector('[data-blog-title]');
  const slug = root.querySelector('[data-blog-slug]');
  const status = root.querySelector('[data-blog-status]');
  const save = root.querySelector('[data-blog-save]');
  const preview = root.querySelector('[data-blog-preview]');
  const previewButton = root.querySelector('[data-blog-preview-button]');
  const copyButton = root.querySelector('[data-blog-copy-url]');
  const urlLabel = root.querySelector('[data-blog-url-preview]');
  const feedback = root.querySelector('[data-blog-feedback]');
  const category = root.querySelector('[data-blog-category]');
  const dialog = root.querySelector('[data-blog-media-dialog]');
  const imageGrid = root.querySelector('[data-blog-media-grid]');
  const imageSearch = root.querySelector('[data-blog-media-search]');
  const imageMore = root.querySelector('[data-blog-media-more]');
  const imageMessage = root.querySelector('[data-blog-media-feedback]');
  const chosenImage = root.querySelector('[data-blog-featured-id]');
  const chosenPreview = root.querySelector('[data-blog-featured-preview]');
  const chosenEmpty = root.querySelector('[data-blog-featured-empty]');
  const chosenName = root.querySelector('[data-blog-featured-name]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  let manualSlug = Boolean(slug.value.trim());
  let previewVisible = false;
  let nextPage = 2;
  let searchTimer;
  let searchVersion = 0;
  let pickerMode = 'featured';

  const announce = (text) => { if (feedback) feedback.textContent = text; };
  const notifyImage = (text) => { if (imageMessage) imageMessage.textContent = text; };
  const slugify = (text) => text.normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '').toLowerCase().trim()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 178);
  const publicUrl = () => root.dataset.blogPublicUrl.replace('__SLUG__', encodeURIComponent(slug.value.trim()));
  const updateUrl = () => {
    const valid = /^[a-z0-9]+(?:[-_][a-z0-9]+)*$/.test(slug.value.trim());
    if (urlLabel) urlLabel.textContent = valid ? publicUrl() : 'Enter a valid slug to generate the public URL.';
    if (copyButton) copyButton.disabled = !valid;
  };
  const updateButton = () => {
    if (save) save.textContent = status?.value === 'published' ? 'Publish article' : 'Save draft';
  };
  title?.addEventListener('input', () => {
    if (!manualSlug) {
      slug.value = slugify(title.value);
      updateUrl();
    }
  });
  slug?.addEventListener('input', () => {
    manualSlug = slug.value.trim().length > 0;
    updateUrl();
  });
  status?.addEventListener('change', updateButton);
  updateButton();
  updateUrl();

  copyButton?.addEventListener('click', async () => {
    if (copyButton.disabled) return;
    const url = publicUrl();
    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(url);
      } else {
        const field = document.createElement('textarea');
        field.value = url;
        field.style.position = 'fixed';
        field.style.opacity = '0';
        document.body.append(field);
        field.select();
        const copied = document.execCommand('copy');
        field.remove();
        if (!copied) throw new Error('Copy was not supported.');
      }
      announce('Public URL copied.');
    } catch (_) {
      announce('Copy unavailable. Select and copy the public URL displayed above.');
    }
  });

  const wordCount = root.querySelector('[data-blog-word-count]');
  const updateCount = () => {
    if (!wordCount) return;
    const count = body.value.trim() ? body.value.trim().split(/\s+/u).length : 0;
    wordCount.textContent = count.toLocaleString() + (count === 1 ? ' word' : ' words');
  };
  body?.addEventListener('input', updateCount);
  updateCount();

  const replaceSelection = (prefix, suffix = '', placeholder = 'text') => {
    const from = body.selectionStart;
    const to = body.selectionEnd;
    const chosen = body.value.slice(from, to) || placeholder;
    body.setRangeText(prefix + chosen + suffix, from, to, 'select');
    body.focus();
    body.setSelectionRange(from + prefix.length, from + prefix.length + chosen.length);
    body.dispatchEvent(new Event('input', { bubbles: true }));
  };
  const prependLines = (prefix) => {
    const from = body.selectionStart;
    const to = body.selectionEnd;
    const selected = body.value.slice(from, to) || 'List item';
    const changed = selected.split('\n').map((line, i) =>
      prefix === 'ordered' ? (i + 1) + '. ' + line : prefix + line).join('\n');
    body.setRangeText(changed, from, to, 'select');
    body.focus();
    body.dispatchEvent(new Event('input', { bubbles: true }));
  };
  const addLink = () => {
    const link = window.prompt('Enter an HTTPS or HTTP link:');
    if (link === null) return;
    try {
      const url = new URL(link);
      if (!['https:', 'http:'].includes(url.protocol)) throw new Error('Invalid protocol');
      replaceSelection('[', '](' + url.toString() + ')', 'Link text');
    } catch (_) {
      announce('Link must start with https:// or http://');
    }
  };

  const format = (action) => {
    switch (action) {
      case 'bold': replaceSelection('**', '**', 'bold text'); break;
      case 'italic': replaceSelection('*', '*', 'italic text'); break;
      case 'strike': replaceSelection('~~', '~~', 'strikethrough text'); break;
      case 'code': replaceSelection('`', '`', 'code'); break;
      case 'quote': prependLines('> '); break;
      case 'unordered': prependLines('- '); break;
      case 'ordered': prependLines('ordered'); break;
      case 'rule': {
        const pos = body.selectionStart;
        body.setRangeText('\n\n---\n\n', pos, pos, 'end');
        body.focus(); body.dispatchEvent(new Event('input', { bubbles: true }));
        break;
      }
      case 'link': addLink(); break;
      case 'image': showMedia('inline'); break;
      default: break;
    }
  };
  root.querySelectorAll('[data-blog-format]').forEach(button =>
    button.addEventListener('click', () => format(button.dataset.blogFormat)));
  root.querySelector('[data-blog-style]')?.addEventListener('change', (event) => {
    const value = event.target.value;
    if (value === 'h2') prependLines('## ');
    if (value === 'h3') prependLines('### ');
    event.target.value = '';
  });
  body?.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'b') {
      event.preventDefault(); format('bold');
    }
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'i') {
      event.preventDefault(); format('italic');
    }
  });

  const togglePreview = async () => {
    if (previewVisible) {
      previewVisible = false;
      preview.hidden = true;
      body.hidden = false;
      previewButton.setAttribute('aria-pressed', 'false');
      previewButton.textContent = 'Preview';
      body.focus();
      return;
    }
    if (!body.value.trim()) {
      announce('Write article content before opening the preview.');
      body.focus();
      return;
    }
    previewButton.disabled = true;
    preview.textContent = 'Preparing preview…';
    try {
      const response = await fetch(root.dataset.blogPreviewUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ body: body.value }),
      });
      if (!response.ok) throw new Error('Unable to render the article preview.');
      const data = await response.json();
      preview.innerHTML = data.html;
      previewVisible = true;
      body.hidden = true;
      preview.hidden = false;
      previewButton.setAttribute('aria-pressed', 'true');
      previewButton.textContent = 'Edit article';
    } catch (error) {
      announce(error.message);
    } finally {
      previewButton.disabled = false;
    }
  };
  previewButton?.addEventListener('click', togglePreview);

  const categoryPanel = root.querySelector('[data-blog-category-new]');
  root.querySelector('[data-blog-new-category]')?.addEventListener('click', () => {
    categoryPanel.hidden = !categoryPanel.hidden;
    if (!categoryPanel.hidden) root.querySelector('[data-blog-category-name]')?.focus();
  });
  const addCategory = async () => {
    const input = root.querySelector('[data-blog-category-name]');
    const name = input.value.trim();
    if (!name) { announce('Enter a category name.'); input.focus(); return; }
    const button = root.querySelector('[data-blog-category-save]');
    button.disabled = true;
    try {
      const response = await fetch(root.dataset.blogCategoryUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ name }),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || 'Could not create category.');
      category.add(new Option(result.name, String(result.id), true, true));
      category.value = String(result.id);
      categoryPanel.hidden = true;
      input.value = '';
      announce('Category created and selected.');
    } catch (error) { announce(error.message); }
    finally { button.disabled = false; }
  };
  root.querySelector('[data-blog-category-save]')?.addEventListener('click', addCategory);
  root.querySelector('[data-blog-category-name]')?.addEventListener('keydown', event => {
    if (event.key === 'Enter') { event.preventDefault(); addCategory(); }
  });

  const setFeatured = ({ id, url, name, alt }) => {
    chosenImage.value = String(id);
    chosenPreview.src = url;
    chosenPreview.alt = alt || name;
    chosenPreview.hidden = false;
    chosenEmpty.hidden = true;
    chosenName.textContent = name;
    announce('Featured image selected.');
  };
  const insertInline = ({ id, alt, name }) => {
    if (previewVisible) togglePreview();
    const safeAlt = (alt || name || 'Article image').replace(/[\[\]\r\n]/g, ' ').slice(0, 150);
    const at = body.selectionStart;
    body.setRangeText('\n\n![' + safeAlt + '](edumia-media:' + id + ')\n\n', at, at, 'end');
    body.focus();
    body.dispatchEvent(new Event('input', { bubbles: true }));
    announce('Library image inserted into the article.');
  };
  root.querySelector('[data-blog-clear-image]')?.addEventListener('click', () => {
    chosenImage.value = '';
    chosenPreview.removeAttribute('src');
    chosenPreview.hidden = true;
    chosenEmpty.hidden = false;
    chosenName.textContent = '';
    announce('Featured image removed. Save to apply.');
  });

  let lastFocus;
  const choiceLabel = () => pickerMode === 'featured'
    ? 'Use as featured image' : 'Insert into article';
  const refreshImageChoices = () => {
    imageGrid?.querySelectorAll('[data-blog-image-cta]').forEach(element => {
      element.textContent = choiceLabel();
    });
  };
  const showMedia = (mode = 'featured') => {
    if (!dialog) return;
    pickerMode = mode === 'inline' ? 'inline' : 'featured';
    dialog.dataset.blogMediaMode = pickerMode;
    root.querySelector('[data-blog-media-heading]').textContent =
      pickerMode === 'featured' ? 'Choose featured image' : 'Insert article image';
    root.querySelector('[data-blog-media-subtitle]').textContent =
      pickerMode === 'featured'
        ? 'Select an image to display in the Featured Image panel.'
        : 'Select an image to insert at the cursor in your article.';
    refreshImageChoices();
    lastFocus = document.activeElement;
    dialog.showModal();
    imageSearch.focus();
  };
  root.querySelector('[data-blog-open-images]')?.addEventListener('click', () => showMedia('featured'));
  root.querySelector('[data-blog-close-images]')?.addEventListener('click', () => dialog.close());
  dialog?.addEventListener('close', () => lastFocus?.focus?.());
  const applyImage = (image) => {
    if (pickerMode === 'featured') setFeatured(image);
    else insertInline(image);
    dialog.close();
  };
  imageGrid?.addEventListener('click', event => {
    const button = event.target.closest('[data-blog-image-select]');
    if (!button || !imageGrid.contains(button)) return;
    applyImage({ id: button.dataset.id, url: button.dataset.url,
      name: button.dataset.name, alt: button.dataset.alt });
  });

  const addMediaCard = (image) => {
    const card = document.createElement('div');
    card.className = 'blog-media-item';
    card.dataset.blogImageItem = '';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'blog-media-select';
    button.dataset.blogImageSelect = '';
    button.dataset.id = String(image.id);
    button.dataset.url = image.url;
    button.dataset.name = image.name;
    button.dataset.alt = image.alt || image.name;
    const img = document.createElement('img');
    img.src = image.url;
    img.alt = '';
    img.loading = 'lazy';
    const heading = document.createElement('strong');
    heading.textContent = image.name;
    const cta = document.createElement('span');
    cta.className = 'blog-media-cta';
    cta.dataset.blogImageCta = '';
    cta.textContent = choiceLabel();
    button.append(img, heading, cta);
    card.append(button);
    return card;
  };
  const searchImages = async (page = 1, append = false) => {
    const thisRequest = ++searchVersion;
    const query = imageSearch.value.trim();
    imageMore.disabled = true;
    notifyImage('Loading images…');
    try {
      const url = new URL(root.dataset.blogMediaSearchUrl, window.location.href);
      url.searchParams.set('q', query);
      url.searchParams.set('page', String(page));
      const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
      if (!response.ok) throw new Error('Unable to search Media Library.');
      const data = await response.json();
      if (thisRequest !== searchVersion) return;
      if (!append) imageGrid.replaceChildren();
      data.images.forEach(image => imageGrid.append(addMediaCard(image)));
      nextPage = data.next_page;
      imageMore.hidden = nextPage === null;
      notifyImage(data.images.length ? 'Select the image you want to use.' : 'No matching images found.');
    } catch (error) {
      if (thisRequest === searchVersion) notifyImage(error.message);
    } finally { if (thisRequest === searchVersion) imageMore.disabled = false; }
  };
  imageSearch?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => searchImages(), 250);
  });
  imageMore?.addEventListener('click', () => {
    if (nextPage) searchImages(nextPage, true);
  });
  root.querySelector('[data-blog-media-file]')?.addEventListener('change', async event => {
    const input = event.target;
    const file = input.files?.[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 10 * 1024 * 1024) {
      notifyImage('Choose a JPEG, PNG or WebP image smaller than 10 MB.');
      input.value = ''; return;
    }
    const data = new FormData();
    data.append('file', file);
    data.append('alt_text', file.name.replace(/\.[^.]+$/, '').slice(0, 255));
    notifyImage('Uploading image…');
    try {
      const response = await fetch(root.dataset.blogMediaUploadUrl, {
        method: 'POST', body: data,
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        credentials: 'same-origin',
      });
      const image = await response.json();
      if (!response.ok) throw new Error(image.message || 'Image upload failed.');
      imageGrid.prepend(addMediaCard(image));
      applyImage(image);
    } catch (error) { notifyImage(error.message); }
    finally { input.value = ''; }
  });
}
