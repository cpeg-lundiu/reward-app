<?php
/** Renders a batch of transaction rows. Shared by the full page and the
 *  lazy-load JSON endpoint. Receives: $items, $currency, $tz (viewer timezone). */
$labels = ['reward' => 'Reward', 'withdraw' => 'Withdrawal', 'adjustment' => 'Adjustment', 'conversion' => 'Currency change'];
$badges = [
    'pending' => '<span class="text-xs font-bold text-amber-600">pending</span>',
    'rejected' => '<span class="text-xs font-bold text-red-500">rejected</span>',
    'approved' => '',
];
foreach ($items as $tx):
    $amount = (int) $tx['amount_cents'];
    ?>
    <div class="flex items-center gap-3 py-2.5 border-b border-brand-50 last:border-0">
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-700 text-sm"><?= e($labels[$tx['type']] ?? $tx['type']) ?> <?= $badges[$tx['status']] ?? '' ?></p>
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
