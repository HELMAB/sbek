document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('load-more-posts');
    if (!loadMoreBtn) return;

    let page = 2; // Start from page 2
    let loading = false;

    loadMoreBtn.addEventListener('click', function() {
        if (loading) return;
        loading = true;
        const originalText = loadMoreBtn.innerText;
        loadMoreBtn.innerText = sbek_ajax.loading_text;

        const data = new FormData();
        data.append('action', 'sbek_load_more');
        data.append('page', page);
        data.append('nonce', sbek_ajax.nonce);
        data.append('lang', sbek_ajax.lang);

        fetch(sbek_ajax.ajax_url, {
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
                loadMoreBtn.innerText = sbek_ajax.no_more_text;
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
