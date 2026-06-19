<?php $fmt = static fn (int $c) => \App\Support\Money::format($c, $currency); ?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-800">My Family</h1>
        <p class="text-slate-500 text-sm">Hi <?= e($auth_user['display_name']) ?>! 👋</p>
    </div>
    <a href="/parent/children/add" class="bg-brand-500 hover:bg-brand-600 text-white font-bold text-sm px-4 py-2.5 rounded-full shadow-md shadow-brand-200">
        + Add child
    </a>
</div>

<?php
$alerts = [];
if ($pending['withdrawals'] > 0) {
    $alerts[] = ['href' => '/parent/money', 'icon' => '💸', 'text' => $pending['withdrawals'] . ' withdrawal request' . ($pending['withdrawals'] > 1 ? 's' : '')];
}
if ($pending['tasks'] > 0) {
    $alerts[] = ['href' => '/parent/tasks/approvals', 'icon' => '⭐', 'text' => $pending['tasks'] . ' task' . ($pending['tasks'] > 1 ? 's' : '') . ' to approve'];
}
if ($pending['claims'] > 0) {
    $alerts[] = ['href' => '/parent/rewards/claims', 'icon' => '🎁', 'text' => $pending['claims'] . ' reward request' . ($pending['claims'] > 1 ? 's' : '')];
}
?>
<?php if ($alerts): ?>
    <div class="space-y-2 mb-5">
        <?php foreach ($alerts as $a): ?>
            <a href="<?= e($a['href']) ?>" class="flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl px-4 py-3 font-semibold text-sm">
                <span class="text-xl"><?= $a['icon'] ?></span>
                <span class="flex-1"><?= e($a['text']) ?></span>
                <span aria-hidden="true">›</span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!$children): ?>
    <div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-8 text-center">
        <div class="text-5xl mb-3">🧸</div>
        <h2 class="font-bold text-slate-700 mb-1">No children yet</h2>
        <p class="text-slate-500 text-sm mb-4">Add your first child to start rewarding them!</p>
        <a href="/parent/children/add" class="inline-block bg-brand-500 hover:bg-brand-600 text-white font-bold px-5 py-3 rounded-2xl">Add a child</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($children as $child): ?>
            <div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-brand-100 flex items-center justify-center text-2xl"><?= e($child['avatar_emoji'] ?: '🐷') ?></div>
                    <div>
                        <p class="font-bold text-slate-800"><?= e($child['display_name']) ?></p>
                        <p class="text-xs text-slate-400">@<?= e($child['username']) ?></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-brand-50 rounded-2xl px-3 py-3 text-center">
                        <p class="text-xs text-slate-400 font-semibold">Balance</p>
                        <p class="text-lg font-extrabold text-brand-600"><?= e($fmt((int) $child['balance_cents'])) ?></p>
                    </div>
                    <div class="bg-amber-50 rounded-2xl px-3 py-3 text-center">
                        <p class="text-xs text-slate-400 font-semibold">Stars</p>
                        <p class="text-lg font-extrabold text-amber-500">⭐ <?= (int) $child['stars'] ?></p>
                    </div>
                </div>
                <a href="/parent/children/<?= (int) $child['id'] ?>/transactions"
                   class="mt-3 block text-center text-sm font-bold text-brand-600 hover:text-brand-700">
                    View transactions ›
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
