<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $title ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
        }
        
        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .logo {
            max-width: 120px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .period-info {
            font-size: 14px;
            color: #95a5a6;
            margin-bottom: 5px;
        }
        
        .generated-info {
            font-size: 11px;
            color: #bdc3c7;
            font-style: italic;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        h2 {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
            margin-bottom: 20px;
            margin-top: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 11px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            border: none;
            padding: 12px 10px;
            text-align: left;
            vertical-align: middle;
        }
        
        th {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #e74c3c;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:nth-child(odd) {
            background-color: #ffffff;
        }
        
        tr:hover {
            background-color: #e8f4fd;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        
        td {
            border-bottom: 1px solid #e9ecef;
        }
        
        td:last-child {
            border-bottom: none;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .metric-cell {
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 20px 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .metric-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-cell.success {
            border-left: 4px solid #27ae60;
        }
        
        .metric-cell.warning {
            border-left: 4px solid #f39c12;
        }
        
        .metric-cell.danger {
            border-left: 4px solid #e74c3c;
        }
        
        .metric-cell.info {
            border-left: 4px solid #3498db;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
            text-align: center;
            font-size: 10px;
            color: #95a5a6;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-text">
            <!-- <img src="../../assets/img/icons/tacos.png" alt="" srcset="">  -->
            TACOS</div>
        <div class="company-name">Restaurant Stock Management</div>
        <div class="report-title"><?= $title ?></div>
        <div class="period-info">Period: <?= $periodStart ?> to <?= $periodEnd ?></div>
        <div class="generated-info">Generated on: <?= $generatedAt ?></div>
    </div>
    
    <?= $content ?>
    
    <div class="footer">
        <p>© <?= date('Y') ?> Restaurant Stock Management System | Confidential Report</p>
    </div>
</body>
</html>
