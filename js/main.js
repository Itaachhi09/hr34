/**
 * HR Management System - Main JavaScript Entry Point
 * Version: 1.23 (Handles role-based landing pages)
 * MODIFIED TO BYPASS LOGIN AND 2FA - FOR DEVELOPMENT/TESTING ONLY
 */

// --- Import display functions from module files ---
import { API_BASE_URL } from './utils.js';
// Dashboard
import { displayDashboardSection } from './dashboard/dashboard.js';
// Core HR
import { displayEmployeeSection } from './core_hr/employees.js';
import { displayDocumentsSection } from './core_hr/documents.js';
import { displayOrgStructureSection } from './core_hr/org_structure.js';
// Time & Attendance
import { displayShiftsSection } from './time_attendance/shifts.js';
import { displaySchedulesSection } from './time_attendance/schedules.js';
import { displayAttendanceSection } from './time_attendance/attendance.js';
import { displayTimesheetsSection, closeTimesheetModal } from './time_attendance/timesheets.js';
// Payroll
import { displaySalariesSection } from './payroll/salaries.js';
import { displayBonusesSection } from './payroll/bonuses.js';
import { displayDeductionsSection } from './payroll/deductions.js';
import { displayPayrollRunsSection } from './payroll/payroll_runs.js';
import { displayPayslipsSection } from './payroll/payslips.js';
// Claims
import {
    displaySubmitClaimSection,
    displayMyClaimsSection,
    displayClaimsApprovalSection,
    displayClaimTypesAdminSection
} from './claims/claims.js';
// Leave Management
import {
    displayLeaveTypesAdminSection,
    displayLeaveRequestsSection,
    displayLeaveBalancesSection
 } from './leave/leave.js';
 // Compensation Management
 import {
    displayCompensationPlansSection,
    displaySalaryAdjustmentsSection,
    displayIncentivesSection
 } from './compensation/compensation.js';
// Analytics functions
import {
    displayAnalyticsDashboardsSection,
    displayAnalyticsReportsSection,
    displayAnalyticsMetricsSection
 } from './analytics/analytics.js';
 // Admin
import { displayUserManagementSection } from './admin/user_management.js';
// User Profile
import { displayUserProfileSection } from './profile/profile.js';
// --- Import Notification Functions ---
import { initializeNotificationSystem, stopNotificationFetching, onNotificationDropdownOpen, onNotificationDropdownClose } from './notifications/notifications.js';


// --- Global Variables ---
window.currentUser = null;

