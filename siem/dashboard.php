<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Admin-only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /public/index.php');
    exit;
}

$pageTitle = 'SIEM Dashboard';

$total_logs = $conn->query("SELECT COUNT(*) as c FROM request_logs")->fetch_assoc()['c'];
$total_alerts = $conn->query("SELECT COUNT(*) as c FROM alerts")->fetch_assoc()['c'];
$critical_alerts = $conn->query("SELECT COUNT(*) as c FROM alerts WHERE severity IN ('high','critical')")->fetch_assoc()['c'];
$alert_types = $conn->query("SELECT rule_name, COUNT(*) as cnt FROM alerts GROUP BY rule_name ORDER BY cnt DESC")->fetch_all(MYSQLI_ASSOC);
$recent_alerts = $conn->query("SELECT a.*, r.ip, r.method, r.endpoint, r.user_id as req_user_id, r.timestamp as req_time FROM alerts a JOIN request_logs r ON a.log_id = r.id ORDER BY a.created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$recent_logs = $conn->query("SELECT * FROM request_logs ORDER BY timestamp DESC LIMIT 30")->fetch_all(MYSQLI_ASSOC);
$top_ips = $conn->query("SELECT ip, COUNT(*) as cnt FROM request_logs GROUP BY ip ORDER BY cnt DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>breachme SIEM — Defender Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --s-bg: #050505;
            --s-surface: #0a0a0a;
            --s-surface2: #0f0f0f;
            --s-cyan: #00d4ff;
            --s-green: #00ff88;
            --s-red: #ff3b3b;
            --s-orange: #ff6b2b;
            --s-yellow: #ffb800;
            --s-purple: #a78bfa;
            --s-text: #ccc;
            --s-muted: #444;
            --s-dim: #333;
            --s-border: rgba(255,255,255,0.05);
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: var(--s-bg); color: var(--s-text); margin: 0; }
        
        /* ---- Top bar ---- */
        .siem-bar {
            background: rgba(5,5,5,0.9);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--s-border);
            height: 48px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .siem-brand {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--s-text);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .siem-brand i { color: var(--s-cyan); }
        
        /* ---- Stat cards ---- */
        .stat {
            background: var(--s-surface2);
            border: 1px solid var(--s-border);
            border-radius: 6px;
            padding: 16px 20px;
        }
        .stat-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--s-muted);
            margin-bottom: 6px;
        }
        .stat-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1;
        }
        
        /* ---- Panel ---- */
        .panel {
            background: var(--s-surface2);
            border: 1px solid var(--s-border);
            border-radius: 6px;
            overflow: hidden;
        }
        .panel-header {
            padding: 10px 16px;
            border-bottom: 1px solid var(--s-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .panel-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--s-muted);
            font-weight: 500;
        }
        .panel-body {
            padding: 0;
        }
        
        /* ---- Log table ---- */
        .log-tbl {
            width: 100%;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            border-collapse: collapse;
        }
        .log-tbl th {
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--s-muted);
            font-weight: 500;
            padding: 8px 12px;
            border-bottom: 1px solid var(--s-border);
            text-align: left;
        }
        .log-tbl td {
            padding: 6px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            color: #888;
            vertical-align: middle;
        }
        .log-tbl tr:hover td { background: rgba(255,255,255,0.01); }
        
        /* ---- Method tags ---- */
        .m-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6rem;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 2px;
            letter-spacing: 0.5px;
        }
        .m-GET { background: rgba(0,212,255,0.08); color: var(--s-cyan); }
        .m-POST { background: rgba(0,255,136,0.08); color: var(--s-green); }
        .m-DELETE { background: rgba(255,59,59,0.08); color: var(--s-red); }
        
        /* ---- Rule tags ---- */
        .r-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.6rem;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 2px;
            letter-spacing: 0.5px;
        }
        .r-XSS { color: var(--s-red); border: 1px solid rgba(255,59,59,0.2); }
        .r-BAC { color: var(--s-orange); border: 1px solid rgba(255,107,43,0.2); }
        .r-IDOR { color: var(--s-cyan); border: 1px solid rgba(0,212,255,0.2); }
        .r-BUSINESS_LOGIC { color: var(--s-yellow); border: 1px solid rgba(255,184,0,0.2); }
        
        /* ---- Severity ---- */
        .sev-critical { color: var(--s-red); }
        .sev-high { color: var(--s-orange); }
        .sev-medium { color: var(--s-yellow); }
        .sev-low { color: var(--s-cyan); }
        
        /* ---- TP/FP buttons ---- */
        .classify-btn {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.58rem;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 2px;
            cursor: pointer;
            letter-spacing: 0.5px;
            background: transparent;
        }
        .tp-btn { color: var(--s-green); border: 1px solid rgba(0,255,136,0.2); }
        .tp-btn:hover, .tp-btn.active { background: var(--s-green); color: #050505; }
        .fp-btn { color: var(--s-red); border: 1px solid rgba(255,59,59,0.2); }
        .fp-btn:hover, .fp-btn.active { background: var(--s-red); color: #fff; }
        
        /* ---- Alert row ---- */
        .alert-row {
            padding: 10px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            border-left: 2px solid transparent;
            transition: background 0.1s;
        }
        .alert-row:hover { background: rgba(255,255,255,0.01); }
        .alert-row.sev-critical-bar { border-left-color: var(--s-red); }
        .alert-row.sev-high-bar { border-left-color: var(--s-orange); }
        .alert-row.sev-medium-bar { border-left-color: var(--s-yellow); }
        
        /* ---- Timeline ---- */
        .tl-item {
            position: relative;
            padding-left: 20px;
            padding-bottom: 16px;
            border-left: 1px solid rgba(255,255,255,0.04);
            margin-left: 4px;
        }
        .tl-item::before {
            content: '';
            position: absolute;
            left: -3px; top: 5px;
            width: 5px; height: 5px;
            border-radius: 50%;
            background: var(--s-muted);
        }
        .tl-item.tl-critical::before { background: var(--s-red); }
        .tl-item.tl-high::before { background: var(--s-orange); }
        .tl-item.tl-medium::before { background: var(--s-yellow); }
        
        /* ---- Live indicator ---- */
        .live-dot {
            width: 6px; height: 6px;
            background: var(--s-green);
            border-radius: 50%;
            display: inline-block;
            animation: blink 2s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .scroll-y { max-height: 420px; overflow-y: auto; }
        .scroll-y::-webkit-scrollbar { width: 4px; }
        .scroll-y::-webkit-scrollbar-thumb { background: #1a1a1a; border-radius: 2px; }
    </style>
</head>
<body>
    <!-- Top bar -->
    <div class="siem-bar">
        <div class="siem-brand me-auto">
            <i class="bi bi-shield-fill-check"></i>
            <span>breachme</span>
            <span style="color:var(--s-muted);font-weight:400;margin-left:4px;">/ siem</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:var(--s-muted);display:flex;align-items:center;gap:6px;">
                <span class="live-dot"></span> MONITORING
            </span>
            <a href="/admin/" style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:var(--s-muted);text-decoration:none;border:1px solid var(--s-border);padding:3px 10px;border-radius:3px;">
                ← ADMIN PANEL
            </a>
        </div>
    </div>

    <div class="container-fluid px-4 py-3">
        <!-- Stats -->
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="stat">
                    <div class="stat-label">Total Requests</div>
                    <div class="stat-value" style="color:var(--s-cyan);"><?= number_format($total_logs) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat">
                    <div class="stat-label">Alerts</div>
                    <div class="stat-value" style="color:var(--s-yellow);"><?= number_format($total_alerts) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat">
                    <div class="stat-label">Critical / High</div>
                    <div class="stat-value" style="color:var(--s-red);"><?= number_format($critical_alerts) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat">
                    <div class="stat-label">Rules Triggered</div>
                    <div style="margin-top:6px;display:flex;gap:4px;flex-wrap:wrap;">
                        <?php foreach ($alert_types as $at): ?>
                        <span class="r-tag r-<?= $at['rule_name'] ?>"><?= $at['rule_name'] ?>:<?= $at['cnt'] ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($alert_types)): ?><span style="font-family:'JetBrains Mono',monospace;font-size:0.65rem;color:var(--s-muted);">—</span><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left: Alerts + Logs -->
            <div class="col-lg-7">
                <!-- Alerts -->
                <div class="panel mb-3">
                    <div class="panel-header">
                        <span class="panel-title">Security Alerts</span>
                        <button onclick="location.reload()" style="background:transparent;border:1px solid var(--s-border);color:var(--s-muted);font-family:'JetBrains Mono',monospace;font-size:0.6rem;padding:2px 8px;border-radius:2px;cursor:pointer;">REFRESH</button>
                    </div>
                    <div class="panel-body scroll-y">
                        <?php if (empty($recent_alerts)): ?>
                        <div style="text-align:center;padding:3rem;color:var(--s-muted);">
                            <div style="font-family:'JetBrains Mono',monospace;font-size:0.75rem;">No alerts detected</div>
                            <div style="font-size:0.7rem;color:#333;margin-top:4px;">Start attacking to generate alerts</div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_alerts as $a): ?>
                        <div class="alert-row sev-<?= $a['severity'] ?>-bar">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="r-tag r-<?= $a['rule_name'] ?>"><?= $a['rule_name'] ?></span>
                                    <span class="sev-<?= $a['severity'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:0.58rem;letter-spacing:0.5px;"><?= strtoupper($a['severity']) ?></span>
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="classify-btn tp-btn <?= $a['is_true_positive']===1?'active':'' ?>" onclick="classifyAlert(<?= $a['id'] ?>,1)">TP</button>
                                    <button class="classify-btn fp-btn <?= $a['is_true_positive']===0&&$a['is_true_positive']!==null?'active':'' ?>" onclick="classifyAlert(<?= $a['id'] ?>,0)">FP</button>
                                </div>
                            </div>
                            <div style="font-size:0.72rem;color:#777;margin-top:4px;"><?= htmlspecialchars($a['description']) ?></div>
                            <div style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:#333;margin-top:4px;">
                                <span class="m-tag m-<?= $a['method'] ?>"><?= $a['method'] ?></span>
                                <?= htmlspecialchars($a['endpoint']) ?> · <?= $a['ip'] ?> · <?= date('H:i:s', strtotime($a['req_time'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Logs -->
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title">HTTP Request Log</span>
                        <span style="font-family:'JetBrains Mono',monospace;font-size:0.58rem;color:#333;">LAST 30</span>
                    </div>
                    <div class="panel-body scroll-y">
                        <table class="log-tbl">
                            <thead><tr><th>Time</th><th>Method</th><th>Endpoint</th><th>IP</th><th>UID</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td style="color:#444;"><?= date('H:i:s', strtotime($log['timestamp'])) ?></td>
                                <td><span class="m-tag m-<?= $log['method'] ?>"><?= $log['method'] ?></span></td>
                                <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($log['endpoint']) ?></td>
                                <td style="color:#444;"><?= $log['ip'] ?></td>
                                <td><?= $log['user_id'] ?: '<span style="color:#222;">—</span>' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Timeline + IPs + Coverage -->
            <div class="col-lg-5">
                <!-- Timeline -->
                <div class="panel mb-3">
                    <div class="panel-header">
                        <span class="panel-title">Attack Timeline</span>
                    </div>
                    <div class="panel-body scroll-y" style="padding:16px;">
                        <?php if (empty($recent_alerts)): ?>
                        <div style="text-align:center;color:#333;font-family:'JetBrains Mono',monospace;font-size:0.7rem;padding:2rem 0;">No events</div>
                        <?php else: ?>
                        <?php foreach (array_slice($recent_alerts, 0, 12) as $a): ?>
                        <div class="tl-item tl-<?= $a['severity'] ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="r-tag r-<?= $a['rule_name'] ?>"><?= $a['rule_name'] ?></span>
                                <span style="font-family:'JetBrains Mono',monospace;font-size:0.58rem;color:#333;"><?= date('H:i:s', strtotime($a['req_time'])) ?></span>
                            </div>
                            <div style="font-size:0.68rem;color:#555;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(substr($a['description'], 0, 70)) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Top IPs -->
                <div class="panel mb-3">
                    <div class="panel-header">
                        <span class="panel-title">Top Source IPs</span>
                    </div>
                    <div class="panel-body" style="padding:12px 16px;">
                        <?php foreach ($top_ips as $ip): ?>
                        <div class="d-flex justify-content-between align-items-center" style="padding:5px 0;border-bottom:1px solid rgba(255,255,255,0.02);">
                            <code style="font-size:0.72rem;color:#888;background:transparent;padding:0;"><?= $ip['ip'] ?></code>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:0.6rem;color:var(--s-muted);"><?= $ip['cnt'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Coverage -->
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title">Vulnerability Coverage</span>
                    </div>
                    <div class="panel-body" style="padding:12px 16px;">
                        <?php
                        $vulns = [
                            ['XSS', 'Stored XSS in product reviews', 'high', 'r-XSS'],
                            ['CLICKJACK', 'Missing X-Frame-Options on checkout', 'medium', 'r-BAC'],
                            ['BAC', 'Admin panel without role validation', 'critical', 'r-BAC'],
                            ['IDOR', 'Order detail without ownership check', 'high', 'r-IDOR'],
                            ['BIZ-LOGIC', 'Voucher reuse + negative qty', 'medium', 'r-BUSINESS_LOGIC']
                        ];
                        foreach ($vulns as $v):
                        ?>
                        <div class="d-flex justify-content-between align-items-center" style="padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.02);">
                            <div>
                                <span style="font-family:'JetBrains Mono',monospace;font-size:0.7rem;font-weight:600;color:#999;"><?= $v[0] ?></span>
                                <div style="font-size:0.62rem;color:#333;"><?= $v[1] ?></div>
                            </div>
                            <span class="sev-<?= $v[2] ?>" style="font-family:'JetBrains Mono',monospace;font-size:0.58rem;letter-spacing:0.5px;"><?= strtoupper($v[2]) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function classifyAlert(id, isTP) {
            fetch('/siem/api/alerts.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({alert_id: id, is_true_positive: isTP})
            }).then(r => r.json()).then(d => {
                if (d.success) location.reload();
            });
        }
        setTimeout(() => location.reload(), 15000);
    </script>
</body>
</html>
