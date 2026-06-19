<div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
    <div class="text-center mb-6">
        <div class="text-6xl mb-2">🐷</div>
        <h1 class="text-2xl font-extrabold text-brand-600">Piggy Rewards</h1>
        <p class="text-slate-500 text-sm mt-1">Save, earn stars, and claim rewards!</p>
    </div>

    <div class="w-full max-w-sm bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
        <!-- Role toggle -->
        <div class="grid grid-cols-2 gap-1 p-1 bg-brand-50 rounded-full mb-5">
            <button type="button" data-role-tab="parent"
                    class="role-tab py-2 rounded-full text-sm font-bold bg-brand-500 text-white">👩‍👦 Parent</button>
            <button type="button" data-role-tab="child"
                    class="role-tab py-2 rounded-full text-sm font-bold text-slate-500">🧒 Child</button>
        </div>

        <form method="post" action="/login" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
            <input type="hidden" name="role" id="role-field" value="parent">

            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1" id="id-label">Email</label>
                <input type="text" name="identifier" id="id-input" required autofocus
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                       placeholder="you@example.com">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300"
                       placeholder="••••••••">
            </div>

            <button type="submit"
                    class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl transition shadow-md shadow-brand-200">
                Log in
            </button>
        </form>

        <p class="text-center text-sm text-slate-500 mt-5" id="register-hint">
            New here? <a href="/register" class="font-bold text-brand-600">Create a parent account</a>
        </p>
    </div>
</div>

<script>
(function () {
    var tabs = document.querySelectorAll('.role-tab');
    var field = document.getElementById('role-field');
    var label = document.getElementById('id-label');
    var input = document.getElementById('id-input');
    var hint = document.getElementById('register-hint');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var role = tab.getAttribute('data-role-tab');
            field.value = role;
            tabs.forEach(function (t) {
                var active = t === tab;
                t.classList.toggle('bg-brand-500', active);
                t.classList.toggle('text-white', active);
                t.classList.toggle('text-slate-500', !active);
            });
            if (role === 'parent') {
                label.textContent = 'Email';
                input.type = 'text';
                input.placeholder = 'you@example.com';
                hint.style.display = '';
            } else {
                label.textContent = 'Username';
                input.type = 'text';
                input.placeholder = 'your username';
                hint.style.display = 'none';
            }
        });
    });
})();
</script>
