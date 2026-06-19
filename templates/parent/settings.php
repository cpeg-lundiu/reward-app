<div class="flex items-center gap-2 mb-4">
    <h1 class="text-2xl font-extrabold text-slate-800">Settings</h1>
</div>

<div class="bg-white rounded-3xl shadow-lg shadow-brand-100 p-6">
    <h2 class="font-bold text-slate-700 mb-1">💱 Family currency</h2>
    <p class="text-sm text-slate-500 mb-4">
        Current currency: <span class="font-bold text-brand-600"><?= e($currency) ?> (<?= e(\App\Support\Money::symbol($currency)) ?>)</span>.
        Changing it converts every child's balance and pending withdrawals using the exchange rate you provide.
    </p>

    <form method="post" action="/parent/settings/currency" class="space-y-4"
          onsubmit="return confirm('This will convert all balances to the new currency. Continue?');">
        <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">New currency</label>
            <select name="currency" required class="w-full rounded-2xl border border-brand-200 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-brand-300">
                <?php foreach ($currencies as $code => $info): ?>
                    <option value="<?= e($code) ?>" <?= $code === $currency ? 'disabled' : '' ?>>
                        <?= e($code) ?> — <?= e($info[1]) ?> (<?= e($info[0]) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Exchange rate</label>
            <input type="number" name="rate" step="0.00000001" min="0" required
                   class="w-full rounded-2xl border border-brand-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-300" placeholder="e.g. 7.8">
            <p class="text-xs text-slate-400 mt-1">New amount = old amount × rate. For example, 1 <?= e($currency) ?> = 7.8 of the new currency → enter 7.8.</p>
        </div>
        <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-3 rounded-2xl shadow-md shadow-brand-200">
            Change currency & convert balances
        </button>
    </form>
</div>
