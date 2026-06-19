<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-extrabold text-slate-800">Rewards</h1>
    <a href="/parent/rewards/claims" class="bg-amber-100 text-amber-700 font-bold text-sm px-4 py-2.5 rounded-full">🎁 Requests</a>
</div>

<!-- Add reward -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6 mb-5">
    <h2 class="font-bold text-slate-700 mb-3">➕ New reward</h2>
    <form method="post" action="/parent/rewards/add" class="space-y-3">
        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
        <div class="flex gap-3">
            <div class="w-20">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Emoji</label>
                <input type="text" name="emoji" value="🎁" maxlength="4" class="w-full text-center rounded-2xl border border-brand-200 px-2 py-3 text-xl focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Title</label>
                <input type="text" name="title" required class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="e.g. Ice cream trip">
            </div>
            <div class="w-24">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Stars</label>
                <input type="number" name="star_cost" min="1" value="10" required class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Description (optional)</label>
            <input type="text" name="description" class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="Any details…">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Available to</label>
            <select name="child_id" class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                <option value="">All children</option>
                <?php foreach ($children as $child): ?>
                    <option value="<?= (int) $child['id'] ?>"><?= e($child['avatar_emoji']) ?> <?= e($child['display_name']) ?> only</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl shadow-md shadow-brand-200">Add reward</button>
    </form>
</div>

<!-- Reward list -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <h2 class="font-bold text-slate-700 mb-3">🎁 Catalog</h2>
    <?php if (!$rewards): ?>
        <p class="text-slate-500 text-sm">No rewards yet.</p>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($rewards as $r): ?>
                <div class="flex items-center gap-3 border border-brand-100 rounded-2xl p-3 <?= (int) $r['active'] ? '' : 'opacity-50' ?>">
                    <div class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-xl"><?= e($r['emoji'] ?: '🎁') ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-800 truncate"><?= e($r['title']) ?></p>
                        <p class="text-xs text-slate-400"><?= $r['child_name'] ? e($r['child_name']) . ' only' : 'All children' ?></p>
                    </div>
                    <span class="text-amber-500 font-bold text-sm whitespace-nowrap">⭐ <?= (int) $r['star_cost'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
