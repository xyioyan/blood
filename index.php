<?php
session_start();
require_once './includes/config.php';

// Fetch WhatsApp group settings from system_settings
$stmt = $pdo->query("SELECT whatsapp_group_name, whatsapp_group_link FROM system_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings || empty($settings['whatsapp_group_link'])) {
    die("WhatsApp group link is not configured yet. Please contact admin.");
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['student_id']) || isset($_SESSION['admin_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_group'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $bloodGroup = trim($_POST['blood_group']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];

    if ($name && $phone && $bloodGroup) {
        $stmt = $pdo->prepare("INSERT INTO whatsapp_group_joins (name, phone, blood_group, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $bloodGroup, $ip, $ua]);

        header("Location: " . $settings['whatsapp_group_link']);
        exit();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blood Donation Community - Join Our WhatsApp Group</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #ff5252 0%, #448AFF 100%);
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
      color: #333;
    }
    
    .container { max-width: 1200px; margin: auto; }
    header { text-align: center; color: white; padding: 30px 0; }
    header h1 { font-size: 2.2rem; margin-bottom: 10px; }
    .card {
      background: white; padding: 25px; border-radius: 15px;
      margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    h2 { color: #25D366; display: flex; align-items: center; gap: 10px; }
    h2 i { font-size: 1.4rem; }
    .form-group { margin-bottom: 20px; }
    label { font-weight: 600; margin-bottom: 6px; display: block; }
    input, select, textarea {
      width: 100%; padding: 12px; border: 1px solid #ddd;
      border-radius: 8px; font-size: 15px;
    }
    .btn {
      padding: 12px 25px; border: none; border-radius: 8px;
      background: #25D366; color: white; font-weight: 600;
      cursor: pointer; transition: 0.3s;
    }
    .btn:hover { background: #128C7E; }
    .btn-alt { background: #ff5252; }
    .btn-alt:hover { background: #d90429; }
    footer { text-align: center; color: white; padding: 20px; }
    /* .login-options { display: flex; gap: 15px; margin-top: 15px; } */
    .login-options {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

.btn-alt {
    background: #ff5252;
    color: white;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.3s ease;
}

.btn-alt:hover {
    background: #d90429;
}

  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1><i class="fas fa-tint"></i> Blood Donation Community</h1>
      <p>Join our WhatsApp community for urgent blood requests & donation updates</p>
    </header>

    <!-- WhatsApp Join Section -->
    <div class="card" id="join-section">
      <h2><i class="fab fa-whatsapp"></i> Join Our WhatsApp Group</h2>
      <p>Volunteers can directly join our WhatsApp group to stay updated. No login required.</p>
      
      <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label for="name">Your Name</label>
          <input type="text" name="name" id="name" placeholder="Enter your full name" required>
        </div>
        <div class="form-group">
          <label for="phone">WhatsApp Number</label>
          <input type="tel" name="phone" id="phone" placeholder="Enter your WhatsApp number" required>
        </div>
        <div class="form-group">
          <label for="blood_group">Blood Group</label>
          <select name="blood_group" id="blood_group" required>
            <option value="">Select your blood group</option>
            <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
            <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
          </select>
        </div>
        <button type="submit" name="join_group" class="btn">
          <i class="fab fa-whatsapp"></i> Join WhatsApp Group
        </button>
      </form>
    </div>

    <?php if (!$isLoggedIn): ?>
<!-- Login Options -->
<div class="card" id="login-section">
  <h2><i class="fas fa-user-shield"></i> Member Login</h2>
  <p>If you are an <b>Admin</b> or <b>Student</b>, please login to access broadcast features.</p>
  <div class="login-options">
    <a href="/blood/admin/login.php" class="btn-alt">
      <i class="fas fa-user-shield"></i> Admin Login
    </a>
    <a href="/blood/student/login.php" class="btn-alt">
      <i class="fas fa-user-graduate"></i> Student Login
    </a>
  </div>
</div>
<?php endif; ?>

    <footer>
      <p>© 2025 Blood Donation Community. All rights reserved.</p>
    </footer>
  </div>
</body>
</html>
