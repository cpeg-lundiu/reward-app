<?php
/**
 * Shared page shell. Receives: $title, $auth_user, $flash, $csrf_token,
 * $active_nav, $content.
 */
$user = $auth_user ?? null;
$role = $user['role'] ?? null;

$nav = [];
if ($role === 'parent') {
    $nav = [
        ['key' => 'home', 'label' => 'Family', 'icon' => '🏠', 'href' => '/parent'],
        ['key' => 'money', 'label' => 'Money', 'icon' => '💰', 'href' => '/parent/money'],
        ['key' => 'tasks', 'label' => 'Tasks', 'icon' => '⭐', 'href' => '/parent/tasks'],
        ['key' => 'rewards', 'label' => 'Rewards', 'icon' => '🎁', 'href' => '/parent/rewards'],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => '⚙️', 'href' => '/parent/settings'],
    ];
} elseif ($role === 'child') {
    $nav = [
        ['key' => 'piggy', 'label' => 'Piggy', 'icon' => '🐷', 'href' => '/child'],
        ['key' => 'tasks', 'label' => 'Tasks', 'icon' => '⭐', 'href' => '/child/tasks'],
        ['key' => 'rewards', 'label' => 'Rewards', 'icon' => '🎁', 'href' => '/child/rewards'],
    ];
}
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Piggy Rewards') ?></title>
<link rel="stylesheet" href="/css/app.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐷</text></svg>">

<div class="min-h-screen flex flex-col bg-brand-50 text-slate-700">

    <?php if ($nav): ?>
    <!-- Top bar -->
    <header class="bg-white/80 backdrop-blur border-b border-brand-100 sticky top-0 z-20">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center justify-between">
            <a href="<?= $role === 'parent' ? '/parent' : '/child' ?>" class="flex items-center gap-2 font-extrabold text-brand-600">
                <span class="text-2xl">🐷</span><span class="hidden sm:inline">Piggy Rewards</span>
            </a>
            <!-- Desktop nav -->
            <nav class="hidden md:flex items-center gap-1">
                <?php foreach ($nav as $item): ?>
                    <a href="<?= e($item['href']) ?>"
                       class="px-3 py-2 rounded-full text-sm font-semibold transition <?= ($active_nav ?? null) === $item['key'] ? 'bg-brand-500 text-white' : 'text-slate-500 hover:bg-brand-100' ?>">
                        <?= $item['icon'] ?> <?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-500 hidden sm:inline"><?= e($user['display_name']) ?></span>
                <form method="post" action="/logout" class="m-0">
                    <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
                    <button class="text-sm font-semibold text-brand-600 hover:text-brand-700">Log out</button>
                </form>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <main class="flex-1 w-full max-w-3xl mx-auto px-4 py-6 <?= $nav ? 'pb-28 md:pb-6' : '' ?>">
        <?php foreach (($flash ?? []) as $msg): ?>
            <?php
            $styles = [
                'success' => 'bg-green-50 text-green-700 border-green-200',
                'error' => 'bg-red-50 text-red-700 border-red-200',
                'info' => 'bg-blue-50 text-blue-700 border-blue-200',
            ];
            $style = $styles[$msg['type']] ?? $styles['info'];
            ?>
            <div class="mb-3 px-4 py-3 rounded-2xl border text-sm font-medium <?= $style ?>">
                <?= e($msg['message']) ?>
            </div>
        <?php endforeach; ?>

        <?= $content ?>
    </main>

    <?php if ($nav): ?>
    <!-- Bottom tab bar (mobile) -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-brand-100 z-20 shadow-[0_-4px_20px_rgba(244,63,118,0.08)]">
        <div class="max-w-3xl mx-auto grid" style="grid-template-columns: repeat(<?= count($nav) ?>, minmax(0, 1fr));">
            <?php foreach ($nav as $item): ?>
                <a href="<?= e($item['href']) ?>"
                   class="flex flex-col items-center justify-center gap-0.5 py-2.5 min-h-[56px] text-[11px] font-semibold transition <?= ($active_nav ?? null) === $item['key'] ? 'text-brand-600' : 'text-slate-400' ?>">
                    <span class="text-xl leading-none"><?= $item['icon'] ?></span>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php endif; ?>
</div>
