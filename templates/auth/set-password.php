<div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
    <div class="text-center mb-6">
        <div class="text-6xl mb-2">🔑</div>
        <h1 class="text-2xl font-extrabold text-brand-600">Choose your password</h1>
        <p class="text-slate-500 text-sm mt-1">Pick a secret password just for you!</p>
    </div>

    <div class="w-full max-w-sm bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
        <form method="post" action="/set-password" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">

            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">New password</label>
                <input type="password" name="password" required autofocus
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                       placeholder="••••••••">
                <p class="text-xs text-slate-400 mt-1"><?= e($password_rules) ?></p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Confirm password</label>
                <input type="password" name="confirm" required
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                       placeholder="••••••••">
            </div>

            <button type="submit"
                    class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl transition shadow-md shadow-brand-200">
                Save password
            </button>
        </form>
    </div>
</div>
