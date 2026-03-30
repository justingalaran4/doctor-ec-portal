<?php
session_start();
require_once 'db.php';

$msg = "";
$msg_type = "";

// Registration Logic
if (isset($_POST['register'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    
    // FIX: Ginawang 'users' (lowercase) para mag-match sa SQL schema
    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check && $check->num_rows > 0) {
        $msg = "Email already registered!";
        $msg_type = "bg-red-500/10 text-red-500 border border-red-500/20";
    } else {
        // FIX: Table: users | Column: fullname (base sa SQL dump mo)
        $sql = "INSERT INTO users (fullname, email, password, role) VALUES ('$full_name', '$email', '$password', 'patient')";
        if ($conn->query($sql)) {
            $msg = "Registration Successful! Please Sign In.";
            $msg_type = "bg-emerald-500/10 text-emerald-500 border border-emerald-500/20";
        } else {
            $msg = "Registration Failed: " . $conn->error;
            $msg_type = "bg-red-500/10 text-red-500 border border-red-500/20";
        }
    }
}

// Login Logic
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password']; 
    
    // --- FORCE LOGIN BYPASS START (Preserved) ---
    if ($email === 'admin@doctorec.com' && $pass === 'admin123') {
        $_SESSION['user_id'] = 11; // In-update sa ID 11 base sa SQL dump mo para sa Master Admin
        $_SESSION['fullname'] = 'System Admin';
        $_SESSION['role'] = 'admin';
        header("Location: admin.php");
        exit();
    }
    // --- FORCE LOGIN BYPASS END ---
    
    // FIX: Ginawang 'users' (lowercase)
    $res = $conn->query("SELECT * FROM users WHERE email='$email'");
    
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        
        if (password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect base sa role
            header("Location: " . ($user['role'] == 'admin' ? 'admin.php' : 'index.php'));
            exit();
        } else {
            $msg = "Authentication Failed: Invalid Credentials";
            $msg_type = "bg-red-500/10 text-red-500 border border-red-500/20";
        }
    } else {
        $msg = "Authentication Failed: User not found";
        $msg_type = "bg-red-500/10 text-red-500 border border-red-500/20";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <title>Doctor EC | Optical Portal</title>
    <style>
        :root {
            --accent: #d97706;
            --accent-hover: #f59e0b;
            --bg-deep: #050505;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-deep);
            overflow-x: hidden;
        }
        @media (min-width: 768px) {
            body { overflow: hidden; }
        }

        .orb {
            position: fixed;
            width: 500px; height: 500px;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.2;
            animation: drift 15s infinite alternate ease-in-out;
        }
        .orb-1 { background: #78350f; top: -10%; right: -10%; }
        .orb-2 { background: #451a03; bottom: -10%; left: -10%; animation-delay: -7s; }

        @keyframes drift {
            from { transform: translate(0, 0) rotate(0deg); }
            to { transform: translate(-100px, 50px) rotate(30deg); }
        }

        .glass-container {
            background: rgba(18, 16, 14, 0.7);
            backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.9);
            transition: transform 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        }
        @media (min-width: 768px) {
            .glass-container:hover {
                transform: translateY(-5px);
                border-color: rgba(217, 119, 6, 0.2);
            }
        }

        .logo-circle {
            width: 65px; height: 65px;
            clip-path: circle(50%);
            background: white;
            padding: 2px;
            transition: all 0.4s ease;
        }
        .logo-circle:hover {
            transform: rotate(10deg) scale(1.1);
            filter: drop-shadow(0 0 15px var(--accent));
        }

        .btn-action {
            background: linear-gradient(135deg, #78350f 0%, #451a03 100%);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .btn-action::before {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: 0.5s;
        }
        .btn-action:hover {
            transform: scale(1.02);
            box-shadow: 0 0 30px rgba(120, 53, 15, 0.5);
            letter-spacing: 1px;
        }
        .btn-action:hover::before { left: 100%; }

        .qr-wrapper {
            background: #ffffff;
            padding: 10px;
            border-radius: 20px;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .qr-wrapper:hover {
            transform: scale(1.1) rotate(-3deg);
            box-shadow: 0 0 25px rgba(217, 119, 6, 0.4);
        }

        .tag {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
            cursor: default;
        }
        .tag:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
            border-color: transparent;
        }

        .field-input {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        .field-input:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--accent);
            box-shadow: 0 0 20px rgba(217, 119, 6, 0.15);
            padding-left: 2rem;
        }

        .tab-btn {
            transition: all 0.3s ease;
            position: relative;
        }
        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; width: 0; height: 2px;
            background: var(--accent);
            transition: 0.3s;
        }
        .tab-btn:hover::after { width: 100%; }
        .tab-active { color: var(--accent); }
        .tab-active::after { width: 100%; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="w-full max-w-[950px] glass-container grid grid-cols-1 md:grid-cols-2 rounded-[30px] md:rounded-[60px] overflow-hidden">
        
        <div class="p-8 md:p-12 flex flex-col justify-between bg-black/30 border-b md:border-b-0 md:border-r border-white/5">
            <div>
                <div class="flex items-center gap-5 mb-8 md:mb-12">
                    <div class="logo-circle flex items-center justify-center shrink-0">
                        <img src="assets/images/logo_ec.jpg" alt="Logo" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h2 class="text-white font-black text-lg md:text-xl tracking-tighter uppercase">Doctor <span class="text-amber-600">EC</span></h2>
                        <p class="text-[9px] text-stone-500 font-bold uppercase tracking-[0.3em]">Optical Specialists</p>
                    </div>
                </div>

                <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tighter leading-[0.85] mb-8">
                    Vision <br><span class="text-amber-700">Perfected.</span>
                </h1>

                <div class="flex flex-wrap gap-2">
                    <span class="tag px-4 py-2 rounded-xl text-[10px] font-bold text-stone-400">Eye Check-up</span>
                    <span class="tag px-4 py-2 rounded-xl text-[10px] font-bold text-stone-400">Repairs</span>
                    <span class="tag px-4 py-2 rounded-xl text-[10px] font-bold text-stone-400">Premium Lenses</span>
                </div>
            </div>

            <div class="mt-12 md:mt-16 bg-white/5 p-6 rounded-[25px] md:rounded-[35px] border border-white/5 flex items-center gap-6 group">
                <div class="qr-wrapper shrink-0">
                    <img src="assets/images/QR Code.png" alt="Portal QR" class="w-12 h-12 md:w-16 md:h-16">
                </div>
                <div class="transform group-hover:translate-x-1 transition-transform">
                    <p class="text-[8px] font-black text-amber-700 uppercase tracking-widest mb-1">Quick Access</p>
                    <p class="text-stone-300 text-[10px] md:text-xs font-bold leading-tight">Scan for record <br class="hidden md:block">and eye grade history.</p>
                </div>
            </div>
        </div>

        <div class="p-8 md:p-16 flex flex-col justify-center bg-black/10">
            <div class="flex gap-10 mb-8 md:mb-12">
                <button onclick="switchTab('login')" id="lTab" class="tab-btn pb-4 text-[11px] font-black uppercase tracking-widest tab-active">Sign In</button>
                <button onclick="switchTab('reg')" id="rTab" class="tab-btn pb-4 text-[11px] font-black uppercase tracking-widest text-stone-600">Register</button>
            </div>

            <?php if($msg): ?>
                <div class="p-4 rounded-2xl mb-8 text-[10px] font-bold flex items-center gap-3 <?php echo $msg_type; ?> animate-pulse">
                    <i class="fas fa-bolt"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form id="lForm" method="POST" class="space-y-6">
                <div class="relative">
                    <label class="text-[9px] font-black text-stone-500 uppercase tracking-widest ml-1 mb-2 block">Username / Email</label>
                    <input type="email" name="email" required placeholder="admin@doctorec.com" 
                           class="field-input w-full px-6 py-4 md:py-5 rounded-2xl outline-none text-white text-sm">
                </div>
                <div class="relative">
                    <label class="text-[9px] font-black text-stone-500 uppercase tracking-widest ml-1 mb-2 block">Security Key</label>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="field-input w-full px-6 py-4 md:py-5 rounded-2xl outline-none text-white text-sm">
                </div>
                <button type="submit" name="login" class="btn-action w-full text-white py-4 md:py-5 rounded-2xl text-[11px] font-black uppercase tracking-widest mt-4">
                    Authenticate <i class="fas fa-chevron-right ml-2 text-[8px]"></i>
                </button>
            </form>

            <form id="rForm" method="POST" class="space-y-4 hidden">
                <div class="relative">
                    <label class="text-[9px] font-black text-stone-500 uppercase tracking-widest ml-1 mb-2 block">Full Legal Name</label>
                    <input type="text" name="full_name" required placeholder="Enter full name" class="field-input w-full px-6 py-4 md:py-5 rounded-2xl outline-none text-white text-sm">
                </div>
                <div class="relative">
                    <label class="text-[9px] font-black text-stone-500 uppercase tracking-widest ml-1 mb-2 block">Email Address</label>
                    <input type="email" name="email" required placeholder="yourname@email.com" class="field-input w-full px-6 py-4 md:py-5 rounded-2xl outline-none text-white text-sm">
                </div>
                <div class="relative">
                    <label class="text-[9px] font-black text-stone-500 uppercase tracking-widest ml-1 mb-2 block">New Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="field-input w-full px-6 py-4 md:py-5 rounded-2xl outline-none text-white text-sm">
                </div>
                <button type="submit" name="register" class="btn-action w-full text-white py-4 md:py-5 rounded-2xl text-[11px] font-black uppercase tracking-widest mt-2">
                    Create Portal Account
                </button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const lForm = document.getElementById('lForm'), rForm = document.getElementById('rForm');
            const lTab = document.getElementById('lTab'), rTab = document.getElementById('rTab');
            if(tab === 'login') {
                lForm.classList.remove('hidden'); rForm.classList.add('hidden');
                lTab.classList.add('tab-active'); lTab.classList.remove('text-stone-600');
                rTab.classList.remove('tab-active'); rTab.classList.add('text-stone-600');
            } else {
                rForm.classList.remove('hidden'); lForm.classList.add('hidden');
                rTab.classList.add('tab-active'); rTab.classList.remove('text-stone-600');
                lTab.classList.remove('tab-active'); lTab.classList.add('text-stone-600');
            }
        }
    </script>
</body>
</html>