// --- Wait for the DOM to be fully loaded ---
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded and parsed. Initializing HR System JS (LOGIN BYPASSED - Role Based)...");

    // --- DOM Elements ---
    // const loginContainer = document.getElementById('login-container'); // No longer a critical element for this flow
    const appContainer = document.getElementById('app-container');
    const mainContentArea = document.getElementById('main-content-area');
    const pageTitleElement = document.getElementById('page-title');
    const timesheetModal = document.getElementById('timesheet-detail-modal');
    const modalOverlayTs = document.getElementById('modal-overlay-ts');
    const modalCloseBtnTs = document.getElementById('modal-close-btn-ts');
    const userDisplayName = document.getElementById('user-display-name');
    const userDisplayRole = document.getElementById('user-display-role');

    const userProfileButton = document.getElementById('user-profile-button');
    const userProfileDropdown = document.getElementById('user-profile-dropdown');
    const userProfileArrow = document.getElementById('user-profile-arrow');
    const viewProfileLink = document.getElementById('view-profile-link');
    const logoutLinkNav = document.getElementById('logout-link-nav'); 
    const notificationBellButton = document.getElementById('notification-bell-button');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationDot = document.getElementById('notification-dot');
    const notificationListElement = document.getElementById('notification-list');

    const sidebarItems = {
        dashboard: document.getElementById('dashboard-link')?.closest('.menu-option'),
        coreHr: document.querySelector('[onclick*="core-hr-dropdown"]')?.closest('.menu-option'),
        employees: document.getElementById('employees-link')?.closest('li'),
        documents: document.getElementById('documents-link')?.closest('li'),
        orgStructure: document.getElementById('org-structure-link')?.closest('li'),
        timeAttendance: document.querySelector('[onclick*="time-attendance-dropdown"]')?.closest('.menu-option'),
        attendance: document.getElementById('attendance-link')?.closest('li'),
        timesheets: document.getElementById('timesheets-link')?.closest('li'),
        schedules: document.getElementById('schedules-link')?.closest('li'),
        shifts: document.getElementById('shifts-link')?.closest('li'),
        payroll: document.querySelector('[onclick*="payroll-dropdown"]')?.closest('.menu-option'),
        payrollRuns: document.getElementById('payroll-runs-link')?.closest('li'),
        salaries: document.getElementById('salaries-link')?.closest('li'),
        bonuses: document.getElementById('bonuses-link')?.closest('li'),
        deductions: document.getElementById('deductions-link')?.closest('li'),
        payslips: document.getElementById('payslips-link')?.closest('li'),
        claims: document.querySelector('[onclick*="claims-dropdown"]')?.closest('.menu-option'),
        submitClaim: document.getElementById('submit-claim-link')?.closest('li'),
        myClaims: document.getElementById('my-claims-link')?.closest('li'),
        claimsApproval: document.getElementById('claims-approval-link')?.closest('li'),
        claimTypesAdmin: document.getElementById('claim-types-admin-link')?.closest('li'),
        leave: document.querySelector('[onclick*="leave-dropdown"]')?.closest('.menu-option'),
        leaveRequests: document.getElementById('leave-requests-link')?.closest('li'),
        leaveBalances: document.getElementById('leave-balances-link')?.closest('li'),
        leaveTypes: document.getElementById('leave-types-link')?.closest('li'),
        compensation: document.querySelector('[onclick*="compensation-dropdown"]')?.closest('.menu-option'),
        compPlans: document.getElementById('comp-plans-link')?.closest('li'),
        salaryAdjust: document.getElementById('salary-adjust-link')?.closest('li'),
        incentives: document.getElementById('incentives-link')?.closest('li'),
        analytics: document.querySelector('[onclick*="analytics-dropdown"]')?.closest('.menu-option'),
        analyticsDashboards: document.getElementById('analytics-dashboards-link')?.closest('li'),
        analyticsReports: document.getElementById('analytics-reports-link')?.closest('li'),
        analyticsMetrics: document.getElementById('analytics-metrics-link')?.closest('li'),
        admin: document.querySelector('[onclick*="admin-dropdown"]')?.closest('.menu-option'),
        userManagement: document.getElementById('user-management-link')?.closest('li'),
    };

    // --- Error Handling for Missing Core Elements ---
     if (!mainContentArea || !pageTitleElement || !appContainer) { // Removed loginContainer from this check
        console.error("CRITICAL: Essential App DOM elements (app-container, main-content-area, page-title) not found!");
        document.body.innerHTML = '<p style="color: red; padding: 20px;">Application Error: Core UI elements are missing. Ensure app-container, main-content-area, and page-title IDs exist in your HTML.</p>';
        return;
    }
    if (!userProfileButton || !userProfileDropdown || !viewProfileLink || !logoutLinkNav || !userProfileArrow) {
        console.warn("Navbar profile elements not fully found. Profile dropdown might not work.");
    }
    if (!notificationBellButton || !notificationDropdown || !notificationDot || !notificationListElement) {
        console.warn("Notification elements not fully found. Notifications might not work.");
    }

    // --- Setup Modal Close Listeners ---
    if (timesheetModal && modalOverlayTs && modalCloseBtnTs) {
         if (typeof closeTimesheetModal === 'function') {
             modalCloseBtnTs.addEventListener('click', closeTimesheetModal);
             modalOverlayTs.addEventListener('click', closeTimesheetModal);
             const footerCloseBtn = document.getElementById('modal-close-btn-ts-footer');
             if (footerCloseBtn) footerCloseBtn.addEventListener('click', closeTimesheetModal);
         } else {
             console.warn("closeTimesheetModal function not found/imported from timesheets.js.");
             modalCloseBtnTs.addEventListener('click', () => timesheetModal.classList.add('hidden'));
             modalOverlayTs.addEventListener('click', () => timesheetModal.classList.add('hidden'));
             const footerCloseBtn = document.getElementById('modal-close-btn-ts-footer');
             if (footerCloseBtn) footerCloseBtn.addEventListener('click', () => timesheetModal.classList.add('hidden'));
         }
    }
    const employeeDetailModal = document.getElementById('employee-detail-modal');
    const employeeModalOverlay = document.getElementById('modal-overlay-employee');
    const employeeModalCloseBtnHeader = document.getElementById('modal-close-btn-employee');
    const employeeModalCloseBtnFooter = document.getElementById('modal-close-btn-employee-footer');

    function closeEmployeeDetailModal() {
        if (employeeDetailModal) employeeDetailModal.classList.add('hidden');
    }
    if(employeeDetailModal && employeeModalOverlay && employeeModalCloseBtnHeader && employeeModalCloseBtnFooter) {
        employeeModalOverlay.addEventListener('click', closeEmployeeDetailModal);
        employeeModalCloseBtnHeader.addEventListener('click', closeEmployeeDetailModal);
        employeeModalCloseBtnFooter.addEventListener('click', closeEmployeeDetailModal);
    }

    // --- Event Listeners for Navbar Profile Dropdown ---
    if (userProfileButton && userProfileDropdown && userProfileArrow) {
        userProfileButton.addEventListener('click', (event) => {
            event.stopPropagation();
            userProfileDropdown.classList.toggle('hidden');
            userProfileArrow.classList.toggle('bx-chevron-down');
            userProfileArrow.classList.toggle('bx-chevron-up');
            if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
                onNotificationDropdownClose(); 
            }
        });
    }

    if (viewProfileLink) {
        viewProfileLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (typeof displayUserProfileSection === 'function') {
                displayUserProfileSection();
                updateActiveSidebarLink(null); 
            } else {
                console.error("displayUserProfileSection function is not defined or imported.");
                 if(mainContentArea) mainContentArea.innerHTML = '<p class="text-red-500 p-4">Error: Profile display function not available.</p>';
            }
            if (userProfileDropdown) userProfileDropdown.classList.add('hidden');
            if (userProfileArrow) {
                userProfileArrow.classList.remove('bx-chevron-up');
                userProfileArrow.classList.add('bx-chevron-down');
            }
        });
    }
    
    // --- Logout Handler ---
    async function handleLogout(event) {
        event.preventDefault();
        console.log("Logout initiated (BYPASS MODE)...");
        window.currentUser = null;
        // stopNotificationFetching(); // Not strictly needed if notifications aren't fully working
        
        // Redirect to a neutral page or clear the UI
        // Since login is bypassed, "logging out" means resetting the UI to a non-user state.
        // A simple way is to redirect to the base path, which might be one of the landing pages.
        // Or, display a "logged out" message.
        if(appContainer) appContainer.style.display = 'none'; // Hide the main app
        document.body.innerHTML = '<div class="flex items-center justify-center min-h-screen"><p class="text-xl">You have been logged out. Please navigate to a landing page to re-enter (e.g., admin_landing.php or employee_landing.php).</p></div>';
        // Alternatively, if you have a generic index.php that redirects or shows a choice:
        // window.location.href = 'index.php'; 
    }

    if (logoutLinkNav) {
        logoutLinkNav.addEventListener('click', handleLogout);
    }

    // --- Event Listeners for Notification Bell ---
    if (notificationBellButton && notificationDropdown) {
        notificationBellButton.addEventListener('click', (event) => {
            event.stopPropagation();
            const isNowHidden = notificationDropdown.classList.toggle('hidden');
            if (userProfileDropdown && !userProfileDropdown.classList.contains('hidden')) {
                userProfileDropdown.classList.add('hidden');
                if(userProfileArrow) {
                    userProfileArrow.classList.remove('bx-chevron-up');
                    userProfileArrow.classList.add('bx-chevron-down');
                }
            }
            if (!isNowHidden) { onNotificationDropdownOpen(); } else { onNotificationDropdownClose(); }
        });
    }

    // Global click listener to close dropdowns
    document.addEventListener('click', (event) => {
        if (userProfileDropdown && userProfileButton && !userProfileButton.contains(event.target) && !userProfileDropdown.contains(event.target)) {
            if (!userProfileDropdown.classList.contains('hidden')) {
                userProfileDropdown.classList.add('hidden');
                if(userProfileArrow) {
                    userProfileArrow.classList.remove('bx-chevron-up');
                    userProfileArrow.classList.add('bx-chevron-down');
                }
            }
        }
        if (notificationDropdown && notificationBellButton && !notificationBellButton.contains(event.target) && !notificationDropdown.contains(event.target)) {
            if (!notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
                onNotificationDropdownClose(); 
            }
        }
    });

    // --- Sidebar Listener Setup ---
    const addClickListenerOnce = (element, handler) => {
        const targetElement = element?.querySelector('a'); // Assumes the clickable part is an <a> tag within the <li> or the menu-option itself if it's an <a>
        if (targetElement && !targetElement.hasAttribute('data-listener-added')) {
            targetElement.addEventListener('click', (e) => {
                e.preventDefault();
                const sidebar = document.querySelector('.sidebar');
                if(sidebar && sidebar.classList.contains('mobile-active')) { closeSidebar(); } // Assuming closeSidebar is defined globally or in this scope
                if (typeof handler === 'function') {
                    try { handler(); } catch (error) {
                         console.error(`Error executing handler for ${targetElement.id || 'sidebar link'}:`, error);
                         if(mainContentArea) { mainContentArea.innerHTML = `<p class="text-red-500 p-4">Error loading section. Please check the console.</p>`; }
                    }
                } else { console.error("Handler is not a function for element:", targetElement); }
                 updateActiveSidebarLink(targetElement);
            });
            targetElement.setAttribute('data-listener-added', 'true');
        } else if (!targetElement && handler && typeof handler === 'function') {
             // If the element itself is the <a> tag and was passed directly
             if(element && element.tagName === 'A' && !element.hasAttribute('data-listener-added')) {
                element.addEventListener('click', (e) => {
                    e.preventDefault();
                    const sidebar = document.querySelector('.sidebar');
                    if(sidebar && sidebar.classList.contains('mobile-active')) { closeSidebar(); }
                    if (typeof handler === 'function') { try { handler(); } catch (error) { console.error(`Error executing handler:`, error); } }
                    updateActiveSidebarLink(element);
                });
                element.setAttribute('data-listener-added', 'true');
             } else {
                console.warn(`Sidebar link element (<a>) not found within provided element for handler: ${handler.name}. Check ID/structure.`);
             }
        }
    };

    function attachSidebarListeners() {
        console.log("Attaching sidebar listeners...");
        if (sidebarItems.dashboard && !sidebarItems.dashboard.classList.contains('hidden')) {
            addClickListenerOnce(sidebarItems.dashboard.querySelector('a'), displayDashboardSection);
        }
        if (sidebarItems.employees && !sidebarItems.employees.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.employees.querySelector('a'), displayEmployeeSection);
        }
        if (sidebarItems.documents && !sidebarItems.documents.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.documents.querySelector('a'), displayDocumentsSection);
        }
        if (sidebarItems.orgStructure && !sidebarItems.orgStructure.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.orgStructure.querySelector('a'), displayOrgStructureSection);
        }
        if (sidebarItems.attendance && !sidebarItems.attendance.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.attendance.querySelector('a'), displayAttendanceSection);
        }
        if (sidebarItems.timesheets && !sidebarItems.timesheets.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.timesheets.querySelector('a'), displayTimesheetsSection);
        }
        if (sidebarItems.schedules && !sidebarItems.schedules.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.schedules.querySelector('a'), displaySchedulesSection);
        }
         if (sidebarItems.shifts && !sidebarItems.shifts.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.shifts.querySelector('a'), displayShiftsSection);
        }
        if (sidebarItems.payrollRuns && !sidebarItems.payrollRuns.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.payrollRuns.querySelector('a'), displayPayrollRunsSection);
        }
        if (sidebarItems.salaries && !sidebarItems.salaries.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.salaries.querySelector('a'), displaySalariesSection);
        }
        if (sidebarItems.bonuses && !sidebarItems.bonuses.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.bonuses.querySelector('a'), displayBonusesSection);
        }
        if (sidebarItems.deductions && !sidebarItems.deductions.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.deductions.querySelector('a'), displayDeductionsSection);
        }
        if (sidebarItems.payslips && !sidebarItems.payslips.classList.contains('hidden')) {
            addClickListenerOnce(sidebarItems.payslips.querySelector('a'), displayPayslipsSection);
        }
        if (sidebarItems.submitClaim && !sidebarItems.submitClaim.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.submitClaim.querySelector('a'), displaySubmitClaimSection);
        }
        if (sidebarItems.myClaims && !sidebarItems.myClaims.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.myClaims.querySelector('a'), displayMyClaimsSection);
        }
        if (sidebarItems.claimsApproval && !sidebarItems.claimsApproval.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.claimsApproval.querySelector('a'), displayClaimsApprovalSection);
        }
        if (sidebarItems.claimTypesAdmin && !sidebarItems.claimTypesAdmin.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.claimTypesAdmin.querySelector('a'), displayClaimTypesAdminSection);
        }
        if (sidebarItems.leaveRequests && !sidebarItems.leaveRequests.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.leaveRequests.querySelector('a'), displayLeaveRequestsSection);
        }
        if (sidebarItems.leaveBalances && !sidebarItems.leaveBalances.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.leaveBalances.querySelector('a'), displayLeaveBalancesSection);
        }
        if (sidebarItems.leaveTypes && !sidebarItems.leaveTypes.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.leaveTypes.querySelector('a'), displayLeaveTypesAdminSection);
        }
        if (sidebarItems.compPlans && !sidebarItems.compPlans.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.compPlans.querySelector('a'), displayCompensationPlansSection);
        }
        if (sidebarItems.salaryAdjust && !sidebarItems.salaryAdjust.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.salaryAdjust.querySelector('a'), displaySalaryAdjustmentsSection);
        }
        if (sidebarItems.incentives && !sidebarItems.incentives.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.incentives.querySelector('a'), displayIncentivesSection);
        }
        if (sidebarItems.analyticsDashboards && !sidebarItems.analyticsDashboards.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.analyticsDashboards.querySelector('a'), displayAnalyticsDashboardsSection);
        }
        if (sidebarItems.analyticsReports && !sidebarItems.analyticsReports.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.analyticsReports.querySelector('a'), displayAnalyticsReportsSection);
        }
        if (sidebarItems.analyticsMetrics && !sidebarItems.analyticsMetrics.classList.contains('hidden')) {
             addClickListenerOnce(sidebarItems.analyticsMetrics.querySelector('a'), displayAnalyticsMetricsSection);
        }
        if (sidebarItems.userManagement && !sidebarItems.userManagement.classList.contains('hidden')) {
            addClickListenerOnce(sidebarItems.userManagement.querySelector('a'), displayUserManagementSection);
        }
        console.log("Sidebar listeners attached/reattached.");
    }
    
    // --- UI Visibility Functions ---
    function showAppUI() {
        // loginContainer is assumed to be removed from HTML or hidden by default in landing pages
        if(appContainer) appContainer.style.display = 'flex';
    }

    // --- Update User Display in Navbar ---
    function updateUserDisplay(userData) {
        if (userData && userDisplayName && userDisplayRole) {
            userDisplayName.textContent = userData.full_name || 'User';
            userDisplayRole.textContent = userData.role_name || 'Role';
        } else {
             if(userDisplayName) userDisplayName.textContent = 'Guest';
             if(userDisplayRole) userDisplayRole.textContent = '';
        }
    }

    // --- Role-Based UI Access Control ---
    function updateSidebarAccess(roleName) {
        console.log(`Updating sidebar access for role: ${roleName}`);
        const allMenuItems = document.querySelectorAll('.sidebar .menu-option');
        const allSubMenuItems = document.querySelectorAll('.sidebar .menu-drop li');

        allMenuItems.forEach(item => item?.classList.add('hidden')); // Hide all main categories first
        allSubMenuItems.forEach(item => item?.classList.add('hidden')); // Hide all sub-items first

        const show = (element, elementName = 'Unknown') => {
            if (element) {
                element.classList.remove('hidden');
                element.style.display = ''; // Explicitly set display if it was 'none'
                // If it's a sub-item (li), ensure its parent menu-option (category) is also shown
                const parentMenuOption = element.closest('.menu-option');
                if(parentMenuOption) {
                    parentMenuOption.classList.remove('hidden');
                    parentMenuOption.style.display = '';
                }
            } else {
                 console.warn(`Attempted to show a non-existent sidebar element: ${elementName}`);
            }
        };
         const hide = (element, elementName = 'Unknown') => {
             if (element) {
                element.classList.add('hidden');
                // Note: Hiding a sub-item doesn't automatically hide its parent category
                // if other sub-items in that category are visible.
             }
        };

        // Always show dashboard
        show(sidebarItems.dashboard, 'Dashboard');

        switch (roleName) {
            case 'System Admin':
            case 'HR Admin': 
                console.log(`Executing ${roleName} access rules.`);
                show(sidebarItems.coreHr, 'Core HR');
                show(sidebarItems.employees, 'Employees');
                show(sidebarItems.documents, 'Documents');
                show(sidebarItems.orgStructure, 'Org Structure');
                show(sidebarItems.timeAttendance, 'Time & Attendance');
                show(sidebarItems.attendance, 'Attendance');
                show(sidebarItems.timesheets, 'Timesheets');
                show(sidebarItems.schedules, 'Schedules');
                show(sidebarItems.shifts, 'Shifts');
                show(sidebarItems.payroll, 'Payroll');
                show(sidebarItems.payrollRuns, 'Payroll Runs');
                show(sidebarItems.salaries, 'Salaries');
                show(sidebarItems.bonuses, 'Bonuses');
                show(sidebarItems.deductions, 'Deductions');
                show(sidebarItems.payslips, 'Payslips'); // Admins might need to view all
                show(sidebarItems.claims, 'Claims');
                // hide(sidebarItems.submitClaim, 'Submit Claim (Admin)'); // Admins typically don't submit their own via general UI
                // hide(sidebarItems.myClaims, 'My Claims (Admin)');
                show(sidebarItems.claimsApproval, 'Claims Approval');
                show(sidebarItems.claimTypesAdmin, 'Claim Types Admin');
                show(sidebarItems.leave, 'Leave');
                show(sidebarItems.leaveRequests, 'Leave Requests'); // Includes approvals
                show(sidebarItems.leaveBalances, 'Leave Balances'); // View all
                show(sidebarItems.leaveTypes, 'Leave Types');
                show(sidebarItems.compensation, 'Compensation');
                show(sidebarItems.compPlans, 'Comp Plans');
                show(sidebarItems.salaryAdjust, 'Salary Adjust');
                show(sidebarItems.incentives, 'Incentives');
                show(sidebarItems.analytics, 'Analytics'); 
                show(sidebarItems.analyticsDashboards, 'Analytics Dashboards');
                show(sidebarItems.analyticsReports, 'Analytics Reports');
                show(sidebarItems.analyticsMetrics, 'Analytics Metrics');
                if (roleName === 'System Admin') {
                    show(sidebarItems.admin, 'Admin');
                    show(sidebarItems.userManagement, 'User Management');
                } else { 
                    hide(sidebarItems.admin, 'Admin');
                }
                break;
            case 'Manager':
                console.log("Executing Manager access rules.");
                show(sidebarItems.claims, 'Claims');
                show(sidebarItems.submitClaim, 'Submit Claim'); 
                show(sidebarItems.myClaims, 'My Claims'); 
                show(sidebarItems.claimsApproval, 'Claims Approval'); 
                show(sidebarItems.leave, 'Leave');
                show(sidebarItems.leaveRequests, 'Leave Requests'); 
                show(sidebarItems.leaveBalances, 'Leave Balances'); 
                show(sidebarItems.timeAttendance, 'Time & Attendance');
                show(sidebarItems.attendance, 'Attendance'); 
                show(sidebarItems.timesheets, 'Timesheets'); 
                show(sidebarItems.payroll, 'Payroll');
                show(sidebarItems.payslips, 'Payslips'); 
                
                // Explicitly hide sections not for Managers
                hide(sidebarItems.coreHr, 'Core HR (Manager)');
                hide(sidebarItems.payrollRuns, 'Payroll Runs (Manager)');
                hide(sidebarItems.salaries, 'Salaries (Manager)');
                hide(sidebarItems.bonuses, 'Bonuses (Manager)');
                hide(sidebarItems.deductions, 'Deductions (Manager)');
                hide(sidebarItems.claimTypesAdmin, 'Claim Types Admin (Manager)');
                hide(sidebarItems.leaveTypes, 'Leave Types (Manager)');
                hide(sidebarItems.compensation, 'Compensation (Manager)');
                hide(sidebarItems.analytics, 'Analytics (Manager)'); 
                hide(sidebarItems.admin, 'Admin (Manager)');
                break;
            case 'Employee':
                console.log("Executing Employee access rules.");
                show(sidebarItems.claims, 'Claims');
                show(sidebarItems.submitClaim, 'Submit Claim');
                show(sidebarItems.myClaims, 'My Claims');
                show(sidebarItems.leave, 'Leave');
                show(sidebarItems.leaveRequests, 'Leave Requests');
                show(sidebarItems.leaveBalances, 'Leave Balances');
                show(sidebarItems.payroll, 'Payroll');
                show(sidebarItems.payslips, 'Payslips');

                // Explicitly hide sections not for Employees
                hide(sidebarItems.coreHr, 'Core HR (Employee)');
                hide(sidebarItems.timeAttendance, 'Time & Attendance (Employee)'); 
                hide(sidebarItems.payrollRuns, 'Payroll Runs (Employee)');
                hide(sidebarItems.salaries, 'Salaries (Employee)');
                hide(sidebarItems.bonuses, 'Bonuses (Employee)');
                hide(sidebarItems.deductions, 'Deductions (Employee)');
                hide(sidebarItems.claimsApproval, 'Claims Approval (Employee)');
                hide(sidebarItems.claimTypesAdmin, 'Claim Types Admin (Employee)');
                hide(sidebarItems.leaveTypes, 'Leave Types (Employee)');
                hide(sidebarItems.compensation, 'Compensation (Employee)');
                hide(sidebarItems.analytics, 'Analytics (Employee)');
                hide(sidebarItems.admin, 'Admin (Employee)');
                break;
            default: 
                console.log("Executing Default access rules (no specific role identified).");
                Object.values(sidebarItems).forEach(item => {
                    if (item && item !== sidebarItems.dashboard) hide(item);
                });
                document.querySelectorAll('.menu-drop').forEach(d => d.classList.add('hidden'));
                break;
        }
        console.log("Sidebar access update complete.");
    }

    // --- Navigation Logic for Notifications & Direct Section Access ---
    const sectionDisplayFunctions = {
        'dashboard': displayDashboardSection,
        'employees': displayEmployeeSection,
        'documents': displayDocumentsSection,
        'org-structure': displayOrgStructureSection,
        'attendance': displayAttendanceSection,
        'timesheets': displayTimesheetsSection,
        'schedules': displaySchedulesSection,
        'shifts': displayShiftsSection,
        'payroll-runs': displayPayrollRunsSection,
        'salaries': displaySalariesSection,
        'bonuses': displayBonusesSection,
        'deductions': displayDeductionsSection,
        'payslips': displayPayslipsSection,
        'submit-claim': displaySubmitClaimSection,
        'my-claims': displayMyClaimsSection,
        'claims-approval': displayClaimsApprovalSection,
        'claim-types-admin': displayClaimTypesAdminSection,
        'leave-requests': displayLeaveRequestsSection,
        'leave-balances': displayLeaveBalancesSection,
        'leave-types': displayLeaveTypesAdminSection,
        'comp-plans': displayCompensationPlansSection,
        'salary-adjust': displaySalaryAdjustmentsSection,
        'incentives': displayIncentivesSection,
        'analytics-dashboards': displayAnalyticsDashboardsSection,
        'analytics-reports': displayAnalyticsReportsSection,
        'analytics-metrics': displayAnalyticsMetricsSection,
        'user-management': displayUserManagementSection,
        'profile': displayUserProfileSection
    };

    window.navigateToSectionById = function(sectionId) {
        console.log(`[Main Navigation] Attempting to navigate to section: ${sectionId}`);
        const displayFunction = sectionDisplayFunctions[sectionId];
        const mainContentArea = document.getElementById('main-content-area'); 

        if (typeof displayFunction === 'function') {
            try {
                displayFunction(); // Call the function to render the section
                // Try to find the corresponding sidebar link to highlight it
                const sidebarLink = document.getElementById(`${sectionId}-link`);
                if (sidebarLink) {
                    updateActiveSidebarLink(sidebarLink);
                } else {
                     // Fallback for links that might not follow the id convention (e.g., dashboard-link)
                     const directSidebarItem = document.querySelector(`.sidebar a[id="${sectionId}-link"]`) || document.querySelector(`.sidebar a[href="#${sectionId}"]`);
                     if (directSidebarItem) {
                         updateActiveSidebarLink(directSidebarItem);
                     } else {
                        console.warn(`[Main Navigation] Sidebar link for sectionId '${sectionId}' not found for highlighting.`);
                        updateActiveSidebarLink(null); // Clear active link if none found
                     }
                }
            } catch (error) {
                console.error(`Error navigating to section '${sectionId}':`, error);
                if (mainContentArea) {
                    mainContentArea.innerHTML = `<p class="text-red-500 p-4">Error loading section: ${sectionId}. Please check the console.</p>`;
                }
            }
        } else {
            console.warn(`[Main Navigation] No display function found for sectionId: ${sectionId}`);
            if (mainContentArea) {
                 mainContentArea.innerHTML = `<p class="text-orange-500 p-4">Section '${sectionId}' not found or not yet implemented.</p>`;
            }
        }
    };
    
    // --- Function to update active sidebar link styling ---
    function updateActiveSidebarLink(clickedLinkElement) {
        // Clear previous active styles
        document.querySelectorAll('.sidebar .menu-name').forEach(el => {
            el.classList.remove('bg-[#EADDCB]', 'text-[#4E3B2A]', 'font-semibold', 'active-link-style');
        });
        document.querySelectorAll('.sidebar .menu-drop a').forEach(el => {
            el.classList.remove('bg-white', 'text-[#4E3B2A]', 'font-semibold', 'active-link-style');
        });

        if (!clickedLinkElement) return; // If null, just clear active styles

        let elementToStyle = clickedLinkElement;
        // Determine if it's a main menu item (menu-name) or a sub-menu item (inside menu-drop)
        const parentMenuDrop = clickedLinkElement.closest('.menu-drop');

        if (parentMenuDrop) { // It's a sub-menu item
            clickedLinkElement.classList.add('bg-white', 'text-[#4E3B2A]', 'font-semibold', 'active-link-style');
            // Also highlight its parent dropdown trigger
            const parentDropdownTrigger = parentMenuDrop.previousElementSibling;
            if (parentDropdownTrigger && parentDropdownTrigger.classList.contains('menu-name')) {
                parentDropdownTrigger.classList.add('bg-[#EADDCB]', 'text-[#4E3B2A]', 'font-semibold', 'active-link-style');
            }
        } else if (clickedLinkElement.classList.contains('menu-name') || clickedLinkElement.closest('.menu-name')) { // It's a main menu item or its child
            const mainMenuItem = clickedLinkElement.classList.contains('menu-name') ? clickedLinkElement : clickedLinkElement.closest('.menu-name');
            if(mainMenuItem) {
                mainMenuItem.classList.add('bg-[#EADDCB]', 'text-[#4E3B2A]', 'font-semibold', 'active-link-style');
            }
        }
    }

    // --- ROLE-BASED LANDING LOGIC ---
    // This section now reads window.DESIGNATED_ROLE set by the PHP landing page
    if (typeof window.DESIGNATED_ROLE !== 'undefined') {
        console.log(`Designated role found: ${window.DESIGNATED_ROLE}. Setting up mock user.`);
        let mockUser = {};
        let defaultSection = 'dashboard'; // A safe fallback

        // Define mock user details based on the role
        // IMPORTANT: Replace these with actual UserIDs and EmployeeIDs from your database
        // that correspond to these roles if you intend to test backend API calls
        // that require specific user/employee data.
        if (window.DESIGNATED_ROLE === 'System Admin') {
            mockUser = {
                user_id: 1, // Example: UserID for a System Admin
                employee_id: 1, // Example: EmployeeID for that Admin
                username: 'sysadmin_landed',
                full_name: 'System Admin (Landed)',
                role_id: 1, // RoleID for System Admin from your DB
                role_name: 'System Admin'
            };
            defaultSection = window.DESIGNATED_DEFAULT_SECTION || 'userManagement';
        } else if (window.DESIGNATED_ROLE === 'HR Admin') { // Added HR Admin as a distinct case
             mockUser = {
                user_id: 2, // Example: UserID for an HR Admin
                employee_id: 2, // Example: EmployeeID for that HR Admin
                username: 'hradmin_landed',
                full_name: 'HR Admin (Landed)',
                role_id: 2, // RoleID for HR Admin from your DB
                role_name: 'HR Admin'
            };
            defaultSection = window.DESIGNATED_DEFAULT_SECTION || 'dashboard';
        } else if (window.DESIGNATED_ROLE === 'Manager') { // Added Manager
             mockUser = {
                user_id: 4, // Example: UserID for a Manager
                employee_id: 4, // Example: EmployeeID for that Manager
                username: 'manager_landed',
                full_name: 'Manager (Landed)',
                role_id: 4, // RoleID for Manager from your DB
                role_name: 'Manager'
            };
            defaultSection = window.DESIGNATED_DEFAULT_SECTION || 'dashboard'; // Or 'claimsApproval'
        } else if (window.DESIGNATED_ROLE === 'Employee') {
            mockUser = {
                user_id: 3, // Example: UserID for an Employee
                employee_id: 3, // Example: EmployeeID for that Employee
                username: 'employee_landed',
                full_name: 'Employee (Landed)',
                role_id: 3, // RoleID for Employee from your DB
                role_name: 'Employee'
            };
            defaultSection = window.DESIGNATED_DEFAULT_SECTION || 'dashboard';
        } else {
            console.warn(`Unknown DESIGNATED_ROLE: ${window.DESIGNATED_ROLE}. Defaulting to basic view.`);
            mockUser = { role_name: 'Guest', full_name: 'Guest User' }; 
            defaultSection = 'dashboard'; 
        }
        
        window.currentUser = mockUser;
        showAppUI(); // Ensure app container is visible
        updateUserDisplay(window.currentUser);
        updateSidebarAccess(window.currentUser.role_name); // This will show/hide sidebar items
        attachSidebarListeners(); // Attach listeners to the now visible items
        
        // Navigate to the default section for the role
        if (typeof sectionDisplayFunctions[defaultSection] === 'function') {
            navigateToSectionById(defaultSection);
        } else {
            console.error(`Default section function for '${defaultSection}' not found. Loading dashboard.`);
            navigateToSectionById('dashboard');
        }
        // initializeNotificationSystem(); // Optional: if you want notifications for mock users

    } else {
        // This case should ideally not be reached if users always go via a landing page.
        // If index.php (or a similar generic entry point without DESIGNATED_ROLE) is accessed.
        console.warn("No DESIGNATED_ROLE found. Application may not load correctly. Consider redirecting from index.php or providing a default role setup for it.");
        window.currentUser = { role_name: 'Guest', full_name: 'Guest User (Default)' };
        showAppUI();
        updateUserDisplay(window.currentUser);
        updateSidebarAccess('Guest'); 
        attachSidebarListeners();
        navigateToSectionById('dashboard');
    }

    console.log("HR System JS Initialized (Role-Based Landing).");

}); // End DOMContentLoaded
