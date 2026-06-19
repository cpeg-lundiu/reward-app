<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-extrabold text-slate-800">Rewards 🎁</h1>
    <div class="bg-amber-50 text-amber-500 font-extrabold text-sm px-4 py-2 rounded-full">⭐ <?= (int) $stars ?> stars</div>
</div>

<!-- Catalog -->
<?php if (!$rewards): ?>
    <div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-8 text-center mb-5">
        <div class="text-5xl mb-2">🎁</div>
        <p class="text-slate-500 text-sm">No rewards available yet. Ask your parent to add some!</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <?php foreach ($rewards as $r): ?>
            <?php $canAfford = $stars >= (int) $r['star_cost']; ?>
            <div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-5 flex flex-col">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 flex items-center justify-center text-2xl"><?= e($r['emoji'] ?: '🎁') ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-800 truncate"><?= e($r['title']) ?></p>
                        <?php if ($r['description']): ?><p class="text-xs text-slate-400 truncate"><?= e($r['description']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-auto pt-2">
                    <span class="font-extrabold text-amber-500">⭐ <?= (int) $r['star_cost'] ?></span>
                    <?php if ($canAfford): ?>
                        <form method="post" action="/child/rewards/claim" class="m-0">
                            <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                            <input type="hidden" name="reward_id" value="<?= (int) $r['id'] ?>">
                            <button class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold px-4 py-2 rounded-xl">Claim</button>
                        </form>
                    <?php else: ?>
                        <span class="text-xs font-bold text-slate-300">Need <?= (int) $r['star_cost'] - $stars ?> more</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- My claims -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <h2 class="font-bold text-slate-700 mb-3">📜 My reward requests</h2>
    <?php if (!$claims): ?>
        <p class="text-slate-500 text-sm">You haven't claimed any rewards yet.</p>
    <?php else: ?>
        <div class="divide-y divide-brand-50">
            <?php foreach ($claims as $c): ?>
                <?php
                $badge = [
                    'pending' => '<span class="text-xs font-bold text-amber-600">⏳ waiting</span>',
                    'completed' => '<span class="text-xs font-bold text-green-600">✓ done</span>',
                    'rejected' => '<span class="text-xs font-bold text-red-500">✕ refunded</span>',
                ][$c['status']] ?? '';
                ?>
                <div class="flex items-center gap-3 py-2.5">
                    <div class="w-9 h-9 rounded-full bg-brand-50 flex items-center justify-center text-lg"><?= e($c['emoji'] ?: '🎁') ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-700 text-sm truncate"><?= e($c['title']) ?> <?= $badge ?></p>
                        <p class="text-xs text-slate-400">⭐ <?= (int) $c['star_cost'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
