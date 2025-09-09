<?php /* Shared UI theme (Tailwind + palette overrides) */ ?>
<script src="https://cdn.tailwindcss.com"></script>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&display=swap" rel="stylesheet">
<style>
    :root {
        --color-navy: #0b1a33; /* dark navy */
        --color-navy-600: #102545;
        --color-sky: #cfe8ff;  /* light sky blue */
        --color-sky-200: #e6f2ff;
        --color-gray: #64748b; /* slate-500 */
        --color-black: #000000;
        --color-white: #ffffff;
    }

    body {
        font-family: 'Georgia', serif;
        background: radial-gradient(circle at 50% 0%, var(--color-navy), #071225);
        color: #e5e7eb; /* light text by default for dark background */
    }
    h1, h2, h3, h4, h5, h6, .font-header { font-family: 'Cinzel', serif; }

    /* Force global text to black for readability */
    body, #app-container, .sidebar, .main, #main-content-area, nav, footer, h1, h2, h3, h4, h5, h6, p, span, label, td, th, a { color: #0f172a !important; }

    /* Map old brown palette classes to navy/sky for backgrounds; keep text dark */
    [class*="text-[#4E3B2A]"] { color: #0f172a !important; }
    [class*="text-[#594423]"] { color: #0f172a !important; }
    [class*="bg-[#4E3B2A]"] { background-color: var(--color-navy-600) !important; }
    [class*="bg-[#594423]"] { background-color: var(--color-navy-600) !important; }
    [class*="hover:bg-[#4E3B2A]"]:hover { background-color: var(--color-navy) !important; }
    [class*="hover:bg-[#594423]"]:hover { background-color: var(--color-navy) !important; }
    [class*="border-[#F7E6CA]"] { border-color: var(--color-sky) !important; }
    [class*="bg-[#F7E6CA]"] { background-color: var(--color-sky-200) !important; }
    [class*="hover:bg-[#F7E6CA]"]:hover { background-color: var(--color-sky) !important; }
    [class*="text-[#4E3B2A]"] { color: #0f172a !important; }

    /* Sidebar look */
    .sidebar { background-color: var(--color-white); color: #0f172a; }
    .sidebar .menu-name { color: var(--color-navy-600); }
    .sidebar .menu-drop a { color: var(--color-navy-600); }
    .main { background-color: #f8fafc; color: #0f172a; }
    .menu-name:hover { background-color: var(--color-sky-200); color: #0b1a33; }

    /* Login card theme */
    #login-container .rounded-lg { background-color: var(--color-white); color: #0b1a33; }
    #login-container input { background: #ffffff; color: #0b1a33; border-color: #cbd5e1; }
    #login-button { background: var(--color-navy-600); }
    #login-button:hover { background: var(--color-navy); }

    /* Navbar accents */
    .nav-accent { color: var(--color-navy-600); }

    /* Ensure light-on-dark for any text over dark zones */
    .bg-dark, .bg-[#0b1a33] { color: #e5e7eb; }

    /* High-contrast links */
    a { color: #1e40af; }
    a:hover { color: #0b1a33; }
</style>


