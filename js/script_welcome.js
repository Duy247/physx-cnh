        function isMobileDevice() {
            return (typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1);
        }
        const images = document.querySelectorAll('#main-content img'); 
        let loadedImages = 0;

        images.forEach(img => {
            img.addEventListener('load', () => {
                loadedImages++;
                if (loadedImages === images.length) {
                    // All images loaded, hide the loading screen and show main content
                    document.getElementById('loading-screen').style.display = 'none';
                    document.getElementById('main-content').style.display = 'block';   
 
                }
            });
        });
        if (sessionStorage.getItem('imagesLoaded')) {
            // If images were loaded before, skip the loading screen
            document.getElementById('loading-screen').style.display = 'none';
            document.getElementById('main-content').style.display = 'block'; 
        } else {
            // Track image loading if it's the first visit
            const images = document.querySelectorAll('#main-content img'); 
            let loadedImages = 0;

            images.forEach(img => {
                img.addEventListener('load', () => {
                    loadedImages++;
                    if (loadedImages === images.length) {
                        // All images loaded, hide the loading screen, show main content, and set sessionStorage
                        document.getElementById('loading-screen').style.display = 'none';
                        document.getElementById('main-content').style.display = 'block'; 
                        sessionStorage.setItem('imagesLoaded', 'true'); 
                    }
                });
            });
        }

        var booksPreVpho = document.getElementById('booksPreVpho');
        var booksVn = document.getElementById('booksVn');
        var booksEn = document.getElementById('booksEn');
        var materialsPreVpho = document.getElementById('materialsPreVpho');
        var materialsOlympiad = document.getElementById('materialsOlympiad');
        var materialsVltt = document.getElementById('materialsVltt');
        var lessons = document.getElementById('lessons');
        
        if (isMobileDevice()) {         
            booksPreVpho.href = "/physics_books_pre_vpho_phone";
            booksVn.href = "/physics_books_vn_phone";
            booksEn.href = "/physics_books_en_phone";
            materialsPreVpho.href = "/physics_materials_pre_vpho_phone";
            materialsOlympiad.href = "/physics_materials_olympiad_phone";
            materialsVltt.href = "/physics_materials_vltt_phone";
            lessons.href = "/physics_lessons_phone";
        }
        function swapImages() {
            const images = document.querySelectorAll('.background img');
            const randomIndex = Math.floor(Math.random() * images.length);
            const adjacentIndices = getAdjacentIndices(randomIndex);
            const swapIndex = adjacentIndices[Math.floor(Math.random() * adjacentIndices.length)];

            if (swapIndex !== undefined) {
                const randomImage = images[randomIndex];
                const swapImage = images[swapIndex];

                const randomImageRect = randomImage.getBoundingClientRect();
                const swapImageRect = swapImage.getBoundingClientRect();

                const dx = swapImageRect.left - randomImageRect.left;
                const dy = swapImageRect.top - randomImageRect.top;

                randomImage.style.opacity = 0.9;
                swapImage.style.opacity = 0.9;
                randomImage.style.transform = `translate(${dx}px, ${dy}px)`;
                swapImage.style.transform = `translate(${-dx}px, ${-dy}px)`;

                setTimeout(() => {                    
                    const temp = randomImage.src;
                    randomImage.src = swapImage.src;
                    swapImage.src = temp;
                   
                    randomImage.style.transition = 'none';
                    swapImage.style.transition = 'none';

                    requestAnimationFrame(() => {                    
                        randomImage.style.transform = 'none';
                        swapImage.style.transform = 'none';
                        randomImage.style.opacity = 0.9; 
                        swapImage.style.opacity = 0.9;

                        requestAnimationFrame(() => {                        
                            randomImage.style.transition = 'transform 1s ease-in-out, opacity 1s ease-in-out';
                            swapImage.style.transition = 'transform 1s ease-in-out, opacity 1s ease-in-out';
                        });
                    });
                }, 1000); 
            }
        }

        function getAdjacentIndices(index) {
            const adjacentIndices = [];
            const numColumns = 3;

            if (index % numColumns !== 0) {
                adjacentIndices.push(index - 1);
            }
            if ((index + 1) % numColumns !== 0) {
                adjacentIndices.push(index + 1);
            }
            if (index >= numColumns) {
                adjacentIndices.push(index - numColumns);
            }
            if (index < (numColumns * (numColumns - 1))) {
                adjacentIndices.push(index + numColumns);
            }

            return adjacentIndices;
        }
        setInterval(swapImages, 3000);  
        function updateHitCount() {
            fetch('./visit_count/hit_counter.php') 
                .then(response => response.json())
                .then(data => {
                    const hitCount = data.count;
                    document.getElementById('hitCount').textContent = hitCount;
                })
                .catch(error => {
                    console.error('Error fetching hit count:', error);
                });
        }

        window.addEventListener('load', updateHitCount);
