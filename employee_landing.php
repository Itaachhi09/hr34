<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avalon - Employee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&display=swap" rel="stylesheet">
    <style>
        /* Apply Georgia to the entire body */
        body {
            font-family: 'Georgia', serif;
        }
        /* Apply Cinzel to header tags and elements with .font-header class */
        h1, h2, h3, h4, h5, h6, .font-header {
            font-family: 'Cinzel', serif;
        }
        /* Sidebar width definitions */
        .sidebar-collapsed { width: 85px; }
        .sidebar-expanded { width: 320px; }

        /* Hide text and arrow when collapsed */
        .sidebar-collapsed .menu-name span,
        .sidebar-collapsed .menu-name .arrow,
        .sidebar-collapsed .sidebar-logo-name { display: none; }

        /* Adjust icon margin when collapsed */
        .sidebar-collapsed .menu-name i.menu-icon { margin-right: 0; }

        /* Hide dropdowns when collapsed */
        .sidebar-collapsed .menu-drop { display: none; }

        /* Overlay for mobile view */
        .sidebar-overlay { background-color: rgba(0, 0, 0, 0.5); position: fixed; inset: 0; z-index: 40; display: none; }
        .sidebar-overlay.active { display: block; }

        /* Hide close button by default */
        .close-sidebar-btn { display: none; }

        /* Responsive adjustments */
        @media (max-width: 968px) {
            .sidebar { position: fixed; left: -100%; transition: left 0.3s ease-in-out; z-index: 50; } /* Ensure sidebar is above overlay */
            .sidebar.mobile-active { left: 0; }
            .main { margin-left: 0 !important; }
            .close-sidebar-btn { display: block; }
            /* Ensure logo name shows when mobile sidebar is active */
             .sidebar.mobile-active .sidebar-logo-name { display: block; }
        }

        /* Hover effect for menu items */
        .menu-name { position: relative; overflow: hidden; }
        .menu-name::after { content: ''; position: absolute; left: 0; bottom: 0; height: 2px; width: 0; background-color: #4E3B2A; transition: width 0.3s ease; }
        .menu-name:hover::after { width: 100%; }

        /* Ensure main content uses Georgia unless overridden */
        #main-content-area, #main-content-area p, #main-content-area label, #main-content-area span, #main-content-area td, #main-content-area button, #main-content-area select, #main-content-area input, #main-content-area textarea {
             font-family: 'Georgia', serif;
        }
        /* Ensure specific headers use Cinzel */
         #main-content-area h3, #main-content-area h4, #main-content-area h5 { /* Added h4, h5 */
             font-family: 'Cinzel', serif;
         }
         #page-title {
            font-family: 'Cinzel', serif;
         }
        
        /* Notification Dropdown Styles */
        .notification-item:hover {
            background-color: #f7fafc; /* Tailwind gray-100 */
        }
        .notification-dot {
            position: absolute;
            top: -2px; /* Adjust as needed */
            right: -2px; /* Adjust as needed */
            height: 8px;
            width: 8px;
            background-color: #ef4444; /* Tailwind red-500 */
            border-radius: 9999px; /* full */
            display: flex; /* To center the number if you add one */
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: white;
        }

        /* Generic Modal Styles */
        .modal {
            transition: opacity 0.25s ease;
        }
        .modal-content {
            transition: transform 0.25s ease;
        }
    </style>
    <script>
        window.DESIGNATED_ROLE = 'Employee';
        window.DESIGNATED_DEFAULT_SECTION = 'dashboard'; // Or 'myClaims', 'leaveRequests'
    </script>
    <script src="js/main.js" type="module" defer></script> <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
