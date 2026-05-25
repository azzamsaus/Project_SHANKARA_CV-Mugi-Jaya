<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SHANKARA Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: -200px;
            left: -200px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            padding: 30px 30px 0;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .logo i {
            font-size: 40px;
            color: white;
        }
        
        .login-header h3 {
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            z-index: 10;
        }
        
        .input-group-custom input {
            padding-left: 45px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            height: 50px;
            font-size: 0.95rem;
            width: 100%;
        }
        
        .input-group-custom input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            outline: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            height: 50px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .login-footer {
            text-align: center;
            padding: 0 30px 30px;
            border-top: 1px solid #e9ecef;
            margin-top: 10px;
            padding-top: 20px;
        }
        
        .alert {
            border-radius: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo">
                <i class="bi bi-box-seam"></i>
            </div>
            <h3>SHANKARA</h3>
            <p>Sistem Tracking Barang & Proyek<br>CV Mugi Jaya</p>
        </div>
        
        <div class="login-body">
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">❌ Username atau password salah!</div>
            <?php endif; ?>
            
            <?php if(isset($_GET['logout'])): ?>
                <div class="alert alert-success">✅ Anda telah berhasil logout!</div>
            <?php endif; ?>
            
            <form action="proses_login.php" method="POST">
                <div class="input-group-custom">
                    <i class="bi bi-person"></i>
                    <input type="text" name="username" placeholder="Username" required autofocus>
                </div>
                <div class="input-group-custom">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
        
        <div class="login-footer">
        </div>
    </div>
</body>
</html>