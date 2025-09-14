/**
 * Dashboard Module
 * Handles display of summary information and key metrics on the dashboard.
 * v1.4 - Added display for Recent Hires, Dept Distribution Chart, and Employee Quick Actions.
 * v1.3 - Added detailed console logging for data fetching.
 * v1.2 - Applied system color theme to dashboard cards and charts.
 * v1.1 - Added role-based dashboard views.
 */
import { API_BASE_URL } from '../utils.js';

// --- DOM Element References ---
let pageTitleElement;
let mainContentArea;
let dashboardSummaryContainer;
let dashboardChartsContainer; // Added for chart rendering
let dashboardQuickActionsContainer; // Added for employee quick actions

// Store chart instances to destroy them before re-rendering
let employeeStatusChartInstance = null;
let departmentDistributionChartInstance = null; // New chart instance

/**
 * Initializes common elements used by the dashboard module.
 */
function initializeDashboardElements() {
    pageTitleElement = document.getElementById('page-title');
    mainContentArea = document.getElementById('main-content-area');
    if (!pageTitleElement || !mainContentArea) {
        console.error("Dashboard Module: Core DOM elements (page-title or main-content-area) not found!");
        return false;
    }
    return true;
}

/**
 * Displays the appropriate dashboard based on the user's role.
 */
export async function displayDashboardSection() {
    console.log("[Display] Displaying Dashboard Section...");
    if (!initializeDashboardElements()) return;

    const user = window.currentUser;
    if (!user || !user.role_name) {
        pageTitleElement.textContent = 'Dashboard';
        mainContentArea.innerHTML = '<p class="text-red-500 p-4">Error: User role not found. Cannot display dashboard.</p>';
        console.error("Dashboard Error: window.currentUser or window.currentUser.role_name is not defined.");
        return;
    }
    console.log("[Dashboard] Current user:", user);

    pageTitleElement.textContent = `${user.role_name} Dashboard`;
    // Add containers for summary, charts, and quick actions
    mainContentArea.innerHTML = `
        <div id="dashboard-summary-container" class="mb-8">
            <p class="text-center py-4 text-gray-500">Loading dashboard summary...</p>
        </div>
        <div id="dashboard-quick-actions-container" class="mb-8">
            </div>
        <div id="dashboard-charts-container" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            </div>
    `;
    dashboardSummaryContainer = document.getElementById('dashboard-summary-container');
    dashboardChartsContainer = document.getElementById('dashboard-charts-container'); // Assign new container
    dashboardQuickActionsContainer = document.getElementById('dashboard-quick-actions-container');


    try {
        const apiUrl = `${API_BASE_URL}get_dashboard_summary_landing.php?role=${encodeURIComponent(user.role_name)}`;
        console.log(`[Dashboard] Fetching summary data from: ${apiUrl}`);
        const response = await fetch(apiUrl);
        console.log(`[Dashboard] Raw response status: ${response.status}`);

        const summaryData = await handleApiResponse(response); 
        console.log("[Dashboard] Parsed summary data:", summaryData);

        if (summaryData.error) { 
            throw new Error(summaryData.error);
        }

        renderDashboardSummary(summaryData, user.role_name);
        if (user.role_name === 'Employee') {
            renderEmployeeQuickActions();
        } else {
            if(dashboardQuickActionsContainer) dashboardQuickActionsContainer.innerHTML = ''; // Clear if not employee
        }
        renderCharts(summaryData.charts || {}, user.role_name);
    } catch (error) {
        console.error('Error loading dashboard summary:', error);
        if (dashboardSummaryContainer) {
            dashboardSummaryContainer.innerHTML = `<p class="text-red-500 p-4 text-center">Could not load dashboard summary. ${error.message}</p>`;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Dashboard Error',
                text: `Failed to load dashboard data: ${error.message}`,
                confirmButtonColor: '#4E3B2A'
            });
        }
    }
}

