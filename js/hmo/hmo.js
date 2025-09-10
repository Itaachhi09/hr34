import { API_BASE_URL } from '../utils.js';

let pageTitleElement;
let mainContentArea;

function initializeElements() {
    pageTitleElement = document.getElementById('page-title');
    mainContentArea = document.getElementById('main-content-area');
    return !!(pageTitleElement && mainContentArea);
}

export async function displayHmoBenefitsSection() {
    if (!initializeElements()) return;
    pageTitleElement.textContent = 'HMO & Benefits';
    mainContentArea.innerHTML = `
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA]">
                <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">HMO Providers</h3>
                <div id="hmo-providers-container" class="text-sm text-gray-700">Loading HMO providers...</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA]">
                <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Employee Benefits</h3>
                <div id="employee-benefits-container" class="text-sm text-gray-700">Loading employee benefits...</div>
            </div>
        </div>
    `;

    try {
        const providersResp = await fetch(`${API_BASE_URL}get_hmo_providers.php`);
        const providers = await providersResp.json().catch(() => []);
        const container = document.getElementById('hmo-providers-container');
        if (Array.isArray(providers) && providers.length) {
            container.innerHTML = `<ul class="list-disc pl-6">${providers.map(p => `<li>${p.name || p.provider_name}</li>`).join('')}</ul>`;
        } else {
            container.textContent = 'No HMO providers found.';
        }
    } catch (e) {
        const container = document.getElementById('hmo-providers-container');
        if (container) container.textContent = 'Failed to load HMO providers.';
    }

    try {
        const benefitsResp = await fetch(`${API_BASE_URL}get_benefits_plans.php`);
        const benefits = await benefitsResp.json().catch(() => []);
        const container = document.getElementById('employee-benefits-container');
        if (Array.isArray(benefits) && benefits.length) {
            container.innerHTML = `<ul class="list-disc pl-6">${benefits.map(b => `<li>${b.plan_name || b.name}</li>`).join('')}</ul>`;
        } else {
            container.textContent = 'No benefits plans found.';
        }
    } catch (e) {
        const container = document.getElementById('employee-benefits-container');
        if (container) container.textContent = 'Failed to load benefits plans.';
    }
}

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


