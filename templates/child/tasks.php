<?php
$cal = $calendar;
$dows = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
$statusDot = [
    'todo' => 'bg-brand-400',
    'completed' => 'bg-amber-400',
    'approved' => 'bg-green-400',
    'rejected' => 'bg-red-300',
];
?>

<h1 class="text-2xl font-extrabold text-slate-800 mb-4">My Tasks ⭐</h1>

<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-4 sm:p-6 mb-5">
    <!-- Month nav -->
    <div class="flex items-center justify-between mb-3">
        <a href="/child/tasks?year=<?= $cal['prev']['year'] ?>&month=<?= $cal['prev']['month'] ?>"
           class="w-9 h-9 flex items-center justify-center rounded-full bg-brand-50 text-brand-600 font-bold">‹</a>
        <h2 class="font-bold text-slate-700"><?= e($cal['label']) ?></h2>
        <a href="/child/tasks?year=<?= $cal['next']['year'] ?>&month=<?= $cal['next']['month'] ?>"
           class="w-9 h-9 flex items-center justify-center rounded-full bg-brand-50 text-brand-600 font-bold">›</a>
    </div>

    <!-- Weekday header -->
    <div class="grid grid-cols-7 text-center text-xs font-bold text-slate-400 mb-1">
        <?php foreach ($dows as $d): ?><div><?= $d ?></div><?php endforeach; ?>
    </div>

    <!-- Day grid -->
    <div class="grid grid-cols-7 gap-1">
        <?php foreach ($cal['weeks'] as $week): ?>
            <?php foreach ($week as $day): ?>
                <?php $has = count($day['occurrences']) > 0; ?>
                <button type="button"
                        <?= $has ? 'data-day="' . e($day['date']) . '"' : 'disabled' ?>
                        class="day-cell aspect-square rounded-xl flex flex-col items-center justify-center gap-0.5 text-sm
                               <?= $day['in_month'] ? 'text-slate-700' : 'text-slate-300' ?>
                               <?= $day['is_today'] ? 'ring-2 ring-brand-400 font-extrabold' : '' ?>
                               <?= $has ? 'bg-brand-50 hover:bg-brand-100 cursor-pointer' : '' ?>">
                    <span><?= $day['day'] ?></span>
                    <?php if ($has): ?>
                        <span class="flex gap-0.5">
                            <?php foreach (array_slice($day['occurrences'], 0, 3) as $occ): ?>
                                <span class="w-1.5 h-1.5 rounded-full <?= $statusDot[$occ['status']] ?? 'bg-brand-400' ?>"></span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Day detail panels -->
<?php foreach ($cal['weeks'] as $week): ?>
    <?php foreach ($week as $day): ?>
        <?php if (!$day['occurrences'] || !$day['in_month']) {
            continue;
        } ?>
        <div class="day-panel bg-white rounded-3xl shadow-lg shadow-brand-100 p-6 mb-4 <?= $day['is_today'] ? '' : 'hidden' ?>" data-panel="<?= e($day['date']) ?>">
            <h3 class="font-bold text-slate-700 mb-3"><?= e(date('l, M j', strtotime($day['date']))) ?><?= $day['is_today'] ? ' · Today' : '' ?></h3>
            <div class="space-y-2">
                <?php foreach ($day['occurrences'] as $occ): $task = $occ['task']; ?>
                    <div class="flex items-center gap-3 border border-brand-100 rounded-2xl p-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-800 truncate"><?= e($task['title']) ?></p>
                            <?php if ($task['description']): ?><p class="text-xs text-slate-400 truncate"><?= e($task['description']) ?></p><?php endif; ?>
                            <p class="text-xs text-amber-500 font-bold">⭐ <?= (int) $task['stars'] ?></p>
                        </div>
                        <?php if ($occ['status'] === 'approved'): ?>
                            <span class="text-green-600 font-bold text-sm">✓ Done</span>
                        <?php elseif ($occ['status'] === 'completed'): ?>
                            <span class="text-amber-600 font-bold text-sm">⏳ Waiting</span>
                        <?php elseif ($day['is_future']): ?>
                            <span class="text-slate-300 font-bold text-sm">Soon</span>
                        <?php else: ?>
                            <form method="post" action="/child/tasks/complete" class="m-0">
                                <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                <input type="hidden" name="due_date" value="<?= e($day['date']) ?>">
                                <input type="hidden" name="year" value="<?= $cal['year'] ?>">
                                <input type="hidden" name="month" value="<?= $cal['month'] ?>">
                                <button class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold px-3 py-2 rounded-xl whitespace-nowrap">
                                    <?= $occ['status'] === 'rejected' ? 'Redo' : 'Mark done' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>

<script>
(function () {
    var cells = document.querySelectorAll('.day-cell[data-day]');
    var panels = document.querySelectorAll('.day-panel');
    cells.forEach(function (cell) {
        cell.addEventListener('click', function () {
            var date = cell.getAttribute('data-day');
            panels.forEach(function (p) {
                p.classList.toggle('hidden', p.getAttribute('data-panel') !== date);
            });
            var active = document.querySelector('.day-panel[data-panel="' + date + '"]');
            if (active) {
                active.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });
})();
</script>
