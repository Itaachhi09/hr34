export async function displayHmoBenefitsSection() {
    const main = document.getElementById('main-content-area');
    if (!main) return;
    main.innerHTML = `
        <div class="space-y-6">
            <h3 class="text-xl font-semibold">HMO & Benefits</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-white border rounded">
                    <h4 class="font-medium mb-2">Providers</h4>
                    <ul id="hmo-provider-list" class="space-y-1 text-sm"></ul>
                </div>
                <div class="p-4 bg-white border rounded">
                    <h4 class="font-medium mb-2">Plans</h4>
                    <ul id="hmo-plan-list" class="space-y-1 text-sm"></ul>
                </div>
            </div>
        </div>`;

    const providersRes = await fetch('php/api/get_hmo_providers.php');
    const providersJson = await providersRes.json();
    const plansRes = await fetch('php/api/get_benefits_plans.php');
    const plansJson = await plansRes.json();

    const providerList = document.getElementById('hmo-provider-list');
    providersJson.providers?.forEach(p => {
        const li = document.createElement('li');
        li.textContent = `${p.ProviderName} ${p.IsActive ? '' : '(Inactive)'}`;
        providerList.appendChild(li);
    });

    const planList = document.getElementById('hmo-plan-list');
    plansJson.plans?.forEach(pl => {
        const li = document.createElement('li');
        li.textContent = `${pl.PlanName} - ₱${pl.MonthlyPremium ?? 0}`;
        planList.appendChild(li);
    });
}


