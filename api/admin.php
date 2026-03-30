<?php
session_start();
require_once 'db.php';

// --- FIRM CONNECTION GUARD ---
if (!$conn) {
    die("<div style='color:white; background:#78350f; padding:20px; border-radius:15px; font-family:sans-serif;'>
            <strong>Critical System Error:</strong> Database connection failed. Please verify your db.php settings.
         </div>");
}

// Protect Admin Access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Logic: Status Updates with admin messages
if (isset($_GET['id']) && isset($_GET['st'])) {
    $id = intval($_GET['id']);
    $st = mysqli_real_escape_string($conn, $_GET['st']);
    // Capture the custom message from the admin
    $user_msg = isset($_GET['msg']) ? mysqli_real_escape_string($conn, $_GET['msg']) : ""; 
    
    // Update both status and the admin message
    $conn->query("UPDATE appointments SET status='$st', admin_message='$user_msg' WHERE id=$id");
    
    // Preserve the report_date in the redirect so the view doesn't reset to today
    $rd = isset($_GET['report_date']) ? "&report_date=" . $_GET['report_date'] : "";
    header("Location: admin.php?view=" . ($_GET['view'] ?? 'overview') . $rd); 
    exit();
}

$view = $_GET['view'] ?? 'overview';
$target_date = $_GET['report_date'] ?? date('Y-m-d'); // Default to today

// Stats Logic
$total_apt = $conn->query("SELECT COUNT(*) as total FROM appointments")->fetch_assoc()['total'];
$pending_apt = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='pending'")->fetch_assoc()['total'];
$confirmed_apt = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='confirmed'")->fetch_assoc()['total'];

// Today's Pulse
$today = date('Y-m-d');
$schedule_res = $conn->query("SELECT a.*, u.fullname FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.appointment_date = '$today' AND a.status = 'confirmed' ORDER BY a.appointment_time ASC");

// Queue
$queue_res = $conn->query("SELECT a.*, u.fullname FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.appointment_date ASC LIMIT 4");

// Dynamic Table View
if ($view == 'patients') {
    $res = $conn->query("SELECT * FROM users WHERE role='patient' ORDER BY fullname ASC");
} elseif ($view == 'history') {
    $res = $conn->query("SELECT a.*, u.fullname FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.status != 'pending' ORDER BY a.appointment_date DESC");
} else {
    $res = $conn->query("SELECT a.*, u.fullname FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.appointment_date ASC");
}

$report_res = $conn->query("SELECT a.*, u.fullname FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.appointment_date = '$target_date' AND a.status = 'confirmed' ORDER BY a.appointment_time ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>Doctor EC | Master Dashboard</title>
    <style>
        :root {
            --accent: #92400e;
            --glass: rgba(20, 18, 17, 0.9); /* Increased opacity for mobile clarity */
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #050505; 
            color: #e7e5e4; 
            font-size: 12px; 
            overflow-x: hidden;
        }

        @media print {
            body { background: white !important; color: black !important; overflow: visible !important; }
            aside, header, .aurora, .btn-action, .no-print, .dashboard-content, #mobile-toggle { display: none !important; }
            .print-only { display: block !important; width: 100% !important; margin: 0 !important; }
            .print-header { border-bottom: 2px solid #92400e; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
            .print-table { width: 100%; border-collapse: collapse; }
            .print-table th, .print-table td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; color: black !important; }
        }

        .print-only { display: none; }
        .aurora { position: fixed; width: 600px; height: 600px; filter: blur(100px); z-index: -1; opacity: 0.12; border-radius: 50%; animation: move 25s infinite alternate; }
        .aurora-1 { background: #78350f; top: -10%; right: -10%; }
        .aurora-2 { background: #451a03; bottom: -10%; left: -10%; animation-delay: -5s; }
        @keyframes move { from { transform: translate(0, 0) scale(1); } to { transform: translate(-100px, 100px) scale(1.1); } }
        .glass-panel { background: var(--glass); backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .logo-circle { width: 48px; height: 48px; clip-path: circle(50%); background: white; padding: 2px; }
        .nav-item { transition: all 0.3s ease; border-left: 3px solid transparent; }
        .nav-item.active { background: rgba(146, 64, 14, 0.15); color: #fbbf24; border-left-color: #92400e; }
        .stat-card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.4s ease; }
        .btn-action { transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; font-size: 9px; font-weight: 800; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #292524; border-radius: 10px; }
        .table-glass { border-collapse: separate; border-spacing: 0 8px; }
        .table-glass tr { background: rgba(255, 255, 255, 0.01); }

        /* Sidebar Mobile Transitions */
        #sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @media (max-width: 1024px) {
            #sidebar {
                transform: translateX(-100%);
                position: fixed;
                height: 100vh;
                width: 300px;
                z-index: 100;
            }
            #sidebar.open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-[#050505]">

    <div class="print-only">
        <div class="print-header">
            <div>
                <h1 style="font-size: 24px; font-weight: 900; color: #92400e;">DOCTOR EC OPTICAL</h1>
                <p style="font-size: 12px; font-weight: 700;">DAILY SERVICE REPORT</p>
            </div>
            <div style="text-align: right;">
                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($target_date)); ?></p>
                <p><strong>Generated by:</strong> Admin Master Console</p>
            </div>
        </div>
        <table class="print-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Patient Name</th>
                    <th>Service</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($pr = $report_res->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('h:i A', strtotime($pr['appointment_time'])); ?></td>
                    <td><?php echo $pr['fullname']; ?></td>
                    <td><?php echo $pr['service_name']; ?></td>
                    <td><?php echo strtoupper($pr['status']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="aurora aurora-1"></div>
    <div class="aurora aurora-2"></div>

    <aside id="sidebar" class="glass-panel flex flex-col p-6 lg:p-8 border-r border-white/5">
        <div class="mb-12 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="logo-circle flex items-center justify-center shadow-lg ring-2 ring-amber-900/20">
                    <img src="logo_ec.jpg" alt="Logo" class="w-full h-full object-cover">
                </div>
                <div>
                    <h1 class="text-white font-black text-lg tracking-tighter">DOCTOR <span class="text-amber-600">EC</span></h1>
                    <span class="text-[8px] text-amber-700 font-black uppercase tracking-[0.3em]">Master Console</span>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-stone-500 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 space-y-2 overflow-y-auto">
            <p class="text-[9px] font-black text-stone-600 uppercase tracking-[0.2em] mb-4 ml-4">Management</p>
            <a href="admin.php?view=overview" class="nav-item flex items-center gap-4 p-4 rounded-2xl font-bold <?php echo $view == 'overview' ? 'active' : 'text-stone-500'; ?>">
                <i class="fas fa-chart-pie text-xs"></i> Insights Dashboard
            </a>
            <a href="admin.php?view=patients" class="nav-item flex items-center gap-4 p-4 rounded-2xl font-bold <?php echo $view == 'patients' ? 'active' : 'text-stone-500'; ?>">
                <i class="fas fa-users-viewfinder text-xs"></i> Patient Directory
            </a>
            <a href="admin.php?view=history" class="nav-item flex items-center gap-4 p-4 rounded-2xl font-bold <?php echo $view == 'history' ? 'active' : 'text-stone-500'; ?>">
                <i class="fas fa-history text-xs"></i> Service Logs
            </a>

            <div class="mt-8 p-6 bg-amber-900/10 border border-amber-900/20 rounded-3xl">
                <p class="text-[8px] font-black text-amber-600 uppercase tracking-widest mb-4">Report Generator</p>
                <form id="reportForm" action="admin.php" method="GET" class="space-y-3">
                    <input type="hidden" name="view" value="<?php echo $view; ?>">
                    <input type="date" name="report_date" value="<?php echo $target_date; ?>" onchange="this.form.submit()"
                           class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2 text-[10px] text-white focus:outline-none focus:border-amber-600 transition-all cursor-pointer">
                    <button type="button" onclick="window.print()" class="w-full py-3 bg-amber-700 hover:bg-amber-600 text-white font-black text-[9px] uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-amber-900/40">
                        <i class="fas fa-file-pdf mr-2"></i> Print Date Report
                    </button>
                </form>
            </div>
        </nav>

        <div class="mt-auto pt-6">
            <div class="bg-white/5 p-4 rounded-[25px] border border-white/5 mb-4 text-center">
                <p id="liveClock" class="text-white font-black text-lg tracking-widest mb-1">00:00:00</p>
                <p class="text-amber-700 text-[8px] font-black uppercase">Live Clinic Time</p>
            </div>
            <a href="logout.php" class="flex items-center justify-center gap-3 p-4 font-black text-red-500/80 hover:text-red-400 bg-red-500/5 hover:bg-red-500/10 rounded-2xl transition-all text-[10px] uppercase tracking-widest">
                <i class="fas fa-power-off"></i> Terminate
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full overflow-hidden dashboard-content relative">
        <header class="h-20 lg:h-24 px-6 lg:px-12 flex items-center justify-between border-b border-white/5 bg-black/20 backdrop-blur-xl sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <button id="mobile-toggle" onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-lg lg:text-2xl font-black text-white tracking-tighter">
                        <?php 
                            if($view == 'overview') echo "Intelligence Center";
                            elseif($view == 'patients') echo "Patient Database";
                            else echo "Service Archive";
                        ?>
                    </h2>
                    <div class="hidden sm:flex items-center gap-2 mt-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <p class="text-stone-500 font-bold text-[9px] uppercase tracking-widest"><?php echo date('l, F d, Y'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4 lg:gap-8">
                <div class="relative group no-print hidden md:block">
                    <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-stone-600 group-focus-within:text-amber-600 transition-colors"></i>
                    <input type="text" id="pSearch" onkeyup="filterTable()" placeholder="Deep search..." 
                           class="w-48 lg:w-72 pl-14 pr-6 py-3 lg:py-4 bg-white/5 border border-white/5 rounded-2xl focus:ring-2 focus:ring-amber-900/50 outline-none text-xs font-bold transition-all text-white">
                </div>
                <div class="flex items-center gap-3 lg:gap-4 lg:pl-8 lg:border-l lg:border-white/10">
                    <div class="text-right hidden sm:block">
                        <p class="text-white font-black text-[10px] leading-none uppercase">Admin EC</p>
                        <p class="text-amber-700 font-bold text-[8px] uppercase tracking-tighter">Authorized</p>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=Admin+EC&background=92400e&color=fff" class="w-8 h-8 lg:w-10 lg:h-10 rounded-xl border border-white/10 shadow-xl">
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-12">
            <div class="md:hidden mb-8">
                <div class="relative group no-print">
                    <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-stone-600"></i>
                    <input type="text" id="pSearchMobile" onkeyup="filterTableMobile()" placeholder="Search logs..." 
                           class="w-full pl-14 pr-6 py-4 bg-white/5 border border-white/5 rounded-2xl outline-none text-xs font-bold text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10">
                
                <div class="lg:col-span-8 space-y-8 lg:space-y-12">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 lg:gap-8">
                        <div class="stat-card p-6 lg:p-8 rounded-[30px] lg:rounded-[40px]">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-10 h-10 lg:w-12 lg:h-12 bg-white/5 text-stone-400 rounded-2xl flex items-center justify-center border border-white/5"><i class="fas fa-layer-group"></i></div>
                                <span class="text-stone-600 text-[8px] font-black tracking-widest uppercase">Volume</span>
                            </div>
                            <h3 class="text-2xl lg:text-3xl font-black text-white"><?php echo $total_apt; ?></h3>
                            <p class="text-stone-500 font-bold uppercase text-[9px] mt-1 tracking-widest">Total Engagements</p>
                        </div>

                        <div class="stat-card p-6 lg:p-8 rounded-[30px] lg:rounded-[40px] border-amber-900/30 bg-amber-900/5">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-10 h-10 lg:w-12 lg:h-12 bg-amber-600/20 text-amber-600 rounded-2xl flex items-center justify-center border border-amber-600/20"><i class="fas fa-clock"></i></div>
                                <span class="text-amber-600 text-[8px] font-black tracking-widest uppercase">Active</span>
                            </div>
                            <h3 class="text-2xl lg:text-3xl font-black text-amber-600"><?php echo $pending_apt; ?></h3>
                            <p class="text-stone-500 font-bold uppercase text-[9px] mt-1 tracking-widest">Awaiting Action</p>
                        </div>

                        <div class="stat-card p-6 lg:p-8 rounded-[30px] lg:rounded-[40px]">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-10 h-10 lg:w-12 lg:h-12 bg-green-500/10 text-green-500 rounded-2xl flex items-center justify-center border border-green-500/10"><i class="fas fa-check-double"></i></div>
                                <span class="text-green-600 text-[8px] font-black tracking-widest uppercase">Success</span>
                            </div>
                            <h3 class="text-2xl lg:text-3xl font-black text-white"><?php echo $confirmed_apt; ?></h3>
                            <p class="text-stone-500 font-bold uppercase text-[9px] mt-1 tracking-widest">Completed Sessions</p>
                        </div>
                    </div>

                    <?php if($view == 'overview'): ?>
                    <section>
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-[10px] font-black text-white uppercase tracking-[0.3em] flex items-center gap-3">
                                <span class="w-8 h-[1px] bg-amber-700"></span> Live Queue
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php if($queue_res->num_rows > 0): while($q = $queue_res->fetch_assoc()): ?>
                            <div class="stat-card p-6 flex items-center justify-between group rounded-[30px]">
                                <div class="flex items-center gap-5">
                                    <div class="w-12 h-12 lg:w-14 lg:h-14 bg-white/5 rounded-2xl flex items-center justify-center text-stone-500 group-hover:bg-amber-600 group-hover:text-white transition-all duration-500">
                                        <i class="fas fa-id-badge text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white text-[14px] lg:text-[15px] tracking-tight"><?php echo $q['fullname']; ?></h4>
                                        <p class="text-[9px] font-black text-amber-700 uppercase tracking-widest mt-1"><?php echo $q['service_name']; ?></p>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <button onclick="approveWithMsg(<?php echo $q['id']; ?>, 'confirmed')" class="btn-action w-8 h-8 lg:w-9 lg:h-9 bg-amber-700 text-white rounded-xl shadow-lg shadow-amber-900/20">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button onclick="approveWithMsg(<?php echo $q['id']; ?>, 'cancelled')" class="btn-action w-8 h-8 lg:w-9 lg:h-9 bg-white/5 text-stone-500 rounded-xl hover:bg-red-600 hover:text-white">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endwhile; else: ?>
                                <div class="col-span-1 md:col-span-2 py-16 text-center border border-dashed border-white/10 rounded-[30px] lg:rounded-[40px]">
                                    <p class="text-stone-600 font-bold text-xs uppercase tracking-widest">System idle. No pending requests.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <div class="overflow-x-auto rounded-3xl">
                        <table class="w-full text-left table-glass min-w-[600px]" id="aptTable">
                            <thead>
                                <tr class="text-[9px] text-stone-600 uppercase font-black tracking-widest">
                                    <th class="px-6 lg:px-8 py-4">Client Identity</th>
                                    <?php if($view == 'patients'): ?>
                                        <th class="px-6 lg:px-8 py-4">System Email</th>
                                        <th class="px-6 lg:px-8 py-4 text-right">Status</th>
                                    <?php else: ?>
                                        <th class="px-6 lg:px-8 py-4">Service Type</th>
                                        <th class="px-6 lg:px-8 py-4">Appointment</th>
                                        <th class="px-6 lg:px-8 py-4 text-right">System State</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="text-stone-300">
                                <?php while($row = $res->fetch_assoc()): ?>
                                <tr class="rounded-2xl border border-white/5">
                                    <td class="px-6 lg:px-8 py-6">
                                        <p class="font-bold text-white text-sm"><?php echo $row['fullname']; ?></p>
                                    </td>
                                    <?php if($view == 'patients'): ?>
                                        <td class="px-6 lg:px-8 py-6 text-stone-500"><?php echo $row['email']; ?></td>
                                        <td class="px-6 lg:px-8 py-6 text-right"><span class="px-4 py-1.5 bg-green-500/10 text-green-500 rounded-full font-black text-[8px] uppercase">Active User</span></td>
                                    <?php else: ?>
                                        <td class="px-6 lg:px-8 py-6">
                                            <span class="px-3 py-1 bg-white/5 border border-white/5 text-stone-400 rounded-lg font-bold text-[9px] uppercase tracking-tighter"><?php echo $row['service_name']; ?></span>
                                        </td>
                                        <td class="px-6 lg:px-8 py-6">
                                            <div class="font-bold text-white"><?php echo date('M d', strtotime($row['appointment_date'])); ?></div>
                                            <div class="text-[9px] text-amber-700 font-black uppercase"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></div>
                                        </td>
                                        <td class="px-6 lg:px-8 py-6 text-right">
                                            <?php 
                                                $s = $row['status'];
                                                $color = ($s == 'confirmed') ? 'text-green-500 bg-green-500/10' : (($s == 'pending') ? 'text-amber-600 bg-amber-600/10' : 'text-red-500 bg-red-500/10');
                                            ?>
                                            <span class="px-4 py-1.5 rounded-full font-black text-[8px] uppercase tracking-widest <?php echo $color; ?>"><?php echo $s; ?></span>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="glass-panel h-full rounded-[40px] lg:rounded-[50px] flex flex-col lg:sticky lg:top-12 p-2 mb-10 lg:mb-0">
                        <div class="p-8 lg:p-10">
                            <h3 class="text-xs font-black text-white uppercase tracking-[0.3em] mb-2">Today's Pulse</h3>
                            <p class="text-amber-700 font-bold text-[10px] uppercase">Operational Schedule</p>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto px-8 lg:px-10 space-y-8 mb-8">
                            <?php 
                            $schedule_res = $conn->query("SELECT a.*, u.fullname FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.appointment_date = '$today' AND a.status = 'confirmed' ORDER BY a.appointment_time ASC");
                            if($schedule_res->num_rows > 0): while($s = $schedule_res->fetch_assoc()): ?>
                            <div class="relative pl-8 group">
                                <div class="absolute left-0 top-1 w-2.5 h-2.5 rounded-full bg-amber-700 group-hover:scale-150 transition-all shadow-[0_0_15px_rgba(180,83,9,0.5)]"></div>
                                <div class="absolute left-[4px] top-6 bottom-[-32px] w-[1px] bg-white/5 group-last:hidden"></div>
                                <p class="text-[9px] font-black text-amber-700 mb-2 uppercase tracking-widest"><?php echo date('h:i A', strtotime($s['appointment_time'])); ?></p>
                                <h4 class="font-bold text-white text-[14px] tracking-tight"><?php echo $s['fullname']; ?></h4>
                                <p class="text-[9px] text-stone-500 font-bold uppercase mt-1"><?php echo $s['service_name']; ?></p>
                            </div>
                            <?php endwhile; else: ?>
                                <div class="text-center py-16 lg:py-20 opacity-10">
                                    <i class="fas fa-calendar-times text-6xl mb-4"></i>
                                    <p class="font-black text-[10px] uppercase">No sessions today</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-8 lg:p-10 bg-white/5 rounded-b-[40px] lg:rounded-b-[50px]">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-[8px] font-black text-stone-500 uppercase tracking-widest">Clinic Load</span>
                                <span class="text-[10px] font-black text-white"><?php echo ($confirmed_apt > 0) ? round(($confirmed_apt / 20) * 100) : 0; ?>%</span>
                            </div>
                            <div class="w-full h-1.5 bg-black/40 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-700" style="width: <?php echo ($confirmed_apt > 0) ? ($confirmed_apt / 20) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Sidebar Toggle Logic
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    }

    // Live Clock
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { hour12: false });
        if(document.getElementById('liveClock')) document.getElementById('liveClock').textContent = timeStr;
    }
    setInterval(updateClock, 1000);
    updateClock();

    function filterTable() {
        let input = document.getElementById("pSearch").value.toLowerCase();
        let tr = document.getElementById("aptTable").getElementsByTagName("tr");
        for (let i = 1; i < tr.length; i++) {
            tr[i].style.display = tr[i].innerText.toLowerCase().includes(input) ? "" : "none";
        }
    }

    // Mobile Search Sync
    function filterTableMobile() {
        let input = document.getElementById("pSearchMobile").value.toLowerCase();
        let tr = document.getElementById("aptTable").getElementsByTagName("tr");
        for (let i = 1; i < tr.length; i++) {
            tr[i].style.display = tr[i].innerText.toLowerCase().includes(input) ? "" : "none";
        }
    }

    function approveWithMsg(id, status) {
        const action = (status === 'confirmed') ? "APPROVE" : "CANCEL";
        const defaultMsg = (status === 'confirmed') ? "Confirmed. See you at Doctor EC!" : "Sorry, the slot is no longer available.";
        const userMsg = prompt(`SYSTEM ACTION: ${action}\n\nType a message for the patient:`, defaultMsg);
        
        if (userMsg !== null) {
            window.location.href = `admin.php?id=${id}&st=${status}&msg=${encodeURIComponent(userMsg)}&view=<?php echo $view; ?>&report_date=<?php echo $target_date; ?>`;
        }
    }
    </script>
</body>
</html>