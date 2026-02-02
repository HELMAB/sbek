document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('load-more-posts');
    if (!loadMoreBtn) return;

    let page = 2; // Start from page 2
    let loading = false;

    loadMoreBtn.addEventListener('click', function() {
        if (loading) return;
        loading = true;
        const originalText = loadMoreBtn.innerText;
        loadMoreBtn.innerText = 'Loading...';

        const data = new FormData();
        data.append('action', 'sabay_load_more');
        data.append('page', page);
        data.append('nonce', sabay_ajax.nonce);

        fetch(sabay_ajax.ajax_url, {
            method: 'POST',
            body: data
        })
        .then(response => response.text())
        .then(html => {
            if (html.trim() !== '') {
                const container = document.getElementById('news-grid-container');
                container.insertAdjacentHTML('beforeend', html);
                page++;
                loading = false;
                loadMoreBtn.innerText = originalText;
            } else {
                loadMoreBtn.innerText = 'No more posts';
                loadMoreBtn.disabled = true;
                loadMoreBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        })
        .catch(err => {
            console.error('Error loading posts:', err);
            loading = false;
            loadMoreBtn.innerText = originalText;
        });
    });
});
