<?php
require_once 'db.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    // Mas safe ang password_hash para sa security
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    // FIX: Ginawang 'users' (lowercase) para mag-match sa database schema
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "This email is already associated with an account.";
        } else {
            // ROLE: 'patient' para mag-match sa iyong records sa database
            $role = 'patient';
            
            // FIX: Ginawang 'users' (lowercase) dito rin
            $stmt_insert = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
            
            if ($stmt_insert) {
                $stmt_insert->bind_param("ssss", $fullname, $email, $password, $role);
                
                if ($stmt_insert->execute()) {
                    $success = "Account created! You can now <a href='login.php' class='font-bold underline'>Login here</a>.";
                } else {
                    $error = "Registration failed: " . $stmt_insert->error;
                }
            } else {
                $error = "System error during preparation.";
            }
        }
    } else {
        // Ito ang lalabas kapag may problema sa query o table name
        $error = "Database Error: Could not prepare statement. Please check your table structure.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Join Doctor EC Optical</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #FAF9F6; }
        .hero-image {
            background-image: url('https://images.unsplash.com/photo-1556761175-5973cf0f32e7?auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
        }
        .overlay { background: linear-gradient(to bottom, rgba(61, 43, 31, 0.4), rgba(26, 26, 26, 0.9)); }
        input { transition: all 0.3s ease; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 md:p-10">
    <div class="max-w-6xl w-full flex flex-col md:flex-row rounded-[30px] md:rounded-[40px] overflow-hidden shadow-[0_32px_64px_-15px_rgba(0,0,0,0.15)] bg-white min-h-[600px] md:min-h-[700px]">
        
        <div class="md:w-1/2 hero-image relative hidden md:block">
            <div class="absolute inset-0 overlay flex flex-col justify-between p-12 md:p-16 text-white">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 border-2 border-white rounded-full flex items-center justify-center text-xs font-bold">EC</div>
                    <span class="tracking-[0.4em] text-xs font-bold uppercase">Optical</span>
                </div>
                
                <div>
                    <h2 class="text-4xl md:text-5xl font-light leading-tight mb-6">See the world <br><span class="italic font-serif">differently.</span></h2>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 text-sm font-light">
                            <span class="w-6 h-[1px] bg-white/50"></span> Modern Eye Examination
                        </div>
                        <div class="flex items-center gap-4 text-sm font-light">
                            <span class="w-6 h-[1px] bg-white/50"></span> Designer Frames
                        </div>
                        <div class="flex items-center gap-4 text-sm font-light">
                            <span class="w-6 h-[1px] bg-white/50"></span> Personal Consultation
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full md:w-1/2 p-8 md:p-16 lg:p-24 flex flex-col justify-center">
            <div class="mb-8">
                <div class="flex md:hidden items-center gap-2 mb-6">
                    <div class="w-8 h-8 border-2 border-[#1a1a1a] rounded-full flex items-center justify-center text-[10px] font-bold">EC</div>
                    <span class="tracking-[0.2em] text-[10px] font-bold uppercase text-[#1a1a1a]">Optical</span>
                </div>
                
                <h3 class="text-3xl font-bold text-[#1a1a1a]">Create Account</h3>
                <p class="text-gray-400 mt-2 text-sm">Join our clinic for a better vision experience.</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-6 text-sm border border-red-100 flex items-center gap-3 animate-pulse">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="bg-emerald-50 text-emerald-600 p-4 rounded-2xl mb-6 text-sm border border-emerald-100 flex items-center gap-3">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5 md:space-y-6">
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] ml-1">Full Name</label>
                    <div class="relative mt-2">
                        <i class="far fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="text" name="fullname" class="w-full pl-12 pr-4 py-4 bg-gray-50 border-none rounded-2xl ring-1 ring-gray-100 focus:ring-2 focus:ring-[#3D2B1F] outline-none" placeholder="John Doe" required>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] ml-1">Email Address</label>
                    <div class="relative mt-2">
                        <i class="far fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="email" name="email" class="w-full pl-12 pr-4 py-4 bg-gray-50 border-none rounded-2xl ring-1 ring-gray-100 focus:ring-2 focus:ring-[#3D2B1F] outline-none" placeholder="john@example.com" required>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] ml-1">Password</label>
                    <div class="relative mt-2">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="password" name="password" class="w-full pl-12 pr-4 py-4 bg-gray-50 border-none rounded-2xl ring-1 ring-gray-100 focus:ring-2 focus:ring-[#3D2B1F] outline-none" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#1a1a1a] text-white py-4 md:py-5 rounded-2xl font-bold text-sm shadow-xl hover:bg-[#3D2B1F] transform transition active:scale-[0.98] mt-2">
                    Register Account
                </button>
            </form>

            <div class="mt-8 md:mt-10 pt-8 border-t border-gray-50 text-center">
                <p class="text-sm text-gray-500">Already a patient? <a href="login.php" class="text-[#3D2B1F] font-bold hover:underline">Sign In Instead</a></p>
            </div>
        </div>
    </div>
</body>
</html>