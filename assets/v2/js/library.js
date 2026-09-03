(() => {
  const root = document.querySelector('[data-library]');
  const dataNode = document.getElementById('library-data');
  if (!root || !dataNode) return;

  const documents = JSON.parse(dataNode.textContent || '[]');
  const input = root.querySelector('[data-search]');
  const kindSelect = root.querySelector('[data-kind]');
  const languageSelect = root.querySelector('[data-language]');
  const competitionSelect = root.querySelector('[data-competition]');
  const yearSelect = root.querySelector('[data-year]');
  const paperFilters = root.querySelector('[data-paper-filters]');
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
  const paperDocuments = documents.filter((document) => document.kind === 'paper' && document.competition && document.year);
  const addOptions = (select, values) => values.forEach(([value, text]) => select?.insertAdjacentHTML('beforeend', `<option value="${escape(value)}">${escape(text)}</option>`));
  addOptions(competitionSelect, [...new Map(paperDocuments.map((document) => [document.competition, document.competitionLabel || document.competition])).entries()].sort((left, right) => left[1].localeCompare(right[1], 'vi')));
  addOptions(yearSelect, [...new Set(paperDocuments.map((document) => String(document.year)))].sort((left, right) => (right === 'Collection') - (left === 'Collection') || Number(right) - Number(left)).map((year) => [year, year]));
  if (competitionSelect && params.get('competition')) competitionSelect.value = params.get('competition');
  if (yearSelect && params.get('year')) yearSelect.value = params.get('year');
  const addedDate = (value) => value ? new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }).format(new Date(`${value}T00:00:00Z`)) : '';
  const fileIcon = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>';
  const arrow = '<svg class="arrow" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 17 17 7M7 7h10v10"/></svg>';

  const state = () => ({
    q: input.value.trim(),
    kind: lockedKind || kindSelect?.value || 'all',
    language: languageSelect?.value || 'all',
    competition: (lockedKind || kindSelect?.value) === 'paper' ? competitionSelect?.value || 'all' : 'all',
    year: (lockedKind || kindSelect?.value) === 'paper' ? yearSelect?.value || 'all' : 'all',
    sort: sortSelect?.value || 'title',
  });
  const syncUrl = ({ q, kind, language, competition, year, sort }) => {
    const url = new URL(location.href);
    [['q', q], ['kind', kind === 'all' ? '' : kind], ['language', language === 'all' ? '' : language], ['competition', competition === 'all' ? '' : competition], ['year', year === 'all' ? '' : year], ['sort', sort === 'title' ? '' : sort]].forEach(([key, value]) => value ? url.searchParams.set(key, value) : url.searchParams.delete(key));
    if (!lockedKind) url.searchParams.delete('orbit');
    history.replaceState(null, '', url);
  };
  const matches = (document, query) => {
    if (!query) return true;
    const text = normalize([document.title, ...(document.authors || []), document.description, document.competitionLabel, document.year].join(' '));
    return normalize(query).split(/\s+/).every((token) => text.includes(token));
  };
  const card = (document) => {
    const visual = document.cover
      ? `<span class="cardVisual hasCover"><img src="${escape(document.cover)}" alt="" loading="lazy" decoding="async" width="82" height="112"></span>`
      : `<span class="cardVisual">${fileIcon}</span>`;
    const authors = (document.authors || []).join(', ');
    const paperType = document.paperType ? (document.paperType === 'theoretical' ? 'Lý thuyết' : document.paperType === 'experimental' ? 'Thực nghiệm' : document.paperType) : '';
    const facts = [paperType, document.role || '', String(document.language).toUpperCase(), document.pages ? `${document.pages} trang` : '', document.addedAt ? `thêm ${addedDate(document.addedAt)}` : ''].filter(Boolean).join(' · ');
    return `<article class="card">${visual}<div><p class="meta"><span>${escape(labels[document.kind] || 'Tài liệu')}</span> ${escape(facts)}</p><h2><a href="/document/${encodeURIComponent(document.slug)}">${escape(document.title)}</a></h2>${authors ? `<p class="author">${escape(authors)}</p>` : ''}${document.description ? `<p class="description">${escape(document.description)}</p>` : ''}</div>${arrow}</article>`;
  };
  const render = () => {
    const current = state();
    syncUrl(current);
    const key = current.sort === 'author' ? (document) => document.authors?.[0] || document.title : (document) => document.title;
    const results = documents.filter((document) => (current.kind === 'all' || document.kind === current.kind) && (current.language === 'all' || document.language === current.language) && (current.competition === 'all' || document.competition === current.competition) && (current.year === 'all' || String(document.year) === current.year) && matches(document, current.q)).sort((a, b) => key(a).localeCompare(key(b), 'vi'));
    const page = results.slice(0, visible);
    if (current.kind === 'paper') {
      const groups = new Map();
      page.forEach((document) => { const label = `${document.competitionLabel || 'Khác'} · ${document.year || 'Collection'}`; groups.set(label, [...(groups.get(label) || []), document]); });
      grid.innerHTML = [...groups].map(([label, groupedDocuments]) => `<section class="paperGroup"><h2>${escape(label)}</h2>${groupedDocuments.map(card).join('')}</section>`).join('');
    } else grid.innerHTML = page.map(card).join('');
    count.textContent = String(results.length);
    if (total) total.textContent = String(results.length);
    more.hidden = results.length <= visible;
    empty.hidden = results.length !== 0;
    grid.hidden = results.length === 0;
    if (paperFilters) paperFilters.hidden = current.kind !== 'paper';
    clear.hidden = !(current.q || current.language !== 'all' || current.competition !== 'all' || current.year !== 'all' || (!lockedKind && current.kind !== 'all'));
  };
  const update = () => { visible = 30; render(); };
  input.addEventListener('input', update);
  kindSelect?.addEventListener('change', update);
  languageSelect?.addEventListener('change', update);
  competitionSelect?.addEventListener('change', update);
  yearSelect?.addEventListener('change', update);
  sortSelect?.addEventListener('change', update);
  more.addEventListener('click', () => { visible += 30; render(); });
  const resetAll = () => {
    input.value = '';
    if (kindSelect) kindSelect.value = lockedKind || 'all';
    languageSelect.value = 'all';
    if (competitionSelect) competitionSelect.value = 'all';
    if (yearSelect) yearSelect.value = 'all';
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