</head>
<body class="bg-[#FFF6E8]">

    <div id="app-container" class="flex min-h-screen w-full">
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <div class="sidebar sidebar-expanded fixed z-50 overflow-y-auto h-screen bg-white border-r border-[#F7E6CA] flex flex-col transition-width duration-300 ease-in-out">
            <div class="h-16 border-b border-[#F7E6CA] flex items-center justify-between px-4 space-x-2 sticky top-0 bg-white z-10 flex-shrink-0">
                <div class="flex items-center space-x-2 overflow-hidden">
                    <img src="logo.png" alt="HR System Logo" class="h-10 w-auto flex-shrink-0">
                    <img src="logo-name.png" alt="Avalon Logo Name" class="h-6 w-auto sidebar-logo-name">
                </div>
                <i id="close-sidebar-btn" class="fa-solid fa-xmark close-sidebar-btn font-bold text-xl cursor-pointer text-[#4E3B2A] hover:text-red-500 flex-shrink-0"></i>
            </div>

            <div class="side-menu px-4 py-6 flex-grow overflow-y-auto">
                <ul class="space-y-2">
                    <li class="menu-option">
                        <a href="#" id="dashboard-link" class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-tachometer-alt text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Dashboard</span>
                            </div>
                        </a>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('core-hr-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-users text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Core HR</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="core-hr-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="employees-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Employees</a></li>
                                <li><a href="#" id="documents-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Documents</a></li>
                                <li><a href="#" id="org-structure-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Org Structure</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('time-attendance-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-clock text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Time & Attendance</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="time-attendance-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="attendance-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Attendance Records</a></li>
                                <li><a href="#" id="timesheets-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Timesheets</a></li>
                                <li><a href="#" id="schedules-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Schedules</a></li>
                                <li><a href="#" id="shifts-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Shifts</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('payroll-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-money-check-dollar text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Payroll</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="payroll-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="payroll-runs-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Payroll Runs</a></li>
                                <li><a href="#" id="salaries-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Salaries</a></li>
                                <li><a href="#" id="bonuses-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Bonuses</a></li>
                                <li><a href="#" id="deductions-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Deductions</a></li>
                                <li><a href="#" id="payslips-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">View Payslips</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('claims-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-receipt text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Claims</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="claims-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="submit-claim-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Submit Claim</a></li>
                                <li><a href="#" id="my-claims-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">My Claims</a></li>
                                <li><a href="#" id="claims-approval-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Approvals</a></li>
                                <li><a href="#" id="claim-types-admin-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Claim Types (Admin)</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('leave-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-calendar-alt text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Leave Management</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="leave-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="leave-requests-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Leave Requests</a></li>
                                <li><a href="#" id="leave-balances-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Leave Balances</a></li>
                                <li><a href="#" id="leave-types-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Leave Types</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('compensation-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-hand-holding-dollar text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Compensation</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="compensation-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="comp-plans-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Compensation Plans</a></li>
                                <li><a href="#" id="salary-adjust-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Salary Adjustments</a></li>
                                <li><a href="#" id="incentives-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Incentives</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('analytics-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-chart-line text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Analytics</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="analytics-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="analytics-dashboards-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Dashboards</a></li>
                                <li><a href="#" id="analytics-reports-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Reports</a></li>
                                <li><a href="#" id="analytics-metrics-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">Metrics</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="menu-option">
                        <div class="menu-name flex justify-between items-center space-x-3 hover:bg-[#F7E6CA] px-4 py-3 rounded-lg transition duration-300 ease-in-out cursor-pointer" onclick="toggleDropdown('admin-dropdown', this)">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-shield-halved text-lg pr-4 menu-icon"></i>
                                <span class="text-sm font-medium">Admin</span>
                            </div>
                            <div class="arrow"><i class="bx bx-chevron-right text-lg font-semibold arrow-icon"></i></div>
                        </div>
                        <div id="admin-dropdown" class="menu-drop hidden flex-col w-full bg-[#F7E6CA] rounded-lg p-3 space-y-1 mt-1">
                            <ul class="space-y-1">
                                <li><a href="#" id="user-management-link" class="block px-3 py-1 text-sm text-gray-800 hover:bg-white rounded hover:text-[#4E3B2A]">User Management</a></li>
                                </ul>
                        </div>
                    </li>
                 </ul>
            </div>
        </div>

        <div class="main w-full md:ml-[320px] transition-all duration-300 ease-in-out flex flex-col min-h-screen">
            <nav class="h-16 w-full bg-white border-b border-[#F7E6CA] flex justify-between items-center px-6 py-4 sticky top-0 z-30 flex-shrink-0">
                <div class="left-nav flex items-center space-x-4 max-w-lg w-full">
                    <button aria-label="Toggle menu" class="menu-btn text-[#4E3B2A] focus:outline-none hover:bg-[#F7E6CA] p-2 rounded-full">
                        <i class="fa-solid fa-bars text-[#594423] text-xl"></i>
                    </button>
                    </div>
                <div class="right-nav flex items-center space-x-4 md:space-x-6">
                    <div class="relative">
                        <button id="notification-bell-button" aria-label="Notifications" class="text-[#4E3B2A] focus:outline-none relative hover:text-[#594423]">
                            <i class="fa-regular fa-bell text-xl"></i>
                            <span id="notification-dot" class="notification-dot hidden">
                                </span>
                        </button>
                        <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 md:w-96 bg-white rounded-md shadow-xl z-50 border border-gray-200">
                            <div class="p-3 border-b border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-700">Notifications</h4>
                            </div>
                            <div id="notification-list" class="max-h-80 overflow-y-auto">
                                <p class="p-4 text-sm text-gray-500 text-center">No new notifications.</p>
                            </div>
                            <div class="p-2 border-t border-gray-200 text-center">
                                <a href="#" id="view-all-notifications-link" class="text-xs text-blue-600 hover:underline">View All Notifications (Not Implemented)</a>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <button id="user-profile-button" type="button" class="flex items-center space-x-2 cursor-pointer group focus:outline-none">
                            <i class="fa-regular fa-user bg-[#594423] text-white px-3 py-2 rounded-lg text-lg group-hover:scale-110 transition-transform"></i>
                            <div class="info hidden md:flex flex-col py-1 text-left">
                                <h1 class="text-[#4E3B2A] font-semibold text-sm group-hover:text-[#594423]" id="user-display-name">Guest</h1>
                                <p class="text-[#594423] text-xs pl-1" id="user-display-role"></p>
                            </div>
                            <i class='bx bx-chevron-down text-[#4E3B2A] group-hover:text-[#594423] transition-transform hidden md:block' id="user-profile-arrow"></i>
                        </button>
                        <div id="user-profile-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 z-50">
                            <a href="#" id="view-profile-link" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-[#4E3B2A]">View Profile</a>
                            <a href="#" id="logout-link-nav" class="block px-4 py-2 text-sm text-gray-700 hover:bg-red-100 hover:text-red-600">Logout</a>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="px-6 py-8 lg:px-8 flex-grow">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold text-[#4E3B2A]" id="page-title">Loading...</h2>
                    <p class="text-gray-600" id="page-subtitle"></p>
                </div>

                <div id="main-content-area">
                    <p class="text-center py-4 text-gray-500">Loading content...</p>
                </div>
            </main>

            <footer class="text-center py-4 text-xs text-gray-500 border-t border-[#F7E6CA] flex-shrink-0">
                © 2025 Avalon HR Management System. All rights reserved.
            </footer>
        </div>

        <div id="timesheet-detail-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="modal-title-ts" role="dialog" aria-modal="true">
            <div id="modal-overlay-ts" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-3xl w-full p-6 space-y-4 overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center pb-3 border-b">
                     <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="modal-title-ts">
                        Timesheet Details (<span id="modal-timesheet-id"></span>)
                    </h3>
                    <button type="button" id="modal-close-btn-ts" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div><strong>Employee:</strong> <span id="modal-employee-name"></span></div>
                        <div><strong>Job Title:</strong> <span id="modal-employee-job"></span></div>
                        <div><strong>Period:</strong> <span id="modal-period-start"></span> to <span id="modal-period-end"></span></div>
                        <div><strong>Status:</strong> <span id="modal-status" class="font-semibold"></span></div>
                        <div><strong>Total Hours:</strong> <span id="modal-total-hours"></span></div>
                        <div><strong>Overtime Hours:</strong> <span id="modal-overtime-hours"></span></div>
                        <div><strong>Submitted:</strong> <span id="modal-submitted-date"></span></div>
                        <div><strong>Approved By:</strong> <span id="modal-approver-name"></span></div>
                    </div>
                    <hr>
                    <div>
                        <h4 class="text-md font-medium text-gray-800 mb-2 font-header">Attendance Entries</h4>
                        <div id="modal-attendance-entries" class="max-h-60 overflow-y-auto border rounded">
                            </div>
                    </div>
                </div>
                 <div class="pt-4 flex justify-end space-x-3 border-t mt-4">
                    <button type="button" id="modal-close-btn-ts-footer" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Close</button>
                </div>
            </div>
        </div>
        
        <div id="add-shift-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="add-shift-modal-title" role="dialog" aria-modal="true">
            <div id="add-shift-modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg w-full p-6 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="add-shift-modal-title">Add New Shift</h3>
                    <button type="button" id="close-add-shift-modal-btn" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <form id="add-shift-modal-form" class="space-y-4">
                    <div>
                        <label for="modal-shift-name" class="block text-sm font-medium text-gray-700 mb-1">Shift Name:</label>
                        <input type="text" id="modal-shift-name" name="shift_name" required placeholder="e.g., Day Shift" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label for="modal-start-time" class="block text-sm font-medium text-gray-700 mb-1">Start Time:</label>
                        <input type="time" id="modal-start-time" name="start_time" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label for="modal-end-time" class="block text-sm font-medium text-gray-700 mb-1">End Time:</label>
                        <input type="time" id="modal-end-time" name="end_time" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label for="modal-break-duration" class="block text-sm font-medium text-gray-700 mb-1">Break (mins):</label>
                        <input type="number" id="modal-break-duration" name="break_duration" min="0" placeholder="e.g., 60" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div class="pt-2 flex justify-end space-x-3">
                        <button type="button" id="cancel-add-shift-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A]">Add Shift</button>
                    </div>
                    <div id="add-shift-modal-status" class="text-sm text-center h-4 mt-2"></div>
                </form>
            </div>
        </div>

        <div id="create-timesheet-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="create-timesheet-modal-title" role="dialog" aria-modal="true">
            <div id="create-timesheet-modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg w-full p-6 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="create-timesheet-modal-title">Create New Timesheet Period</h3>
                    <button type="button" id="close-create-timesheet-modal-btn" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <form id="create-timesheet-modal-form" class="space-y-4">
                    <div>
                        <label for="modal-ts-employee-select" class="block text-sm font-medium text-gray-700 mb-1">Employee:</label>
                        <select id="modal-ts-employee-select" name="employee_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                            <option value="">Loading employees...</option>
                        </select>
                    </div>
                    <div>
                        <label for="modal-ts-period-start" class="block text-sm font-medium text-gray-700 mb-1">Period Start Date:</label>
                        <input type="date" id="modal-ts-period-start" name="period_start_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div>
                        <label for="modal-ts-period-end" class="block text-sm font-medium text-gray-700 mb-1">Period End Date:</label>
                        <input type="date" id="modal-ts-period-end" name="period_end_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                    </div>
                    <div class="pt-2 flex justify-end space-x-3">
                        <button type="button" id="cancel-create-timesheet-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A]">Create Timesheet</button>
                    </div>
                    <div id="create-timesheet-modal-status" class="text-sm text-center h-4 mt-2"></div>
                </form>
            </div>
        </div>
        
        <div id="add-schedule-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="add-schedule-modal-title" role="dialog" aria-modal="true">
            <div id="add-schedule-modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-xl w-full p-6 space-y-4"> <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="add-schedule-modal-title">Assign New Schedule</h3>
                    <button type="button" id="close-add-schedule-modal-btn" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <form id="add-schedule-modal-form" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="modal-schedule-employee-select" class="block text-sm font-medium text-gray-700 mb-1">Employee:</label>
                            <select id="modal-schedule-employee-select" name="employee_id" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                <option value="">Loading employees...</option>
                            </select>
                        </div>
                        <div>
                            <label for="modal-schedule-shift-select" class="block text-sm font-medium text-gray-700 mb-1">Shift (Optional):</label>
                            <select id="modal-schedule-shift-select" name="shift_id" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                                <option value="">-- No Specific Shift --</option>
                                </select>
                        </div>
                         <div>
                            <label for="modal-schedule-workdays" class="block text-sm font-medium text-gray-700 mb-1">Work Days:</label>
                            <input type="text" id="modal-schedule-workdays" name="workdays" placeholder="e.g., Mon-Fri" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                        <div>
                            <label for="modal-schedule-start-date" class="block text-sm font-medium text-gray-700 mb-1">Start Date:</label>
                            <input type="date" id="modal-schedule-start-date" name="start_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                        <div>
                            <label for="modal-schedule-end-date" class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional):</label>
                            <input type="date" id="modal-schedule-end-date" name="end_date" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end space-x-3">
                        <button type="button" id="cancel-add-schedule-modal-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A]">Add Schedule</button>
                    </div>
                    <div id="add-schedule-modal-status" class="text-sm text-center h-4 mt-2"></div>
                </form>
            </div>
        </div>
        
        <div id="employee-detail-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 modal" aria-labelledby="modal-title-employee" role="dialog" aria-modal="true">
            <div id="modal-overlay-employee" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="modal-content bg-white rounded-lg shadow-xl transform transition-all sm:max-w-3xl w-full p-6 space-y-4 overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-medium text-[#4E3B2A] font-header" id="modal-title-employee">Employee Details</h3>
                    <button type="button" id="modal-close-btn-employee" class="text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div id="employee-detail-content" class="mt-4 space-y-3 text-sm">
                    <p>Loading details...</p> </div>
                <div class="pt-4 flex justify-end space-x-3 border-t mt-4">
                    <button type="button" id="modal-close-btn-employee-footer" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Close</button>
                </div>
            </div>
        </div>

    </div> <script>
        // Sidebar toggle and dropdown logic (kept for UI interaction)
        const menuBtn = document.querySelector('.menu-btn');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main');
        const overlay = document.getElementById('sidebar-overlay');
        const closeBtn = document.getElementById('close-sidebar-btn');
        const body = document.body;

        function closeSidebar() {
            if(sidebar) sidebar.classList.remove('mobile-active');
            if(overlay) overlay.classList.remove('active');
            if(body) body.style.overflow = 'auto';
        }

        function openSidebar() {
            if(sidebar) sidebar.classList.add('mobile-active');
            if(overlay) overlay.classList.add('active');
            if(body) body.style.overflow = 'hidden';
        }

        function toggleSidebar() {
            if (!sidebar || !mainContent) return;
            const isMobile = window.innerWidth <= 968;
            if (isMobile) {
                sidebar.classList.add('sidebar-expanded');
                sidebar.classList.remove('sidebar-collapsed');
                if (sidebar.classList.contains('mobile-active')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                sidebar.classList.toggle('sidebar-collapsed');
                sidebar.classList.toggle('sidebar-expanded');
                if (sidebar.classList.contains('sidebar-collapsed')) {
                    mainContent.classList.remove('md:ml-[320px]');
                    mainContent.classList.add('md:ml-[85px]');
                } else {
                    mainContent.classList.remove('md:ml-[85px]');
                    mainContent.classList.add('md:ml-[320px]');
                }
            }
        }

        if(menuBtn) menuBtn.addEventListener('click', toggleSidebar);
        if(overlay) overlay.addEventListener('click', closeSidebar);
        if(closeBtn) closeBtn.addEventListener('click', closeSidebar);

        window.addEventListener('resize', () => {
             if (!sidebar || !mainContent) return;
            const isMobile = window.innerWidth <= 968;
            if (!isMobile) {
                closeSidebar(); 
                 if (sidebar.classList.contains('sidebar-collapsed')) {
                    mainContent.classList.remove('md:ml-[320px]');
                    mainContent.classList.add('md:ml-[85px]');
                } else {
                    sidebar.classList.add('sidebar-expanded');
                    sidebar.classList.remove('sidebar-collapsed');
                    mainContent.classList.remove('md:ml-[85px]');
                    mainContent.classList.add('md:ml-[320px]');
                }
             } else {
                 sidebar.classList.add('sidebar-expanded');
                 sidebar.classList.remove('sidebar-collapsed');
                 mainContent.classList.remove('md:ml-[85px]', 'md:ml-[320px]');
                 if (sidebar.classList.contains('mobile-active')) {
                     const nameLogo = sidebar.querySelector('.sidebar-logo-name');
                     if (nameLogo) nameLogo.style.display = 'block';
                 }
            }
        });

        // Global function for sidebar dropdowns, accessible by inline onclick
        window.toggleDropdown = function(dropdownId, element) {
            const dropdown = document.getElementById(dropdownId);
            const icon = element.querySelector('.arrow-icon'); 

            document.querySelectorAll('.menu-drop').forEach(d => {
                if (d.id !== dropdownId && !d.classList.contains('hidden')) {
                     d.classList.add('hidden');
                     const correspondingMenuName = d.previousElementSibling;
                     const correspondingIcon = correspondingMenuName ? correspondingMenuName.querySelector('.arrow-icon') : null;
                     if(correspondingIcon) {
                         correspondingIcon.classList.remove('bx-chevron-down');
                         correspondingIcon.classList.add('bx-chevron-right');
                     }
                }
            });

            if(dropdown) dropdown.classList.toggle('hidden');
            if(icon) {
                icon.classList.toggle('bx-chevron-right');
                icon.classList.toggle('bx-chevron-down');
            }
        }

        // Initial margin adjustment on load
        document.addEventListener('DOMContentLoaded', () => {
            if (!sidebar || !mainContent) return;
             if (window.innerWidth > 968) { 
                 if (sidebar.classList.contains('sidebar-collapsed')) {
                    mainContent.classList.remove('md:ml-[320px]');
                    mainContent.classList.add('md:ml-[85px]');
                } else { 
                    mainContent.classList.remove('md:ml-[85px]');
                    mainContent.classList.add('md:ml-[320px]');
                }
             } else { 
                 mainContent.classList.remove('md:ml-[85px]', 'md:ml-[320px]');
             }
        });
    </script>

</body>
</html>
