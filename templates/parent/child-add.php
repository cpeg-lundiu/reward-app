<?php $avatars = ['🐷', '🐱', '🐶', '🐰', '🦊', '🐻', '🐼', '🐨', '🦁', '🐸', '🦄', '🐵']; ?>

<div class="flex items-center gap-2 mb-4">
    <a href="/parent" class="text-brand-600 font-bold">‹ Back</a>
    <h1 class="text-2xl font-extrabold text-slate-800">Add a child</h1>
</div>

<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <form method="post" action="/parent/children/add" class="space-y-4">
        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">

        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-2">Pick an avatar</label>
            <div class="grid grid-cols-6 gap-2" id="avatar-grid">
                <?php foreach ($avatars as $i => $emoji): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="avatar_emoji" value="<?= e($emoji) ?>" class="peer sr-only" <?= $i === 0 ? 'checked' : '' ?>>
                        <span class="flex items-center justify-center aspect-square rounded-2xl text-2xl bg-brand-50 peer-checked:bg-brand-200 peer-checked:ring-2 peer-checked:ring-brand-400"><?= $emoji ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Child's name</label>
            <input type="text" name="display_name" required
                   class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                   placeholder="e.g. Emma">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Login username</label>
            <input type="text" name="username" required
                   class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                   placeholder="e.g. emma" pattern="[A-Za-z0-9_]{3,30}">
            <p class="text-xs text-slate-400 mt-1">3–30 letters, numbers, or underscores. Your child uses this to log in.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Preset password</label>
            <input type="text" name="password" required
                   class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                   placeholder="Temporary password">
            <p class="text-xs text-slate-400 mt-1"><?= e($password_rules) ?> Your child will set their own on first login.</p>
        </div>

        <button type="submit"
                class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl transition shadow-md shadow-brand-200">
            Create account
        </button>
    </form>
</div>
