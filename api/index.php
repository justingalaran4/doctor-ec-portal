<?php
// PRESERVED: Session and Database Logic
session_start();
require_once 'db.php';

// RESOLUTION: Safety Check for Database Connection
// Sinisiguro nito na hindi mag-cr-crash ang buong page kung may issue sa cloud DB connection.
if (!$conn) {
    die("Database Connection Error. Please check your hosting environment variables.");
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$msg = ""; 
$msg_type = "";

// PRESERVED: Core Booking Logic
if (isset($_POST['book'])) {
    $svc = mysqli_real_escape_string($conn, $_POST['service']); 
    $dt = $_POST['date']; 
    $tm = $_POST['time']; 
    $uid = $_SESSION['user_id'];
    
    $dayOfWeek = date('l', strtotime($dt));
    $timeSelected = date('H:i', strtotime($tm));
    
    $openingTime = "09:00";
    $closingTime = "17:00";

    if ($dt < date("Y-m-d")) {
        $msg = "We cannot look into the past! Please choose a future date.";
        $msg_type = "bg-red-500/10 text-red-600 border-red-200 backdrop-blur-md";
    } 
    elseif ($dayOfWeek == 'Saturday') {
        $msg = "Doctor EC is closed on Saturdays. Please pick another day!";
        $msg_type = "bg-orange-500/10 text-orange-600 border-orange-200 backdrop-blur-md";
    } 
    elseif ($timeSelected < $openingTime || $timeSelected > $closingTime) {
        $msg = "Clinic is only available from 9:00 AM to 5:00 PM.";
        $msg_type = "bg-blue-500/10 text-blue-600 border-blue-200 backdrop-blur-md";
    }
    else {
        // RESOLUTION: Added Status Check logic
        // Sinisiguro na ang 'confirmed' slot lang ang bawal i-double book.
        $check = $conn->query("SELECT id FROM appointments WHERE appointment_date='$dt' AND appointment_time='$tm' AND status='confirmed'");
        if ($check && $check->num_rows > 0) {
            $msg = "This slot is already reserved. Please try another time.";
            $msg_type = "bg-amber-500/10 text-amber-600 border-amber-200 backdrop-blur-md";
        } else {
            $sql = "INSERT INTO appointments (user_id, service_name, appointment_date, appointment_time, status) 
                    VALUES ('$uid', '$svc', '$dt', '$tm', 'pending')";
            if ($conn->query($sql)) {
                $msg = "Booking request submitted successfully!";
                $msg_type = "bg-green-500/10 text-green-600 border-green-200 backdrop-blur-md";
            }
        }
    }
}

$uid = $_SESSION['user_id'];
// LOGIC ADDED: Select admin_message for notifications
$notif_query = $conn->query("SELECT status, service_name, admin_message FROM appointments WHERE user_id='$uid' AND status IN ('confirmed', 'rejected') ORDER BY id DESC LIMIT 1");
$notif = ($notif_query) ? $notif_query->fetch_assoc() : null;

$count_query = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE user_id='$uid' AND status='pending'");
$pending_count = ($count_query) ? $count_query->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <title>Doctor EC Optical | Patient Web Portal</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fdfcfb; font-size: 13px; color: #1e293b; }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-link:hover { background: rgba(61, 43, 31, 0.05); color: #3d2b1f; transform: translateX(5px); }
        .sidebar-active { background: #3d2b1f !important; color: white !important; box-shadow: 0 10px 20px -5px rgba(61, 43, 31, 0.3); }

        .service-card { transition: all 0.4s ease; border: 1px solid rgba(226, 232, 240, 0.5); background: white; cursor: pointer; }
        .service-card:hover { transform: translateY(-8px); border-color: #3d2b1f; box-shadow: 0 20px 25px -5px rgba(61, 43, 31, 0.1); }
        
        .marble-bg {
            background-image: radial-gradient(at 0% 0%, hsla(28,100%,97%,1) 0, transparent 50%), 
                              radial-gradient(at 50% 0%, hsla(210,100%,96%,1) 0, transparent 50%);
        }

        .espresso-btn { 
            background: linear-gradient(135deg, #3d2b1f 0%, #1e140d 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .espresso-btn::after {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg); transition: 0.5s;
        }
        .espresso-btn:hover::after { left: 100%; }
        .espresso-btn:hover { transform: scale(1.02); box-shadow: 0 10px 20px rgba(61, 43, 31, 0.3); }

        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-5px); } 100% { transform: translateY(0px); } }
        .animate-float { animation: float 3s ease-in-out infinite; }

        #receipt-to-download {
            position: absolute; left: -9999px; top: 0; width: 350px; background: white; padding: 40px; border-radius: 40px;
        }

        .logo-container {
            width: 48px; height: 48px;
            border-radius: 50%;
            border: 2px solid #3d2b1f;
            overflow: hidden;
            display: flex; justify-content: center; align-items: center;
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .logo-container img { width: 100%; height: 100%; object-fit: cover; transform: scale(1.1); }

        /* Mobile Sidebar Overlay */
        @media (max-width: 1024px) {
            .sidebar-mobile {
                position: fixed; top: 0; left: -100%; height: 100%; width: 280px;
                z-index: 50; transition: 0.4s ease;
            }
            .sidebar-open { left: 0; }
            .main-content { padding: 1.5rem !important; }
        }
    </style>
</head>
<body class="flex flex-col h-screen overflow-hidden marble-bg">

    <header class="h-20 glass-card px-4 md:px-8 flex items-center justify-between shadow-sm z-40 sticky top-0">
        <div class="flex items-center gap-3">
            <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 flex items-center justify-center text-slate-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="logo-container animate-float hidden sm:flex">
                <img src="logo_ec.jpg" alt="Logo">
            </div>
            <div>
                <h1 class="font-black text-slate-800 leading-none text-xl md:text-2xl tracking-tighter uppercase">Doctor EC</h1>
                <p class="text-[8px] md:text-[10px] text-amber-800 font-black uppercase tracking-[0.3em] mt-1">Optical Specialist</p>
            </div>
        </div>

        <div class="flex items-center gap-3 md:gap-6">
            <div id="liveClock" class="hidden md:block text-right border-r pr-6 border-slate-200">
                <p class="text-[10px] font-black text-amber-800 uppercase tracking-widest" id="clockDay"></p>
                <p class="text-sm font-bold text-slate-700" id="clockTime"></p>
            </div>

            <div class="relative group">
                <button class="w-9 h-9 md:w-10 md:h-10 rounded-full bg-white text-slate-400 flex items-center justify-center hover:text-amber-800 transition-all border border-slate-100 shadow-sm">
                    <i class="fas fa-bell text-sm"></i>
                    <?php if($notif): ?><span class="absolute top-0 right-0 w-2.5 h-2.5 bg-amber-600 rounded-full border-2 border-white animate-pulse"></span><?php endif; ?>
                </button>
                <?php if($notif): ?>
                <div class="absolute right-0 mt-3 w-72 md:w-80 bg-white/95 backdrop-blur-xl border border-slate-100 shadow-2xl rounded-2xl p-5 hidden group-hover:block transition-all z-50">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Recent Status Update</p>
                    <div class="p-4 bg-amber-50/50 rounded-xl border border-amber-100/50">
                        <div class="flex items-center gap-3 mb-2">
                             <i class="fas <?php echo $notif['status'] == 'confirmed' ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'; ?> text-lg"></i>
                             <p class="text-[11px] font-bold text-slate-700">Your <?php echo $notif['service_name']; ?> request is <?php echo $notif['status']; ?>.</p>
                        </div>
                        <?php if(!empty($notif['admin_message'])): ?>
                            <p class="text-[10px] text-slate-500 italic mt-2 border-t border-amber-200 pt-2">Note: "<?php echo $notif['admin_message']; ?>"</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-3 md:border-l md:border-slate-200 md:pl-6">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-black text-slate-800 lowercase leading-none mb-1"><?php echo $_SESSION['fullname']; ?></p>
                    <p class="text-[10px] text-slate-500 font-bold uppercase">Patient</p>
                </div>
                <div class="w-9 h-9 md:w-10 md:h-10 bg-[#3d2b1f] rounded-full shadow-lg flex items-center justify-center text-white border-2 border-white">
                    <i class="fas fa-user-check text-xs"></i>
                </div>
                <a href="logout.php" class="text-slate-300 hover:text-red-500 transition-all ml-1"><i class="fas fa-power-off"></i></a>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden relative">
        
        <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden lg:hidden"></div>

        <aside id="sidebar" class="sidebar-mobile lg:relative lg:left-0 lg:flex w-72 glass-card flex-col p-6 m-0 lg:m-4 lg:rounded-[2rem] shadow-xl z-50 bg-white lg:bg-white/75">
            <div class="flex lg:hidden justify-end mb-4">
                <button onclick="toggleSidebar()" class="text-slate-400"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="mb-10 px-2 mt-4">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Navigation Menu</p>
            </div>
            <nav class="space-y-3 flex-1">
                <button onclick="showSection('dashboard'); if(window.innerWidth < 1024) toggleSidebar();" class="sidebar-link sidebar-active w-full flex items-center gap-4 px-5 py-4 rounded-2xl font-bold" id="btn-dashboard">
                    <i class="fas fa-house-chimney-user w-5"></i> Dashboard
                </button>
                <button onclick="showSection('book'); if(window.innerWidth < 1024) toggleSidebar();" class="sidebar-link w-full flex items-center gap-4 px-5 py-4 rounded-2xl text-slate-500 font-bold" id="btn-book">
                    <i class="fas fa-calendar-plus w-5"></i> Book Appointment
                </button>
            </nav>

            <div class="mb-6 p-4 bg-slate-50/50 rounded-2xl border border-white/50">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[9px] font-black text-slate-400 uppercase">Pending Waitlist</span>
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-lg text-[10px] font-black"><?php echo $pending_count; ?></span>
                </div>
                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-amber-500 h-full transition-all duration-1000" style="width: <?php echo ($pending_count > 0) ? '65%' : '0%'; ?>"></div>
                </div>
            </div>

            <div class="mt-auto espresso-btn rounded-3xl p-6 text-white overflow-hidden relative">
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-4">
                        <i class="far fa-clock text-amber-400"></i>
                        <p class="text-[10px] font-black uppercase tracking-widest">Clinic Hours</p>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-[11px] font-bold"><span class="text-stone-400">Mon - Fri</span> <span>9AM - 5PM</span></div>
                        <div class="flex justify-between text-[11px] font-bold"><span class="text-stone-400">Sunday</span> <span>9AM - 5PM</span></div>
                        <div class="pt-2 mt-2 border-t border-stone-700/50 flex justify-between text-[11px] font-black text-orange-400"><span>Saturday</span> <span>CLOSED</span></div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto main-content p-6 md:p-12">
            
            <div id="section-dashboard" class="animate-in fade-in slide-in-from-left-4 duration-500">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
                    <div>
                        <h2 class="text-3xl md:text-4xl font-black text-slate-800 tracking-tight mb-2" id="greetingText">...</h2>
                        <p class="text-slate-500 font-medium">Your optical health overview.</p>
                    </div>
                    <button onclick="showSection('book')" class="espresso-btn text-white px-6 py-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 w-full md:w-auto">
                        <i class="fas fa-plus"></i> New Booking
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4 mb-14">
                    <?php 
                    $services_list = ["Computerized Eye Check-Up", "Affordable Frames", "Anti-Rad Lenses", "Transitions", "Double Vista Lenses", "Progressive Lenses", "Tinted Lenses", "Ishihara Test", "Eye Wash", " Eyeglasses Repairs"];
                    $icons = ["desktop", "glasses", "shield-virus", "sun", "eye", "chart-line", "palette", "vial", "tint", "tools"];
                    foreach($services_list as $idx => $s): ?>
                    <div class="service-card p-4 md:p-6 rounded-[1.5rem] md:rounded-[2rem] text-center shadow-sm group">
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-slate-50 text-slate-600 rounded-xl md:rounded-2xl flex items-center justify-center mx-auto mb-3 md:mb-4 text-lg md:text-xl group-hover:bg-[#3d2b1f] group-hover:text-white transition-all">
                            <i class="fas fa-<?php echo $icons[$idx]; ?>"></i>
                        </div>
                        <h4 class="font-black text-slate-800 text-[8px] md:text-[9px] uppercase tracking-wider"><?php echo $s; ?></h4>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="max-w-4xl">
                    <h3 class="text-sm font-black text-slate-800 mb-6 flex items-center gap-3">
                        <i class="fas fa-history text-amber-800"></i> Reservation Records
                    </h3>
                    <div class="space-y-4">
                        <?php
                        // RESOLUTION: Pre-check Query to prevent errors on empty tables
                        $query = $conn->query("SELECT * FROM appointments WHERE user_id='$uid' ORDER BY id DESC");
                        if($query && $query->num_rows > 0):
                            while($row = $query->fetch_assoc()):
                                $st = $row['status'];
                                $badge = ($st == 'confirmed') ? 'bg-green-100 text-green-700' : ($st == 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                        ?>
                            <div class="bg-white p-5 md:p-7 rounded-[2rem] md:rounded-[2.5rem] border border-slate-100 shadow-sm transition-all hover:shadow-md group">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-center gap-4 md:gap-5">
                                        <div class="w-12 h-12 md:w-14 md:h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 text-lg group-hover:bg-amber-50 group-hover:text-amber-600 transition-colors">
                                            <i class="far fa-calendar-check"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm md:text-base font-black text-slate-800"><?php echo $row['service_name']; ?></p>
                                            <p class="text-[10px] md:text-[11px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                                                <i class="far fa-clock mr-1"></i> <?php echo date('M d, Y', strtotime($row['appointment_date'])); ?> • <?php echo date('h:i A', strtotime($row['appointment_time'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between sm:justify-end gap-4">
                                        <span class="text-[9px] md:text-[10px] font-black uppercase px-4 py-2 rounded-xl <?php echo $badge; ?>"><?php echo $st; ?></span>
                                        <?php if($st == 'confirmed'): ?>
                                        <button onclick="downloadPhoneReceipt('<?php echo $row['id']; ?>', '<?php echo addslashes($row['service_name']); ?>', '<?php echo date('M d, Y', strtotime($row['appointment_date'])); ?>', '<?php echo date('h:i A', strtotime($row['appointment_time'])); ?>')" class="w-10 h-10 md:w-12 md:h-12 espresso-btn text-white rounded-xl md:rounded-2xl flex items-center justify-center shadow-lg">
                                            <i class="fas fa-file-arrow-down"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if(!empty($row['admin_message'])): ?>
                                <div class="mt-4 p-3 bg-slate-50/80 rounded-xl border-l-4 border-amber-400 text-[11px] md:text-[12px] text-slate-600 font-bold italic">
                                    "<?php echo $row['admin_message']; ?>"
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; else: ?>
                            <div class="text-center py-16 glass-card rounded-[2rem] border-dashed border-2 border-slate-200">
                                <i class="fas fa-calendar-day text-slate-200 text-5xl mb-4"></i>
                                <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">No records found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="section-book" class="hidden animate-in slide-in-from-bottom-4 duration-500 max-w-4xl">
                <div class="mb-10">
                    <h2 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight">New Appointment</h2>
                    <p class="text-slate-500 font-medium">Pick a service and your preferred time.</p>
                </div>
                
                <?php if(!empty($msg)): ?>
                    <div class="<?php echo $msg_type; ?> p-5 rounded-2xl border mb-8 text-[11px] font-black flex items-center gap-4">
                        <i class="fas fa-info-circle text-lg"></i>
                        <span class="flex-1"><?php echo $msg; ?></span>
                        <button onclick="this.parentElement.style.display='none'"><i class="fas fa-times opacity-50"></i></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="bookingForm" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">1. Select Service</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <?php foreach($services_list as $idx => $s): ?>
                            <div class="service-option bg-white p-4 rounded-xl border border-slate-100 shadow-sm cursor-pointer transition-all hover:border-[#3d2b1f] flex flex-col items-center text-center gap-2 group" 
                                 onclick="selectService('<?php echo $s; ?>', this)">
                                <i class="fas fa-<?php echo $icons[$idx]; ?> text-slate-300 group-hover:text-[#3d2b1f]"></i>
                                <span class="text-[9px] md:text-[10px] font-bold text-slate-700"><?php echo $s; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="service" id="selectedServiceInput" required>
                    </div>

                    <div class="space-y-6 bg-white/80 backdrop-blur-md p-6 md:p-8 rounded-[2rem] md:rounded-[3rem] border border-white shadow-xl h-fit">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">2. Schedule Details</label>
                        <div>
                            <p class="text-[11px] font-bold text-slate-600 mb-2">Date</p>
                            <input type="date" name="date" min="<?php echo date('Y-m-d'); ?>" class="w-full p-4 bg-slate-50 border-none rounded-xl ring-1 ring-slate-100 outline-none text-xs font-bold" required>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-600 mb-2">Time</p>
                            <input type="time" name="time" class="w-full p-4 bg-slate-50 border-none rounded-xl ring-1 ring-slate-100 outline-none text-xs font-bold" required>
                            <p class="text-[9px] text-slate-400 mt-2 italic">* 9:00 AM - 5:00 PM</p>
                        </div>
                        <button type="submit" name="book" class="w-full espresso-btn text-white py-5 rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-xl">
                            CONFIRM BOOKING
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div id="receipt-to-download">
        <div style="text-align: center; border-bottom: 4px solid #3d2b1f; padding-bottom: 30px; margin-bottom: 30px;">
            <p style="margin: 0; font-size: 11px; color: #7c2d12; font-weight: 800; text-transform: uppercase; letter-spacing: 3px;">Doctor EC Optical</p>
            <h1 style="margin: 5px 0 0; font-size: 24px; color: #0f172a; font-weight: 900;">BOOKING RECEIPT</h1>
        </div>
        <div style="margin-bottom: 35px;">
            <p style="margin: 0; font-size: 9px; color: #94a3b8; font-weight: 800; text-transform: uppercase;">Patient Name</p>
            <p style="margin: 5px 0 20px; font-size: 18px; color: #0f172a; font-weight: 900;"><?php echo $_SESSION['fullname']; ?></p>
            <p style="margin: 0; font-size: 9px; color: #94a3b8; font-weight: 800; text-transform: uppercase;">Service Requested</p>
            <p style="margin: 5px 0 20px; font-size: 16px; color: #3d2b1f; font-weight: 800;" id="r-service"></p>
            <div style="display: flex; background: #fdfaf7; border-radius: 20px; padding: 20px; border: 1px solid #f1f5f9; gap: 20px;">
                <div style="flex: 1;"><p style="margin: 0; font-size: 8px; color: #64748b;">Appt Date</p><p id="r-date" style="font-weight: 900;"></p></div>
                <div style="flex: 1; text-align: right;"><p style="margin: 0; font-size: 8px; color: #64748b;">Time</p><p id="r-time" style="font-weight: 900;"></p></div>
            </div>
        </div>
        <div style="text-align: center; border-top: 2px dashed #e2e8f0; padding-top: 25px;">
            <p style="font-size: 12px; font-weight: 900;">TICKET ID: #EC-<span id="r-id"></span></p>
        </div>
    </div>

    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('sidebar-open');
        overlay.classList.toggle('hidden');
    }

    function updateClock() {
        const now = new Date();
        const hrs = now.getHours();
        let greeting = "Good Evening";
        if (hrs < 12) greeting = "Good Morning";
        else if (hrs < 18) greeting = "Good Afternoon";
        
        const gText = document.getElementById('greetingText');
        if(gText) gText.innerHTML = `${greeting}, <span class="text-[#3d2b1f]"><?php echo explode(' ', $_SESSION['fullname'])[0]; ?></span> 👋`;
        
        document.getElementById('clockDay').innerText = now.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
        document.getElementById('clockTime').innerText = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    function selectService(val, el) {
        document.getElementById('selectedServiceInput').value = val;
        document.querySelectorAll('.service-option').forEach(opt => {
            opt.classList.remove('border-[#3d2b1f]', 'bg-[#3d2b1f]/5', 'ring-2', 'ring-[#3d2b1f]');
        });
        el.classList.add('border-[#3d2b1f]', 'bg-[#3d2b1f]/5', 'ring-2', 'ring-[#3d2b1f]');
    }

    function showSection(name) {
        document.getElementById('section-dashboard').classList.add('hidden');
        document.getElementById('section-book').classList.add('hidden');
        
        const btns = ['btn-dashboard', 'btn-book'];
        btns.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.className = "sidebar-link w-full flex items-center gap-4 px-5 py-4 rounded-2xl text-slate-500 font-bold";
        });

        document.getElementById('section-' + name).classList.remove('hidden');
        const activeBtn = document.getElementById('btn-' + name);
        if(activeBtn) activeBtn.className = "sidebar-link sidebar-active w-full flex items-center gap-4 px-5 py-4 rounded-2xl font-bold";
    }

    function downloadPhoneReceipt(id, svc, date, time) {
        document.getElementById('r-id').innerText = id;
        document.getElementById('r-service').innerText = svc;
        document.getElementById('r-date').innerText = date;
        document.getElementById('r-time').innerText = time;
        
        const target = document.getElementById('receipt-to-download');
        html2canvas(target, { scale: 3, backgroundColor: "#ffffff" }).then(canvas => {
            const link = document.createElement('a');
            link.download = `DoctorEC_Ticket_${id}.png`;
            link.href = canvas.toDataURL("image/png");
            link.click();
        });
    }

    <?php if(!empty($msg)): ?> showSection('book'); <?php endif; ?>
    </script>
</body>
</html>