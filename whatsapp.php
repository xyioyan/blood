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
    <title>Blood Request System - Blood Group Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #ff5252 0%, #448AFF 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            padding: 30px 0;
            color: white;
        }
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        header p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            color: #ff5252;
            border-bottom: 3px solid #ff5252;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        h2 {
            color: #ff5252;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        h3 {
            color: #448AFF;
            margin: 15px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #ff5252;
            box-shadow: 0 0 0 2px rgba(255, 82, 82, 0.2);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: #ff5252;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
        }
        
        .btn:hover {
            background: #e00000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-success {
            background: #25D366;
        }
        
        .btn-success:hover {
            background: #128C7E;
        }
        
        .request-list {
            list-style: none;
        }
        
        .request-item {
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .request-title {
            font-weight: 600;
            font-size: 18px;
            color: #ff5252;
        }
        
        .request-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .detail-item {
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .message-preview {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .blood-group-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            margin-left: 10px;
        }
        
        footer {
            text-align: center;
            padding: 30px 0;
            color: white;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .request-details {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-tint"></i> Blood Request System</h1>
            <p>Request blood donations, manage requests, and coordinate with donors</p>
        </header>
        
        <div class="card">
            <div class="tabs">
                <div class="tab active" data-tab="request">Make a Request</div>
                <div class="tab" data-tab="manage">Manage Requests (Admin)</div>
                <div class="tab" data-tab="donors">Available Donors</div>
            </div>
            
            <!-- Request Blood Tab -->
            <div class="tab-content active" id="request-tab">
                <h2>Request Blood Donation</h2>
                <p>Fill out the form below to request blood. Our administrators will verify your request and notify available donors.</p>
                
                <form id="blood-request-form">
                    <div class="form-group">
                        <label for="patient-name">Patient Name *</label>
                        <input type="text" id="patient-name" required placeholder="Enter patient's full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact-name">Your Name *</label>
                        <input type="text" id="contact-name" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact-phone">Your Phone Number *</label>
                        <input type="tel" id="contact-phone" required placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="blood-group">Required Blood Group *</label>
                        <select id="blood-group" required>
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="units-needed">Units Needed *</label>
                        <input type="number" id="units-needed" min="1" max="10" required placeholder="Number of units needed">
                    </div>
                    
                    <div class="form-group">
                        <label for="hospital">Hospital Name *</label>
                        <input type="text" id="hospital" required placeholder="Enter hospital name">
                    </div>
                    
                    <div class="form-group">
                        <label for="hospital-address">Hospital Address *</label>
                        <textarea id="hospital-address" required placeholder="Enter full hospital address"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="urgency">Urgency Level *</label>
                        <select id="urgency" required>
                            <option value="">Select Urgency</option>
                            <option value="Critical">Critical (Within 2 hours)</option>
                            <option value="High">High (Within 6 hours)</option>
                            <option value="Medium">Medium (Within 24 hours)</option>
                            <option value="Low">Low (Within 48 hours)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="additional-info">Additional Information</label>
                        <textarea id="additional-info" placeholder="Any additional information that might help donors"></textarea>
                    </div>
                    
                    <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Submit Request</button>
                </form>
            </div>
            
            <!-- Manage Requests Tab -->
            <div class="tab-content" id="manage-tab">
                <h2>Manage Blood Requests</h2>
                <p>Review and manage blood donation requests. Verify details and send WhatsApp broadcasts to available donors.</p>
                
                <div class="request-list">
                    <div class="request-item">
                        <div class="request-header">
                            <div class="request-title">John Smith <span class="blood-group-badge" style="background-color: #FF5252;">A+</span></div>
                            <div class="request-status status-pending">Pending</div>
                        </div>
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Contact:</span> Mary Smith (9845012345)
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Hospital:</span> City General Hospital
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Units Needed:</span> 2
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Urgency:</span> <span style="color: #e00000;">Critical</span>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-success approve-btn"><i class="fas fa-check"></i> Approve</button>
                            <button class="btn reject-btn"><i class="fas fa-times"></i> Reject</button>
                            <button class="btn"><i class="fas fa-eye"></i> View Details</button>
                        </div>
                    </div>
                    
                    <div class="request-item">
                        <div class="request-header">
                            <div class="request-title">Sarah Johnson <span class="blood-group-badge" style="background-color: #448AFF;">B+</span></div>
                            <div class="request-status status-approved">Approved</div>
                        </div>
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Contact:</span> David Johnson (9845567890)
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Hospital:</span> Central Medical Center
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Units Needed:</span> 1
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Urgency:</span> <span style="color: #ff8c00;">High</span>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn btn-success"><i class="fab fa-whatsapp"></i> Send WhatsApp</button>
                            <button class="btn"><i class="fas fa-edit"></i> Update Status</button>
                        </div>
                        
                        <div class="message-preview">
                            <h3>WhatsApp Broadcast Preview:</h3>
                            <p>🆘 URGENT: B+ blood needed at Central Medical Center for Sarah Johnson. Contact David Johnson at 9845567890. Please share with potential donors! 🩸</p>
                        </div>
                    </div>
                    
                    <div class="request-item">
                        <div class="request-header">
                            <div class="request-title">Robert Brown <span class="blood-group-badge" style="background-color: #7C4DFF;">AB+</span></div>
                            <div class="request-status status-rejected">Rejected</div>
                        </div>
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Contact:</span> Emily Brown (9845513579)
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Hospital:</span> Westside Hospital
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Reason for Rejection:</span> Incomplete information
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn"><i class="fas fa-comment"></i> Contact Requester</button>
                            <button class="btn"><i class="fas fa-redo"></i> Reopen Request</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Available Donors Tab -->
            <div class="tab-content" id="donors-tab">
                <h2>Available Donors</h2>
                <p>View donors who are currently available for blood donation based on their blood group and last donation date.</p>
                
                <div class="form-group">
                    <label for="filter-blood-group">Filter by Blood Group</label>
                    <select id="filter-blood-group">
                        <option value="">All Blood Groups</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                
                <div class="request-list">
                    <div class="request-item">
                        <div class="request-header">
                            <div class="request-title">Michael Chen <span class="blood-group-badge" style="background-color: #FF5252;">A+</span></div>
                            <div class="request-status status-available">Available</div>
                        </div>
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Phone:</span> 9845024680
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Last Donation:</span> 4 months ago
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Location:</span> Downtown Area
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn"><i class="fas fa-phone"></i> Contact</button>
                            <button class="btn"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                        </div>
                    </div>
                    
                    <div class="request-item">
                        <div class="request-header">
                            <div class="request-title">Jessica Williams <span class="blood-group-badge" style="background-color: #448AFF;">B+</span></div>
                            <div class="request-status status-available">Available</div>
                        </div>
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Phone:</span> 9845536914
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Last Donation:</span> 5 months ago
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Location:</span> North District
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn"><i class="fas fa-phone"></i> Contact</button>
                            <button class="btn"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                        </div>
                    </div>
                    
                    <div class="request-item">
                        <div class="request-header">
                            <div class="request-title">David Miller <span class="blood-group-badge" style="background-color: #FFC107;">O+</span></div>
                            <div class="request-status status-unavailable">Unavailable</div>
                        </div>
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Phone:</span> 9845578246
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Last Donation:</span> 1 month ago
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Available In:</span> 2 months
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <p>© 2023 Blood Donation Management System. All rights reserved.</p>
            <p>Contact: admin@blooddonation.org | +1 (555) 123-4567</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabId = tab.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Form submission
            const requestForm = document.getElementById('blood-request-form');
            requestForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Basic validation
                const patientName = document.getElementById('patient-name').value;
                const contactName = document.getElementById('contact-name').value;
                const contactPhone = document.getElementById('contact-phone').value;
                const bloodGroup = document.getElementById('blood-group').value;
                const hospital = document.getElementById('hospital').value;
                
                if (!patientName || !contactName || !contactPhone || !bloodGroup || !hospital) {
                    alert('Please fill in all required fields.');
                    return;
                }
                
                // In a real application, you would send this data to the server
                alert('Request submitted successfully! An administrator will review your request shortly.');
                requestForm.reset();
            });
            
            // Approve button functionality
            const approveButtons = document.querySelectorAll('.approve-btn');
            approveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const requestItem = this.closest('.request-item');
                    const statusDiv = requestItem.querySelector('.request-status');
                    
                    statusDiv.textContent = 'Approved';
                    statusDiv.className = 'request-status status-approved';
                    
                    // Add WhatsApp message preview
                    const patientName = requestItem.querySelector('.request-title').textContent.split(' ')[0];
                    const bloodGroup = requestItem.querySelector('.blood-group-badge').textContent;
                    const hospital = requestItem.querySelector('.detail-item:nth-child(2)').textContent.replace('Hospital: ', '');
                    const contactInfo = requestItem.querySelector('.detail-item:first-child').textContent.replace('Contact: ', '');
                    
                    let messagePreview = document.createElement('div');
                    messagePreview.className = 'message-preview';
                    messagePreview.innerHTML = `
                        <h3>WhatsApp Broadcast Preview:</h3>
                        <p>🆘 URGENT: ${bloodGroup} blood needed at ${hospital} for ${patientName}. Contact ${contactInfo}. Please share with potential donors! 🩸</p>
                    `;
                    
                    requestItem.querySelector('.action-buttons').after(messagePreview);
                });
            });
        });
    </script>
</body>
</html>
