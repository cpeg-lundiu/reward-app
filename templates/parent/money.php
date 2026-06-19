<?php $fmt = static fn (int $c) => \App\Support\Money::format($c, $currency); ?>

<h1 class="text-2xl font-extrabold text-slate-800 mb-4">Money</h1>

<!-- Add balance -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6 mb-5">
    <h2 class="font-bold text-slate-700 mb-3">💰 Add balance</h2>
    <?php if (!$children): ?>
        <p class="text-slate-500 text-sm">Add a child first to give them money.</p>
    <?php else: ?>
        <form method="post" action="/parent/money/add" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Child</label>
                <select name="child_id" required class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                    <?php foreach ($children as $child): ?>
                        <option value="<?= (int) $child['id'] ?>"><?= e($child['avatar_emoji']) ?> <?= e($child['display_name']) ?> — <?= e($fmt((int) $child['balance_cents'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Amount (<?= e(\App\Support\Money::symbol($currency)) ?>)</label>
                    <input type="number" name="amount" step="0.01" min="0" required
                           class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="0.00">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Note (optional)</label>
                    <input type="text" name="note" class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="e.g. Allowance">
                </div>
            </div>
            <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl shadow-md shadow-brand-200">Add money</button>
        </form>
    <?php endif; ?>
</div>

<!-- Convert stars to balance -->
<?php if ($children): ?>
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6 mb-5">
    <h2 class="font-bold text-slate-700 mb-1">⭐➡️💰 Convert stars to balance</h2>
    <p class="text-xs text-slate-400 mb-3">Choose how many stars to take and how much money to add — you decide the value.</p>
    <form method="post" action="/parent/money/convert-stars" class="space-y-3">
        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Child</label>
            <select name="child_id" required class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                <?php foreach ($children as $child): ?>
                    <option value="<?= (int) $child['id'] ?>"><?= e($child['avatar_emoji']) ?> <?= e($child['display_name']) ?> — ⭐ <?= (int) $child['stars'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-3">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Stars to convert</label>
                <input type="number" name="stars" min="1" step="1" required
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="e.g. 10">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Balance to add (<?= e(\App\Support\Money::symbol($currency)) ?>)</label>
                <input type="number" name="amount" step="0.01" min="0" required
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="0.00">
            </div>
        </div>
        <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl shadow-md shadow-brand-200">Convert stars</button>
    </form>
</div>
<?php endif; ?>

<!-- Pending withdrawals -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <h2 class="font-bold text-slate-700 mb-3">💸 Withdrawal requests</h2>
    <?php if (!$withdrawals): ?>
        <p class="text-slate-500 text-sm">No pending withdrawal requests.</p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($withdrawals as $w): ?>
                <div class="flex items-center gap-3 border border-brand-100 rounded-2xl p-3">
                    <div class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-xl"><?= e($w['avatar_emoji'] ?: '🐷') ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-800"><?= e($w['display_name']) ?> wants <?= e(\App\Support\Money::format(abs((int) $w['amount_cents']), $w['currency'])) ?></p>
                        <?php if ($w['note']): ?><p class="text-xs text-slate-400 truncate"><?= e($w['note']) ?></p><?php endif; ?>
                    </div>
                    <form method="post" action="/parent/withdrawals/<?= (int) $w['id'] ?>/approve" class="m-0">
                        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                        <button class="bg-green-500 hover:bg-green-600 text-white text-sm font-bold px-3 py-2 rounded-xl">Approve</button>
                    </form>
                    <form method="post" action="/parent/withdrawals/<?= (int) $w['id'] ?>/reject" class="m-0">
                        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                        <button class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-bold px-3 py-2 rounded-xl">Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
