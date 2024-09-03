
    const menuItems = document.querySelectorAll('.menu a');
    const iframe = document.getElementById('content-iframe');
    const menu = document.querySelector('.menu');
    const menuToggle = document.querySelector('.menu-toggle');
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');

    function filterBooks() {
      const searchTerm = searchInput.value.toLowerCase().trim();
      const bookItems = document.querySelectorAll('.book-item');
      bookItems.forEach(item => {
        const bookTitle = (item.getAttribute('data-title') || '').toLowerCase();
        const bookAuthor = (item.getAttribute('data-author') || '').toLowerCase();
        if (bookTitle.includes(searchTerm) || bookAuthor.includes(searchTerm)) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    }

    function sortBooks() {
      const sortBy = sortSelect.value;
      const bookItems = document.querySelectorAll('.book-item');
      const bookList = Array.from(bookItems);
      bookList.sort((a, b) => {
        const titleA = a.getAttribute(`data-${sortBy}`) || '';
        const titleB = b.getAttribute(`data-${sortBy}`) || '';
        return titleA.localeCompare(titleB);
      });
      const bookContainer = document.querySelector('.book-container');
      bookContainer.innerHTML = '';
      bookList.forEach(item => {
        bookContainer.appendChild(item);
      });
    }

    menuItems.forEach(item => {
      item.addEventListener('click', function(e) {
        e.preventDefault();
        const file = this.getAttribute('data-file');
        if (file) {
          const fileExtension = file.split('.').pop().toLowerCase();
          if (fileExtension === 'pdf') {
            iframe.src = '../physics' + file;
          } else {
            iframe.src = '../physics' + file;
          }
        }
        const href = this.getAttribute('href');
        if (href) {
          window.location.href = href;
        }
      });
    });

    menuToggle.addEventListener('click', function() {
      menu.classList.toggle('retracted');
      if (menu.classList.contains('retracted')) {
        menuToggle.innerHTML = '&gt;';
      } else {
        menuToggle.innerHTML = '&lt;';
      }
    });
    searchInput.addEventListener('input', filterBooks);
    sortSelect.addEventListener('change', sortBooks);