/**
 * Renders the summary cards on the dashboard.
 */
function renderDashboardSummary(summaryData, userRole) {
    if (!dashboardSummaryContainer) return;
    if (!summaryData || typeof summaryData !== 'object') {
        console.error("[Render] renderDashboardSummary: summaryData is invalid or null.", summaryData);
        dashboardSummaryContainer.innerHTML = '<p class="text-red-500 p-4 text-center">Failed to render dashboard summary: Invalid data received.</p>';
        return;
    }

    let cardsHtml = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">';
    
    const cardBgColor = 'bg-[#F7E6CA]'; 
    const textColor = 'text-[#4E3B2A]'; 
    const iconColor = 'text-[#594423]'; 
    const valueColor = 'text-[#594423]'; 

    if (userRole === 'System Admin' || userRole === 'HR Admin' || userRole === 'HR Staff') {
        cardsHtml += createSummaryCard('Total Employees', summaryData.total_employees || 0, 'fa-users', cardBgColor, textColor, iconColor, valueColor);
        cardsHtml += createSummaryCard('Active Employees', summaryData.active_employees || 0, 'fa-user-check', cardBgColor, textColor, iconColor, valueColor);
        // Leave cards removed
        // New Card for Recent Hires
        cardsHtml += createSummaryCard('Recent Hires (30d)', summaryData.recent_hires_last_30_days || 0, 'fa-user-plus', cardBgColor, textColor, iconColor, valueColor);
    } else if (userRole === 'Manager') {
        cardsHtml += createSummaryCard('Team Members', summaryData.team_members || 0, 'fa-users-cog', cardBgColor, textColor, iconColor, valueColor);
        // Leave cards removed
        cardsHtml += createSummaryCard('Pending Timesheets', summaryData.pending_timesheets || 0, 'fa-clock', cardBgColor, textColor, iconColor, valueColor);
        cardsHtml += createSummaryCard('Open Team Tasks', summaryData.open_tasks || 0, 'fa-tasks', cardBgColor, textColor, iconColor, valueColor);
    } else if (userRole === 'Employee' || userRole === 'HR Staff') {
        // Leave/Claims cards removed
        cardsHtml += createSummaryCard('Upcoming Payslip', summaryData.upcoming_payslip_date || 'N/A', 'fa-money-bill-wave', cardBgColor, textColor, iconColor, valueColor);
        cardsHtml += createSummaryCard('My Documents', summaryData.my_documents_count || 0, 'fa-folder-open', cardBgColor, textColor, iconColor, valueColor);
    } else {
        cardsHtml += '<p class="col-span-full text-center text-gray-500">No specific dashboard summary for this role.</p>';
    }

    cardsHtml += '</div>';
    dashboardSummaryContainer.innerHTML = cardsHtml;
}

/**
 * Renders quick action buttons for the Employee dashboard.
 */
function renderEmployeeQuickActions() {
    if (!dashboardQuickActionsContainer) {
        console.error("Dashboard Quick Actions Container not found for Employee.");
        return;
    }

    dashboardQuickActionsContainer.innerHTML = `
        <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA]">
            <h3 class="text-lg font-semibold text-[#4E3B2A] mb-4 font-header">Quick Actions</h3>
            <div class="flex flex-wrap gap-4">
                <button id="quick-action-submit-leave" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A] transition duration-150 ease-in-out flex items-center space-x-2">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Submit Leave</span>
                </button>
                <button id="quick-action-submit-claim" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A] transition duration-150 ease-in-out flex items-center space-x-2">
                    <i class="fas fa-receipt"></i>
                    <span>Submit Claim</span>
                </button>
                 <button id="quick-action-view-payslips" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A] transition duration-150 ease-in-out flex items-center space-x-2">
                    <i class="fas fa-file-invoice"></i>
                    <span>View Payslips</span>
                </button>
            </div>
        </div>
    `;

    // Add event listeners for the new buttons
    document.getElementById('quick-action-submit-leave')?.addEventListener('click', () => {
        const leaveLink = document.getElementById('leave-requests-link'); 
        if (leaveLink) {
            leaveLink.click(); 
        } else {
            console.warn("Quick Action: Leave Requests link not found for navigation.");
        }
    });

    document.getElementById('quick-action-submit-claim')?.addEventListener('click', () => {
        const claimLink = document.getElementById('submit-claim-link'); 
        if (claimLink) {
            claimLink.click();
        } else {
            console.warn("Quick Action: Submit Claim link not found for navigation.");
        }
    });
     document.getElementById('quick-action-view-payslips')?.addEventListener('click', () => {
        const payslipsLink = document.getElementById('payslips-link'); 
        if (payslipsLink) {
            payslipsLink.click();
        } else {
            console.warn("Quick Action: View Payslips link not found for navigation.");
        }
    });
}


