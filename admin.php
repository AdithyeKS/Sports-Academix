<?php
// FILE: admin.php (Advanced Version with Good Search Bars and Print Option)
session_start();

// --- PREVENT BROWSER CACHING (SECURITY ADDITION) ---
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// --- SESSION CHECK ---
// Redirects to login page if the user is not logged in or is not an admin.
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: log.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Management System - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* This CSS provides the complete styling for the dashboard. */
        body { background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; color: #1f2937 } .h-screen { height: 100vh } .flex { display: flex } .flex-col { flex-direction: column } .flex-1 { flex: 1 1 0% } .flex-shrink-0 { flex-shrink: 0 } .overflow-hidden { overflow: hidden } .overflow-y-auto { overflow-y: auto } .overflow-x-auto { overflow-x: auto } .h-full { height: 100% } .p-4 { padding: 1rem } .p-6 { padding: 1.5rem } .px-2 { padding-left: .5rem; padding-right: .5rem } .py-1 { padding-top: .25rem; padding-bottom: .25rem } .px-3 { padding-left: .75rem; padding-right: .75rem } .py-2 { padding-top: .5rem; padding-bottom: .5rem } .px-4 { padding-left: 1rem; padding-right: 1rem } .py-3 { padding-top: .75rem; padding-bottom: .75rem } .px-6 { padding-left: 1.5rem; padding-right: 1.5rem } .py-4 { padding-top: 1rem; padding-bottom: 1rem } .mt-1 { margin-top: .25rem } .mr-2 { margin-right: .5rem } .mb-2 { margin-bottom: .5rem } .mr-3 { margin-right: .75rem } .mr-4 { margin-right: 1rem } .mt-4 { margin-top: 1rem } .mb-4 { margin-bottom: 1rem } .mt-6 { margin-top: 1.5rem } .mb-6 { margin-bottom: 1.5rem } .mb-8 { margin-bottom: 2rem } .mt-auto { margin-top: auto } .space-x-2>*:not(:first-child) { margin-left: .5rem } .space-x-4>*:not(:first-child) { margin-left: 1rem } .space-y-4>*:not(:first-child) { margin-top: 1rem } .gap-4 { gap: 1rem } .gap-6 { gap: 1.5rem } .items-center { align-items: center } .items-start { align-items: flex-start } .justify-between { justify-content: space-between } .grid { display: grid } .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) } .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) } @media (min-width:768px) { .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) } } @media (min-width:1024px) { .lg\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) } .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)) } } .w-10 { width: 2.5rem } .h-10 { height: 2.5rem } .w-16 { width: 4rem } .h-16 { height: 4rem } .w-64 { width: 16rem } .min-w-full { min-width: 100% } .text-xs { font-size: .75rem } .text-sm { font-size: .875rem } .text-lg { font-size: 1.125rem } .text-xl { font-size: 1.25rem } .text-2xl { font-size: 1.5rem } .text-3xl { font-size: 1.875rem } .font-bold { font-weight: 700 } .font-semibold { font-weight: 600 } .font-medium { font-weight: 500 } .uppercase { text-transform: uppercase } .tracking-wider { letter-spacing: .05em } .text-left { text-align: left } .text-center { text-align: center } .text-right { text-align: right } .whitespace-nowrap { white-space: nowrap } .bg-black { background-color: #000 } .bg-white { background-color: #fff } .bg-gray-50 { background-color: #f9fafb } .bg-blue-50 { background-color: #eff6ff } .bg-blue-100 { background-color: #dbeafe } .bg-blue-600 { background-color: #2563eb } .bg-blue-900 { background-color: #1e3a8a } .bg-green-50 { background-color: #f0fdf4 } .bg-green-100 { background-color: #dcfce7 } .bg-green-500 { background-color: #22c55e } .bg-green-600 { background-color: #16a34a } .bg-purple-50 { background-color: #faf5ff } .bg-purple-100 { background-color: #f3e8ff } .bg-yellow-50 { background-color: #fefce8 } .bg-yellow-100 { background-color: #fef08a } .bg-red-100 { background-color: #fee2e2 } .bg-red-500 { background-color: #ef4444 } .bg-red-600 { background-color: #dc2626 } .bg-gray-100 { background-color: #f3f4f6 } .bg-gray-400 { background-color: #9ca3af; } .text-white { color: #fff } .text-gray-500 { color: #6b7281 } .text-gray-600 { color: #4b5563 } .text-gray-800 { color: #1f2937 } .text-blue-500 { color: #3b82f6 } .text-blue-600 { color: #2563eb; } .text-blue-800 { color: #1e40af } .text-green-600 { color: #16a34a } .text-green-800 { color: #166534 } .text-purple-800 { color: #5b21b6 } .text-yellow-800 { color: #854d0e } .text-red-600 { color: #dc2626 } .text-red-800 { color: #991b1b } .rounded-md { border-radius: .375rem } .rounded-lg { border-radius: .5rem } .rounded-full { border-radius: 9999px } .shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -2px rgba(0, 0, 0, .1) } .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, .05) } .divide-y>*:not(:first-child) { border-top: 1px solid #e5e7eb } .cursor-pointer { cursor: pointer } .cursor-not-allowed { cursor: not-allowed; } .transition { transition: all .15s ease-in-out } .hover\:bg-blue-700:hover { background-color: #1d4ed8 } .hover\:bg-blue-100:hover { background-color: #dbeafe } .hover\:bg-green-100:hover { background-color: #dcfce7 } .hover\:bg-purple-100:hover { background-color: #f3e8ff } .hover\:bg-yellow-100:hover { background-color: #fef08a } .hover\:bg-red-700:hover { background-color: #b91c1c } .hover\:text-blue-900:hover { color: #1e3a8a } .hover\:text-red-900:hover { color: #7f1d1d } .sidebar { transition: all .3s ease-in-out } .dashboard-card { transition: all .3s ease-in-out } .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, .1) } .tab-content { display: none } .tab-content.active { display: block; animation: fadeIn .5s ease-in-out } @keyframes fadeIn { from { opacity: 0; transform: translateY(10px) } to { opacity: 1; transform: translateY(0) } } .modal-hidden { display: none } .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, .5); z-index: 999 } .modal-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 1.5rem; border-radius: .5rem; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, .1); z-index: 1000; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto } .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 1rem } .modal-header .close-btn { font-size: 1.5rem; font-weight: 700; line-height: 1; border: none; background: none; cursor: pointer } .modal-body form label { display: block; margin-bottom: .5rem; font-weight: 600; color: #374151 } .modal-body form input, .modal-body form select, .modal-body form textarea { width: 100%; box-sizing: border-box; padding: .5rem .75rem; border: 1px solid #d1d5db; border-radius: .375rem; margin-bottom: 1rem } .modal-body form button[type=submit] { width: 100%; padding: .75rem; border-radius: .375rem; color: #fff; background-color: #2563eb; cursor: pointer; font-weight: 600; transition: background-color .2s } .modal-body form button[type=submit]:hover { background-color: #1d4ed8 } a.logout-link { display: block; text-decoration: none; color: white; } .status-tag { padding: .25rem .75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 500; text-transform: capitalize; } .status-pending { background-color: rgba(234, 179, 8, 0.2); color: #ca8a04; } .status-reviewed { background-color: rgba(59, 130, 246, 0.2); color: #2563eb; } .status-resolved { background-color: rgba(16, 185, 129, 0.2); color: #059669; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #e0e0e0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .toast-notification {
            background-color: #333;
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateX(100%);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            min-width: 250px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .toast-notification.show {
            opacity: 1;
            transform: translateX(0);
        }
        .toast-notification.success { background-color: #16a34a; }
        .toast-notification.error { background-color: #dc2626; }
        .toast-notification.info { background-color: #2563eb; }
        .toast-notification i { margin-right: 0.5rem; }

        /* Loading Overlay */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1500;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.3s ease-out;
        }
        #loading-overlay.show {
            visibility: visible;
            opacity: 1;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Dashboard Card Icons */
        .dashboard-card i {
            font-size: 2.5rem; /* Larger icons for impact */
            opacity: 0.6; /* Slightly subdued icon color */
        }
        /* Specific icon colors */
        .dashboard-card .fa-users { color: #3b82f6; } /* Blue for users */
        .dashboard-card .fa-futbol { color: #22c55e; } /* Green for sports */
        .dashboard-card .fa-calendar-alt { color: #8b5cf6; } /* Purple for events */
        .dashboard-card .fa-clipboard-list { color: #eab308; } /* Yellow for registrations */

        /* Team Members Modal specific styles */
        #teamMembersModal .modal-container {
            max-width: 800px; /* Wider for team members table */
        }
        /* Ensure inputs within modals also get proper styling */
        .modal-body select, .modal-body input[type="text"], .modal-body input[type="number"], .modal-body input[type="date"], .modal-body input[type="datetime-local"] {
            margin-bottom: 0.75rem; /* Adjust margin for better spacing within form groups */
        }
        .modal-body label {
            margin-bottom: 0.25rem;
        }
        #addTeamMemberForm div {
            margin-bottom: 0; /* Remove default margin from div if flex item */
        }

        /* NEW: Search bar styling */
        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%; /* Or a specific width */
            max-width: 300px; /* Limit width */
            flex-shrink: 1; /* Allow to shrink */
            flex-grow: 1; /* Allow to grow */
            order: 0; /* Default order */
        }
        /* For the headers with buttons, place search bar first */
        .tab-content > div.flex.justify-between.items-center.mb-6 {
            flex-wrap: wrap; /* Allow items to wrap on smaller screens */
            gap: 1rem; /* Space between items in the header */
        }
        .tab-content > div.flex.justify-between.items-center.mb-6 > .search-input-wrapper {
            order: -1; /* Place search bar first */
            width: auto; /* Allow auto width within flex */
        }
        /* Ensure buttons and other elements align nicely */
        .tab-content > div.flex.justify-between.items-center.mb-6 > button {
            flex-shrink: 0;
        }


        .search-input-wrapper input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem; /* Left padding for icon */
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            color: #1f2937;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .search-input-wrapper input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .search-input-wrapper .search-icon {
            position: absolute;
            left: 0.75rem;
            color: #6b7281;
            pointer-events: none; /* Make icon unclickable */
        }

        .search-input-wrapper .clear-search-btn {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: #6b7281;
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            line-height: 1;
            display: none; /* Hidden by default, show when input has value */
            z-index: 10; /* Ensure it's above the input */
        }

        /* Show clear button when input has text */
        .search-input-wrapper input:not(:placeholder-shown) + .clear-search-btn {
            display: block;
        }

        /* Adjust input padding when clear button is visible to prevent overlap */
        .search-input-wrapper input:not(:placeholder-shown) {
            padding-right: 2.5rem; /* Ensure space for the clear button */
        }

        .disabled-button {
            background-color: #9ca3af !important; /* Tailwind gray-400 */
            cursor: not-allowed !important;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <div class="sidebar w-64 bg-black text-white p-4 flex flex-col flex-shrink-0">
            <div class="p-4 flex items-center space-x-2">
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI0NSIgZmlsbD0iIzFlM2E4YSIvPjxwYXRoIGQ9Ik0zg3MEw1MCAzMGwzg0MEg3MGwtMjAtMjAtMjAgMjBIMzBaIiBmaWxsPSIjZmZmIi8+PC9zdmc+" alt="Logo" class="w-16 h-16">
                <h1 class="text-xl font-bold">Sports Academix</h1>
            </div>
            <nav class="mt-6 flex flex-col flex-1">
                <div>
                    <div class="px-4 py-3 rounded-md bg-blue-900 cursor-pointer tab-button" data-tab="dashboard"><i class="fas fa-tachometer-alt fa-fw mr-2"></i> Dashboard</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="users"><i class="fas fa-users fa-fw mr-2"></i> User Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="departments"><i class="fas fa-building fa-fw mr-2"></i> Department Mngmt</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="sports"><i class="fas fa-futbol fa-fw mr-2"></i> Sports Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="events"><i class="fas fa-calendar-alt fa-fw mr-2"></i> Event Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="upcoming"><i class="fas fa-calendar-check fa-fw mr-2"></i> Upcoming Matches</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="registrations"><i class="fas fa-clipboard-list fa-fw mr-2"></i> Registrations</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="appeals"><i class="fas fa-gavel fa-fw mr-2"></i> Match Appeals</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="teams"><i class="fas fa-people-group fa-fw mr-2"></i> Team Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="scores"><i class="fas fa-star fa-fw mr-2"></i> Score Management</div>
                    <div class="px-4 py-3 rounded-md hover:bg-blue-700 cursor-pointer tab-button" data-tab="feedback"><i class="fas fa-comments fa-fw mr-2"></i> User Feedback</div>
                </div>
                <a href="logout.php" class="logout-link mt-auto px-4 py-3 rounded-md hover:bg-red-700 cursor-pointer transition"><i class="fas fa-sign-out-alt fa-fw mr-2"></i><span>Logout</span></a>
            </nav>
        </div>
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm p-4 flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-800" id="page-title">Dashboard</h2>
                <div class="flex items-center space-x-4"><span>Admin</span></div>
            </header>
            <main class="flex-1 overflow-y-auto p-6">
                
                <div id="dashboard" class="tab-content active"></div>
                
                <!-- User Management Tab -->
                <div id="users" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">User Management</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="usersFilter" placeholder="Search users..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="usersFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Department Management Tab -->
                <div id="departments" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Department Management</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="departmentsFilter" placeholder="Search departments..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="departmentsFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                        <button id="addDepartmentBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-plus mr-2"></i> Add Department</button>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="departmentsTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Sports Management Tab -->
                <div id="sports" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Sports Management</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="sportsFilter" placeholder="Search sports..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="sportsFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                        <button id="addSportBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-plus mr-2"></i> Add Sport</button>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Players</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points (W/D/L)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sportsTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Event Management Tab -->
                <div id="events" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Event Management</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="eventsFilter" placeholder="Search events..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="eventsFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                        <button id="addEventBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-calendar-plus mr-2"></i> Create Event</button>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teams</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="eventsTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Upcoming Match Management Tab -->
                <div id="upcoming" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Upcoming Match Management</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="upcomingFilter" placeholder="Search upcoming matches..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="upcomingFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teams</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="upcomingTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Registrations Tab Content -->
                <div id="registrations" class="tab-content">
                    <h2 class="text-xl font-semibold mb-4">Registration Management</h2>

                    <div class="bg-white rounded-lg shadow p-4 mb-6">
                        <h3 class="font-semibold text-lg mb-3">Filter Registrations</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 items-end">
                            <div>
                                <label for="registrationFilter" class="block text-sm font-medium text-gray-700">General Search</label>
                                <div class="search-input-wrapper" style="max-width: none;"> <!-- Override max-width for this specific filter -->
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="registrationFilter" placeholder="Name, Sport, Dept/Team..." class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                    <button type="button" class="clear-search-btn" data-filter-id="registrationFilter"><i class="fas fa-times-circle"></i></button>
                                </div>
                            </div>
                            <div>
                                <label for="filterSportId" class="block text-sm font-medium text-gray-700">Filter by Sport</label>
                                <select id="filterSportId" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                    <option value="">All Sports</option>
                                    <!-- Options populated by JavaScript -->
                                </select>
                            </div>
                            <div>
                                <label for="filterRegType" class="block text-sm font-medium text-gray-700">Filter by Type</label>
                                <select id="filterRegType" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                                    <option value="">All Types</option>
                                    <option value="Team">Team</option>
                                    <option value="Department">Department</option>
                                </select>
                            </div>
                            <div>
                                <label for="filterFromDate" class="block text-sm font-medium text-gray-700">Registered From</label>
                                <input type="date" id="filterFromDate" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label for="filterToDate" class="block text-sm font-medium text-gray-700">Registered To</label>
                                <input type="date" id="filterToDate" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                            </div>
                            <div class="flex space-x-2">
                                <button id="clearRegistrationFiltersBtn" class="bg-gray-400 text-white px-4 py-2 rounded-md hover:bg-gray-500 w-full"><i class="fas fa-redo mr-2"></i> Clear Filters</button>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <button class="registration-sub-tab-button bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mr-2" data-reg-tab="pending">Pending</button>
                                <button class="registration-sub-tab-button bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 mr-2" data-reg-tab="approved">Approved</button>
                                <button class="registration-sub-tab-button bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300" data-reg-tab="rejected">Rejected</button>
                            </div>
                            <button id="printRegistrationsBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-print mr-2"></i> Print Current View</button>
                        </div>
                    </div>


                    <div id="pendingRegistrationsSection" class="registration-sub-tab-content">
                        <h3 class="text-lg font-semibold mb-4">Pending Registrations</h3>
                        <div class="bg-white rounded-lg shadow overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration For</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered At</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingRegistrationsTableBody" class="divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="approvedRegistrationsSection" class="registration-sub-tab-content modal-hidden">
                        <h3 class="text-lg font-semibold mb-4">Approved Registrations</h3>
                        <div class="bg-white rounded-lg shadow overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration For</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered At</th>
                                    </tr>
                                </thead>
                                <tbody id="approvedRegistrationsTableBody" class="divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="rejectedRegistrationsSection" class="registration-sub-tab-content modal-hidden">
                        <h3 class="text-lg font-semibold mb-4">Rejected Registrations</h3>
                        <div class="bg-white rounded-lg shadow overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration For</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered At</th>
                                    </tr>
                                </thead>
                                <tbody id="rejectedRegistrationsTableBody" class="divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Match Appeals Tab -->
                <div id="appeals" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Match Result Appeals</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="appealsFilter" placeholder="Search appeals..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="appealsFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appellant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="appealsTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Team Management Tab -->
                <div id="teams" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Team Management</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="teamsFilter" placeholder="Search teams..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="teamsFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                        <button id="addTeamBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-plus mr-2"></i> Create Team</button>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sport</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Players</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="teamsTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Score Management Tab -->
                <div id="scores" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Score Management</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="scoresFilter" placeholder="Search matches..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="scoresFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="scoresTableBody" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>

                <!-- User Feedback Tab -->
                <div id="feedback" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">User Feedback</h2>
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="feedbackFilter" placeholder="Search feedback..." class="text-gray-800">
                            <button type="button" class="clear-search-btn" data-filter-id="feedbackFilter"><i class="fas fa-times-circle"></i></button>
                        </div>
                    </div>
                    <div id="feedbackContainer" class="space-y-4"></div>
                </div>

            </main>
        </div>
    </div>

    <!-- Main Modal (for Add/Edit forms) -->
    <div id="mainModal" class="modal-hidden">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modalTitle" class="text-xl font-semibold">Modal Title</h3>
                <button id="closeModalBtn" class="close-btn">×</button>
            </div>
            <div id="modalBody" class="modal-body"></div>
        </div>
    </div>

    <!-- Team Members Management Modal (New Modal) -->
    <div id="teamMembersModal" class="modal-hidden">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="teamMembersModalTitle" class="text-xl font-semibold">Manage Team Members</h3>
                <button id="closeTeamMembersModalBtn" class="close-btn">×</button>
            </div>
            <div id="teamMembersModalBody" class="modal-body">
                <h4 class="text-lg font-medium mb-3">Add New Member</h4>
                <form id="addTeamMemberForm" class="flex flex-col md:flex-row gap-4 items-end mb-6 p-4 bg-gray-50 rounded-lg">
                    <input type="hidden" id="addMemberTeamId" name="team_id">
                    <div class="flex-1 w-full">
                        <label for="addMemberUserId" class="block text-sm font-medium text-gray-700">Select User</label>
                        <select id="addMemberUserId" name="user_id" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></select>
                    </div>
                    <div class="flex-1 w-full">
                        <label for="addMemberRole" class="block text-sm font-medium text-gray-700">Role in Team</label>
                        <input type="text" id="addMemberRole" name="role_in_team" value="Player" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex-shrink-0 w-full md:w-auto"><i class="fas fa-plus mr-2"></i> Add Member</button>
                </form>

                <div class="flex justify-between items-center mb-3 mt-6">
                    <h4 class="text-lg font-medium">Current Members</h4>
                    <button id="printTeamMembersBtn" class="bg-gray-400 text-white px-4 py-2 rounded-md hover:bg-gray-500"><i class="fas fa-print mr-2"></i> Print List</button>
                </div>
                <div class="bg-white rounded-lg shadow overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teamMembersTableBody" class="divide-y divide-gray-200">
                            <!-- Team members will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- Global Toast Notifications Container -->
    <div id="toast-container"></div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="modal-hidden">
        <div class="spinner"></div>
    </div>

    <script>
    const sanitizeHTML = (text) => {
        if (text === null || typeof text === 'undefined') return '';
        const temp = document.createElement('div');
        temp.textContent = String(text);
        return temp.innerHTML;
    };

    // --- Loading Overlay Functions ---
    const loadingOverlay = document.getElementById('loading-overlay');
    const showLoading = () => loadingOverlay.classList.add('show');
    const hideLoading = () => loadingOverlay.classList.remove('show');

    // --- Toast Notification Functions ---
    const toastContainer = document.getElementById('toast-container');
    const showToast = (message, type = 'info', duration = 3000) => {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        
        let iconHtml = '';
        if (type === 'success') iconHtml = '<i class="fas fa-check-circle"></i>';
        else if (type === 'error') iconHtml = '<i class="fas fa-times-circle"></i>';
        else if (type === 'info') iconHtml = '<i class="fas fa-info-circle"></i>';

        toast.innerHTML = `${iconHtml}<span>${sanitizeHTML(message)}</span>`;
        toastContainer.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);

        // Animate out and remove
        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove());
        }, duration);
    };

    const apiRequest = async (endpoint, action, method = 'GET', body = null, queryParams = null) => {
        showLoading(); // Show loading indicator
        let url = `api.php?endpoint=${endpoint}&action=${action}`;
        if (queryParams) {
            url += '&' + new URLSearchParams(queryParams).toString();
        }
        const options = { method, headers: {} };
        if (method !== 'GET' && body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        try {
            const response = await fetch(url, options);
            const text = await response.text(); // Get raw text to check for JSON parsing errors
            if (!response.ok) {
                try { 
                    const result = JSON.parse(text); 
                    if (response.status === 401 || response.status === 403) { // Unauthorized or Forbidden
                        showToast(result.message || 'Session expired or unauthorized. Redirecting to login.', 'error', 2000);
                        setTimeout(() => window.location.href = 'log.php', 2000);
                    } else {
                        throw new Error(result.message || `HTTP error ${response.status}`); 
                    }
                } catch (e) { 
                    // If JSON parsing fails, the raw text might contain useful debug info.
                    throw new Error(`Server returned non-JSON error: ${response.status}. Response: ${text.substring(0, 200)}...`); 
                }
            }
            const result = JSON.parse(text);
            if (result.status !== 'success') { throw new Error(result.message || 'An unknown API error occurred'); }
            return result;
        } catch (error) {
            console.error(`API Request Error on ${url}:`, error);
            showToast(`API Error: ${error.message}`, 'error');
            throw error;
        } finally {
            hideLoading(); // Hide loading indicator
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        let db = { 
            stats: {}, users: [], allUsers: [], sports: [], departments: [], events: [], registrations: [], teams: [], feedback: [], appeals: [],
            approvedRegistrations: [], rejectedRegistrations: [], upcomingEvents: [], teamMembers: [], allTeamMembers: [] // Added rejectedRegistrations
        };
        const pageTitle = document.getElementById('page-title');
        const mainModal = document.getElementById('mainModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        const closeModalBtn = document.getElementById('closeModalBtn');

        // Team Members Modal elements
        const teamMembersModal = document.getElementById('teamMembersModal');
        const teamMembersModalTitle = document.getElementById('teamMembersModalTitle');
        const teamMembersModalBody = document.getElementById('teamMembersModalBody');
        const closeTeamMembersModalBtn = document.getElementById('closeTeamMembersModalBtn');
        let currentTeamIdForMembers = null; // To keep track of which team's members are being managed

        const getStatusClass = (status) => { 
            if (!status) return 'bg-gray-100 text-gray-800'; 
            status = status.toLowerCase(); 
            const classes = { 
                'active': 'bg-green-100 text-green-800', 
                'ongoing': 'bg-blue-100 text-blue-800', 
                'schedule': 'bg-blue-100 text-blue-800', 
                'approved': 'bg-green-100 text-green-800', 
                'inactive': 'bg-red-100 text-red-800', 
                'blocked': 'bg-red-100 text-red-800', 
                'cancelled': 'bg-red-100 text-red-800', 
                'rejected': 'bg-red-100 text-red-800', // Style for rejected
                'pending': 'status-pending', 
                'postponed': 'bg-yellow-100 text-yellow-800', 
                'completed': 'bg-gray-100 text-gray-800', 
                'reviewed': 'status-reviewed', 
                'resolved': 'status-resolved' 
            }; 
            return classes[status] || 'bg-gray-100 text-gray-800'; 
        };

        const renderTable = (tbodyId, data, rowGenerator, colCount) => {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return;
            tbody.innerHTML = data && data.length > 0 ? data.map(rowGenerator).join('') : `<tr><td colspan="${colCount}" class="text-center p-4 text-gray-500">No data available.</td></tr>`;
        };
        
        const renderDashboard = () => {
             const stats = db.stats || {};
            const container = document.getElementById('dashboard');
            container.innerHTML = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="dashboard-card bg-white rounded-lg shadow p-6 flex justify-between items-center"><div><h3 class="text-gray-500 text-sm">Total Users</h3><p class="text-3xl font-bold">${stats.totalUsers || 0}</p></div><i class="fas fa-users text-blue-500"></i></div>
                        <div class="dashboard-card bg-white rounded-lg shadow p-6 flex justify-between items-center"><div><h3 class="text-gray-500 text-sm">Active Sports</h3><p class="text-3xl font-bold">${stats.activeSports || 0}</p></div><i class="fas fa-futbol text-green-500"></i></div>
                        <div class="dashboard-card bg-white rounded-lg shadow p-6 flex justify-between items-center"><div><h3 class="text-gray-500 text-sm">Upcoming Events</h3><p class="text-3xl font-bold">${stats.upcomingEvents || 0}</p></div><i class="fas fa-calendar-alt text-purple-500"></i></div>
                        <div class="dashboard-card bg-white rounded-lg shadow p-6 flex justify-between items-center"><div><h3 class="text-gray-500 text-sm">Pending Registrations</h3><p class="text-3xl font-bold">${stats.pendingRegistrations || 0}</p></div><i class="fas fa-clipboard-list text-yellow-500"></i></div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4">Available Sports</h3>
                            <div id="dashboardSportsContainer" class="space-y-2"></div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <button data-tab-link="sports" class="p-4 bg-blue-50 text-blue-800 rounded-lg hover:bg-blue-100 transition flex flex-col items-center"><i class="fas fa-plus-circle text-2xl mb-2"></i><span>Add New Sport</span></button>
                                <button data-tab-link="events" class="p-4 bg-green-50 text-green-800 rounded-lg hover:bg-green-100 transition flex flex-col items-center"><i class="fas fa-calendar-plus text-2xl mb-2"></i><span>Create Event</span></button>
                                <button data-tab-link="teams" class="p-4 bg-purple-50 text-purple-800 rounded-lg hover:bg-purple-100 transition flex flex-col items-center"><i class="fas fa-users text-2xl mb-2"></i><span>Manage Teams</span></button>
                                <button data-tab-link="registrations" class="p-4 bg-yellow-50 text-yellow-800 rounded-lg hover:bg-yellow-100 transition flex flex-col items-center"><i class="fas fa-clipboard-check text-2xl mb-2"></i><span>Review Registrations</span></button>
                            </div>
                        </div>
                    </div>`;
            const sportsContainer = document.getElementById('dashboardSportsContainer');
            if (sportsContainer) {
                if (db.sports && db.sports.length > 0) {
                    sportsContainer.innerHTML = db.sports.slice(0, 5).map(sport => `<div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50"><div class="flex items-center"><i class="fas fa-futbol text-gray-400 mr-3 text-xl"></i><p class="font-semibold text-sm text-gray-800">${sanitizeHTML(sport.name)}</p></div><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(sport.status)}">${sanitizeHTML(sport.status)}</span></div>`).join('');
                    if (db.sports.length > 5) { sportsContainer.innerHTML += `<a href="#" class="text-sm text-blue-600 hover:underline mt-2 block text-center" data-tab-link="sports">View All Sports</a>`; }
                } else { sportsContainer.innerHTML = '<p class="text-sm text-gray-500">No sports have been added yet.</p>'; }
            }
        };

        // --- GENERAL FILTER for all pages (except registrations) ---
        const applyGeneralFilter = (tabId) => {
            const filterInput = document.getElementById(`${tabId}Filter`);
            const filterText = filterInput ? filterInput.value.toLowerCase() : '';
            let filteredData = [];
            let rowGenerator;
            let colCount;

            switch(tabId) {
                case 'users':
                    filteredData = db.users.filter(user => 
                        user.name.toLowerCase().includes(filterText) || 
                        user.email.toLowerCase().includes(filterText) ||
                        (user.student_id && user.student_id.toLowerCase().includes(filterText)) ||
                        user.role.toLowerCase().includes(filterText) ||
                        user.status.toLowerCase().includes(filterText)
                    );
                    rowGenerator = user => {
                        const statusButton = user.status === 'active' 
                            ? `<button class="text-red-600 hover:text-red-900 mr-4" data-endpoint="users" data-action="update_user_status" data-id="${user.user_id}" data-status="blocked" title="Block User"><i class="fas fa-ban"></i> Block</button>`
                            : `<button class="text-green-600 hover:text-green-900 mr-4" data-endpoint="users" data-action="update_user_status" data-id="${user.user_id}" data-status="active" title="Activate User"><i class="fas fa-check-circle"></i> Activate</button>`;
                        return `<tr class="hover:bg-gray-50"><td class="px-6 py-4">${sanitizeHTML(user.user_id)}</td><td class="px-6 py-4">${sanitizeHTML(user.name)}</td><td class="px-6 py-4">${sanitizeHTML(user.email)}</td><td class="px-6 py-4">${sanitizeHTML(user.student_id) || 'N/A'}</td><td class="px-6 py-4">${sanitizeHTML(user.role)}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(user.status)}">${sanitizeHTML(user.status)}</span></td><td class="px-6 py-4 whitespace-nowrap">${statusButton}<button class="text-red-600 hover:text-red-900" data-endpoint="users" data-action="delete_user" data-id="${user.user_id}" title="Delete User"><i class="fas fa-trash"></i> Delete</button></td></tr>`;
                    };
                    colCount = 7;
                    renderTable('usersTableBody', filteredData, rowGenerator, colCount);
                    break;
                case 'departments':
                    filteredData = db.departments.filter(dept => 
                        dept.department_name.toLowerCase().includes(filterText)
                    );
                    rowGenerator = dept => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${sanitizeHTML(dept.department_name)}</td><td class="px-6 py-4"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-department" data-id="${dept.department_id}" title="Edit Department"><i class="fas fa-edit"></i></button><button class="text-red-600 hover:text-red-900" data-endpoint="departments" data-action="delete_department" data-id="${dept.department_id}" title="Delete Department"><i class="fas fa-trash"></i></button></td></tr>`;
                    colCount = 2;
                    renderTable('departmentsTableBody', filteredData, rowGenerator, colCount);
                    break;
                case 'sports':
                    filteredData = db.sports.filter(sport => 
                        sport.name.toLowerCase().includes(filterText) ||
                        sport.status.toLowerCase().includes(filterText)
                    );
                    rowGenerator = sport => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${sanitizeHTML(sport.name)}</td><td class="px-6 py-4">${sanitizeHTML(sport.max_players)}</td><td class="px-6 py-4 font-semibold">${sanitizeHTML(sport.points_for_win)} / ${sanitizeHTML(sport.points_for_draw)} / ${sanitizeHTML(sport.points_for_loss)}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(sport.status)}">${sanitizeHTML(sport.status)}</span></td><td class="px-6 py-4"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-sport" data-id="${sport.sport_id}" title="Edit Sport"><i class="fas fa-edit"></i></button><button class="text-red-600 hover:text-red-900" data-endpoint="sports" data-action="delete_sport" data-id="${sport.sport_id}" title="Delete Sport"><i class="fas fa-trash"></i></button></td></tr>`;
                    colCount = 5;
                    renderTable('sportsTableBody', filteredData, rowGenerator, colCount);
                    break;
                case 'events':
                    filteredData = db.events.filter(event => 
                        event.event_name.toLowerCase().includes(filterText) ||
                        (event.sport_name && event.sport_name.toLowerCase().includes(filterText)) ||
                        (event.venue && event.venue.toLowerCase().includes(filterText)) ||
                        event.status.toLowerCase().includes(filterText) ||
                        (event.team1_name && event.team1_name.toLowerCase().includes(filterText)) ||
                        (event.team2_name && event.team2_name.toLowerCase().includes(filterText))
                    );
                    rowGenerator = event => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${sanitizeHTML(event.event_name)}</td><td class="px-6 py-4">${sanitizeHTML(event.sport_name)}</td><td class="px-6 py-4 whitespace-nowrap">${new Date(event.event_date).toLocaleString()}</td><td class="px-6 py-4 text-sm">${sanitizeHTML(event.team1_name) || 'N/A'} vs ${sanitizeHTML(event.team2_name) || 'N/A'}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(event.status)}">${sanitizeHTML(event.status)}</span></td><td class="px-6 py-4 whitespace-nowrap"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-event" data-id="${event.event_id}" title="Edit Event"><i class="fas fa-edit"></i></button><button class="text-red-600 hover:text-red-900" data-endpoint="events" data-action="delete_event" data-id="${event.event_id}" title="Delete Event"><i class="fas fa-trash"></i></button></td></tr>`;
                    colCount = 6;
                    renderTable('eventsTableBody', filteredData, rowGenerator, colCount);
                    break;
                case 'upcoming':
                    filteredData = db.upcomingEvents.filter(event => 
                        event.event_name.toLowerCase().includes(filterText) ||
                        (event.sport_name && event.sport_name.toLowerCase().includes(filterText)) ||
                        (event.venue && event.venue.toLowerCase().includes(filterText)) ||
                        event.status.toLowerCase().includes(filterText) ||
                        (event.team1_name && event.team1_name.toLowerCase().includes(filterText)) ||
                        (event.team2_name && event.team2_name.toLowerCase().includes(filterText))
                    );
                    rowGenerator = event => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${sanitizeHTML(event.event_name)}</td><td class="px-6 py-4">${sanitizeHTML(event.sport_name)}</td><td class="px-6 py-4 whitespace-nowrap">${new Date(event.event_date).toLocaleString()}</td><td class="px-6 py-4 text-sm">${sanitizeHTML(event.team1_name) || 'N/A'} vs ${sanitizeHTML(event.team2_name) || 'N/A'}</td><td class="px-6 py-4">${sanitizeHTML(event.venue)}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(event.status)}">${sanitizeHTML(event.status)}</span></td><td class="px-6 py-4 whitespace-nowrap"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-event" data-id="${event.event_id}" title="Edit Event"><i class="fas fa-edit"></i></button><button class="text-red-600 hover:text-red-900" data-endpoint="events" data-action="delete_event" data-id="${event.event_id}" title="Delete Event"><i class="fas fa-trash"></i></button></td></tr>`;
                    colCount = 7;
                    renderTable('upcomingTableBody', filteredData, rowGenerator, colCount);
                    break;
                case 'appeals':
                    filteredData = db.appeals.filter(appeal => 
                        (appeal.user_name && appeal.user_name.toLowerCase().includes(filterText)) ||
                        (appeal.event_name && appeal.event_name.toLowerCase().includes(filterText)) ||
                        (appeal.team1_name && appeal.team1_name.toLowerCase().includes(filterText)) ||
                        (appeal.team2_name && appeal.team2_name.toLowerCase().includes(filterText)) ||
                        (appeal.reason && appeal.reason.toLowerCase().includes(filterText)) ||
                        appeal.status.toLowerCase().includes(filterText)
                    );
                     rowGenerator = appeal => {
                        let actionsHtml = '';
                        if (appeal.status === 'pending') { actionsHtml = `<button class="text-blue-600 hover:text-blue-900 mr-4" data-endpoint="appeals" data-action="update_appeal_status" data-id="${appeal.appeal_id}" data-status="reviewed">Mark as Reviewed</button>`; }
                        else if (appeal.status === 'reviewed') { actionsHtml = `<button class="text-green-600 hover:text-green-900 mr-4" data-endpoint="appeals" data-action="update_appeal_status" data-id="${appeal.appeal_id}" data-status="resolved">Mark as Resolved</button>`; }
                        actionsHtml += `<button class="text-red-600 hover:text-red-900" data-endpoint="appeals" data-action="delete_appeal" data-id="${appeal.appeal_id}">Delete</button>`;
                        return `<tr class="hover:bg-gray-50"><td class="px-6 py-4"><div class="font-medium">${sanitizeHTML(appeal.team1_name) || 'N/A'} vs ${sanitizeHTML(appeal.team2_name) || 'N/A'}</div><div class="text-sm text-gray-500">${sanitizeHTML(appeal.event_name)}</div></td><td class="px-6 py-4">${sanitizeHTML(appeal.user_name)}</td><td class="px-6 py-4 text-sm" style="white-space: normal; max-width: 300px;">${sanitizeHTML(appeal.reason)}</td><td class="px-6 py-4"><span class="status-tag ${getStatusClass(appeal.status)}">${sanitizeHTML(appeal.status)}</span></td><td class="px-6 py-4 whitespace-nowrap">${actionsHtml}</td></tr>`;
                    };
                    colCount = 5;
                    renderTable('appealsTableBody', filteredData, rowGenerator, colCount);
                    break;
                case 'teams':
                    filteredData = db.teams.filter(team => 
                        team.team_name.toLowerCase().includes(filterText) ||
                        (team.sport_name && team.sport_name.toLowerCase().includes(filterText)) ||
                        (team.creator_name && team.creator_name.toLowerCase().includes(filterText))
                    );
                    rowGenerator = team => `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${sanitizeHTML(team.team_name)}</td><td class="px-6 py-4">${sanitizeHTML(team.sport_name)}</td><td class="px-6 py-4">${sanitizeHTML(team.creator_name) || 'N/A'}</td><td class="px-6 py-4">${sanitizeHTML(team.player_count)}</td><td class="px-6 py-4 whitespace-nowrap"><button class="text-blue-600 hover:text-blue-900 mr-4" data-action="edit-team" data-id="${team.team_id}" title="Edit Team"><i class="fas fa-edit"></i></button><button class="text-green-600 hover:text-green-900 mr-4" data-action="manage-team-members" data-id="${team.team_id}" data-team-name="${sanitizeHTML(team.team_name)}" title="Manage Team Members"><i class="fas fa-users-cog"></i></button><button class="text-red-600 hover:text-red-900" data-endpoint="teams" data-action="delete_team" data-id="${team.team_id}" title="Delete Team"><i class="fas fa-trash"></i></button></td></tr>`;
                    colCount = 5;
                    renderTable('teamsTableBody', filteredData, rowGenerator, colCount);
                    break;
                case 'scores':
                    filteredData = db.events.filter(event => // Scores tab uses the same event data
                        event.event_name.toLowerCase().includes(filterText) ||
                        (event.team1_name && event.team1_name.toLowerCase().includes(filterText)) ||
                        (event.team2_name && event.team2_name.toLowerCase().includes(filterText)) ||
                        event.status.toLowerCase().includes(filterText)
                    );
                    rowGenerator = event => {
                        const isUpdatable = new Date(event.event_date) < new Date() || event.status === 'completed';
                        const buttonHtml = isUpdatable ? `<button class="bg-blue-600 text-white px-3 py-1 text-sm rounded-md hover:bg-blue-700" data-action="update-score" data-id="${event.event_id}">Update</button>` : `<button class="bg-gray-400 text-white px-3 py-1 text-sm rounded-md cursor-not-allowed" disabled title="Cannot update score for a future event">Update</button>`;
                        const resultText = (event.result || 'pending').replace(/_/g, ' ').replace('team1 win', `${sanitizeHTML(event.team1_name)} Wins`).replace('team2 win', `${sanitizeHTML(event.team2_name)} Wins`);
                        return `<tr class="hover:bg-gray-50"><td class="px-6 py-4 font-medium">${sanitizeHTML(event.team1_name) || 'Team 1'} vs ${sanitizeHTML(event.team2_name) || 'Team 2'}<br><span class="text-xs text-gray-500">${sanitizeHTML(event.event_name)}</span></td><td class="px-6 py-4 whitespace-nowrap">${new Date(event.event_date).toLocaleDateString()}</td><td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full ${getStatusClass(event.status)}">${sanitizeHTML(event.status)}</span></td><td class="px-6 py-4 font-bold text-xl">${sanitizeHTML(event.team1_score)} - ${sanitizeHTML(event.team2_score)}</td><td class="px-6 py-4">${resultText}</td><td class="px-6 py-4">${buttonHtml}</td></tr>`;
                    };
                    colCount = 6;
                    renderTable('scoresTableBody', filteredData, rowGenerator, colCount);
                    break;
                case 'feedback':
                    filteredData = db.feedback.filter(fb => 
                        (fb.user_name && fb.user_name.toLowerCase().includes(filterText)) ||
                        (fb.user_email && fb.user_email.toLowerCase().includes(filterText)) ||
                        (fb.subject && fb.subject.toLowerCase().includes(filterText)) ||
                        (fb.message && fb.message.toLowerCase().includes(filterText))
                    );
                    const feedbackContainer = document.getElementById('feedbackContainer');
                    if (feedbackContainer) feedbackContainer.innerHTML = filteredData.length > 0 ? filteredData.map(fb => `<div class="shadow rounded-lg p-4 ${fb.is_read == 1 ? 'bg-gray-50' : 'bg-white border-l-4 border-blue-500'}"><div class="flex justify-between items-start"><div><p class="font-bold text-lg">${sanitizeHTML(fb.subject)}</p><p class="text-sm text-gray-500">From: ${sanitizeHTML(fb.user_name)} (${sanitizeHTML(fb.user_email)}) on ${new Date(fb.submitted_at).toLocaleString()}</p></div><div class="flex items-center space-x-4"><button class="${fb.is_read == 1 ? 'text-gray-500 hover:text-gray-800' : 'text-blue-600 hover:text-blue-900'}" data-endpoint="feedback" data-action="toggle_feedback_read" data-id="${fb.feedback_id}" title="${fb.is_read == 1 ? 'Mark as Unread' : 'Mark as Read'}"><i class="fas ${fb.is_read == 1 ? 'fa-eye-slash' : 'fa-eye'}"></i></button><button class="text-red-600 hover:text-red-900" data-endpoint="feedback" data-action="delete_feedback" data-id="${fb.feedback_id}" title="Delete Feedback"><i class="fas fa-trash"></i></button></div></div><div class="mt-4 pt-4 border-t border-gray-200"><p class="text-gray-800">${sanitizeHTML(fb.message).replace(/\n/g, '<br>')}</p></div></div>`).join('') : '<p class="text-center p-4 text-gray-500">No feedback submitted yet.</p>';
                    break;
            }
        };


        const applyRegistrationFilter = () => {
            const filterInput = document.getElementById('registrationFilter');
            const filterText = filterInput ? filterInput.value.toLowerCase() : '';
            const filterSportId = document.getElementById('filterSportId').value;
            const filterRegType = document.getElementById('filterRegType').value;
            const filterFromDate = document.getElementById('filterFromDate').value;
            const filterToDate = document.getElementById('filterToDate').value;

            const filterData = (registrationsArray) => {
                let filtered = registrationsArray;

                // 1. General Text Filter
                if (filterText) {
                    filtered = filtered.filter(reg => 
                        (reg.user_name && reg.user_name.toLowerCase().includes(filterText)) ||
                        (reg.sport_name && reg.sport_name.toLowerCase().includes(filterText)) ||
                        (reg.registration_detail && reg.registration_detail.toLowerCase().includes(filterText)) ||
                        (reg.registration_type && reg.registration_type.toLowerCase().includes(filterText))
                    );
                }

                // 2. Sport ID Filter
                if (filterSportId) {
                    filtered = filtered.filter(reg => reg.sport_id == filterSportId);
                }

                // 3. Registration Type Filter
                if (filterRegType) {
                    filtered = filtered.filter(reg => reg.registration_type === filterRegType);
                }

                // 4. Date Range Filter
                if (filterFromDate || filterToDate) {
                    const fromDateTime = filterFromDate ? new Date(filterFromDate + 'T00:00:00').getTime() : -Infinity;
                    const toDateTime = filterToDate ? new Date(filterToDate + 'T23:59:59').getTime() : Infinity;

                    filtered = filtered.filter(reg => {
                        const regDateTime = new Date(reg.registered_at).getTime();
                        return regDateTime >= fromDateTime && regDateTime <= toDateTime;
                    });
                }
                return filtered;
            };

            const filteredPending = filterData(db.registrations);
            const filteredApproved = filterData(db.approvedRegistrations);
            const filteredRejected = filterData(db.rejectedRegistrations); // Filter rejected registrations

            renderTable('pendingRegistrationsTableBody', filteredPending, reg => {
                let approveButton = `<button class="bg-green-600 text-white px-3 py-1 text-sm rounded-md hover:bg-green-700 mr-4" data-endpoint="registrations" data-action="approve_reg" data-id="${reg.registration_id}">Approve</button>`;
                let approveButtonTitle = 'Approve this registration';
                let isApproveDisabled = false;
                let disabledClass = '';

                if (reg.team_id !== null) { // Only validate if it's a team registration
                    const targetTeam = db.teams.find(t => t.team_id == reg.team_id);
                    if (targetTeam) {
                        const targetSportId = targetTeam.sport_id;
                        // Find if this user (reg.user_id) is already in any team that belongs to targetSportId
                        const existingConflict = db.allTeamMembers.find(member => 
                            member.user_id == reg.user_id && 
                            member.sport_id == targetSportId
                        );
                        if (existingConflict) {
                            isApproveDisabled = true;
                            disabledClass = 'disabled-button';
                            approveButtonTitle = `User is already in team "${sanitizeHTML(existingConflict.team_name)}" for the same sport. A player can only be in one team per sport.`;
                        }
                    } else {
                        // This case implies a data inconsistency: registration refers to a team_id that doesn't exist in db.teams
                        isApproveDisabled = true;
                        disabledClass = 'disabled-button';
                        approveButtonTitle = 'Error: Target team not found for this registration. Cannot proceed with team assignment.';
                    }
                } else {
                    // If team_id is NULL, it's an individual registration, no "one player per team per sport" check needed for auto-add.
                    // The backend will just approve the registration without adding to a team.
                    approveButtonTitle = "Approve individual registration";
                }

                // Reconstruct the button with disabled state and title
                if (isApproveDisabled) {
                    approveButton = `<button class="bg-gray-400 text-white px-3 py-1 text-sm rounded-md mr-4 ${disabledClass}" data-action="approve_reg" data-id="${reg.registration_id}" disabled title="${approveButtonTitle}">Approve</button>`;
                }

                return `<tr class="hover:bg-gray-50"><td class="px-6 py-4">${sanitizeHTML(reg.user_name)}</td><td class="px-6 py-4">${sanitizeHTML(reg.registration_detail)} <span class="text-xs text-gray-500">(${sanitizeHTML(reg.registration_type)})</span></td><td class="px-6 py-4">${sanitizeHTML(reg.sport_name) || 'N/A'}</td><td class="px-6 py-4">${new Date(reg.registered_at).toLocaleString()}</td><td class="px-6 py-4 whitespace-nowrap">${approveButton}<button class="text-red-600 hover:text-red-900" data-endpoint="registrations" data-action="reject_reg" data-id="${reg.registration_id}">Reject</button></td></tr>`;
            }, 5);
            
            renderTable('approvedRegistrationsTableBody', filteredApproved, reg => `<tr class="hover:bg-gray-50"><td class="px-6 py-4">${sanitizeHTML(reg.user_name)}</td><td class="px-6 py-4">${sanitizeHTML(reg.registration_detail)} <span class="text-xs text-gray-500">(${sanitizeHTML(reg.registration_type)})</span></td><td class="px-6 py-4">${sanitizeHTML(reg.sport_name) || 'N/A'}</td><td class="px-6 py-4">${new Date(reg.registered_at).toLocaleString()}</td></tr>`, 4);

            // Render Rejected Registrations
            renderTable('rejectedRegistrationsTableBody', filteredRejected, reg => `<tr class="hover:bg-gray-50"><td class="px-6 py-4">${sanitizeHTML(reg.user_name)}</td><td class="px-6 py-4">${sanitizeHTML(reg.registration_detail)} <span class="text-xs text-gray-500">(${sanitizeHTML(reg.registration_type)})</span></td><td class="px-6 py-4">${sanitizeHTML(reg.sport_name) || 'N/A'}</td><td class="px-6 py-4">${new Date(reg.registered_at).toLocaleString()}</td></tr>`, 4);
        };

        const populateRegistrationFilters = () => {
            const filterSportSelect = document.getElementById('filterSportId');
            filterSportSelect.innerHTML = '<option value="">All Sports</option>'; // Reset
            db.sports.forEach(sport => {
                const option = document.createElement('option');
                option.value = sport.sport_id;
                option.textContent = sanitizeHTML(sport.name);
                filterSportSelect.appendChild(option);
            });
        };

        const clearRegistrationFilters = () => {
            document.getElementById('registrationFilter').value = '';
            document.getElementById('filterSportId').value = '';
            document.getElementById('filterRegType').value = '';
            document.getElementById('filterFromDate').value = '';
            document.getElementById('filterToDate').value = '';
            applyRegistrationFilter();
        };

        const handleRegistrationSubTabSwitch = (tabId) => {
            document.querySelectorAll('#registrations .registration-sub-tab-content').forEach(tab => tab.classList.add('modal-hidden'));
            document.getElementById(`${tabId}RegistrationsSection`)?.classList.remove('modal-hidden');

            document.querySelectorAll('.registration-sub-tab-button').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-800');
            });
            document.querySelector(`.registration-sub-tab-button[data-reg-tab="${tabId}"]`)?.classList.remove('bg-gray-200', 'text-gray-800');
            document.querySelector(`.registration-sub-tab-button[data-reg-tab="${tabId}"]`)?.classList.add('bg-blue-600', 'text-white');
            
            applyRegistrationFilter();
        };

        const renderAllTables = () => {
            // Render each table and apply its specific filter
            applyGeneralFilter('users');
            applyGeneralFilter('departments');
            applyGeneralFilter('sports');
            applyGeneralFilter('events');
            applyGeneralFilter('upcoming');
            applyGeneralFilter('appeals');
            applyGeneralFilter('teams');
            applyGeneralFilter('scores');
            applyGeneralFilter('feedback');
            // Registrations tab has its own dedicated filter function
            // applyRegistrationFilter() is called by handleRegistrationSubTabSwitch
        };

        const renderTeamMembersTable = (teamId) => {
            const team = db.teams.find(t => t.team_id == teamId);
            if (!team) return;

            const teamMembersModalTitle = document.getElementById('teamMembersModalTitle');
            teamMembersModalTitle.innerHTML = `Manage Members for: <span class="text-blue-600">${sanitizeHTML(team.team_name)}</span>`;
            
            const teamMembersTbody = document.getElementById('teamMembersTableBody');
            // db.teamMembers is already filtered for the current team by fetchTeamMembers
            renderTable('teamMembersTableBody', db.teamMembers, member => `<tr class="hover:bg-gray-50"><td class="px-6 py-4">${sanitizeHTML(member.name)}</td><td class="px-6 py-4">${sanitizeHTML(member.role_in_team)}</td><td class="px-6 py-4"><button class="text-red-600 hover:text-red-900" data-endpoint="teams" data-action="remove_team_member" data-id="${member.team_member}" data-team-id="${teamId}" title="Remove Member"><i class="fas fa-user-minus"></i> Remove</button></td></tr>`, 3); // Updated colCount
        };

        const populateUsersForTeamMembersDropdown = (teamId) => {
            const selectElement = document.getElementById('addMemberUserId');
            selectElement.innerHTML = '<option value="">-- Select User --</option>'; // Clear previous options
            selectElement.disabled = true;

            if (!db.allUsers || db.allUsers.length === 0) {
                selectElement.innerHTML = '<option value="">No users available</option>';
                return;
            }

            const currentTeam = db.teams.find(t => t.team_id == teamId);
            if (!currentTeam) {
                selectElement.innerHTML = '<option value="">Error: Team not found</option>';
                return;
            }
            const currentTeamSportId = currentTeam.sport_id;

            // Filter users to only show those with role 'user' and not already in ANY team for the current team's sport
            const availableUsers = db.allUsers.filter(user => 
                user.role === 'user' &&
                !db.allTeamMembers.some(member => 
                    member.user_id === user.user_id && 
                    member.sport_id === currentTeamSportId
                )
            );

            if (availableUsers.length > 0) {
                availableUsers.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.user_id;
                    option.textContent = `${sanitizeHTML(user.name)} (${sanitizeHTML(user.email)})`;
                    selectElement.appendChild(option);
                });
                selectElement.disabled = false;
            } else {
                selectElement.innerHTML = '<option value="">No more users to add for this sport</option>';
            }
        };

        const refreshData = async () => {
            try {
                // Fetch all data concurrently
                const [
                    dashRes, usersRes, allUsersRes, deptsRes, sportsRes, eventsRes, 
                    pendingRegsRes, approvedRegsRes, rejectedRegsRes, teamsRes, feedbackRes, // Added rejectedRegsRes
                    upcomingRes, appealsRes, allTeamMembersRes 
                ] = await Promise.all([
                    apiRequest('dashboard', 'get_dashboard_data'),
                    apiRequest('users', 'get_users', 'GET', null, { role: 'user' }), // Get only non-admin users for main user table
                    apiRequest('users', 'get_users', 'GET', null, { role: 'all' }), // Get ALL users for team member dropdown
                    apiRequest('departments', 'get_departments'),
                    apiRequest('sports', 'get_sports'),
                    apiRequest('events', 'get_events'),
                    apiRequest('registrations', 'get_pending_registrations'),
                    apiRequest('registrations', 'get_approved_registrations'),
                    apiRequest('registrations', 'get_rejected_registrations'), // Fetch rejected registrations
                    apiRequest('teams', 'get_teams'),
                    apiRequest('feedback', 'get_feedback'),
                    apiRequest('events', 'get_upcoming_events'),
                    apiRequest('appeals', 'get_appeals'),
                    apiRequest('teams', 'get_all_team_members') 
                ]);
                
                // Update the global db object
                db = { 
                    stats: dashRes.data.stats, 
                    users: usersRes.data, 
                    allUsers: allUsersRes.data, // Store all users for dropdowns
                    departments: deptsRes.data, 
                    sports: sportsRes.data, 
                    events: eventsRes.data, 
                    registrations: pendingRegsRes.data, 
                    approvedRegistrations: approvedRegsRes.data, 
                    rejectedRegistrations: rejectedRegsRes.data, // Store rejected registrations
                    teams: teamsRes.data, 
                    feedback: feedbackRes.data, 
                    upcomingEvents: upcomingRes.data, 
                    appeals: appealsRes.data,
                    allTeamMembers: allTeamMembersRes.data, 
                    teamMembers: [] 
                };

                // If team members modal is open, refresh its data specifically
                if (!teamMembersModal.classList.contains('modal-hidden') && currentTeamIdForMembers) {
                    await fetchTeamMembers(currentTeamIdForMembers); // This will also call populateUsersForTeamMembersDropdown
                } else {
                    populateUsersForTeamMembersDropdown(null); // Clear dropdown if modal not open
                }

                // Render UI components
                renderDashboard();
                renderAllTables(); 
                populateRegistrationFilters(); 
                // handleRegistrationSubTabSwitch is called after renderAllTables to ensure correct sub-tab state and filter application
                handleRegistrationSubTabSwitch('pending'); 
                
            } catch (error) {
                // Error handled by apiRequest's showToast, just log here if needed
                console.error("Error refreshing data:", error);
                document.body.innerHTML = `<div style="padding: 2rem; text-align: center; color: red;"><strong>Fatal Error:</strong> Could not load initial data. Please check the browser console for errors and ensure api.php and config.php are working correctly.</div>`;
            }
        };

        const showModal = (title, content, callback = null) => {
            modalTitle.textContent = title;
            modalBody.innerHTML = content;
            mainModal.classList.remove('modal-hidden');
            if (callback && typeof callback === 'function') {
                callback();
            }
        };
        const hideModal = () => {
            mainModal.classList.add('modal-hidden');
        };

        const showTeamMembersModal = async (teamId, teamName) => {
            currentTeamIdForMembers = teamId;
            teamMembersModal.classList.remove('modal-hidden');
            document.getElementById('addMemberTeamId').value = teamId; // Set hidden input for form submission
            teamMembersModalTitle.innerHTML = `Manage Members for: <span class="text-blue-600">${sanitizeHTML(teamName)}</span>`;
            await fetchTeamMembers(teamId); // Fetch and render members for this team
        };
        const hideTeamMembersModal = () => {
            teamMembersModal.classList.add('modal-hidden');
            currentTeamIdForMembers = null; // Clear active team ID
        };
        
        const fetchTeamMembers = async (teamId) => {
            try {
                const response = await apiRequest('teams', 'get_team_members', 'GET', null, { team_id: teamId });
                db.teamMembers = response.data; // Store team members in db
                renderTeamMembersTable(teamId); // Re-render the table with fresh data
                populateUsersForTeamMembersDropdown(teamId); // Update dropdown of available users
            } catch (error) {
                showToast('Failed to load team members.', 'error');
            }
        };

        const getDepartmentFormHtml = (dept = {}) => `<form id="departmentForm" data-id="${dept.department_id || ''}"><label for="department_name">Department Name</label><input type="text" id="department_name" name="department_name" value="${sanitizeHTML(dept.department_name || '')}" required><button type="submit" class="mt-4">${dept.department_id ? 'Update' : 'Add'} Department</button></form>`;
        const getSportFormHtml = (sport = {}) => `<form id="sportForm" data-id="${sport.sport_id || ''}"><label for="name">Sport Name</label><input type="text" id="name" name="name" value="${sanitizeHTML(sport.name || '')}" required><label for="max_players">Max Players</label><input type="number" id="max_players" name="max_players" value="${sanitizeHTML(sport.max_players || '0')}" required min="1"><div style="display:flex; gap: 1rem;"><div style="flex:1;"><label for="points_for_win">Points for Win</label><input type="number" id="points_for_win" name="points_for_win" value="${sport.points_for_win != null ? sanitizeHTML(sport.points_for_win) : '3'}" required></div><div style="flex:1;"><label for="points_for_draw">Points for Draw</label><input type="number" id="points_for_draw" name="points_for_draw" value="${sport.points_for_draw != null ? sanitizeHTML(sport.points_for_draw) : '1'}" required></div><div style="flex:1;"><label for="points_for_loss">Points for Loss</label><input type="number" id="points_for_loss" name="points_for_loss" value="${sport.points_for_loss != null ? sanitizeHTML(sport.points_for_loss) : '0'}" required></div></div><label for="status">Status</label><select id="status" name="status" required><option value="active" ${sport.status === 'active' ? 'selected' : ''}>Active</option><option value="inactive" ${sport.status === 'inactive' ? 'selected' : ''}>Inactive</option></select><button type="submit" class="mt-4">${sport.sport_id ? 'Update' : 'Add'} Sport</button></form>`;
        
        const getEventFormHtml = (event = {}, sports = []) => {
            const isEdit = !!event.event_id;
            const sportOptions = sports
                .filter(s => s.status === 'active')
                .map(s => `<option value="${s.sport_id}" ${s.sport_id == event.sport_id ? 'selected' : ''}>${sanitizeHTML(s.name)}</option>`).join('');
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            const minDateTime = now.toISOString().slice(0, 16);
            const eventDate = event.event_date ? new Date(new Date(event.event_date).getTime() - (new Date().getTimezoneOffset() * 60000)).toISOString().slice(0, 16) : '';
            return `
                <form id="eventForm" data-id="${isEdit ? event.event_id : ''}">
                    <label for="event_name">Event Name</label><input type="text" id="event_name" name="event_name" value="${sanitizeHTML(event.event_name || '')}" required>
                    <label for="sport_id">Sport</label><select id="sport_id" name="sport_id" required><option value="">-- Select Sport --</option>${sportOptions}</select>
                    <label for="event_date">Date and Time</label><input type="datetime-local" id="event_date" name="event_date" value="${eventDate}" min="${minDateTime}" required>
                    <label for="venue">Venue</label><input type="text" id="venue" name="venue" value="${sanitizeHTML(event.venue || '')}" required>
                    <div style="display:flex; gap:1rem;">
                        <div style="flex:1;"><label for="team1_id">Team 1</label><select id="team1_id" name="team1_id" required disabled><option value="">-- Select a sport first --</option></select></div>
                        <div style="flex:1;"><label for="team2_id">Team 2</label><select id="team2_id" name="team2_id" required disabled><option value="">-- Select a sport first --</option></select></div>
                    </div>
                    ${isEdit ? `<label for="status">Event Status</label><select id="status" name="status" required><option value="schedule" ${event.status === 'schedule' ? 'selected':''}>Scheduled</option><option value="ongoing" ${event.status === 'ongoing' ? 'selected':''}>Ongoing</option><option value="completed" ${event.status === 'completed' ? 'selected':''}>Completed</option><option value="postponed" ${event.status === 'postponed' ? 'selected':''}>Postponed</option><option value="cancelled" ${event.status === 'cancelled' ? 'selected':''}>Cancelled</option></select>` : ''}
                    <label for="description">Description (Optional)</label><textarea id="description" name="description" rows="3">${sanitizeHTML(event.description || '')}</textarea>
                    <button type="submit" class="mt-4">${isEdit ? 'Update' : 'Create'} Event</button>
                </form>
            `;
        };

        const initializeEventFormLogic = (event = {}) => {
            const sportSelect = document.getElementById('sport_id');
            const team1Select = document.getElementById('team1_id');
            const team2Select = document.getElementById('team2_id');
            if (!sportSelect) return;
            const isEdit = !!event.event_id;
            const originalTeam1 = event.team1_id || '';
            const originalTeam2 = event.team2_id || '';
            const updateTeamsDropdown = async (sportId) => {
                team1Select.innerHTML = '<option value="">Loading...</option>';
                team2Select.innerHTML = '<option value="">Loading...</option>';
                team1Select.disabled = true;
                team2Select.disabled = true;
                if (!sportId) {
                    team1Select.innerHTML = '<option value="">-- Select a sport first --</option>';
                    team2Select.innerHTML = '<option value="">-- Select a sport first --</option>';
                    return;
                }
                try {
                    const response = await apiRequest('teams', 'get_teams_by_sport', 'GET', null, { sport_id: sportId });
                    const teams = response.data;
                    team1Select.innerHTML = '<option value="">-- Select Team 1 --</option>';
                    team2Select.innerHTML = '<option value="">-- Select Team 2 --</option>';
                    if (teams.length > 0) {
                        teams.forEach(team => {
                            team1Select.add(new Option(sanitizeHTML(team.team_name), team.team_id));
                            team2Select.add(new Option(sanitizeHTML(team.team_name), team.team_id));
                        });
                        team1Select.disabled = false;
                        team2Select.disabled = false;
                        if (originalTeam1) team1Select.value = originalTeam1;
                        if (originalTeam2) team2Select.value = originalTeam2;
                    } else {
                        team1Select.innerHTML = '<option value="">-- No teams for this sport --</option>';
                        team2Select.innerHTML = '<option value="">-- No teams for this sport --</option>';
                    }
                } catch (error) {
                    team1Select.innerHTML = '<option value="">Error loading teams</option>';
                    team2Select.innerHTML = '<option value="">Error loading teams</option>';
                }
            };
            sportSelect.addEventListener('change', (e) => updateTeamsDropdown(e.target.value));
            if (isEdit && sportSelect.value) {
                updateTeamsDropdown(sportSelect.value);
            }
        };

        const getTeamFormHtml = (team = {}, sports = []) => { const isEdit = !!team.team_id; const sportOptions = sports.filter(s => s.status === 'active').map(s => `<option value="${s.sport_id}" ${s.sport_id == team.sport_id ? 'selected':''}>${sanitizeHTML(s.name)}</option>`).join(''); return `<form id="teamForm" data-id="${isEdit ? team.team_id : ''}"><label for="team_name">Team Name</label><input type="text" id="team_name" name="team_name" value="${sanitizeHTML(team.team_name || '')}" required><label for="sport_id">Sport</label><select id="sport_id" name="sport_id" required><option value="">-- Select a Sport --</option>${sportOptions}</select><button type="submit" class="mt-4">${isEdit ? 'Update' : 'Create'} Team</button></form>`;};
        const getScoreFormHtml = (event = {}) => `<form id="scoreForm" data-id="${event.event_id}"><div style="display:flex; gap:1rem; align-items:center; justify-content:center; text-align:center;"><div style="flex:1;"><label for="team1_score">${sanitizeHTML(event.team1_name) || 'Team 1'}</label><input type="number" id="team1_score" name="team1_score" value="${sanitizeHTML(event.team1_score || 0)}" required min="0" style="font-size:1.5rem; text-align:center;"></div><div style="font-size:2rem; font-weight:bold;">-</div><div style="flex:1;"><label for="team2_score">${sanitizeHTML(event.team2_name) || 'Team 2'}</label><input type="number" id="team2_score" name="team2_score" value="${sanitizeHTML(event.team2_score || 0)}" required min="0" style="font-size:1.5rem; text-align:center;"></div></div><p class="text-sm text-center text-gray-500 mt-2 mb-4">Updating the score will automatically mark the event as 'Completed'.</p><button type="submit" class="mt-4">Update Score & Finalize</button></form>`;
        
        function handleTabSwitch(tabId) { 
            pageTitle.textContent = document.querySelector(`.tab-button[data-tab="${tabId}"]`).textContent.trim(); 
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active')); 
            document.getElementById(tabId)?.classList.add('active'); 
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('bg-blue-900')); 
            document.querySelector(`.tab-button[data-tab="${tabId}"]`)?.classList.add('bg-blue-900'); 
            
            // Apply filter when switching tabs
            if (tabId === 'registrations') {
                applyRegistrationFilter();
            } else if (tabId !== 'dashboard') { // Don't apply filter to dashboard
                applyGeneralFilter(tabId);
            }
        }
        document.querySelectorAll('.tab-button').forEach(button => button.addEventListener('click', function() { handleTabSwitch(this.dataset.tab); }));
        document.body.addEventListener('click', e => { const ql = e.target.closest('[data-tab-link]'); if(ql) { e.preventDefault(); handleTabSwitch(ql.dataset.tabLink); } });
        
        closeModalBtn.addEventListener('click', hideModal);
        mainModal.addEventListener('click', e => { if(e.target === mainModal.querySelector('.modal-overlay')) hideModal(); });

        closeTeamMembersModalBtn.addEventListener('click', hideTeamMembersModal);
        teamMembersModal.addEventListener('click', e => { if(e.target === teamMembersModal.querySelector('.modal-overlay')) hideTeamMembersModal(); });
        
        document.body.addEventListener('click', e => {
            const addDeptBtn = e.target.closest('#addDepartmentBtn');
            const addSportBtn = e.target.closest('#addSportBtn');
            const addEventBtn = e.target.closest('#addEventBtn');
            const addTeamBtn = e.target.closest('#addTeamBtn');

            if (addDeptBtn) showModal('Add New Department', getDepartmentFormHtml());
            if (addSportBtn) showModal('Add New Sport', getSportFormHtml());
            if (addTeamBtn) showModal('Create New Team', getTeamFormHtml({}, db.sports));
            if (addEventBtn) {
                const html = getEventFormHtml({}, db.sports);
                showModal('Create New Event', html, () => initializeEventFormLogic({}));
            }
        });

        document.body.addEventListener('click', async (e) => {
            const button = e.target.closest('button[data-action]');
            if (!button) return;
            e.preventDefault();
            const { action, id, endpoint, status, teamId, teamName } = button.dataset; // Added teamId and teamName for team members

            // Prevent action if button is disabled (client-side)
            if (button.disabled) {
                showToast(button.title || 'This action is currently disabled.', 'info');
                return;
            }

            if (endpoint) {
                if (action.startsWith('delete_') && !confirm('Are you sure you want to delete this item? This action cannot be undone.')) return;
                
                try {
                    await apiRequest(endpoint, action, 'POST', { id: id, status: status, team_member_id: id }); // For delete and status updates
                    showToast('Action completed successfully.', 'success');
                    // Special handling for team member removal
                    if (action === 'remove_team_member' && currentTeamIdForMembers) {
                        await fetchTeamMembers(currentTeamIdForMembers); // Re-fetch team members for the open modal
                        refreshData(); // Also refresh main data for team player count
                    } else {
                        refreshData(); // Re-fetch all data to update tables
                    }
                } catch (error) {
                    // showToast already called by apiRequest
                }
            } 
            else if (action === 'edit-department') {
                const record = db.departments.find(d => d.department_id == id); if (record) showModal('Edit Department', getDepartmentFormHtml(record));
            } else if (action === 'edit-sport') {
                const record = db.sports.find(s => s.sport_id == id); if (record) showModal('Edit Sport', getSportFormHtml(record));
            } else if (action === 'edit-event') {
                const record = db.events.find(ev => ev.event_id == id) || db.upcomingEvents.find(ev => ev.event_id == id);
                if (record) {
                    const html = getEventFormHtml(record, db.sports);
                    showModal('Edit Event', html, () => initializeEventFormLogic(record));
                }
            } else if (action === 'edit-team') {
                const record = db.teams.find(t => t.team_id == id); if (record) showModal('Edit Team', getTeamFormHtml(record, db.sports));
            } else if (action === 'update-score') {
                const record = db.events.find(ev => ev.event_id == id); if (record) showModal('Update Score', getScoreFormHtml(record));
            } else if (action === 'manage-team-members') { // NEW: Manage Team Members button
                showTeamMembersModal(id, teamName);
            }
        });

        document.body.addEventListener('submit', async (e) => {
            if (!e.target.id) return;
            e.preventDefault();
            const form = e.target;
            const recordId = form.dataset.id;
            const data = Object.fromEntries(new FormData(form).entries());
            if (recordId) data.id = recordId;

            if (form.id === 'eventForm' && data.team1_id && data.team2_id && data.team1_id === data.team2_id) {
                showToast('Team 1 and Team 2 cannot be the same.', 'error'); return;
            }

            let endpoint, action;
            const isEdit = !!recordId;
            switch(form.id) {
                case 'departmentForm': endpoint = 'departments'; action = isEdit ? 'update_department' : 'add_department'; break;
                case 'sportForm': endpoint = 'sports'; action = isEdit ? 'update_sport' : 'add_sport'; break;
                case 'eventForm': endpoint = 'events'; action = isEdit ? 'update_event' : 'add_event'; break;
                case 'scoreForm': endpoint = 'events'; action = 'update_score'; break;
                case 'teamForm':  endpoint = 'teams'; action = isEdit ? 'update_team' : 'add_team'; break;
                case 'addTeamMemberForm': endpoint = 'teams'; action = 'add_team_member'; break; // NEW action
            }

            if (endpoint && action) {
                try {
                    const result = await apiRequest(endpoint, action, 'POST', data);
                    showToast(result.message, 'success');
                    if (form.id === 'addTeamMemberForm') {
                        document.getElementById('addMemberRole').value = 'Player'; // Reset role field
                        document.getElementById('addMemberUserId').value = ''; // Reset selected user
                        await fetchTeamMembers(currentTeamIdForMembers); // Refresh team members list in modal
                        refreshData(); // Also refresh main data for team player count on main screen
                    } else {
                        hideModal();
                        refreshData(); // Re-fetch all data to update tables
                    }
                } catch(error) { /* Handled by apiRequest */ }
            }
        });

        // --- Event listeners for Registration sub-tabs ---
        document.body.addEventListener('click', e => {
            const regSubTabBtn = e.target.closest('.registration-sub-tab-button');
            if (regSubTabBtn) {
                handleRegistrationSubTabSwitch(regSubTabBtn.dataset.regTab);
            }
        });

        // --- Event listeners for the Registration filter inputs ---
        document.body.addEventListener('input', e => {
            const filterInputs = ['registrationFilter', 'filterSportId', 'filterRegType', 'filterFromDate', 'filterToDate'];
            if (filterInputs.includes(e.target.id)) {
                applyRegistrationFilter();
            }
        });

        // --- Event listener for Clear Registration Filters button ---
        document.body.addEventListener('click', e => {
            if (e.target.closest('#clearRegistrationFiltersBtn')) {
                clearRegistrationFilters();
            }
        });

        // --- NEW: Event listeners for general page filters ---
        const generalFilterInputs = ['usersFilter', 'departmentsFilter', 'sportsFilter', 'eventsFilter', 'upcomingFilter', 'appealsFilter', 'teamsFilter', 'scoresFilter', 'feedbackFilter'];
        generalFilterInputs.forEach(filterId => {
            document.body.addEventListener('input', e => {
                if (e.target.id === filterId) {
                    applyGeneralFilter(e.target.id.replace('Filter', ''));
                }
            });
        });

        // --- NEW: Event listener for clear buttons on general filters ---
        document.body.addEventListener('click', e => {
            const clearBtn = e.target.closest('.clear-search-btn');
            if (clearBtn) {
                const filterInputId = clearBtn.dataset.filterId;
                const filterInput = document.getElementById(filterInputId);
                if (filterInput) {
                    filterInput.value = ''; // Clear the input
                    filterInput.dispatchEvent(new Event('input')); // Trigger input event to re-apply filter
                }
            }
        });


        // --- Event listener for Print Registrations button ---
        document.body.addEventListener('click', e => {
            if (e.target.closest('#printRegistrationsBtn')) {
                const activeRegTabContent = document.querySelector('#registrations .registration-sub-tab-content:not(.modal-hidden)');
                if (activeRegTabContent) {
                    const printWindow = window.open('', '', 'height=600,width=800');
                    printWindow.document.write('<html><head><title>Registrations Report</title>');
                    printWindow.document.write('<style>');
                    printWindow.document.write('body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 20px; }');
                    printWindow.document.write('h3 { margin-bottom: 15px; color: #1f2937; }');
                    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
                    printWindow.document.write('th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 0.9em; }');
                    printWindow.document.write('thead th { background-color: #f3f4f6; font-weight: 600; }');
                    printWindow.document.write('tbody tr:nth-child(even) { background-color: #f9fafb; }');
                    printWindow.document.write('@media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } thead th { background-color: #f3f4f6 !important; } }');
                    printWindow.document.write('</style>');
                    printWindow.document.write('</head><body>');
                    printWindow.document.write('<h3>' + activeRegTabContent.querySelector('h3').textContent + '</h3>');
                    printWindow.document.write(activeRegTabContent.querySelector('table').outerHTML);
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.print();
                } else {
                    showToast('No registration tab is active to print.', 'error');
                }
            }
        });

        // --- NEW: Event listener for Print Team Members button ---
        document.body.addEventListener('click', e => {
            if (e.target.closest('#printTeamMembersBtn')) {
                const teamMembersTable = document.getElementById('teamMembersTableBody');
                const teamNameHeader = document.getElementById('teamMembersModalTitle').textContent; // Get the team name from the modal title

                if (teamMembersTable && teamNameHeader) {
                    const printWindow = window.open('', '', 'height=600,width=800');
                    printWindow.document.write('<html><head><title>Team Members Report</title>');
                    printWindow.document.write('<style>');
                    printWindow.document.write('body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 20px; }');
                    printWindow.document.write('h3 { margin-bottom: 15px; color: #1f2937; }');
                    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
                    printWindow.document.write('th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 0.9em; }');
                    printWindow.document.write('thead th { background-color: #f3f4f6; font-weight: 600; }');
                    printWindow.document.write('tbody tr:nth-child(even) { background-color: #f9fafb; }');
                    printWindow.document.write('@media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } thead th { background-color: #f3f4f6 !important; } }');
                    printWindow.document.write('</style>');
                    printWindow.document.write('</head><body>');
                    printWindow.document.write('<h3>Team Members: ' + sanitizeHTML(teamNameHeader.replace('Manage Members for: ', '')) + '</h3>'); // Extract and sanitize team name
                    
                    // Construct a printable table, including headers
                    const originalTable = document.getElementById('teamMembersModalBody').querySelector('table');
                    let tableHtml = originalTable.outerHTML;

                    printWindow.document.write(tableHtml);
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.print();
                } else {
                    showToast('No team members table found to print.', 'error');
                }
            }
        });

        refreshData();
    });
    </script>
</body>
</html