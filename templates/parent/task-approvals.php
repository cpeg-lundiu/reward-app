<div class="flex items-center gap-2 mb-4">
    <a href="/parent/tasks" class="text-brand-600 font-bold">‹ Back</a>
    <h1 class="text-2xl font-extrabold text-slate-800">Task approvals</h1>
</div>

<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <?php if (!$submissions): ?>
        <div class="text-center py-6">
            <div class="text-4xl mb-2">🎉</div>
            <p class="text-slate-500 text-sm">No tasks waiting for approval.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($submissions as $s): ?>
                <div class="flex items-center gap-3 border border-brand-100 rounded-2xl p-3">
                    <div class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-xl"><?= e($s['avatar_emoji'] ?: '🐷') ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-800 truncate"><?= e($s['title']) ?></p>
                        <p class="text-xs text-slate-400"><?= e($s['child_name']) ?> · <?= e($s['due_date']) ?> · ⭐ <?= (int) $s['stars'] ?></p>
                    </div>
                    <form method="post" action="/parent/tasks/<?= (int) $s['id'] ?>/approve" class="m-0">
                        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                        <button class="bg-green-500 hover:bg-green-600 text-white text-sm font-bold px-3 py-2 rounded-xl">Approve</button>
                    </form>
                    <form method="post" action="/parent/tasks/<?= (int) $s['id'] ?>/reject" class="m-0">
                        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                        <button class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-bold px-3 py-2 rounded-xl">Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
