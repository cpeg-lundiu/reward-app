<div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
    <div class="text-center mb-6">
        <div class="text-6xl mb-2">🐷</div>
        <h1 class="text-2xl font-extrabold text-brand-600">Create your family</h1>
        <p class="text-slate-500 text-sm mt-1">Set up a parent account to get started.</p>
    </div>

    <div class="w-full max-w-sm bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
        <form method="post" action="/register" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">

            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Your name</label>
                <input type="text" name="display_name" required
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                       placeholder="e.g. Mom">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Email</label>
                <input type="email" name="email" required
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                       placeholder="you@example.com">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Family currency</label>
                <select name="currency" required
                        class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                    <?php foreach ($currencies as $code => $info): ?>
                        <option value="<?= e($code) ?>" <?= $code === 'USD' ? 'selected' : '' ?>>
                            <?= e($code) ?> — <?= e($info[1]) ?> (<?= e($info[0]) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-slate-400 mt-1">You can change this later with an exchange rate.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Your timezone</label>
                <select name="timezone" id="timezone" required
                        class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                    <?php foreach ($timezones as $tz): ?>
                        <option value="<?= e($tz) ?>" <?= $tz === 'UTC' ? 'selected' : '' ?>><?= e($tz) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-slate-400 mt-1">Used to show times correctly. We'll try to detect it automatically.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                       placeholder="••••••••">
                <p class="text-xs text-slate-400 mt-1"><?= e($password_rules) ?></p>
            </div>

            <button type="submit"
                    class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl transition shadow-md shadow-brand-200">
                Create account
            </button>
        </form>

        <p class="text-center text-sm text-slate-500 mt-5">
            Already have an account? <a href="/login" class="font-bold text-brand-600">Log in</a>
        </p>
    </div>
</div>

<script>
// Pre-select the browser's timezone if we can detect it.
(function () {
    try {
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        var sel = document.getElementById('timezone');
        if (tz && sel && [].some.call(sel.options, function (o) { return o.value === tz; })) {
            sel.value = tz;
        }
    } catch (e) { /* leave default */ }
})();
</script>
