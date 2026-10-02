<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install iTTiBA Commerce</title>
    <style>
        :root{color-scheme:light;--ink:#111827;--muted:#64748b;--line:#e2e8f0;--canvas:#f8fafc;--surface:#fff;--brand:#7c3aed;--brand-dark:#6d28d9;--brand-soft:#f5f3ff;--success:#047857;--success-soft:#ecfdf5;--danger:#be123c;--danger-soft:#fff1f2;--warning:#b45309;--warning-soft:#fffbeb}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:radial-gradient(circle at 10% 0,#ede9fe 0,transparent 30rem),var(--canvas);color:var(--ink);font:500 14px/1.55 Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}button,input,select{font:inherit}button{cursor:pointer}.shell{width:min(1180px,calc(100% - 32px));margin:0 auto;padding:32px 0 56px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:24px}.brand{display:flex;align-items:center;gap:12px}.brand-mark{display:grid;width:44px;height:44px;place-items:center;border-radius:13px;background:linear-gradient(145deg,#8b5cf6,#6d28d9);color:#fff;font-size:19px;font-weight:900;box-shadow:0 10px 28px #7c3aed33}.brand strong{display:block;font-size:17px}.brand small{display:block;color:var(--muted)}.secure{display:flex;align-items:center;gap:8px;color:var(--success);font-size:13px;font-weight:700}.layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:24px}.sidebar,.card{border:1px solid var(--line);border-radius:18px;background:var(--surface);box-shadow:0 12px 40px rgba(15,23,42,.06)}.sidebar{align-self:start;padding:22px;position:sticky;top:24px}.sidebar h1{margin:0;font-size:22px;line-height:1.25}.sidebar>p{margin:9px 0 22px;color:var(--muted)}.steps{display:grid;gap:7px}.step-button{display:flex;width:100%;align-items:center;gap:11px;border:0;border-radius:11px;background:transparent;padding:10px;text-align:left;color:var(--muted)}.step-button[aria-current=true]{background:var(--brand-soft);color:var(--brand-dark)}.step-button:disabled{cursor:default}.step-number{display:grid;width:28px;height:28px;flex:0 0 auto;place-items:center;border:1px solid var(--line);border-radius:9px;background:#fff;font-size:12px;font-weight:800}.step-button[aria-current=true] .step-number{border-color:var(--brand);background:var(--brand);color:#fff}.step-copy b{display:block;font-size:13px}.step-copy small{font-size:11px;color:inherit;opacity:.8}.support-note{margin-top:24px;padding-top:18px;border-top:1px solid var(--line);color:var(--muted);font-size:12px}.card{overflow:hidden}.card-head{padding:26px 28px 20px;border-bottom:1px solid var(--line)}.eyebrow{margin:0 0 6px;color:var(--brand-dark);font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.card-head h2{margin:0;font-size:25px;letter-spacing:-.025em}.card-head p{max-width:680px;margin:7px 0 0;color:var(--muted)}.panel{padding:26px 28px}.panel[hidden]{display:none}.requirements{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.requirement{display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--line);border-radius:11px;padding:11px 12px}.requirement div{min-width:0}.requirement b{display:block;font-size:13px}.requirement small{display:block;overflow:hidden;color:var(--muted);font-size:11px;text-overflow:ellipsis;white-space:nowrap}.status-dot{display:grid;width:24px;height:24px;flex:0 0 auto;place-items:center;border-radius:999px;font-size:12px;font-weight:900}.status-dot.ok{background:var(--success-soft);color:var(--success)}.status-dot.bad{background:var(--danger-soft);color:var(--danger)}.notice{margin-bottom:18px;border:1px solid;border-radius:12px;padding:12px 14px}.notice.error{border-color:#fecdd3;background:var(--danger-soft);color:var(--danger)}.notice.warning{border-color:#fde68a;background:var(--warning-soft);color:var(--warning)}.notice.success{border-color:#a7f3d0;background:var(--success-soft);color:var(--success)}.notice ul{margin:6px 0 0;padding-left:18px}.section-title{margin:0 0 4px;font-size:16px}.section-copy{margin:0 0 18px;color:var(--muted);font-size:13px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}.field{display:block}.field.full{grid-column:1/-1}.field span{display:flex;justify-content:space-between;margin-bottom:7px;font-size:13px;font-weight:700}.field em{color:var(--danger);font-style:normal}.field small{font-weight:500;color:var(--muted)}input,select{width:100%;height:44px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;padding:0 12px;color:var(--ink);outline:none;transition:.15s}input:focus,select:focus{border-color:var(--brand);box-shadow:0 0 0 3px #7c3aed1f}input:user-invalid{border-color:#fb7185}.divider{height:1px;margin:24px 0;background:var(--line)}.database-actions{display:flex;align-items:center;gap:12px;margin-top:16px}.db-status{font-size:12px;font-weight:650}.db-status.ok{color:var(--success)}.db-status.bad{color:var(--danger)}.actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:28px;padding-top:20px;border-top:1px solid var(--line)}.actions-right{display:flex;gap:10px;margin-left:auto}.button{display:inline-flex;min-height:42px;align-items:center;justify-content:center;gap:8px;border:1px solid transparent;border-radius:10px;padding:0 17px;font-weight:750;text-decoration:none;transition:.15s}.button.primary{background:var(--brand);color:#fff}.button.primary:hover{background:var(--brand-dark)}.button.secondary{border-color:#cbd5e1;background:#fff;color:#334155}.button.secondary:hover{background:#f8fafc}.button:disabled{cursor:not-allowed;opacity:.55}.summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:18px}.summary div{border:1px solid var(--line);border-radius:11px;background:#f8fafc;padding:12px}.summary small{display:block;color:var(--muted)}.summary b{display:block;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.checkbox{display:flex;align-items:flex-start;gap:10px;margin-top:20px;color:#475569}.checkbox input{width:17px;height:17px;margin-top:2px;accent-color:var(--brand)}.spinner{display:none;width:16px;height:16px;border:2px solid #ffffff66;border-top-color:#fff;border-radius:999px;animation:spin .7s linear infinite}.submitting .spinner{display:block}@keyframes spin{to{transform:rotate(360deg)}}noscript{display:block;margin-bottom:18px}.mobile-progress{display:none;margin-bottom:14px;color:var(--brand-dark);font-size:12px;font-weight:800}
        @media(max-width:820px){.shell{width:min(100% - 22px,680px);padding-top:18px}.topbar{margin-bottom:15px}.secure{display:none}.layout{grid-template-columns:1fr}.sidebar{display:none}.mobile-progress{display:block}.card-head,.panel{padding:21px 18px}.requirements,.form-grid,.summary{grid-template-columns:1fr}.field.full{grid-column:auto}.database-actions{align-items:stretch;flex-direction:column}.actions{align-items:stretch;flex-direction:column}.actions-right{width:100%;margin:0}.actions-right .button,.actions>.button{flex:1}.card-head h2{font-size:22px}}
    </style>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <div class="brand"><span class="brand-mark">iT</span><span><strong>iTTiBA Commerce</strong><small>Secure application installer</small></span></div>
        <div class="secure"><span aria-hidden="true">●</span> Credentials stay on this server</div>
    </header>
    <div class="layout">
        <aside class="sidebar">
            <h1>Let's set up your store</h1>
            <p>Complete four short steps. You can review everything before installation starts.</p>
            <nav class="steps" aria-label="Installation progress">
                @foreach ([['Requirements','Server readiness'],['Application','Database connection'],['Store','Delivery defaults'],['Administrator','Secure owner account']] as $index => [$label,$description])
                    <button type="button" class="step-button" data-step-link="{{ $index + 1 }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" disabled><span class="step-number">{{ $index + 1 }}</span><span class="step-copy"><b>{{ $label }}</b><small>{{ $description }}</small></span></button>
                @endforeach
            </nav>
            <p class="support-note">The installer locks itself after success. Reinstallation requires server-level access to the private lock file.</p>
        </aside>

        <section class="card">
            <div class="card-head"><p class="eyebrow">Fresh installation</p><h2 id="panel-title">Server requirements</h2><p id="panel-description">Confirm that the server can run the application safely.</p></div>
            <form id="installer-form" method="POST" action="{{ route('installer.store') }}" novalidate>
                @csrf
                <div class="panel" data-step="1">
                    <p class="mobile-progress">Step 1 of 4 · Requirements</p>
                    <noscript class="notice error">JavaScript is required to run the guided installer.</noscript>
                    @if ($errors->any())
                        <div class="notice error" role="alert"><b>Please correct the following:</b><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    @unless ($requirements['passed'])<div class="notice warning"><b>Server action required.</b> Fix every failed item, then refresh this page.</div>@endunless
                    <div class="requirements">
                        @foreach ($requirements['items'] as $item)
                            <div class="requirement"><div><b>{{ $item['label'] }}</b><small title="{{ $item['value'] }}">{{ $item['value'] }}</small></div><span class="status-dot {{ $item['passed'] ? 'ok' : 'bad' }}" aria-label="{{ $item['passed'] ? 'Passed' : 'Failed' }}">{{ $item['passed'] ? '✓' : '!' }}</span></div>
                        @endforeach
                    </div>
                    <div class="actions"><button type="button" class="button secondary" onclick="window.location.reload()">Recheck</button><div class="actions-right"><button type="button" class="button primary" data-next {{ $requirements['passed'] ? '' : 'disabled' }}>Continue</button></div></div>
                </div>

                <div class="panel" data-step="2" hidden>
                    <p class="mobile-progress">Step 2 of 4 · Application & database</p>
                    <h3 class="section-title">Application</h3><p class="section-copy">Use the public URL where customers will access the store.</p>
                    <div class="form-grid">
                        <label class="field"><span>Application name <em>*</em></span><input name="app_name" value="{{ old('app_name', 'iTTiBA Commerce') }}" required maxlength="80"></label>
                        <label class="field"><span>Application URL <em>*</em></span><input type="url" name="app_url" value="{{ old('app_url', $defaultUrl) }}" required maxlength="255" placeholder="https://example.com"></label>
                        <label class="field full"><span>Timezone <em>*</em></span><select name="timezone" required>@foreach ($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('timezone','Asia/Dhaka') === $timezone)>{{ $timezone }}</option>@endforeach</select></label>
                    </div>
                    <div class="divider"></div>
                    <h3 class="section-title">MySQL database</h3><p class="section-copy">Create an empty MySQL database and user in your hosting panel first.</p>
                    <div class="form-grid" data-database-fields>
                        <label class="field"><span>Database host <em>*</em></span><input name="db_host" value="{{ old('db_host','127.0.0.1') }}" required maxlength="255" autocomplete="off"></label>
                        <label class="field"><span>Port <em>*</em></span><input type="number" name="db_port" value="{{ old('db_port','3306') }}" required min="1" max="65535"></label>
                        <label class="field"><span>Database name <em>*</em></span><input name="db_database" value="{{ old('db_database') }}" required maxlength="64" autocomplete="off"></label>
                        <label class="field"><span>Database username <em>*</em></span><input name="db_username" value="{{ old('db_username') }}" required maxlength="128" autocomplete="off"></label>
                        <label class="field full"><span>Database password <small>May be empty locally</small></span><input type="password" name="db_password" maxlength="1000" autocomplete="new-password"></label>
                    </div>
                    <div class="database-actions"><button type="button" id="test-database" class="button secondary">Test connection</button><span id="database-status" class="db-status" role="status" aria-live="polite">Connection has not been tested.</span></div>
                    <div class="actions"><button type="button" class="button secondary" data-back>Back</button><div class="actions-right"><button type="button" class="button primary" data-next>Continue</button></div></div>
                </div>

                <div class="panel" data-step="3" hidden>
                    <p class="mobile-progress">Step 3 of 4 · Store information</p>
                    <h3 class="section-title">Store identity</h3><p class="section-copy">These details become the initial storefront and order contact settings.</p>
                    <div class="form-grid">
                        <label class="field"><span>Store name <em>*</em></span><input name="store_name" value="{{ old('store_name','iTTiBA International') }}" required maxlength="120"></label>
                        <label class="field"><span>Store email</span><input type="email" name="store_email" value="{{ old('store_email') }}" maxlength="255"></label>
                        <label class="field full"><span>Order phone / WhatsApp</span><input type="tel" name="store_phone" value="{{ old('store_phone') }}" maxlength="16" placeholder="8801XXXXXXXXX"></label>
                    </div>
                    <div class="divider"></div>
                    <h3 class="section-title">Delivery defaults</h3><p class="section-copy">District selection at checkout will apply these charges automatically.</p>
                    <div class="form-grid">
                        <label class="field"><span>Inside Dhaka charge <em>*</em></span><input type="number" name="delivery_inside_dhaka" value="{{ old('delivery_inside_dhaka','80') }}" required min="0" step="0.01"></label>
                        <label class="field"><span>Outside Dhaka charge <em>*</em></span><input type="number" name="delivery_outside_dhaka" value="{{ old('delivery_outside_dhaka','150') }}" required min="0" step="0.01"></label>
                    </div>
                    <div class="actions"><button type="button" class="button secondary" data-back>Back</button><div class="actions-right"><button type="button" class="button primary" data-next>Continue</button></div></div>
                </div>

                <div class="panel" data-step="4" hidden>
                    <p class="mobile-progress">Step 4 of 4 · Administrator</p>
                    <h3 class="section-title">Super administrator</h3><p class="section-copy">This owner account receives full access to every administration feature.</p>
                    <div class="form-grid">
                        <label class="field"><span>Full name <em>*</em></span><input name="admin_name" value="{{ old('admin_name','Super Admin') }}" required maxlength="120" autocomplete="name"></label>
                        <label class="field"><span>Email address <em>*</em></span><input type="email" name="admin_email" value="{{ old('admin_email') }}" required maxlength="255" autocomplete="email"></label>
                        <label class="field full"><span>Phone number</span><input type="tel" name="admin_phone" value="{{ old('admin_phone') }}" maxlength="16" autocomplete="tel" placeholder="+8801XXXXXXXXX"></label>
                        <label class="field"><span>Password <em>*</em></span><input type="password" name="admin_password" required minlength="12" autocomplete="new-password"></label>
                        <label class="field"><span>Confirm password <em>*</em></span><input type="password" name="admin_password_confirmation" required minlength="12" autocomplete="new-password"></label>
                    </div>
                    <div class="summary" aria-label="Installation summary"><div><small>Application URL</small><b data-summary="app_url">—</b></div><div><small>Database</small><b data-summary="db_database">—</b></div><div><small>Store</small><b data-summary="store_name">—</b></div><div><small>Administrator</small><b data-summary="admin_email">—</b></div></div>
                    <label class="checkbox"><input type="checkbox" name="terms" value="1" required><span>I confirm that the selected database is intended for this new installation and may receive the application's tables.</span></label>
                    <div class="actions"><button type="button" class="button secondary" data-back>Back</button><div class="actions-right"><button type="submit" id="install-button" class="button primary"><span class="spinner" aria-hidden="true"></span><span data-submit-label>Install application</span></button></div></div>
                </div>
            </form>
        </section>
    </div>
</main>
<script>
(() => {
    const form = document.getElementById('installer-form');
    const panels = [...document.querySelectorAll('[data-step]')];
    const links = [...document.querySelectorAll('[data-step-link]')];
    const title = document.getElementById('panel-title');
    const description = document.getElementById('panel-description');
    const databaseFields = [...document.querySelectorAll('[data-database-fields] input')];
    const databaseStatus = document.getElementById('database-status');
    const testButton = document.getElementById('test-database');
    const headings = [
        ['Server requirements','Confirm that the server can run the application safely.'],
        ['Application & database','Connect the application to a new, empty MySQL database.'],
        ['Store information','Set the initial store identity and delivery charges.'],
        ['Administrator account','Create the secure owner account and review your setup.'],
    ];
    let currentStep = 1;
    let databaseReady = false;

    const showStep = (step) => {
        currentStep = step;
        panels.forEach(panel => panel.hidden = Number(panel.dataset.step) !== step);
        links.forEach(link => link.setAttribute('aria-current', Number(link.dataset.stepLink) === step ? 'true' : 'false'));
        title.textContent = headings[step - 1][0];
        description.textContent = headings[step - 1][1];
        if (step === 4) document.querySelectorAll('[data-summary]').forEach(node => node.textContent = form.elements[node.dataset.summary]?.value || '—');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    const validatePanel = () => {
        const fields = [...panels[currentStep - 1].querySelectorAll('input,select')];
        const invalid = fields.find(field => !field.checkValidity());
        if (invalid) { invalid.reportValidity(); invalid.focus(); return false; }
        return true;
    };
    document.querySelectorAll('[data-next]').forEach(button => button.addEventListener('click', () => {
        if (!validatePanel()) return;
        if (currentStep === 2 && !databaseReady) { databaseStatus.textContent = 'Test a new, empty database before continuing.'; databaseStatus.className = 'db-status bad'; return; }
        showStep(Math.min(4, currentStep + 1));
    }));
    document.querySelectorAll('[data-back]').forEach(button => button.addEventListener('click', () => showStep(Math.max(1, currentStep - 1))));
    databaseFields.forEach(field => field.addEventListener('input', () => { databaseReady = false; databaseStatus.textContent = 'Connection has changed. Test it again.'; databaseStatus.className = 'db-status'; }));
    testButton.addEventListener('click', async () => {
        const invalid = databaseFields.find(field => !field.checkValidity());
        if (invalid) { invalid.reportValidity(); invalid.focus(); return; }
        testButton.disabled = true; databaseStatus.textContent = 'Testing secure connection…'; databaseStatus.className = 'db-status';
        const body = new FormData(); databaseFields.forEach(field => body.append(field.name, field.value));
        try {
            const response = await fetch(@json(route('installer.test-database')), { method:'POST', headers:{ 'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content }, body });
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Connection test failed.'));
            databaseReady = Number(data.tables) === 0;
            databaseStatus.textContent = data.message;
            databaseStatus.className = `db-status ${databaseReady ? 'ok' : 'bad'}`;
        } catch (error) {
            databaseReady = false; databaseStatus.textContent = error.message; databaseStatus.className = 'db-status bad';
        } finally { testButton.disabled = false; }
    });
    form.addEventListener('submit', event => {
        if (!validatePanel()) { event.preventDefault(); return; }
        const button = document.getElementById('install-button'); button.disabled = true; button.classList.add('submitting'); button.querySelector('[data-submit-label]').textContent = 'Installing…';
    });
    @if ($errors->hasAny(['db_host','db_port','db_database','db_username','db_password','db_connection'])) showStep(2);
    @elseif ($errors->hasAny(['store_name','store_email','store_phone','delivery_inside_dhaka','delivery_outside_dhaka'])) showStep(3);
    @elseif ($errors->hasAny(['admin_name','admin_email','admin_phone','admin_password','terms','installation'])) showStep(4);
    @endif
})();
</script>
</body>
</html>
