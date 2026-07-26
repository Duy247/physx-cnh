(function () {
    'use strict';

    const iframe = document.getElementById('content-iframe');
    const menu = document.querySelector('.menu');
    const menuToggle = document.querySelector('.menu-toggle');
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');
    const desktopViewport = window.matchMedia('(min-width: 769px)');

    function filterBooks() {
        const searchTerm = searchInput.value.toLocaleLowerCase('vi').trim();
        document.querySelectorAll('.book-item').forEach(function (item) {
            const title = (item.dataset.title || '').toLocaleLowerCase('vi');
            const author = (item.dataset.author || '').toLocaleLowerCase('vi');
            item.hidden = !(title.includes(searchTerm) || author.includes(searchTerm));
        });
    }

    function sortBooks() {
        const sortBy = sortSelect.value;
        const container = document.querySelector('.book-container');
        const items = Array.from(container.querySelectorAll('.book-item'));

        items.sort(function (a, b) {
            return (a.dataset[sortBy] || '').localeCompare(
                b.dataset[sortBy] || '',
                'vi',
                {sensitivity: 'base'}
            );
        });
        items.forEach(function (item) {
            container.appendChild(item);
        });
    }

    document.querySelectorAll('.open-resource').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!desktopViewport.matches) return;
            event.preventDefault();
            iframe.src = link.href;
        });
    });

    menuToggle.addEventListener('click', function () {
        const retracted = menu.classList.toggle('retracted');
        menuToggle.innerHTML = retracted ? '&gt;' : '&lt;';
        menuToggle.setAttribute('aria-expanded', String(!retracted));
        menuToggle.setAttribute('aria-label', retracted ? 'Mở danh mục' : 'Thu gọn danh mục');
    });

    searchInput.addEventListener('input', filterBooks);
    sortSelect.addEventListener('change', sortBooks);
})();
