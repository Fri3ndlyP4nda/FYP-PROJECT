<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'APEL Management System' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background: #f8f5f6;
            color: #2b2b2b;
        }

        .navbar {
            background: #8B1E3F;
            color: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(139, 30, 63, 0.18);
        }

        .navbar h1 {
            font-size: 20px;
        }

        .navbar .nav-links a,
        .navbar .nav-links button {
            color: white;
            text-decoration: none;
            margin-left: 16px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .container {
            width: 100%;
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 24px;
        }

        .auth-wrapper {
            min-height: calc(100vh - 72px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-card {
            width: 100%;
            max-width: 450px;
        }

        h2 {
            margin-bottom: 18px;
            font-size: 28px;
            color: #8B1E3F;
        }

        p.muted {
            color: #6b7280;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d8c7cd;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #8B1E3F;
            box-shadow: 0 0 0 3px rgba(139, 30, 63, 0.15);
        }

        .btn {
            display: inline-block;
            background: #8B1E3F;
            color: white;
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn:hover {
            background: #6E1832;
        }

        .btn-secondary {
            background: #b08a96;
            color: white;
        }

        .btn-secondary:hover {
            background: #946f7b;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .form-footer {
            margin-top: 16px;
            font-size: 14px;
            color: #4b5563;
        }

        .form-footer a {
            color: #8B1E3F;
            text-decoration: none;
            font-weight: 600;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h2 {
            margin-bottom: 6px;
        }

        .grid {
            display: grid;
            gap: 20px;
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }

        .actions a {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        th,
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #8B1E3F;
            color: white;
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-approved,
        .badge-pass {
            background: #dcfce7;
            color: #166534;
        }

        .badge-rejected,
        .badge-fail {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-submitted {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .link {
            color: #8B1E3F;
            text-decoration: none;
            font-weight: 600;
        }

        .dashboard-shell {
            padding-top: 10px;
            padding-bottom: 30px;
        }

        .dashboard-banner {
            display: grid;
            grid-template-columns: 1.6fr 0.8fr;
            gap: 20px;
            align-items: stretch;
            margin-bottom: 24px;
        }

        .banner-content {
            background: linear-gradient(135deg, #8B1E3F 0%, #6E1832 100%);
            color: white;
            border-radius: 24px;
            padding: 34px;
            box-shadow: 0 16px 40px rgba(139, 30, 63, 0.20);
        }

        .dashboard-pill {
            display: inline-block;
            margin-bottom: 14px;
            padding: 7px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        .banner-content h2 {
            color: white;
            margin-bottom: 14px;
            font-size: 34px;
            line-height: 1.2;
        }

        .banner-content p {
            color: rgba(255, 255, 255, 0.92);
            line-height: 1.7;
            max-width: 720px;
            margin-bottom: 22px;
        }

        .banner-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-light {
            background: white;
            color: #8B1E3F;
            border: 1px solid rgba(255, 255, 255, 0.7);
        }

        .btn-light:hover {
            background: #f5e9ee;
            color: #6E1832;
        }

        .banner-side {
            display: flex;
        }

        .mini-profile-card {
            background: white;
            border-radius: 24px;
            padding: 26px;
            width: 100%;
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.08);
            border: 1px solid #efe2e7;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .mini-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #8B1E3F;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .mini-profile-card strong {
            font-size: 28px;
            color: #2b2b2b;
            margin-bottom: 6px;
        }

        .mini-profile-card small {
            color: #6b7280;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1e7ea;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 30px rgba(0, 0, 0, 0.09);
        }

        .stat-title {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: #8B1E3F;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-bottom: 12px;
        }

        .stat-card strong {
            display: block;
            font-size: 24px;
            color: #2b2b2b;
            margin-bottom: 8px;
        }

        .stat-card p {
            margin: 0;
            color: #6b7280;
            line-height: 1.6;
            font-size: 14px;
        }

        .action-panel {
            background: white;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1e7ea;
        }

        .panel-heading {
            margin-bottom: 20px;
        }

        .panel-heading h3 {
            font-size: 24px;
            color: #8B1E3F;
            margin-bottom: 6px;
        }

        .panel-heading p {
            margin: 0;
            color: #6b7280;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .action-grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .action-card {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            text-decoration: none;
            background: linear-gradient(135deg, #fff 0%, #fcf7f9 100%);
            border: 1px solid #efe3e7;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.2s ease;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(139, 30, 63, 0.10);
            border-color: #d9b8c4;
        }

        .action-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 16px;
            background: #8B1E3F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 10px 20px rgba(139, 30, 63, 0.18);
        }

        .action-card h4 {
            margin: 2px 0 8px;
            color: #8B1E3F;
            font-size: 18px;
        }

        .action-card p {
            margin: 0;
            color: #6b7280;
            line-height: 1.6;
            font-size: 14px;
        }

        @media (max-width: 992px) {
            .dashboard-banner {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-grid,
            .action-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .app-shell {
            padding-top: 10px;
            padding-bottom: 40px;
        }

        .page-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .section-pill {
            display: inline-block;
            margin-bottom: 10px;
            padding: 7px 14px;
            border-radius: 999px;
            background: #f3dce4;
            color: #8B1E3F;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        .page-hero-text {
            max-width: 760px;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .mini-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .mini-stat-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(139, 30, 63, 0.04);
            border: 1px solid rgba(240, 228, 232, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .mini-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 38px rgba(139, 30, 63, 0.08);
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(139, 30, 63, 0.2);
        }

        .mini-stat-card span {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .mini-stat-card strong {
            font-size: 28px;
            color: #8B1E3F;
        }

        .record-card {
            background: white;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0e4e8;
            margin-bottom: 20px;
        }

        .record-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .record-kicker {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #8B1E3F;
            margin-bottom: 8px;
        }

        .record-card h3 {
            font-size: 24px;
            color: #2b2b2b;
            margin: 0;
        }

        .record-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .meta-box {
            background: #fcf7f9;
            border: 1px solid #f1e5e9;
            border-radius: 16px;
            padding: 16px;
        }

        .meta-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #8B1E3F;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }

        .meta-box strong {
            font-size: 15px;
            color: #2b2b2b;
            line-height: 1.5;
        }

        .record-body-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
        }

        .record-panel {
            background: #fff;
            border: 1px solid #f1e5e9;
            border-radius: 18px;
            padding: 18px;
        }

        .record-panel h4 {
            font-size: 17px;
            color: #8B1E3F;
            margin-bottom: 12px;
        }

        .doc-list {
            padding-left: 18px;
            margin: 0;
        }

        .doc-list li {
            margin-bottom: 8px;
        }

        .feedback-text,
        .empty-inline {
            color: #6b7280;
            line-height: 1.7;
            font-size: 14px;
        }

        .record-footer {
            display: flex;
            justify-content: flex-end;
        }

        .empty-state-card {
            background: linear-gradient(135deg, #ffffff 0%, #fbf5f7 100%);
            border: 1px solid #f0e4e8;
            border-radius: 24px;
            padding: 34px;
            text-align: center;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.06);
        }

        .empty-mark {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #8B1E3F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-weight: 700;
        }

        .empty-state-card h3 {
            font-size: 26px;
            color: #8B1E3F;
            margin-bottom: 10px;
        }

        .empty-state-card p {
            max-width: 600px;
            margin: 0 auto 20px;
            color: #6b7280;
            line-height: 1.7;
        }

        .form-split-layout {
            display: grid;
            grid-template-columns: 1.5fr 0.9fr;
            gap: 22px;
            align-items: start;
        }

        .form-main-card {
            border-radius: 22px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .upload-box {
            border: 1.5px dashed #d8bcc7;
            background: #fcf7f9;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 18px;
        }

        .upload-box input {
            margin-bottom: 10px;
            background: white;
        }

        .upload-box p {
            margin-bottom: 6px;
            color: #6b7280;
            font-size: 14px;
        }

        .upload-box small {
            color: #8b7280;
        }

        .form-submit-row {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .info-side-card {
            background: linear-gradient(135deg, #8B1E3F 0%, #6E1832 100%);
            color: white;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 16px 32px rgba(139, 30, 63, 0.18);
        }

        .side-label {
            display: inline-block;
            margin-bottom: 12px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .info-side-card h3 {
            font-size: 24px;
            margin-bottom: 14px;
        }

        .check-list {
            padding-left: 18px;
            margin-bottom: 24px;
        }

        .check-list li {
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .tip-box {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 16px;
        }

        .tip-box strong {
            display: block;
            margin-bottom: 8px;
        }

        .tip-box p {
            margin: 0;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.92);
        }

        @media (max-width: 992px) {

            .mini-stats-grid,
            .record-meta-grid,
            .record-body-grid,
            .form-split-layout,
            .form-row {
                grid-template-columns: 1fr;
            }

            .record-footer {
                justify-content: flex-start;
            }
        }

        .eval-shell {
            padding-top: 10px;
            padding-bottom: 40px;
        }

        .eval-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .eval-hero-text {
            max-width: 760px;
            line-height: 1.6;
        }

        .table-card {
            background: white;
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0e4e8;
        }

        .table-card-header {
            margin-bottom: 18px;
        }

        .table-card-header h3 {
            font-size: 22px;
            color: #8B1E3F;
            margin-bottom: 6px;
        }

        .table-card-header p {
            color: #6b7280;
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .table-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 10px 14px;
            font-size: 13px;
        }

        .table-empty {
            text-align: center;
            padding: 24px 12px;
        }

        .small-empty-mark {
            width: 52px;
            height: 52px;
            font-size: 14px;
            margin-bottom: 14px;
        }

        .table-empty h4 {
            font-size: 22px;
            color: #8B1E3F;
            margin-bottom: 8px;
        }

        .table-empty p {
            color: #6b7280;
            margin: 0;
        }

        .review-layout {
            display: grid;
            grid-template-columns: 1.4fr 0.9fr;
            gap: 22px;
            align-items: start;
        }

        .review-side {
            position: sticky;
            top: 24px;
        }

        .side-form-title {
            font-size: 24px;
            color: #8B1E3F;
            margin-bottom: 18px;
        }

        .doc-grid {
            display: grid;
            gap: 14px;
        }

        .doc-card-link {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            background: #fcf7f9;
            border: 1px solid #f0e4e8;
            border-radius: 16px;
            padding: 16px;
            transition: all 0.2s ease;
        }

        .doc-card-link:hover {
            transform: translateY(-2px);
            border-color: #d9b8c4;
            box-shadow: 0 10px 22px rgba(139, 30, 63, 0.08);
        }

        .doc-file-icon {
            width: 52px;
            height: 52px;
            min-width: 52px;
            border-radius: 14px;
            background: #8B1E3F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }

        .doc-card-link strong {
            display: block;
            color: #2b2b2b;
            margin-bottom: 4px;
        }

        .doc-card-link p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        @media (max-width: 992px) {
            .review-layout {
                grid-template-columns: 1fr;
            }

            .review-side {
                position: static;
            }
        }

        .admin-shell {
            padding-top: 10px;
            padding-bottom: 40px;
        }

        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .admin-stat-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(139, 30, 63, 0.04);
            border: 1px solid rgba(240, 228, 232, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .admin-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 38px rgba(139, 30, 63, 0.08);
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(139, 30, 63, 0.2);
        }

        .admin-stat-card span {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .admin-stat-card strong {
            font-size: 28px;
            color: #8B1E3F;
        }

        .tip-box-light {
            background: #fcf7f9;
            color: #2b2b2b;
            margin-top: 14px;
            margin-bottom: 8px;
            border: 1px solid #f0e4e8;
        }

        .tip-box-light p {
            color: #6b7280;
        }

        @media (max-width: 1200px) {
            .admin-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .admin-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .user-shell {
            padding-top: 10px;
            padding-bottom: 40px;
        }

        .user-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .user-stat-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0e4e8;
        }

        .user-stat-card span {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .user-stat-card strong {
            font-size: 28px;
            color: #8B1E3F;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 12px;
            background: #8B1E3F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(139, 30, 63, 0.18);
        }

        .user-name {
            color: #2b2b2b;
            font-size: 15px;
        }

        .role-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            background: #f3e8ec;
            color: #8B1E3F;
        }

        .role-student {
            background: #f7e8ed;
            color: #8B1E3F;
        }

        .role-evaluator {
            background: #efe7fb;
            color: #6d28d9;
        }

        .role-admin {
            background: #feeccf;
            color: #b45309;
        }

        @media (max-width: 1100px) {
            .user-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .user-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .user-shell {
            padding-top: 10px;
            padding-bottom: 40px;
        }

        .user-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .user-stat-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0e4e8;
        }

        .user-stat-card span {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .user-stat-card strong {
            font-size: 28px;
            color: #8B1E3F;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 12px;
            background: #8B1E3F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(139, 30, 63, 0.18);
        }

        .user-name {
            color: #2b2b2b;
            font-size: 15px;
        }

        .role-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            background: #f3e8ec;
            color: #8B1E3F;
        }

        .role-student {
            background: #f7e8ed;
            color: #8B1E3F;
        }

        .role-evaluator {
            background: #efe7fb;
            color: #6d28d9;
        }

        .role-admin {
            background: #feeccf;
            color: #b45309;
        }

        @media (max-width: 1100px) {
            .user-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .user-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .grading-shell {
            padding-top: 10px;
            padding-bottom: 40px;
        }

        .grading-layout {
            display: grid;
            grid-template-columns: 1.5fr 0.7fr;
            gap: 20px;
        }

        .question-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 18px;
            border: 1px solid #f0e4e8;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.05);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .question-number {
            font-weight: 700;
            color: #8B1E3F;
        }

        .question-type {
            font-size: 12px;
            color: #6b7280;
        }

        .answer-text {
            background: #fcf7f9;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #f1e5e9;
            margin-bottom: 14px;
        }

        .grading-input input {
            width: 100px;
        }

        .grading-side {
            position: sticky;
            top: 20px;
        }

        .grading-summary {
            padding: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            color: #2b2b2b;
        }

        @media (max-width: 992px) {
            .grading-layout {
                grid-template-columns: 1fr;
            }
        }

        .papers-shell {
            padding-top: 10px;
            padding-bottom: 40px;
        }

        .papers-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .papers-stat-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
            border: 1px solid #f0e4e8;
        }

        .papers-stat-card span {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .papers-stat-card strong {
            font-size: 28px;
            color: #8B1E3F;
        }

        .paper-title-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .paper-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 12px;
            background: #8B1E3F;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(139, 30, 63, 0.18);
        }

        .paper-title {
            color: #2b2b2b;
            font-size: 15px;
        }

        .app-id-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            background: #f6edf0;
            color: #8B1E3F;
            font-size: 12px;
            font-weight: 700;
            word-break: break-all;
        }

        .paper-file-link {
            display: inline-block;
            text-decoration: none;
            font-weight: 700;
            color: #8B1E3F;
            background: #f9eef2;
            border: 1px solid #edd9e0;
            padding: 8px 12px;
            border-radius: 10px;
            transition: 0.2s ease;
        }

        .paper-file-link:hover {
            background: #f3dfe6;
        }

        @media (max-width: 900px) {
            .papers-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .paper-create-shell {
            padding-top: 10px;
            padding-bottom: 40px;
        }

        .paper-create-layout {
            display: grid;
            grid-template-columns: 1.5fr 0.9fr;
            gap: 22px;
            align-items: start;
        }

        @media (max-width: 992px) {
            .paper-create-layout {
                grid-template-columns: 1fr;
            }
        }

        .type-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .type-apel-a {
            background: #f7e8ed;
            color: #8B1E3F;
        }

        .type-apel-c {
            background: #efe7fb;
            color: #6d28d9;
        }

        .stage-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #f6edf0;
            color: #8B1E3F;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d8cfd4;
            border-radius: 12px;
            background: #fff;
            font-size: 14px;
            color: #2b2b2b;
            outline: none;
            transition: 0.2s ease;
        }

        .form-control:focus {
            border-color: #9b1c48;
            box-shadow: 0 0 0 3px rgba(155, 28, 72, 0.10);
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #7f1638;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/app-style.css') }}">
    @stack('styles')
</head>

<body>
    @if(!request()->routeIs('login', 'register', '2fa.*', 'password.*', 'password.request', 'password.reset', 'password.email', 'password.update'))
        <div class="navbar">
            <h1>APEL Management System</h1>

            @auth
                <div class="nav-links">
                    <span>{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit">Logout</button>
                    </form>
                </div>
            @endauth
        </div>
    @endif

    @yield('content')
    @stack('scripts')

    @if (session('success') || session('error'))
        <div id="toast-notification-container" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999; pointer-events: none; font-family: inherit;">
            <div class="toast-card" style="pointer-events: auto; min-width: 320px; max-width: 380px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-radius: 14px; padding: 16px 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-left: 5px solid {{ session('success') ? '#10b981' : '#ef4444' }}; display: flex; align-items: center; justify-content: space-between; transform: translateX(120%); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); gap: 12px;">
                <div style="flex: 1;">
                    <strong style="display: block; font-size: 13.5px; color: #1f2937; margin-bottom: 2px;">{{ session('success') ? 'Success' : 'Error' }}</strong>
                    <span style="font-size: 13px; color: #4b5563; line-height: 1.4;">{{ session('success') ?? session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.style.transform = 'translateX(120%)'" style="background: none; border: none; font-size: 20px; color: #9ca3af; cursor: pointer; padding: 0; line-height: 1; font-weight: 500;">&times;</button>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toast = document.querySelector('.toast-card');
                if (toast) {
                    setTimeout(() => {
                        toast.style.transform = 'translateX(0)';
                    }, 150);
                    setTimeout(() => {
                        toast.style.transform = 'translateX(120%)';
                    }, 5000);
                }
            });
        </script>
        @if (session('success') && str_contains(strtolower(session('success')), 'draft'))
            <script>
                alert("{{ session('success') }}");
            </script>
        @endif
    @endif
</body>

</html>
