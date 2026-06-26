<?php $symbol = \App\Support\Money::symbol($currency); ?>

<div class="text-center mb-2">
    <h1 class="text-2xl font-extrabold text-slate-800">Hi <?= e($auth_user['display_name']) ?>! <?= e($auth_user['avatar_emoji'] ?: '🐷') ?></h1>
</div>

<!-- Piggy bank centerpiece -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6 mb-5">
    <div class="text-center">
        <div class="select-none leading-none" style="font-size:130px;">🐷</div>
    </div>

    <div class="text-center mt-2">
        <p class="text-sm font-semibold text-slate-400">My balance</p>
        <p class="text-4xl font-extrabold text-brand-600"><?= e(\App\Support\Money::format((int) $balance_cents, $currency)) ?></p>
        <?php if ($pending_cents > 0): ?>
            <p class="text-xs text-amber-600 mt-1">⏳ <?= e(\App\Support\Money::format($pending_cents, $currency)) ?> pending withdrawal</p>
        <?php endif; ?>
    </div>
</div>

<!-- Stars + withdraw -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
    <div class="bg-amber-50 rounded-3xl shadow-lg shadow-amber-100 p-5 text-center flex flex-col items-center justify-center">
        <p class="text-sm font-semibold text-amber-500">My stars</p>
        <p class="text-3xl font-extrabold text-amber-500">⭐ <?= (int) $stars ?></p>
        <a href="/child/rewards" class="text-xs font-bold text-amber-600 mt-1">Spend them →</a>
    </div>

    <div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-5">
        <h2 class="font-bold text-slate-700 mb-2">🐷 Take out money</h2>
        <form method="post" action="/child/withdraw" class="space-y-2">
            <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
            <input type="number" name="amount" step="0.01" min="0" required placeholder="<?= e($symbol) ?>0.00"
                   class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300">
            <input type="text" name="note" placeholder="What for? (optional)"
                   class="w-full rounded-2xl border border-brand-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
            <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-2.5 rounded-2xl shadow-md shadow-brand-200">Ask to withdraw</button>
            <p class="text-xs text-slate-400 text-center">Available: <?= e(\App\Support\Money::format(max(0, (int) $available_cents), $currency)) ?></p>
        </form>
    </div>
</div>

<!-- Recent activity -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <h2 class="font-bold text-slate-700 mb-3">📜 Recent activity</h2>
    <?php if (!$transactions): ?>
        <p class="text-slate-500 text-sm">No money activity yet.</p>
    <?php else: ?>
        <div class="divide-y divide-brand-50">
            <?php foreach ($transactions as $tx): ?>
                <?php
                $amount = (int) $tx['amount_cents'];
                $labels = ['reward' => 'Reward', 'withdraw' => 'Withdrawal', 'adjustment' => 'Adjustment', 'conversion' => 'Currency change'];
                $statusBadge = [
                    'pending' => '<span class="text-xs font-bold text-amber-600">pending</span>',
                    'rejected' => '<span class="text-xs font-bold text-red-500">rejected</span>',
                    'approved' => '',
                ][$tx['status']] ?? '';
                ?>
                <div class="flex items-center gap-3 py-2.5">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-700 text-sm"><?= e($labels[$tx['type']] ?? $tx['type']) ?> <?= $statusBadge ?></p>
                        <?php if (!empty($tx['note'])): ?>
                            <p class="text-xs text-slate-400 truncate"><?= e($tx['note']) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-slate-400"><?= e(\App\Support\Tz::display($tx['created_at'], $tz)) ?></p>
                    </div>
                    <span class="font-bold text-sm whitespace-nowrap <?= $amount < 0 ? 'text-red-500' : ($tx['type'] === 'conversion' ? 'text-slate-400' : 'text-green-600') ?>">
                        <?php if ($tx['type'] === 'conversion'): ?>
                            💱
                        <?php else: ?>
                            <?= $amount < 0 ? '−' : '+' ?><?= e(\App\Support\Money::format(abs($amount), $tx['currency'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
