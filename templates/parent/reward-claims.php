<div class="flex items-center gap-2 mb-4">
    <a href="/parent/rewards" class="text-brand-600 font-bold">‹ Back</a>
    <h1 class="text-2xl font-extrabold text-slate-800">Reward requests</h1>
</div>

<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <?php if (!$claims): ?>
        <div class="text-center py-6">
            <div class="text-4xl mb-2">🎉</div>
            <p class="text-slate-500 text-sm">No reward requests right now.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($claims as $c): ?>
                <div class="flex items-center gap-3 border border-brand-100 rounded-2xl p-3">
                    <div class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-xl"><?= e($c['emoji'] ?: '🎁') ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-800 truncate"><?= e($c['title']) ?></p>
                        <p class="text-xs text-slate-400"><?= e($c['avatar_emoji'] ?: '🐷') ?> <?= e($c['child_name']) ?> · ⭐ <?= (int) $c['star_cost'] ?></p>
                    </div>
                    <form method="post" action="/parent/rewards/claims/<?= (int) $c['id'] ?>/complete" class="m-0">
                        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                        <button class="bg-green-500 hover:bg-green-600 text-white text-sm font-bold px-3 py-2 rounded-xl">Complete</button>
                    </form>
                    <form method="post" action="/parent/rewards/claims/<?= (int) $c['id'] ?>/reject" class="m-0">
                        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                        <button class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-bold px-3 py-2 rounded-xl">Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