/**
 * Helper function to create HTML for a summary card.
 */
function createSummaryCard(title, value, iconClass, bgColor, textColor, iconColor, valueColor) {
    return `
        <div class="${bgColor} p-6 rounded-lg shadow-lg border border-[#EADDCB] hover:shadow-xl transition-shadow duration-300 ease-in-out">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium ${textColor} uppercase tracking-wider">${title}</p>
                    <p class="text-3xl font-bold ${valueColor}">${value}</p>
                </div>
                <div class="p-3 bg-opacity-20 bg-[#594423] rounded-full">
                    <i class="fas ${iconClass} ${iconColor} text-2xl"></i>
                </div>
            </div>
        </div>
    `;
}

/**
 * Renders charts on the dashboard.
 */
function renderCharts(chartData, userRole) {
    if (!dashboardChartsContainer) { // Check if the container exists
        console.error("Dashboard Charts Container not found.");
        return;
    }
    dashboardChartsContainer.innerHTML = ''; 

    // Destroy previous chart instances
    if (employeeStatusChartInstance) employeeStatusChartInstance.destroy();
    if (departmentDistributionChartInstance) departmentDistributionChartInstance.destroy();
    employeeStatusChartInstance = null;
    departmentDistributionChartInstance = null;


    const primaryChartColor = 'rgba(89, 68, 35, 0.7)'; 
    const secondaryChartColor = 'rgba(78, 59, 42, 0.7)'; 
    const tertiaryChartColor = 'rgba(247, 230, 202, 0.7)'; 
    const borderColor = 'rgba(78, 59, 42, 1)'; 
    const altColor1 = 'rgba(199, 149, 92, 0.7)'; 
    const altColor2 = 'rgba(156, 102, 68, 0.7)'; 

    if (!chartData || typeof chartData !== 'object') {
        console.warn("[Render] renderCharts: chartData is invalid or null.", chartData);
        dashboardChartsContainer.innerHTML = '<p class="col-span-full text-center text-gray-500 py-4">Chart data is unavailable.</p>';
        return;
    }

    if (userRole === 'System Admin' || userRole === 'HR Admin' || userRole === 'HR Staff') {
        // Chart 1: Employee Status Distribution
        if (chartData.employee_status_distribution && chartData.employee_status_distribution.data && chartData.employee_status_distribution.data.some(d => d > 0)) {
            const div1 = document.createElement('div');
            div1.className = 'bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA] min-h-[300px]'; 
            const canvas1 = document.createElement('canvas');
            canvas1.id = 'employeeStatusChart';
            div1.appendChild(canvas1);
            dashboardChartsContainer.appendChild(div1);
            
            employeeStatusChartInstance = new Chart(canvas1, {
                type: 'doughnut',
                data: {
                    labels: chartData.employee_status_distribution.labels || ['Active', 'Inactive'],
                    datasets: [{
                        label: 'Employee Status',
                        data: chartData.employee_status_distribution.data,
                        backgroundColor: [primaryChartColor, 'rgba(203, 102, 102, 0.7)'], 
                        borderColor: [borderColor, 'rgba(203, 102, 102, 1)'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { color: '#4E3B2A' } }, title: { display: true, text: 'Employee Status Distribution', color: '#4E3B2A', font: { size: 16, family: 'Cinzel' } } }
                }
            });
        }

        // Leave charts removed
        
        // New Chart: Employee Distribution by Department
        if (chartData.employee_distribution_by_department && chartData.employee_distribution_by_department.data && chartData.employee_distribution_by_department.data.length > 0) {
            const div3 = document.createElement('div');
            div3.className = 'bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA] min-h-[300px] lg:col-span-2'; 
            const canvas3 = document.createElement('canvas');
            canvas3.id = 'departmentDistributionChart';
            div3.appendChild(canvas3);
            dashboardChartsContainer.appendChild(div3);

            departmentDistributionChartInstance = new Chart(canvas3, {
                type: 'pie', 
                data: {
                    labels: chartData.employee_distribution_by_department.labels,
                    datasets: [{
                        label: 'Employees by Department',
                        data: chartData.employee_distribution_by_department.data,
                        backgroundColor: [primaryChartColor, secondaryChartColor, altColor1, altColor2, tertiaryChartColor, 'rgba(128, 128, 128, 0.7)'], 
                        borderColor: borderColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { color: '#4E3B2A' } }, title: { display: true, text: 'Active Employee Distribution by Department', color: '#4E3B2A', font: { size: 16, family: 'Cinzel' } } }
                }
            });
        }


    } else if (userRole === 'Employee') {
        // No employee leave charts
    }

    if (dashboardChartsContainer.children.length === 0) {
        dashboardChartsContainer.innerHTML = '<p class="col-span-full text-center text-gray-500 py-4">No charts available for this role or no data to display.</p>';
    }
}

/**
 * Handles API response, checking status and parsing JSON.
 */
async function handleApiResponse(response) {
    const contentType = response.headers.get("content-type");
    let data;

    const rawText = await response.text().catch(e => {
        console.error("[HandleAPIResponse] Error reading response text:", e);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}. Failed to read response body.`);
        }
        return "[[Failed to read response body]]"; 
    });
    console.log(`[HandleAPIResponse] Raw response text (Status ${response.status}):`, rawText.substring(0, 500) + (rawText.length > 500 ? "..." : ""));

    if (!response.ok) {
        let errorPayload = { error: `HTTP error! Status: ${response.status}` };
        if (contentType && contentType.includes("application/json")) {
            try {
                data = JSON.parse(rawText); 
                errorPayload.error = data.error || errorPayload.error;
                errorPayload.details = data.details; 
            } catch (jsonError) {
                console.error("[HandleAPIResponse] Failed to parse JSON error response:", jsonError);
                errorPayload.error += ` (Non-JSON error response received, see raw text log)`;
            }
        } else {
             errorPayload.error = `Server returned non-JSON error (Status: ${response.status}). See raw text log.`;
        }
        const error = new Error(errorPayload.error);
        error.details = errorPayload.details;
        throw error;
    }

    try {
        if (response.status === 204) { 
             console.log("[HandleAPIResponse] Received 204 No Content.");
             return { message: "Operation completed successfully (No Content)." };
        }
        if (!rawText || !rawText.trim()) {
             console.warn("[HandleAPIResponse] Received successful status, but response body was empty or whitespace.");
             return {}; 
        }
        try {
            data = JSON.parse(rawText);
            console.log("[HandleAPIResponse] Successfully parsed JSON data:", data);
            return data;
        } catch (jsonError) {
            console.error("[HandleAPIResponse] Failed to parse successful response as JSON:", jsonError);
            throw new Error("Received successful status, but failed to parse response as JSON. See raw text log.");
        }
    } catch (e) { 
        console.error("[HandleAPIResponse] Error processing successful response body:", e);
        throw e; 
    }
}
