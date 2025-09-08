
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avalon</title>
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
    window.DESIGNATED_ROLE = 'System Admin';
    window.DESIGNATED_DEFAULT_SECTION = 'userManagement'; 
</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
    <script src="js/main.js" type="module" defer></script>
    
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
                    <h2 class="text-2xl font-semibold text-[#4E3B2A]" id="page-title">Dashboard</h2>
                    <p class="text-gray-600" id="page-subtitle">Welcome to Avalon HR System</p>
                </div>

                <div id="main-content-area">
                    <p class="text-center py-4 text-gray-500">Loading content...</p>
                </div>
            </main>

            <footer class="text-center py-4 text-xs text-gray-500 border-t border-[#F7E6CA] flex-shrink-0">
                © 2025 Avalon HR Management System. All rights reserved.
            </footer>
        </div>

        <script>
        // Minimal helpers for sidebar + dropdowns used by main.js
        (function(){
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const main = document.querySelector('.main');
            const menuBtn = document.querySelector('.menu-btn');
            const closeBtn = document.getElementById('close-sidebar-btn');

            window.closeSidebar = function closeSidebar(){
                if (sidebar) sidebar.classList.remove('mobile-active');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            };
            function openSidebar(){
                if (sidebar) sidebar.classList.add('mobile-active');
                if (overlay) overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            function toggleSidebar(){
                if (!sidebar || !main) return;
                const isMobile = window.innerWidth <= 968;
                if (isMobile){
                    sidebar.classList.add('sidebar-expanded');
                    sidebar.classList.remove('sidebar-collapsed');
                    if (sidebar.classList.contains('mobile-active')) { window.closeSidebar(); } else { openSidebar(); }
                } else {
                    sidebar.classList.toggle('sidebar-collapsed');
                    sidebar.classList.toggle('sidebar-expanded');
                    if (sidebar.classList.contains('sidebar-collapsed')) {
                        main.classList.remove('md:ml-[320px]');
                        main.classList.add('md:ml-[85px]');
                    } else {
                        main.classList.remove('md:ml-[85px]');
                        main.classList.add('md:ml-[320px]');
                    }
                }
            }
            if (menuBtn) menuBtn.addEventListener('click', toggleSidebar);
            if (overlay) overlay.addEventListener('click', window.closeSidebar);
            if (closeBtn) closeBtn.addEventListener('click', window.closeSidebar);

            window.toggleDropdown = function(dropdownId, element){
                const dropdown = document.getElementById(dropdownId);
                const icon = element ? element.querySelector('.arrow-icon') : null;
                document.querySelectorAll('.menu-drop').forEach(d => {
                    if (d.id !== dropdownId && !d.classList.contains('hidden')) {
                        d.classList.add('hidden');
                        const correspondingMenuName = d.previousElementSibling;
                        const correspondingIcon = correspondingMenuName ? correspondingMenuName.querySelector('.arrow-icon') : null;
                        if (correspondingIcon){
                            correspondingIcon.classList.remove('bx-chevron-down');
                            correspondingIcon.classList.add('bx-chevron-right');
                        }
                    }
                });
                if (dropdown) dropdown.classList.toggle('hidden');
                if (icon){
                    icon.classList.toggle('bx-chevron-right');
                    icon.classList.toggle('bx-chevron-down');
                }
            };

            // Ensure correct layout on load
            window.addEventListener('resize', () => {
                if (!sidebar || !main) return;
                const isMobile = window.innerWidth <= 968;
                if (!isMobile){
                    window.closeSidebar();
                    if (sidebar.classList.contains('sidebar-collapsed')){
                        main.classList.remove('md:ml-[320px]');
                        main.classList.add('md:ml-[85px]');
                    } else {
                        sidebar.classList.add('sidebar-expanded');
                        sidebar.classList.remove('sidebar-collapsed');
                        main.classList.remove('md:ml-[85px]');
                        main.classList.add('md:ml-[320px]');
                    }
                } else {
                    sidebar.classList.add('sidebar-expanded');
                    sidebar.classList.remove('sidebar-collapsed');
                    main.classList.remove('md:ml-[85px]', 'md:ml-[320px]');
                }
            });
        })();
        </script>


        

