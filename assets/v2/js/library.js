(() => {
  const root = document.querySelector('[data-library]');
  const dataNode = document.getElementById('library-data');
  if (!root || !dataNode) return;

  const documents = JSON.parse(dataNode.textContent || '[]');
  const input = root.querySelector('[data-search]');
  const kindSelect = root.querySelector('[data-kind]');
  const languageSelect = root.querySelector('[data-language]');
  const sortSelect = root.querySelector('[data-sort]');
  const grid = root.querySelector('[data-results]');
  const count = root.querySelector('[data-result-count]');
  const total = document.querySelector('[data-library-total]');
  const clear = root.querySelector('[data-clear]');
  const more = root.querySelector('[data-more]');
  const empty = root.querySelector('[data-empty]');
  const reset = root.querySelector('[data-reset]');
  const lockedKind = root.dataset.orbit === '1' ? root.dataset.initialKind : '';
  const labels = { book: 'Sách', material: 'Chuyên đề', paper: 'Đề thi', magazine: 'Tạp chí' };
  let visible = 30;

  const params = new URLSearchParams(location.search);
  input.value = params.get('q') || input.value || '';
  if (kindSelect && params.get('kind') && [...kindSelect.options].some((option) => option.value === params.get('kind'))) kindSelect.value = params.get('kind');
  if (languageSelect && params.get('language')) languageSelect.value = params.get('language');
  if (sortSelect && params.get('sort')) sortSelect.value = params.get('sort');

  const normalize = (value) => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('vi');
  const escape = (value) => String(value || '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
  const addedDate = (value) => value ? new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${value}T00:00:00Z`)) : '';
  const fileIcon = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>';
  const arrow = '<svg class="arrow" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 17 17 7M7 7h10v10"/></svg>';

  const state = () => ({
    q: input.value.trim(),
    kind: lockedKind || kindSelect?.value || 'all',
    language: languageSelect?.value || 'all',
    sort: sortSelect?.value || 'title',
  });
  const syncUrl = ({ q, kind, language, sort }) => {
    const url = new URL(location.href);
    [['q', q], ['kind', kind === 'all' ? '' : kind], ['language', language === 'all' ? '' : language], ['sort', sort === 'title' ? '' : sort]].forEach(([key, value]) => value ? url.searchParams.set(key, value) : url.searchParams.delete(key));
    if (!lockedKind) url.searchParams.delete('orbit');
    history.replaceState(null, '', url);
  };
  const matches = (document, query) => {
    if (!query) return true;
    const text = normalize([document.title, ...(document.authors || []), document.description].join(' '));
    return normalize(query).split(/\s+/).every((token) => text.includes(token));
  };
  const card = (document) => {
    const visual = document.cover
      ? `<span class="cardVisual hasCover"><img src="${escape(document.cover)}" alt="" loading="lazy" decoding="async" width="82" height="112"></span>`
      : `<span class="cardVisual">${fileIcon}</span>`;
    const authors = (document.authors || []).join(', ');
    const facts = [String(document.language).toUpperCase(), document.pages ? `${document.pages} trang` : '', document.addedAt ? `thêm ${addedDate(document.addedAt)}` : ''].filter(Boolean).join(' · ');
    return `<article class="card">${visual}<div><p class="meta"><span>${escape(labels[document.kind] || 'Tài liệu')}</span> ${escape(facts)}</p><h2><a href="/document/${encodeURIComponent(document.slug)}">${escape(document.title)}</a></h2>${authors ? `<p class="author">${escape(authors)}</p>` : ''}${document.description ? `<p class="description">${escape(document.description)}</p>` : ''}</div>${arrow}</article>`;
  };
  const render = () => {
    const current = state();
    syncUrl(current);
    const key = current.sort === 'author' ? (document) => document.authors?.[0] || document.title : (document) => document.title;
    const results = documents.filter((document) => (current.kind === 'all' || document.kind === current.kind) && (current.language === 'all' || document.language === current.language) && matches(document, current.q)).sort((a, b) => key(a).localeCompare(key(b), 'vi'));
    grid.innerHTML = results.slice(0, visible).map(card).join('');
    count.textContent = String(results.length);
    if (total) total.textContent = String(results.length);
    more.hidden = results.length <= visible;
    empty.hidden = results.length !== 0;
    grid.hidden = results.length === 0;
    clear.hidden = !(current.q || current.language !== 'all' || (!lockedKind && current.kind !== 'all'));
  };
  const update = () => { visible = 30; render(); };
  input.addEventListener('input', update);
  kindSelect?.addEventListener('change', update);
  languageSelect?.addEventListener('change', update);
  sortSelect?.addEventListener('change', update);
  more.addEventListener('click', () => { visible += 30; render(); });
  const resetAll = () => {
    input.value = '';
    if (kindSelect) kindSelect.value = lockedKind || 'all';
    languageSelect.value = 'all';
    sortSelect.value = 'title';
    update();
  };
  clear.addEventListener('click', resetAll);
  reset.addEventListener('click', resetAll);
  document.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); input.focus(); }
  });
  addEventListener('pageshow', render);
  render();
})();
