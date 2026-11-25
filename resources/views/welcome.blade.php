<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>IT Ticketing System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <style>
    :root {
      --bg: #f3f4f6;
      --surface: #ffffff;
      --text: #111827;
      --text-secondary: #6b7280;
      --border: #e5e7eb;
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --primary-light: #dbeafe;
      --success: #10b981;
      --success-light: #d1fae5;
      --warning: #f59e0b;
      --warning-light: #fef3c7;
      --danger: #ef4444;
      --danger-light: #fee2e2;
      --info: #3b82f6;
      --info-light: #dbeafe;
      --sidebar-width: 260px;
      --radius: 12px;
      --shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
      --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.05);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .hidden { display: none !important; }

    /* ----------- LANDING PAGE ---------- */
    .landing-view {
      min-height: 100vh;
      display: flex; flex-direction: column;
    }
    .landing-hero {
      background: linear-gradient(135deg, #ffffff 0%, #eef2ff 100%);
      border-bottom: 1px solid var(--border);
      padding: 28px 32px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .landing-brand {
      display: flex; align-items: center; gap: 12px;
      font-size: 1.25rem; font-weight: 800; color: var(--text);
      cursor: pointer;
    }
    .landing-brand img {
      height: 36px;
      width: auto;
      object-fit: contain;
    }
    .landing-brand-icon {
      width: 40px; height: 40px; background: var(--primary);
      color: white; border-radius: 12px; display: grid; place-items: center;
      font-size: 1.2rem;
    }
    .landing-btn-text {
      background: none; border: none; color: var(--primary);
      font-weight: 600; cursor: pointer; font-size: 0.95rem;
    }

    .landing-body {
      flex: 1; display: grid; place-items: center;
      padding: 32px;
      background:
        radial-gradient(circle at 10% 20%, rgba(37,99,235,0.04) 0%, transparent 25%),
        radial-gradient(circle at 90% 80%, rgba(16,185,129,0.04) 0%, transparent 25%),
        var(--bg);
    }
    .landing-card {
      background: white; border: 1px solid var(--border);
      border-radius: var(--radius); box-shadow: var(--shadow-lg);
      width: 100%; max-width: 640px; padding: 32px;
    }
    .landing-card h2 { font-size: 1.5rem; margin-bottom: 6px; }
    .landing-sub { color: var(--text-secondary); margin-bottom: 22px; }

    .form-group { margin-bottom: 16px; }
    .form-group label {
      display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.85rem; color: var(--text-secondary);
    }
    .form-control {
      width: 100%; padding: 10px 12px; border: 1px solid var(--border);
      border-radius: 8px; font-size: 0.95rem; outline: none; font-family: inherit; background: white;
    }
    .form-control:focus { border-color: var(--primary); }
    textarea.form-control { resize: vertical; min-height: 110px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 18px; border: none; border-radius: 10px;
      font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: .15s;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-block { width: 100%; justify-content: center; padding: 12px; font-size: 1rem; }

    .success-box {
      background: var(--success-light); color: #065f46;
      border: 1px solid #a7f3d0; border-radius: 10px; padding: 20px; text-align: center;
    }
    .success-box h3 { margin-bottom: 6px; }

    .landing-footer {
      text-align: center; padding: 20px; color: var(--text-secondary); font-size: 0.85rem;
    }

    /* ----------- APP / DASHBOARD ---------- */
    .app { display: flex; height: 100vh; overflow: hidden; }

    /* Sidebar */
    .sidebar {
      width: var(--sidebar-width); background: var(--surface);
      border-right: 1px solid var(--border); display: flex; flex-direction: column;
      padding: 24px 0; position: fixed; height: 100vh; z-index: 50;
    }
    .brand { display: flex; align-items: center; gap: 12px; padding: 0 24px 24px; font-size: 1.25rem; font-weight: 700; color: var(--text); cursor: pointer; }
    .brand-icon { width: 36px; height: 36px; background: var(--primary); color: white; border-radius: 10px; display: grid; place-items: center; font-size: 1.1rem; }
    .nav { flex: 1; }
    .nav-item {
      display: flex; align-items: center; gap: 12px; padding: 12px 24px; margin: 0 12px 4px;
      border-radius: 10px; cursor: pointer; color: var(--text-secondary); font-weight: 500;
      transition: .15s; text-decoration: none;
    }
    .nav-item:hover { background: var(--bg); color: var(--text); }
    .nav-item.active { background: var(--primary-light); color: var(--primary); }
    .nav-item.signout-nav { color: #dc2626; font-weight: 600; }
    .nav-item.signout-nav:hover { background: #fee2e2; color: #b91c1c; }
    .user {
      padding: 16px 24px 0; border-top: 1px solid var(--border); margin: 0 16px;
      display: flex; align-items: center; gap: 12px;
    }
    .avatar { width: 36px; height: 36px; background: var(--primary); color: white; border-radius: 50%; display: grid; place-items: center; font-weight: 600; font-size:0.85rem; }

    /* Main */
    .main {
      margin-left: var(--sidebar-width); flex: 1; padding: 32px; overflow-y: auto;
      max-width: calc(100vw - var(--sidebar-width)); min-height: 100vh;
    }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .header h1 { font-size: 1.6rem; font-weight: 700; }
    .btn-secondary { background: white; border: 1px solid var(--border); color: var(--text); }
    .btn-secondary:hover { background: var(--bg); }

    /* Stats */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
    .stat-label { font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 6px; }
 .stat-value { font-size: 1.75rem; font-weight: 700; }
    .stat-card.open .stat-value { color: var(--info); }
    .stat-card.progress .stat-value { color: var(--warning); }
    .stat-card.resolved .stat-value { color: var(--success); }
    .stat-card.critical .stat-value { color: var(--danger); }

    /* Toolbar */
    .toolbar { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
    .search { position: relative; flex: 1; min-width: 240px; }
    .search input { width: 100%; padding: 10px 14px 10px 38px; border: 1px solid var(--border); border-radius: 10px; font-size: 0.9rem; outline: none; }
    .search input:focus { border-color: var(--primary); }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
    .select { padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; background: white; font-size: 0.9rem; outline: none; cursor: pointer; }

    /* Table */
    .panel { background: white; border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
    .ticket-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .ticket-table th, .ticket-table td { padding: 14px 18px; text-align: left; }
    .ticket-table th { background: #f9fafb; font-weight: 600; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid var(--border); }
    .ticket-table tbody tr { border-bottom: 1px solid var(--border); cursor: pointer; transition: .12s; }
    .ticket-table tbody tr:hover { background: #f9fafb; }
    .ticket-table tbody tr:last-child { border-bottom: none; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 600; text-transform: capitalize; }
    .badge-open { background: var(--info-light); color: #1e40af; }
    .badge-progress { background: var(--warning-light); color: #92400e; }
    .badge-resolved { background: var(--success-light); color: #065f46; }
    .badge-closed { background: #f3f4f6; color: #374151; }
    .priority { font-weight: 600; font-size: 0.85rem; }
    .priority-low { color: var(--text-secondary); }
    .priority-medium { color: var(--warning); }
    .priority-high { color: var(--danger); }
    .priority-critical { color: #991b1b; background: var(--danger-light); padding: 4px 10px; border-radius: 999px; display: inline-block; font-size: 0.78rem; }
    .ticket-id { font-weight: 600; color: var(--primary); }

    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 100; opacity: 0; pointer-events: none; transition: .2s; }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    .modal { background: white; border-radius: var(--radius); width: 560px; max-width: 92vw; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg); transform: translateY(12px); transition: .2s; }
    .modal-overlay.active .modal { transform: translateY(0); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border); }
    .modal-header h2 { font-size: 1.15rem; }
    .close-btn { background: none; border: none; font-size: 1.4rem; color: var(--text-secondary); cursor: pointer; width: 32px; height: 32px; border-radius: 8px; line-height: 1; }
    .close-btn:hover { background: var(--bg); }
    .modal-body { padding: 20px 24px; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid var(--border); }

    .empty { text-align: center; padding: 48px 24px; color: var(--text-secondary); }
    .empty-icon { font-size: 2.5rem; margin-bottom: 12px; opacity: .7; }

    /* Detail View */
    .detail-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .detail-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 6px; }
    .detail-meta { color: var(--text-secondary); font-size: 0.9rem; }
    .detail-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
    .detail-card { background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow); }
    .detail-card h3 { font-size: 0.85rem; text-transform: uppercase; color: var(--text-secondary); letter-spacing: .04em; margin-bottom: 12px; }
    .comment { padding: 12px 0; border-bottom: 1px solid var(--border); }
    .comment:last-child { border-bottom: none; }
    .comment-author { font-weight: 600; font-size: 0.9rem; margin-bottom: 4px; }
    .comment-time { font-size: 0.78rem; color: var(--text-secondary); margin-bottom: 6px; }
    .comment p { font-size: 0.9rem; color: #374151; }

    /* Analytics */
    .chart-container {
      background: white; border: 1px solid var(--border); border-radius: var(--radius);
      padding: 24px; box-shadow: var(--shadow);
 }
    .chart-box { position: relative; height: 360px; }

    @media (max-width: 1020px) {
      .sidebar { transform: translateX(-100%); position: fixed; }
      .main { margin-left: 0; max-width: 100vw; }
      .detail-grid { grid-template-columns: 1fr; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
      .landing-hero { padding: 20px; }
      .landing-body { padding: 20px; }
      .landing-card { padding: 24px; }
    }
  </style>
@include('partials.branding-head')</head>
<body>

 <!-- LANDING PAGE (Public Submit) -->
  <div id="landing-view" class="landing-view">
    <div class="landing-hero">
      <div class="landing-brand prei-brand" onclick="goHome()">
        @include('partials.brand')
      </div>
      <button class="landing-btn-text" onclick="showLogin()">Sign In / Portal →</button>
    </div>
    <div class="landing-body">
      <div class="landing-card">
        <div id="landingForm">
          <h2>Submit a Support Request</h2>
          <div class="landing-sub">Tell us what's wrong and we'll get back to you shortly.</div>
          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <input class="form-control" id="pubName" placeholder="Jane Doe">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input class="form-control" id="pubEmail" type="email" placeholder="jane@company.com">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Category</label>
              <select class="form-control" id="pubCategory">
                <!-- Dynamically populated -->
              </select>
            </div>
            <div class="form-group">
              <label>Priority</label>
              <select class="form-control" id="pubPriority">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="urgent">Critical</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Subject</label>
            <input class="form-control" id="pubSubject" placeholder="Brief summary of the issue">
 </div>
          <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" id="pubDescription" placeholder="Describe the problem in detail..."></textarea>
          </div>
          <button class="btn btn-primary btn-block" onclick="submitPublicTicket()">Submit Ticket</button>
        </div>
        <div id="landingSuccess" class="hidden">
          <div class="success-box">
            <h3>✅ Ticket Submitted</h3>
            <p>Your ticket ID is <strong id="pubTicketId"></strong>. Our team will reach out to you soon.</p>
            <button class="btn btn-secondary" style="margin-top:14px" onclick="resetLanding()">Submit another</button>
          </div>
        </div>
      </div>
    </div>
    <div class="landing-footer">
      © 2026 HelpDesk Pro — Need help? Contact IT at <a href="#" style="color:var(--text-secondary)">it@pasclresources.com.ph</a>
    </div>
  </div>

  <!-- LOGIN PAGE -->
  <div id="login-view" class="landing-view hidden">
    <div class="landing-hero">
      <div class="landing-brand prei-brand" onclick="goHome()">
        <img src="{{ asset('img/prei-logo.png') }}" alt="">
        <div>PREI HelpDesk</div>
      </div>
      <button class="landing-btn-text" onclick="showPublicSubmit()">Submit Request Form →</button>
    </div>
    <div class="landing-body">
      <div class="landing-card" style="max-width: 440px;">
        <h2>Staff & User Portal</h2>
        @if(session('status'))<p role="status" style="color:#166534">{{ session('status') }}</p>@endif
        <div class="landing-sub">Sign in to track your support tickets.</div>
        
        <form onsubmit="event.preventDefault(); submitLogin();">
        <div id="loginError" role="alert" aria-live="polite" class="hidden" style="background: var(--danger-light); color: #991b1b; padding: 12px; border-radius: 8px; font-size: 0.9rem; margin-bottom: 16px; border: 1px solid #fca5a5;">
          Invalid email or password.
        </div>

        <div class="form-group">
          <label for="loginEmail">Email Address</label>
          <input class="form-control" id="loginEmail" name="email" autocomplete="username" required type="email" placeholder="name@example.com">
        </div>
        <div class="form-group">
          <label for="loginPassword">Password</label>
          <input class="form-control" id="loginPassword" name="password" autocomplete="current-password" required type="password" placeholder="••••••••">
        </div>
        <button id="loginSubmit" type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">Sign In</button>
        </form>
        <p style="margin-top:18px"><a href="{{ route('password.request') }}">Forgot password?</a> · <a href="{{ route('register') }}">Create customer account</a></p>


      </div>
    </div>
    <div class="landing-footer">
      © 2026 HelpDesk Pro — Need help? Contact IT at <a href="#" style="color:var(--text-secondary)">it@pasclresources.com.ph</a>
    </div>
  </div>

  <!-- APP / STAFF DASHBOARD -->
  <div id="app-view" class="app hidden">
    <aside class="sidebar">
      <div class="brand" onclick="goHome()">
        <img src="{{ asset('img/prei-logo.png') }}" alt="" width="50" height="36">
        <div>HelpDesk Pro</div>
      </div>
      <nav class="nav" id="sidebarNav">
        <a href="#" class="nav-item active" onclick="showView('dashboard'); setActive(this); return false;">
          <span>🏠</span> Dashboard
        </a>
        <a href="#" class="nav-item" onclick="showView('tickets'); setActive(this); return false;">
          <span>🎫</span> All Tickets
        </a>
        <a href="#" class="nav-item" onclick="filterStatus('In Progress'); setActive(this); return false;">
          <span>⏳</span> My Tickets
        </a>
        <a href="#" class="nav-item" onclick="filterStatus('Open'); setActive(this); return false;">
          <span>📥</span> Unassigned
        </a>
        <a href="#" class="nav-item" onclick="showView('analytics'); setActive(this); return false;">
          <span>📊</span> Analytics
        </a>
        <a href="#" class="nav-item signout-nav" onclick="logout(); return false;" style="margin-top: 20px; border-top: 1px dashed var(--border); padding-top: 12px;">
          <span>🚪</span> Sign Out
        </a>
      </nav>
      <div class="user">
        <div class="avatar" id="sidebarAvatar">JD</div>
        <div class="user-info" style="flex: 1; min-width: 0;">
          <div id="sidebarUserName" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;">John Doe</div>
          <div class="user-role" id="sidebarUserRole" style="font-size: 0.8rem; color: var(--text-secondary);">IT Administrator</div>
        </div>
        <button onclick="logout()" title="Sign Out" style="background: white; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; color: #dc2626; font-size: 0.78rem; padding: 5px 8px; display: flex; align-items: center; gap: 4px; font-weight: 600; transition: .15s; white-space: nowrap;" onmouseover="this.style.backgroundColor='#fee2e2';" onmouseout="this.style.backgroundColor='white';">
          <span>🚪</span> Exit
        </button>
      </div>
    </aside>

    <main class="main">
      <!-- Dashboard / Ticket List -->
      <div id="access-empty-view" class="hidden" style="padding:32px"><h1>Welcome</h1><p>Your account does not have access to the ticket dashboard. Contact an administrator to request access.</p><button class="btn btn-primary" onclick="openModal()">Create ticket</button></div>
      <div id="dashboard-view">
        <div class="header">
          <h1 id="dashboardTitle">Dashboard</h1>
          <div style="display: flex; gap: 10px; align-items: center;">
            <button class="btn btn-primary" onclick="openModal()">+ New Ticket</button>
            <button class="btn btn-secondary" onclick="logout()" style="display: flex; align-items: center; gap: 6px; font-weight: 500;">
              <span>🚪</span> Sign Out
            </button>
          </div>
        </div>
        <div class="stats-grid">
          <div class="stat-card open">
            <div class="stat-label">Open Tickets</div>
            <div class="stat-value" id="stat-open">0</div>
          </div>
          <div class="stat-card progress">
            <div class="stat-label">In Progress</div>
            <div class="stat-value" id="stat-progress">0</div>
          </div>
          <div class="stat-card resolved">
            <div class="stat-label">Resolved</div>
            <div class="stat-value" id="stat-resolved">0</div>
          </div>
          <div class="stat-card critical">
            <div class="stat-label">Critical Priority</div>
            <div class="stat-value" id="stat-critical">0</div>
          </div>
        </div>

        <div class="header" style="margin-top:8px"><h2 style="font-size:1.15rem">Recent Tickets</h2></div>
        <div class="toolbar">
          <div class="search">
            <span class="search-icon">🔍</span>
            <input id="searchInput" type="text" placeholder="Search tickets..." oninput="renderTickets()">
          </div>
          <select class="select" id="filterStatus" onchange="renderTickets()">
            <option value="">All Status</option>
            <option value="Open">Open</option>
            <option value="In Progress">In Progress</option>
            <option value="Resolved">Resolved</option>
            <option value="Closed">Closed</option>
          </select>
          <select class="select" id="filterPriority" onchange="renderTickets()">
            <option value="">All Priorities</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
        </div>
        <div class="panel">
          <table class="ticket-table">
            <thead>
              <tr>
                <th>Ticket</th>
                <th>Subject</th>
                <th>Requester</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Category</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody id="ticketTableBody"></tbody>
          </table>
          <div id="emptyState" class="hidden">
 <div class="empty">
              <div class="empty-icon">📭</div>
              <div>No tickets found.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ticket Detail -->
      <div id="ticket-detail-view" class="hidden">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
          <button class="btn btn-secondary" onclick="backFromDetail()">← Back</button>
          <button class="btn btn-secondary" onclick="logout()" style="display: flex; align-items: center; gap: 6px; font-weight: 500;">
            <span>🚪</span> Sign Out
          </button>
        </div>
        <div class="detail-header">
          <div>
            <div id="detailTitle" class="detail-title"></div>
            <div id="detailMeta" class="detail-meta"></div>
          </div>
          <div style="display:flex; gap:8px; align-items:center;">
            <select id="detailStatus" class="select" onchange="updateTicketStatusFromDetail()"></select>
            <span id="detailStatusStatic" class="badge hidden"></span>
          </div>
        </div>
        <div class="detail-grid">
          <div class="detail-card">
            <h3>Description</h3>
            <p id="detailDesc" style="line-height:1.6; color:#374151;"></p>
            <h3 style="margin-top:20px">Comments</h3>
            <div id="detailComments"></div>
            <div id="comment-form" style="margin-top:12px">
              <textarea id="commentInput" class="form-control" placeholder="Add a comment..."></textarea>
              <button class="btn btn-primary" style="margin-top:10px" onclick="addComment()">Post Comment</button>
            </div>
          </div>
          <div>
            <div class="detail-card" style="margin-bottom:16px">
              <h3>Details</h3>
              <div id="detailProperties" style="font-size:0.9rem; line-height:2"></div>
            </div>
            <div class="detail-card">
              <h3>Assignee</h3>
              <div id="detailAssignee" style="font-size:0.9rem; margin-top:6px">
                <select id="detailAssigneeSelect" class="form-control" onchange="updateTicketAssignee()" style="margin-top:6px;"></select>
                <div id="detailAssigneeStatic" class="hidden" style="margin-top:6px;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Analytics -->
      <div id="analytics-view" class="hidden">
        <div class="header">
          <h1>Analytics</h1>
          <button class="btn btn-secondary" onclick="logout()" style="display: flex; align-items: center; gap: 6px; font-weight: 500;">
            <span>🚪</span> Sign Out
          </button>
        </div>
        <div class="chart-container">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
 <h2 style="font-size:1.1rem">Ticket Activity</h2>
            <span style="font-size:0.85rem; color:var(--text-secondary)">Past month + Current month</span>
          </div>
          <div class="chart-box">
            <canvas id="ticketChart"></canvas>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Admin New Ticket Modal -->
  <div class="modal-overlay" id="modal">
    <div class="modal">
      <div class="modal-header">
        <h2>Create New Ticket</h2>
        <button class="close-btn" onclick="closeModal()">×</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Subject</label>
          <input class="form-control" id="inSubject" placeholder="Brief summary of the issue">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Category</label>
            <select class="form-control" id="inCategory">
              <!-- Dynamically populated -->
            </select>
          </div>
          <div class="form-group">
            <label>Priority</label>
            <select class="form-control" id="inPriority">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="urgent">Critical</option>
            </select>
          </div>
        </div>
        <div class="form-row" id="modalRequesterRow">
          <div class="form-group">
            <label>Requester Name</label>
            <input class="form-control" id="inRequester" placeholder="Jane Doe">
          </div>
          <div class="form-group">
            <label>Requester Email</label>
            <input class="form-control" id="inRequesterEmail" type="email" placeholder="jane@company.com">
          </div>
        </div>
        <div class="form-row" id="modalAssigneeRow">
          <div class="form-group">
            <label>Assignee</label>
            <select class="form-control" id="inAssignee">
              <option value="">Unassigned</option>
              <!-- Dynamically populated -->
            </select>
          </div>
          <div class="form-group">
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea class="form-control" id="inDescription" placeholder="Detailed description of the issue..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" onclick="createTicket()">Create Ticket</button>
      </div>
    </div>
  </div>

  <script>
    let tickets = [];
    let categories = [];
    let agents = [];
    let chartInstance = null;
    let currentUser = null;
    let csrfToken = '{{ csrf_token() }}';
    function canAccess(permission) {
      return currentUser && (currentUser.role === 'admin' || (currentUser.permissions || []).includes(permission));
    }

    // Helper functions
    function timeAgo(iso) {
      if (!iso) return 'N/A';
      const diff = Math.floor((new Date() - new Date(iso)) / 1000 / 60);
      if (diff < 2) return 'just now';
      if (diff < 60) return diff + 'm ago';
      const h = Math.floor(diff / 60);
      if (h < 24) return h + 'h ago';
      return Math.floor(h / 24) + 'd ago';
    }
    function fmtDate(iso) { if (!iso) return 'N/A'; return new Date(iso).toLocaleString(); }

    function badgeClass(s) {
      if (s === 'open') return 'badge-open';
      if (s === 'in_progress') return 'badge-progress';
      if (s === 'resolved') return 'badge-resolved';
      return 'badge-closed';
    }
    
    function formatStatusText(s) {
      if (s === 'in_progress') return 'In Progress';
      return s.charAt(0).toUpperCase() + s.slice(1);
    }

    function priorityHtml(p) {
      const pLower = p.toLowerCase();
      const pText = pLower === 'urgent' ? 'Critical' : p.charAt(0).toUpperCase() + p.slice(1);
      const cls = pLower === 'urgent' ? 'priority-critical' : ('priority-' + pLower);
      if (pLower === 'urgent') return '<span class="' + cls + '">Critical</span>';
      return '<span class="priority ' + cls + '">' + pText + '</span>';
    }

    function formatPriorityText(p) {
      if (p === 'urgent') return 'Critical';
      return p.charAt(0).toUpperCase() + p.slice(1);
    }

    // Auth flows
    function showLogin() {
      document.getElementById('landing-view').classList.add('hidden');
      document.getElementById('app-view').classList.add('hidden');
      document.getElementById('login-view').classList.remove('hidden');
      document.getElementById('loginError').classList.add('hidden');
      document.getElementById('loginEmail').value = '';
      document.getElementById('loginPassword').value = '';
    }

    function showPublicSubmit() {
      window.location.href = '{{ route('support.create') }}';
    }

    async function submitLogin() {
      const email = document.getElementById('loginEmail').value.trim();
      const password = document.getElementById('loginPassword').value;
      const errorDiv = document.getElementById('loginError');

      if (!email || !password) {
        errorDiv.textContent = 'Please enter email and password.';
        errorDiv.classList.remove('hidden');
        return;
      }

      try {
        const res = await fetch('{{ url('/api') }}/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'
          },
          body: JSON.stringify({ email, password })
        });

        if (res.ok) {
          currentUser = await res.json();
          csrfToken = currentUser.csrf_token;
          document.getElementById('loginPassword').value = '';
          errorDiv.classList.add('hidden');
          await enterDashboard();
        } else {
          const err = await res.json();
          errorDiv.textContent = err.error || err.message || 'Invalid credentials.';
          errorDiv.classList.remove('hidden');
        }
      } catch (err) {
        console.error('Error logging in:', err);
        errorDiv.textContent = 'A connection error occurred.';
        errorDiv.classList.remove('hidden');
      }
    }

    async function logout() {
      try {
        const res = await fetch('{{ url('/api') }}/logout', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'
          }
        });
        if (!res.ok && res.status !== 401) throw new Error('Unable to sign out. Please try again.');
        if (res.ok) csrfToken = (await res.json()).csrf_token;
      } catch (err) {
        alert('Unable to sign out. Please try again.');
        return;
      }
      currentUser = null;
      document.querySelectorAll('[onclick*="showPublicSubmit()"]').forEach(el => el.classList.remove('hidden'));
      sessionStorage.removeItem('helpdesk_user');
      tickets = [];
      showLogin();
    }

    // Dashboard navigation & setup
    async function enterDashboard() {
      // If not logged in, show login page
      if (!currentUser) {
        showLogin();
        return;
      }

      if (currentUser.redirect_url || canAccess('tickets.view')) {
        window.location.href = currentUser.redirect_url || '{{ route('helpdesk.index') }}';
        return;
      }

      document.getElementById('landing-view').classList.add('hidden');
      document.getElementById('login-view').classList.add('hidden');
      document.getElementById('app-view').classList.remove('hidden');

      // Populate Categories & Agents if empty
      if (categories.length === 0) {
        await loadCategories();
      }
      if (agents.length === 0) {
        await loadAgents();
      }

      // Configure Modal view based on role
      configureModalLayout();

      // Configure Sidebar options based on role
      renderSidebar();
      document.querySelectorAll('[onclick*="openModal()"], [onclick*="createTicket()"], [onclick*="showPublicSubmit()"]').forEach(el => {
        el.classList.toggle('hidden', !canAccess('tickets.create'));
      });
      document.getElementById('comment-form').classList.toggle('hidden', !canAccess('tickets.comment'));

      // Load tickets
      if (canAccess('tickets.view')) await fetchTickets();
      else tickets = [];

      // Set default view to dashboard list
      showView('dashboard');
    }

    async function loadCategories() {
      try {
        const res = await fetch('{{ url('/api') }}/categories');
        categories = await res.json();
        
        // Populate public category select and in-app category select
        const catOptionsHtml = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        document.getElementById('pubCategory').innerHTML = catOptionsHtml;
        document.getElementById('inCategory').innerHTML = catOptionsHtml;
      } catch (err) {
        console.error('Error loading categories:', err);
      }
    }

    async function loadAgents() {
      try {
        const res = await fetch('{{ url('/api') }}/agents');
        if (!res.ok) { agents = []; return; }
        agents = await res.json();
        
        // Populate Admin modal assignee select
        const inAgentSelect = document.getElementById('inAssignee');
        inAgentSelect.innerHTML = '<option value="">Unassigned</option>' + 
          agents.map(a => `<option value="${a.id}">${a.name}</option>`).join('');
      } catch (err) {
        console.error('Error loading agents:', err);
      }
    }

    function configureModalLayout() {
      const reqRow = document.getElementById('modalRequesterRow');
      const assRow = document.getElementById('modalAssigneeRow');
      
      if (currentUser.role === 'user') {
        reqRow.classList.add('hidden');
        assRow.classList.add('hidden');
      } else {
        reqRow.classList.remove('hidden');
        assRow.classList.toggle('hidden', !canAccess('tickets.assign'));
      }
    }

    function renderSidebar() {
      const nav = document.getElementById('sidebarNav');
      if (currentUser.role === 'user') {
        nav.innerHTML = `
          <a href="#" class="nav-item active" onclick="showView('dashboard'); setActive(this); return false;">
            <span>🎫</span> My Tickets
          </a>
          <a href="#" class="nav-item" onclick="openModal(); return false;">
            <span>➕</span> New Ticket
          </a>
          <a href="#" class="nav-item signout-nav" onclick="logout(); return false;" style="margin-top: 24px; border-top: 1px dashed var(--border); padding-top: 14px;">
            <span>🚪</span> Sign Out
          </a>
        `;
      } else {
        nav.innerHTML = `
          <a href="#" class="nav-item active" onclick="showView('dashboard'); setActive(this); return false;">
            <span>🏠</span> Dashboard
          </a>
          <a href="#" class="nav-item" onclick="showView('tickets'); setActive(this); return false;">
            <span>🎫</span> All Tickets
          </a>
          <a href="#" class="nav-item" onclick="filterStatus('In Progress'); setActive(this); return false;">
            <span>⏳</span> My Tickets
          </a>
          <a href="#" class="nav-item" onclick="filterStatus('Open'); setActive(this); return false;">
            <span>📥</span> Unassigned
          </a>
          <a href="#" class="nav-item" onclick="showView('analytics'); setActive(this); return false;">
            <span>📊</span> Analytics
          </a>
          <a href="#" class="nav-item signout-nav" onclick="logout(); return false;" style="margin-top: 24px; border-top: 1px dashed var(--border); padding-top: 14px;">
            <span>🚪</span> Sign Out
          </a>
        `;
      }

      nav.querySelectorAll('a').forEach(link => {
        const action = link.getAttribute('onclick') || '';
        if (action.includes('analytics') && !canAccess('analytics.view')) link.remove();
        else if ((action.includes('showView') || action.includes('filterStatus')) && !canAccess('tickets.view')) link.remove();
        else if (action.includes('openModal') && !canAccess('tickets.create')) link.remove();
      });
      if (currentUser.role === 'admin') {
        const usersLink = document.createElement('a');
        usersLink.className = 'nav-item';
        usersLink.href = '{{ route('admin.users.index') }}';
        usersLink.textContent = '👥 User Management';
        nav.insertBefore(usersLink, nav.querySelector('.signout-nav'));
      }

      // Update user info at bottom of sidebar
      document.getElementById('sidebarAvatar').textContent = initials(currentUser.name);
      document.getElementById('sidebarUserName').textContent = currentUser.name;
      document.getElementById('sidebarUserRole').textContent = currentUser.role === 'admin' 
        ? 'IT Administrator' 
        : (currentUser.role === 'agent' ? 'System Developer' : 'Client');
    }

    async function fetchTickets() {
      try {
        const url = `{{ url('/api/tickets') }}`;
        const res = await fetch(url);
        if (!res.ok) { tickets = []; renderAll(); return; }
        tickets = await res.json();
        renderAll();
      } catch (err) {
        console.error('Error fetching tickets:', err);
      }
    }

    function renderAll() {
      updateStats();
      renderTickets();
    }

    function updateStats() {
      document.getElementById('dashboardTitle').textContent = currentUser.role === 'user' ? 'My Support Tickets' : 'Dashboard';

      document.getElementById('stat-open').textContent = tickets.filter(t => t.status === 'open').length;
      document.getElementById('stat-progress').textContent = tickets.filter(t => t.status === 'in_progress').length;
      document.getElementById('stat-resolved').textContent = tickets.filter(t => t.status === 'resolved').length;
      document.getElementById('stat-critical').textContent = tickets.filter(t => t.priority === 'urgent' && t.status !== 'closed').length;
      
      // If client user, adjust stat titles slightly
      const statCards = document.querySelectorAll('.stat-card');
      if (currentUser.role === 'user') {
        statCards[0].querySelector('.stat-label').textContent = 'My Open Tickets';
        statCards[1].querySelector('.stat-label').textContent = 'My In Progress';
        statCards[2].querySelector('.stat-label').textContent = 'My Resolved';
        statCards[3].querySelector('.stat-label').textContent = 'My Critical';
      } else {
        statCards[0].querySelector('.stat-label').textContent = 'Open Tickets';
        statCards[1].querySelector('.stat-label').textContent = 'In Progress';
        statCards[2].querySelector('.stat-label').textContent = 'Resolved';
        statCards[3].querySelector('.stat-label').textContent = 'Critical Priority';
      }
    }

    function renderTickets() {
      const q = document.getElementById('searchInput').value.toLowerCase().trim();
      const st = document.getElementById('filterStatus').value;
      const pr = document.getElementById('filterPriority').value;
      const tb = document.getElementById('ticketTableBody');
      const empty = document.getElementById('emptyState');
      
      let list = tickets.filter(t => {
        // Search matches ID, Subject/Title, User/Requester name, or email
        const userMatch = t.user && (t.user.name.toLowerCase().includes(q) || t.user.email.toLowerCase().includes(q));
        const ticketMatch = t.title.toLowerCase().includes(q) || String(t.id).includes(q) || t.ticket_number.toLowerCase().includes(q);
        if (q && !ticketMatch && !userMatch) return false;
        
        // Match status dropdown filters
        const mockSt = st === 'Open' ? 'open' : (st === 'In Progress' ? 'in_progress' : (st === 'Resolved' ? 'resolved' : (st === 'Closed' ? 'closed' : '')));
        if (mockSt && t.status !== mockSt) return false;
        
        // Match priority filters
        const mockPr = pr === 'Low' ? 'low' : (pr === 'Medium' ? 'medium' : (pr === 'High' ? 'high' : (pr === 'Critical' ? 'urgent' : '')));
        if (mockPr && t.priority !== mockPr) return false;
        
        return true;
      });

      if (!list.length) { 
        tb.innerHTML = ''; 
        empty.classList.remove('hidden'); 
        return; 
      }
      
      empty.classList.add('hidden');
      tb.innerHTML = list.map(t =>
        `<tr onclick="openTicket(${t.id})">
          <td class="ticket-id">#${t.ticket_number}</td>
          <td>${t.title}</td>
          <td>${t.user ? t.user.name : 'Unknown'}</td>
          <td>${priorityHtml(t.priority)}</td>
          <td><span class="badge ${badgeClass(t.status)}">${formatStatusText(t.status)}</span></td>
          <td>${t.category ? t.category.name : 'Other'}</td>
          <td>${timeAgo(t.updated_at)}</td>
        </tr>`
      ).join('');
    }

    let currentTicketId = null;

    function openTicket(id) {
      const t = tickets.find(x => x.id === id); 
      if (!t) return;
      currentTicketId = id;
      showView('detail');
      
      document.getElementById('detailTitle').textContent = t.title;
      document.getElementById('detailMeta').innerHTML = `Ticket #${t.ticket_number} • Created ${fmtDate(t.created_at)}`;
      document.getElementById('detailDesc').textContent = t.description;
      
      // Status options (conditional dropdown or badge for client)
      const sel = document.getElementById('detailStatus');
      const staticStatus = document.getElementById('detailStatusStatic');
      
      if (!canAccess('tickets.status')) {
        sel.classList.add('hidden');
        staticStatus.classList.remove('hidden');
        staticStatus.className = `badge ${badgeClass(t.status)}`;
        staticStatus.textContent = formatStatusText(t.status);
      } else {
        sel.classList.remove('hidden');
        staticStatus.classList.add('hidden');
        sel.innerHTML = [
          { val: 'open', text: 'Open' },
          { val: 'in_progress', text: 'In Progress' },
          { val: 'resolved', text: 'Resolved' },
          { val: 'closed', text: 'Closed' }
        ].map(s => `<option value="${s.val}" ${t.status === s.val ? 'selected' : ''}>${s.text}</option>`).join('');
      }
      
      document.getElementById('detailProperties').innerHTML = `
        <div><strong>Priority:</strong> ${formatPriorityText(t.priority)}</div>
        <div><strong>Category:</strong> ${t.category ? t.category.name : 'N/A'}</div>
        <div><strong>Requester:</strong> ${t.user ? `${t.user.name} (${t.user.email})` : 'N/A'}</div>
      `;
      
      // Assignee (dropdown for staff, static for client)
      const agentSel = document.getElementById('detailAssigneeSelect');
      const staticAssignee = document.getElementById('detailAssigneeStatic');
      
      if (!canAccess('tickets.assign')) {
        agentSel.classList.add('hidden');
        staticAssignee.classList.remove('hidden');
        staticAssignee.innerHTML = t.agent
          ? `<div style="display:flex;align-items:center;gap:10px"><div class="avatar" style="width:28px;height:28px;font-size:0.75rem">${initials(t.agent.name)}</div><div>${t.agent.name}</div></div>`
          : `<span style="color:var(--text-secondary)">Unassigned</span>`;
      } else {
        agentSel.classList.remove('hidden');
        staticAssignee.classList.add('hidden');
        agentSel.innerHTML = '<option value="">Unassigned</option>' + 
          agents.map(a => `<option value="${a.id}" ${t.agent_id == a.id ? 'selected' : ''}>${a.name}</option>`).join('');
      }
      
      renderComments(t);
    }

    async function updateTicketStatusFromDetail() {
      if (!currentTicketId) return;
      const status = document.getElementById('detailStatus').value;
      try {
        const res = await fetch(`{{ url('/api') }}/tickets/${currentTicketId}/status`, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'
          },
          body: JSON.stringify({ status })
        });
        if (res.ok) {
          const updatedTicket = await res.json();
          // Update local copy
          const idx = tickets.findIndex(x => x.id === currentTicketId);
          if (idx !== -1) {
            tickets[idx] = updatedTicket;
            renderAll();
          }
        }
      } catch (err) {
        console.error('Error updating status:', err);
      }
    }

    async function updateTicketAssignee() {
      if (!currentTicketId) return;
      const agent_id = document.getElementById('detailAssigneeSelect').value || null;
      try {
        const res = await fetch(`{{ url('/api') }}/tickets/${currentTicketId}/assign`, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'
          },
          body: JSON.stringify({ agent_id })
        });
        if (res.ok) {
          const updatedTicket = await res.json();
          const idx = tickets.findIndex(x => x.id === currentTicketId);
          if (idx !== -1) {
            tickets[idx] = updatedTicket;
            renderAll();
          }
        }
      } catch (err) {
        console.error('Error assigning agent:', err);
      }
    }

    function renderComments(t) {
      const cBox = document.getElementById('detailComments');
      if (!t.comments || !t.comments.length) { 
        cBox.innerHTML = '<p style="color:var(--text-secondary);font-size:0.9rem">No comments yet.</p>'; 
        return; 
      }
      cBox.innerHTML = t.comments.map(c =>
        `<div class="comment">
          <div style="display:flex; justify-content:space-between">
            <div class="comment-author">${c.user ? c.user.name : 'Unknown'}</div>
            <div class="comment-time">${timeAgo(c.created_at)}</div>
          </div>
          <p>${c.body}</p>
        </div>`
      ).join('');
    }

    async function addComment() {
      const input = document.getElementById('commentInput');
      const text = input.value.trim(); 
      if (!text || !currentTicketId) return;
      
      try {
        const res = await fetch(`{{ url('/api') }}/tickets/${currentTicketId}/comments`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            body: text,
            user_id: currentUser.id
          })
        });
        if (res.ok) {
          const newComment = await res.json();
          const t = tickets.find(x => x.id === currentTicketId);
          if (t) {
            if (!t.comments) t.comments = [];
            t.comments.push(newComment);
            t.updated_at = new Date().toISOString();
            renderComments(t);
            renderAll();
          }
          input.value = '';
        }
      } catch (err) {
        console.error('Error adding comment:', err);
      }
    }

    async function showView(view) {
      document.getElementById('access-empty-view').classList.add('hidden');
      document.getElementById('dashboard-view').classList.add('hidden');
      document.getElementById('ticket-detail-view').classList.add('hidden');
      document.getElementById('analytics-view').classList.add('hidden');
      if (!canAccess('tickets.view')) {
        document.getElementById('access-empty-view').classList.remove('hidden');
        return;
      }
      if (view === 'detail') {
        document.getElementById('ticket-detail-view').classList.remove('hidden');
      } else if (view === 'analytics') {
        if (!canAccess('analytics.view')) return;
        try {
          const res = await fetch('{{ url('/api/analytics') }}', { headers: { 'Accept': 'application/json' } });
          if (!res.ok) { alert('You do not have access to analytics.'); return; }
          tickets = await res.json();
        } catch (err) { alert('Unable to load analytics. Please try again.'); return; }
        document.getElementById('analytics-view').classList.remove('hidden');
        renderChart();
      } else {
        document.getElementById('dashboard-view').classList.remove('hidden');
        if (view === 'tickets') {
          document.getElementById('searchInput').value = '';
          document.getElementById('filterStatus').value = '';
          document.getElementById('filterPriority').value = '';
          renderTickets();
        }
        if (view === 'dashboard') {
          document.getElementById('searchInput').value = '';
          document.getElementById('filterStatus').value = '';
          document.getElementById('filterPriority').value = '';
          renderTickets();
        }
      }
    }

    function backFromDetail() {
      showView('dashboard');
    }

    function filterStatus(status) {
      showView('dashboard');
      const mockSt = status === 'In Progress' ? 'In Progress' : status;
      document.getElementById('filterStatus').value = mockSt;
      renderTickets();
    }

    function setActive(el) {
      document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
      el.classList.add('active');
    }

    function openModal() { if (canAccess('tickets.create')) document.getElementById('modal').classList.add('active'); }
    function closeModal() { document.getElementById('modal').classList.remove('active'); }

    async function createTicket() {
      const subject = document.getElementById('inSubject').value.trim();
      const category_id = document.getElementById('inCategory').value;
      const priority = document.getElementById('inPriority').value;
      const description = document.getElementById('inDescription').value.trim();
      
      let requester_name = '';
      let requester_email = '';
      let agent_id = null;

      if (currentUser.role === 'user') {
        requester_name = currentUser.name;
        requester_email = currentUser.email;
      } else {
        requester_name = document.getElementById('inRequester').value.trim() || 'Unknown';
        requester_email = document.getElementById('inRequesterEmail').value.trim() || 'user1@example.com';
        agent_id = document.getElementById('inAssignee').value || null;
      }
      
      if (!subject || !description) { 
        alert('Please enter both subject and description.'); 
        return; 
      }
      
      try {
        const res = await fetch('{{ url('/api') }}/tickets', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'
          },
          body: JSON.stringify({
            title: subject,
            category_id,
            priority,
            requester_name,
            requester_email,
            agent_id,
            description
          })
        });
        if (res.ok) {
          const newTicket = await res.json();
          tickets.unshift(newTicket);
          
          document.getElementById('inSubject').value = '';
          document.getElementById('inDescription').value = '';
          document.getElementById('inRequester').value = '';
          document.getElementById('inRequesterEmail').value = '';
          document.getElementById('inAssignee').value = '';
          
          closeModal();
          renderAll();
        }
      } catch (err) {
        console.error('Error creating ticket:', err);
      }
    }

    function initials(name) {
      if (!name) return '??';
      return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
    }

    // Landing page public ticket submission
    async function submitPublicTicket() {
      const name = document.getElementById('pubName').value.trim() || 'Anonymous';
      const email = document.getElementById('pubEmail').value.trim();
      const category_id = document.getElementById('pubCategory').value;
      const priority = document.getElementById('pubPriority').value;
      const subject = document.getElementById('pubSubject').value.trim();
      const description = document.getElementById('pubDescription').value.trim();
      
      if (!subject || !description || !email) { 
        alert('Please fill out email, subject, and description.'); 
        return; 
      }
      
      try {
        const res = await fetch('{{ url('/api') }}/tickets', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'
          },
          body: JSON.stringify({
            title: subject,
            category_id,
            priority,
            requester_name: name,
            requester_email: email,
            description
          })
        });
        if (res.ok) {
          const newTicket = await res.json();
          tickets.unshift(newTicket);
          
          document.getElementById('landingForm').classList.add('hidden');
          document.getElementById('landingSuccess').classList.remove('hidden');
          document.getElementById('pubTicketId').textContent = '#' + newTicket.ticket_number;
          renderAll();
        }
      } catch (err) {
        console.error('Error submitting public ticket:', err);
      }
    }

    function resetLanding() {
      document.getElementById('landingForm').classList.remove('hidden');
      document.getElementById('landingSuccess').classList.add('hidden');
      ['pubName', 'pubEmail', 'pubSubject', 'pubDescription'].forEach(id => document.getElementById(id).value = '');
      document.getElementById('pubCategory').selectedIndex = 0;
      document.getElementById('pubPriority').selectedIndex = 1;
    }

    function goHome() {
      if (currentUser) {
        enterDashboard();
      } else {
        document.getElementById('app-view').classList.add('hidden');
        document.getElementById('login-view').classList.add('hidden');
        document.getElementById('landing-view').classList.remove('hidden');
        resetLanding();
      }
    }

    // Chart logic
    function toDayKey(iso) {
      const d = new Date(iso);
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const da = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${da}`;
    }

    function dayLabel(key) {
      const [, m, d] = key.split('-');
      const month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][parseInt(m) - 1];
      return `${month} ${parseInt(d)}`;
    }

    function renderChart() {
      const canvas = document.getElementById('ticketChart');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      if (chartInstance) chartInstance.destroy();

      const today = new Date();
      const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const labels = [];
      const openedData = [];
      const closedData = [];

      const openedCounts = {};
      const closedCounts = {};

      tickets.forEach(t => {
        if (t.created_at) {
          const k = toDayKey(t.created_at);
          openedCounts[k] = (openedCounts[k] || 0) + 1;
        }
        if (t.status === 'closed' && t.updated_at) {
          const k = toDayKey(t.updated_at);
          closedCounts[k] = (closedCounts[k] || 0) + 1;
        }
      });

      for (let d = new Date(start); d <= today; d.setDate(d.getDate() + 1)) {
        const k = toDayKey(d.toISOString());
        labels.push(dayLabel(k));
        openedData.push(openedCounts[k] || 0);
        closedData.push(closedCounts[k] || 0);
      }

      chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Opened',
              data: openedData,
              borderColor: '#2563eb',
              backgroundColor: 'rgba(37,99,235,0.08)',
              tension: 0.3,
              fill: true,
              pointRadius: 3,
              pointBackgroundColor: '#2563eb',
            },
            {
              label: 'Closed',
              data: closedData,
              borderColor: '#10b981',
              backgroundColor: 'rgba(16,185,129,0.08)',
              tension: 0.3,
              fill: true,
              pointRadius: 3,
              pointBackgroundColor: '#10b981',
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { position: 'top', labels: { usePointStyle: true } },
            tooltip: {
              backgroundColor: 'rgba(17,24,39,0.9)',
              padding: 10,
              cornerRadius: 8,
              titleFont: { size: 13 },
              bodyFont: { size: 13 }
            }
          },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
          }
        }
      });
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    
    // Automatically load categories and try to restore user session on start
    (async function init() {
      await loadCategories();
      sessionStorage.removeItem('helpdesk_user');
      try {
        const res = await fetch('{{ url('/api') }}/me', { headers: { 'Accept': 'application/json' } });
        if (res.ok) { currentUser = await res.json(); await enterDashboard(); }
        else if (location.pathname.endsWith('/login')) showLogin();
      } catch (err) { if (location.pathname.endsWith('/login')) showLogin(); }
    })();
  </script> 
</body>
</html>

