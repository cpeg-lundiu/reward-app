<?php
$freqLabels = ['once' => 'One-time', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
$weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>

<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-extrabold text-slate-800">Tasks</h1>
    <a href="/parent/tasks/approvals" class="bg-amber-100 text-amber-700 font-bold text-sm px-4 py-2.5 rounded-full">⭐ Approvals</a>
</div>

<!-- Add task -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6 mb-5">
    <h2 class="font-bold text-slate-700 mb-3">➕ New task</h2>
    <?php if (!$children): ?>
        <p class="text-slate-500 text-sm">Add a child first to assign tasks.</p>
    <?php else: ?>
        <form method="post" action="/parent/tasks/add" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Child</label>
                    <select name="child_id" required class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                        <?php foreach ($children as $child): ?>
                            <option value="<?= (int) $child['id'] ?>"><?= e($child['avatar_emoji']) ?> <?= e($child['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-24">
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Stars</label>
                    <input type="number" name="stars" min="1" value="1" required class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Title</label>
                <input type="text" name="title" required class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="e.g. Make your bed">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Description (optional)</label>
                <input type="text" name="description" class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="Any details…">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Repeats</label>
                <select name="frequency" id="freq" class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                    <?php foreach ($freqLabels as $val => $label): ?>
                        <option value="<?= $val ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div data-freq="once" class="freq-field">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Date</label>
                <input type="date" name="specific_date" value="<?= e($today) ?>" class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>
            <div data-freq="weekly" class="freq-field hidden">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Day of week</label>
                <select name="weekday" class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                    <?php foreach ($weekdays as $i => $name): ?><option value="<?= $i ?>"><?= $name ?></option><?php endforeach; ?>
                </select>
            </div>
            <div data-freq="monthly" class="freq-field hidden">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Day of month</label>
                <input type="number" name="day_of_month" min="1" max="31" value="1" class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>

            <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl shadow-md shadow-brand-200">Add task</button>
        </form>
    <?php endif; ?>
</div>

<!-- Task list -->
<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <h2 class="font-bold text-slate-700 mb-3">📋 All tasks</h2>
    <?php if (!$tasks): ?>
        <p class="text-slate-500 text-sm">No tasks yet.</p>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($tasks as $t): ?>
                <div class="flex items-center gap-3 border border-brand-100 rounded-2xl p-3 <?= (int) $t['active'] ? '' : 'opacity-50' ?>">
                    <div class="w-9 h-9 rounded-full bg-brand-100 flex items-center justify-center"><?= e($t['avatar_emoji'] ?: '🐷') ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-800 truncate"><?= e($t['title']) ?></p>
                        <p class="text-xs text-slate-400"><?= e($t['child_name']) ?> · <?= e($freqLabels[$t['frequency']] ?? $t['frequency']) ?></p>
                    </div>
                    <span class="text-amber-500 font-bold text-sm whitespace-nowrap">⭐ <?= (int) $t['stars'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var freq = document.getElementById('freq');
    if (!freq) return;
    function sync() {
        document.querySelectorAll('.freq-field').forEach(function (el) {
            el.classList.toggle('hidden', el.getAttribute('data-freq') !== freq.value);
        });
    }
    freq.addEventListener('change', sync);
    sync();
})();
</script>
