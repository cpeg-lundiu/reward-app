<div class="flex items-center gap-3 mb-4">
    <a href="/parent" class="text-brand-600 font-bold">‹ Back</a>
    <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-full bg-brand-100 flex items-center justify-center text-xl"><?= e($child['avatar_emoji'] ?: '🐷') ?></div>
        <h1 class="text-xl font-extrabold text-slate-800"><?= e($child['display_name']) ?>'s history</h1>
    </div>
</div>

<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <h2 class="font-bold text-slate-700 mb-2">📜 All transactions</h2>

    <?php if (!$items): ?>
        <p class="text-slate-500 text-sm">No transactions yet.</p>
    <?php else: ?>
        <div id="tx-list"
             data-child="<?= (int) $child['id'] ?>"
             data-offset="<?= count($items) ?>"
             data-has-more="<?= $has_more ? '1' : '0' ?>">
            <?= $this->fetch('parent/_transaction-rows.php', ['items' => $items, 'currency' => $currency]) ?>
        </div>

        <div id="tx-sentinel" class="py-4 text-center text-sm text-slate-400 <?= $has_more ? '' : 'hidden' ?>">
            Loading more…
        </div>
        <p id="tx-end" class="py-3 text-center text-xs text-slate-300 <?= $has_more ? 'hidden' : '' ?>">— end of history —</p>
    <?php endif; ?>
</div>

<script>
(function () {
    var list = document.getElementById('tx-list');
    var sentinel = document.getElementById('tx-sentinel');
    var end = document.getElementById('tx-end');
    if (!list || !sentinel) return;

    var loading = false;

    function hasMore() { return list.getAttribute('data-has-more') === '1'; }

    function loadMore() {
        if (loading || !hasMore()) return;
        loading = true;
        var childId = list.getAttribute('data-child');
        var offset = parseInt(list.getAttribute('data-offset'), 10) || 0;

        fetch('/parent/children/' + childId + '/transactions/data?offset=' + offset, {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                list.insertAdjacentHTML('beforeend', data.html);
                list.setAttribute('data-offset', String(offset + data.count));
                list.setAttribute('data-has-more', data.hasMore ? '1' : '0');
                if (!data.hasMore) {
                    sentinel.classList.add('hidden');
                    if (end) end.classList.remove('hidden');
                }
                loading = false;
            })
            .catch(function () {
                sentinel.textContent = 'Could not load more. Tap to retry.';
                loading = false;
            });
    }

    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) { if (entry.isIntersecting) loadMore(); });
        }, { rootMargin: '120px' });
        io.observe(sentinel);
    }
    // Tap-to-load fallback / retry.
    sentinel.addEventListener('click', loadMore);
})();
</script>
