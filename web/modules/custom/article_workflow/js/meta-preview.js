/**
 * @file
 * Shows the auto meta description as a live placeholder while editing.
 */
((Drupal, once) => {
  const clean = (text) => {
    const div = document.createElement('div');
    div.innerHTML = text;
    return (div.textContent || '').replace(/\s+/g, ' ').trim();
  };

  const generate = (text, max) => {
    const value = clean(text);
    if (value.length <= max) {
      return value;
    }
    let cut = value.slice(0, max - 1);
    const space = cut.lastIndexOf(' ');
    if (space > Math.floor(max * 0.6)) {
      cut = cut.slice(0, space);
    }
    return `${cut.replace(/[\s,;:.-]+$/, '')}…`;
  };

  Drupal.behaviors.articleWorkflowMetaPreview = {
    attach(context) {
      once('aw-meta-preview', '[data-aw-meta]', context).forEach((meta) => {
        const teaser = document.querySelector('[data-aw-teaser]');
        if (!teaser) {
          return;
        }
        const max = parseInt(meta.dataset.awMax, 10) || 160;
        const fallback = meta.getAttribute('placeholder');
        const update = () => {
          const preview = generate(teaser.value, max);
          meta.setAttribute('placeholder', preview || fallback);
        };
        teaser.addEventListener('input', update);
        update();
      });
    },
  };
})(Drupal, once);
