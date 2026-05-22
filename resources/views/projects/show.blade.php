@extends('layouts.master')
@section('title', $project->project_name)
@section('content')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            /* background: linear-gradient(135deg, #d7d9da 0%, #e1dede 100%); */
            margin-top: 20px;
        }

        .card {
            background: #fff;
            transition: all 0.3s ease;
            border: 0;
            margin-bottom: 30px;
            border-radius: 16px;
            position: relative;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        /* .card:hover:not(.modal-open .card) {
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
                transform: translateY(-2px);
            } */

        body.modal-open .card {
            pointer-events: none;
        }

        body.modal-open .modal .card {
            pointer-events: auto;
        }

        #project-show-page .card:not(.modal .card) {
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            margin-bottom: 18px;
        }

        #project-show-page .card-body:not(.modal .card-body) {
            background: transparent !important;
            padding: 0 !important;
        }

        #project-show-page .card-header:not(.modal .card-header) {
            border-radius: 10px !important;
            margin-bottom: 14px;
        }

        #project-show-page,
        #project-show-page .tab-content,
        #project-show-page .tab-pane,
        #project-show-page .bg-light,
        #project-show-page .list-group-item,
        #project-show-page .dropdown-menu {
            background-color: #ffffff !important;
        }

        #project-show-page .modal .card,
        #project-show-page .modal .card-body,
        #project-show-page .modal .card-header {
            background: revert-layer;
        }

        .chat-app .people-list {
            width: 280px;
            position: absolute;
            left: 0;
            top: 0;
            padding: 20px;
            z-index: 7
        }

        .chat-app .chat {
            margin-left: 280px;
            border-left: 1px solid #eaeaea
        }

        .people-list {
            -moz-transition: .5s;
            -o-transition: .5s;
            -webkit-transition: .5s;
            transition: .5s
        }

        .people-list .chat-list li {
            padding: 10px 15px;
            list-style: none;
            border-radius: 3px
        }

        .people-list .chat-list li:hover {
            background: #efefef;
            cursor: pointer
        }

        .people-list .chat-list li.active {
            background: #efefef
        }

        .people-list .chat-list li .name {
            font-size: 15px
        }

        .people-list .chat-list img {
            width: 45px;
            border-radius: 50%
        }

        .people-list img {
            float: left;
            border-radius: 50%
        }

        .people-list .about {
            float: left;
            padding-left: 8px
        }

        .people-list .status {
            color: #999;
            font-size: 13px
        }

        .chat .chat-header {
            padding: 15px 20px;
            border-bottom: 2px solid #f4f7f6
        }

        .chat .chat-header img {
            float: left;
            border-radius: 40px;
            width: 40px
        }

        .chat .chat-header .chat-about {
            float: left;
            padding-left: 10px
        }

        .chat .chat-history {
            padding: 20px;
            border-bottom: 2px solid #fff
        }

        .chat .chat-history ul {
            padding: 0
        }

        .chat .chat-history ul li {
            list-style: none;
            margin-bottom: 30px
        }

        .chat .chat-history ul li:last-child {
            margin-bottom: 0px
        }

        .chat .chat-history .message-data {
            margin-bottom: 15px
        }

        .chat .chat-history .message-data img {
            border-radius: 40px;
            width: 40px
        }

        .chat .chat-history .message-data-time {
            color: #434651;
            padding-left: 6px
        }

        .chat .chat-history .message {
            color: #444;
            padding: 18px 20px;
            line-height: 26px;
            font-size: 16px;
            border-radius: 7px;
            display: inline-block;
            position: relative
        }

        .chat .chat-history .message:after {
            bottom: 100%;
            left: 7%;
            border: solid transparent;
            content: " ";
            height: 0;
            width: 0;
            position: absolute;
            pointer-events: none;
            border-bottom-color: #fff;
            border-width: 10px;
            margin-left: -10px
        }

        .chat .chat-history .my-message {
            background: #efefef
        }

        .chat .chat-history .my-message:after {
            bottom: 100%;
            left: 30px;
            border: solid transparent;
            content: " ";
            height: 0;
            width: 0;
            position: absolute;
            pointer-events: none;
            border-bottom-color: #efefef;
            border-width: 10px;
            margin-left: -10px
        }

        .chat .chat-history .other-message {
            background: #e8f1f3;
            text-align: right
        }

        .chat .chat-history .other-message:after {
            border-bottom-color: #e8f1f3;
            left: 93%
        }

        .chat .chat-message {
            padding: 20px
        }

        .online,
        .offline,
        .me {
            margin-right: 2px;
            font-size: 8px;
            vertical-align: middle
        }

        .online {
            color: #86c541
        }

        .offline {
            color: #e47297
        }

        .me {
            color: #1d8ecd
        }

        .float-right {
            float: right
        }

        .clearfix:after {
            visibility: hidden;
            display: block;
            font-size: 0;
            content: " ";
            clear: both;
            height: 0
        }

        @media only screen and (max-width: 767px) {
            .chat-app .people-list {
                height: 465px;
                width: 100%;
                overflow-x: auto;
                background: #fff;
                left: -400px;
                display: none
            }

            .chat-app .people-list.open {
                left: 0
            }

            .chat-app .chat {
                margin: 0
            }

            .chat-app .chat .chat-header {
                border-radius: 0.55rem 0.55rem 0 0
            }

            .chat-app .chat-history {
                height: 300px;
                overflow-x: auto
            }
        }

        @media only screen and (min-width: 768px) and (max-width: 992px) {
            .chat-app .chat-list {
                height: 650px;
                overflow-x: auto
            }

            .chat-app .chat-history {
                height: 600px;
                overflow-x: auto
            }
        }

        @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 1) {
            .chat-app .chat-list {
                height: 480px;
                overflow-x: auto
            }

            .chat-app .chat-history {
                height: calc(100vh - 350px);
                overflow-x: auto
            }
        }

        .main-container {
            width: 650px;
            /* margin-left: auto;
                                                                                                                                                                                            margin-right: auto; */
        }

        .tags-input {
            border: 1px solid #ced4da;
            padding: 5px;
            border-radius: 4px;
            display: flex;
            flex-wrap: wrap;
            cursor: text;
        }

        #project-show-page #communication .tags-input,
        #project-show-page #communication .ck.ck-editor__main > .ck-editor__editable,
        #project-show-page #communication .ck.ck-toolbar,
        #project-show-page #communication #emailDiv,
        #project-show-page #communication #emailDiv .card,
        #project-show-page #communication #emailDiv .card-body,
        #project-show-page #communication #emailDiv .card-header,
        #project-show-page #communication #emailDiv .list-group-item,
        #project-show-page #communication #emailDiv .table,
        #project-show-page #communication #emailDiv .table td,
        #project-show-page #communication #emailDiv .table th {
            background-color: #ffffff !important;
            border-color: var(--solen-primary-border-stronger) !important;
            color: var(--solen-warm-text) !important;
        }

        #project-show-page #communication .tags-input {
            min-height: 44px;
            padding: 0.45rem 0.75rem;
            border-radius: 10px;
            align-items: center;
        }

        .tags-input input {
            border: none;
            outline: none;
            flex-grow: 1;
            min-width: 150px;
        }

        #project-show-page #communication .tags-input input {
            background: transparent !important;
            color: var(--solen-warm-text) !important;
        }

        .tag {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
        }

        #project-show-page #communication .tag {
            background: var(--solen-gradient) !important;
            color: #ffffff !important;
            border-radius: 999px;
        }

        .tag i {
            margin-left: 5px;
            cursor: pointer;
        }

        .invalid-feedback {
            display: none;
            color: red;
        }

        .blink-dot {
            position: relative;
        }

        .blink-dot::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 10px;
            height: 10px;
            background-color: red;
            border-radius: 50%;
            animation: blinker 1s linear infinite;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.6);
        }

        @keyframes blinker {
            50% {
                opacity: 0;
            }
        }

        .card-header {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            color: white;
            border-radius: 16px 16px 0 0 !important;
            padding: 1.5rem;
            border: none;
        }

        .project-tag-panel {
            background: #ffffff;
            border: 0;
            border-radius: 14px;
            padding: 1rem 1.25rem;
            margin: -0.5rem 0 1rem;
        }

        .project-tag-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .project-tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }

        .project-tag-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 600;
            line-height: 1;
        }

        .project-tag-chip.inverter-tag {
            background: rgba(13, 148, 136, 0.12);
            color: var(--solen-primary-deep);
        }

        .project-tag-chip.adder-tag {
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
        }

        .project-tag-chip.pto-greenlight-tag {
            background: rgba(22, 163, 74, 0.12);
            color: #15803d;
        }

        .nav-tabs {
            border: none;
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .project-primary-tabs,
        .project-department-tabs {
            align-items: center;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            gap: 0.55rem;
        }

        .project-primary-tabs {
            margin-bottom: 0.5rem !important;
            padding: 0.75rem !important;
            border-radius: 14px !important;
        }

        .project-department-tabs {
            padding: 0.8rem !important;
            border-radius: 14px !important;
        }

        .project-primary-tabs .nav-link,
        .project-department-tabs .nav-link {
            border: 0 !important;
            border-radius: 999px !important;
            box-shadow: 0 8px 20px var(--solen-warm-shadow-soft);
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            background: #f8f9fa;
            color: #2c3e50;
        }

        .nav-tabs .nav-link.active {
            background: var(--solen-gradient);
            color: white;
        }

        #project-show-page .nav-tabs {
            background: transparent !important;
            border: 0 !important;
        }

        #project-show-page .nav-tabs .nav-item,
        #project-show-page .nav-tabs .nav-link,
        #project-show-page .nav-tabs .nav-link:hover,
        #project-show-page .nav-tabs .nav-link:focus,
        #project-show-page .nav-tabs .nav-link.active {
            border: 0 !important;
            border-color: transparent !important;
        }

        #project-show-page .nav-tabs .nav-link:hover,
        #project-show-page .nav-tabs .nav-link:focus {
            background: var(--solen-primary-soft);
            color: var(--solen-warm-hover);
        }

        #project-show-page .nav-tabs .nav-link.active {
            background: var(--solen-gradient) !important;
            color: #ffffff !important;
        }

        #project-show-page > .tab-content > .tab-pane:not(.active) {
            display: none !important;
        }

        #project-show-page > .tab-content > .tab-pane.active {
            display: block;
        }

        #project-show-page .project-primary-tabs .nav-link {
            background: #ffffff;
            color: var(--solen-warm-text);
            min-width: 112px;
            text-align: center;
        }

        #project-show-page .project-department-tabs .nav-link {
            background: #ffffff;
            color: #92400e;
            font-size: 0.88rem;
            padding: 0.6rem 1.15rem;
        }

        #project-show-page .project-department-tabs .nav-item.bg-success {
            background: transparent !important;
        }

        #project-show-page .project-department-tabs .nav-item.bg-success .nav-link:not(.active) {
            background: linear-gradient(135deg, #fde68a 0%, #fb923c 100%) !important;
            color: #7c2d12 !important;
            box-shadow: inset 0 0 0 1px rgba(194, 65, 12, 0.18), 0 8px 20px rgba(251, 146, 60, 0.16);
        }

        #project-show-page .project-department-tabs .nav-item.bg-success .nav-link:not(.active)::after {
            content: "\2713";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1rem;
            height: 1rem;
            margin-left: 0.45rem;
            border-radius: 50%;
            background: rgba(124, 45, 18, 0.14);
            font-size: 0.72rem;
            font-weight: 800;
        }

        #project-show-page .project-primary-tabs .nav-link.active,
        #project-show-page .project-department-tabs .nav-link.active {
            box-shadow: 0 10px 22px var(--solen-primary-border-stronger);
            transform: translateY(-1px);
        }

        .department-detail-heading {
            margin: 0 auto 1.25rem;
            text-align: center;
        }

        .department-detail-heading span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            max-width: 100%;
            padding: 0.7rem 1.35rem;
            border-radius: 999px;
            background: rgba(255, 247, 237, 0.78);
            color: var(--solen-warm-hover);
            font-size: 1.05rem;
            font-weight: 800;
            box-shadow: 0 8px 22px var(--solen-warm-shadow-soft);
        }

        .project-title-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 0;
            background: transparent;
            color: var(--solen-warm-text);
            font: inherit;
            font-weight: 800;
            text-transform: uppercase;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            transition: all 0.2s ease;
        }

        .project-title-status:hover,
        .project-title-status:focus {
            background: var(--solen-primary-soft);
            color: var(--solen-warm-hover);
            outline: none;
        }

        .project-title-status-menu {
            border: 1px solid var(--solen-primary-border);
            background: #ffffff;
            box-shadow: 0 16px 35px rgba(120, 53, 15, 0.12);
        }

        .project-title-status-menu .dropdown-item {
            color: var(--solen-warm-text);
            font-weight: 600;
        }

        .project-title-status-menu .dropdown-item:hover,
        .project-title-status-menu .dropdown-item.active {
            background: #ffffff;
            color: var(--solen-warm-hover);
        }

        .project-summary-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem 1rem;
            text-align: center;
        }

        .project-days-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 86px;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            background: #ffffff;
            color: var(--solen-warm-text);
            font-size: 0.95rem;
            font-weight: 800;
            box-shadow: 0 8px 18px var(--solen-warm-shadow-soft);
        }

        .project-summary-title {
            margin: 0;
            width: 100%;
            color: var(--solen-warm-text);
            font-size: 1.45rem;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }

        .project-summary-main {
            width: 100%;
        }

        .project-customer-address {
            margin: 0;
            color: var(--solen-warm-text);
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .project-customer-contact {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem 1.4rem;
            margin-top: 0.35rem;
            color: var(--solen-muted);
            font-size: 0.94rem;
            font-weight: 600;
        }

        .project-assignee-control {
            width: auto;
            text-align: center;
        }

        .project-assignee-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border: 0;
            background: transparent;
            color: var(--solen-warm-text);
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            box-shadow: none;
            transition: all 0.2s ease;
        }

        .project-assignee-toggle:hover,
        .project-assignee-toggle:focus {
            background: var(--solen-primary-soft);
            color: var(--solen-warm-hover);
            outline: none;
        }

        .project-assignee-label {
            font: inherit;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: none;
            color: inherit;
        }

        .project-assignee-name {
            font: inherit;
            font-weight: 800;
            line-height: 1.2;
            color: inherit;
        }

        .project-assignee-menu {
            min-width: 260px;
            max-height: 280px;
            overflow-y: auto;
            border: 1px solid var(--solen-primary-border);
            background: #ffffff;
            box-shadow: 0 16px 35px rgba(120, 53, 15, 0.12);
        }

        .project-assignee-menu .dropdown-item {
            color: var(--solen-warm-text);
            font-weight: 600;
        }

        .project-assignee-menu .dropdown-item:hover,
        .project-assignee-menu .dropdown-item.active {
            background: #ffffff;
            color: var(--solen-warm-hover);
        }

        .btn-dark {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-dark:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }

        #project-show-page .form-control,
        #project-show-page .form-select,
        #project-show-page textarea.form-control,
        #project-show-page .select2-container--default .select2-selection--single,
        #project-show-page .select2-container--default .select2-selection--multiple {
            min-height: 44px;
            background-color: #ffffff !important;
            border: 1px solid var(--solen-primary-border-stronger) !important;
            color: var(--solen-warm-text) !important;
            box-shadow: none !important;
        }

        #project-show-page .form-control:disabled,
        #project-show-page .form-select:disabled,
        #project-show-page .form-control[readonly] {
            background-color: #ffffff !important;
            opacity: 1;
        }

        #project-show-page .select2-container {
            width: 100% !important;
        }

        #project-show-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            color: var(--solen-warm-text) !important;
            padding-left: 1rem;
        }

        #project-show-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
        }

        #project-show-page .select2-dropdown,
        #project-show-page .select2-results__option {
            background-color: #ffffff;
            color: var(--solen-warm-text);
        }

        #project-show-page .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: var(--solen-gradient);
            color: #ffffff;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead {
            background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
            color: white;
        }

        #project-show-page > .tab-content .table thead,
        #project-show-page > .tab-content .table thead th {
            background: #ffffff !important;
            color: var(--solen-warm-text) !important;
            border-color: var(--solen-primary-border-strong) !important;
        }

        #project-show-page #adderTable thead,
        #project-show-page #adderTable thead th {
            background: #ffffff !important;
            color: var(--solen-warm-text) !important;
            border-color: var(--solen-primary-border-strong) !important;
        }

        #project-show-page .account-transactions-table thead,
        #project-show-page .account-transactions-table thead th {
            background: #ffffff !important;
            color: var(--solen-warm-text) !important;
            border-color: var(--solen-primary-border-strong) !important;
        }

        .table tbody tr {
            transition: all 0.3s;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        .nav-item.dropdown:hover .dropdown-menu {
            display: block;
            position: absolute;
            /* Optional for controlling positioning */
            top: 100%;
            /* Ensures the menu appears below the parent item */
            left: 0;
            /* Aligns the dropdown menu with the parent */
            z-index: 1000;
            /* Keeps the dropdown menu on top */
        }

        /* Premium Modal Styles */
        #assign-notes .form-control:focus {
            border-color: var(--solen-primary);
            box-shadow: 0 0 0 0.2rem var(--solen-primary-border);
        }

        #assign-notes .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--solen-primary-shadow);
        }

        #assign-notes .form-check-input:checked {
            background-color: var(--solen-warm-text);
            border-color: var(--solen-warm-text);
        }

        #assign-notes .form-check-input:focus {
            border-color: var(--solen-primary);
            box-shadow: 0 0 0 0.2rem var(--solen-primary-border);
        }

        /* Project detail redesign based on project-detail-sample.html */
        :root {
            --workspace-cream: #ffffff;
            --workspace-amber: #f59e0b;
            --workspace-ink: #451a03;
            --workspace-ink-60: rgba(69, 26, 3, 0.6);
            --workspace-ink-40: rgba(69, 26, 3, 0.4);
            --workspace-line: rgba(69, 26, 3, 0.08);
            --workspace-soft: rgba(245, 158, 11, 0.1);
        }

        .body:has(#project-show-page.project-workspace-redesign) {
            padding-top: 0 !important;
        }

        #project-show-page.project-workspace-redesign {
            --workspace-gutter: clamp(0.25rem, 0.7vw, 0.65rem);
            width: 100%;
            margin: -1rem 0 0;
            padding-bottom: 3rem;
            background: var(--workspace-cream) !important;
            color: var(--workspace-ink);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: clip;
        }

        #project-show-page.project-workspace-redesign::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background: transparent;
            z-index: -1;
        }

        #project-show-page.project-workspace-redesign > .card:first-child {
            position: relative;
            z-index: 100;
            border-bottom: 0 !important;
            background: #ffffff !important;
            backdrop-filter: blur(12px);
            margin-bottom: 0 !important;
            padding: 1.5rem var(--workspace-gutter) 1.25rem;
        }

        #project-show-page.project-workspace-redesign > .card:first-child::after {
            content: "";
            display: block;
            width: 100%;
            height: 1px;
            margin: 1.5rem auto 0;
            background: var(--workspace-line);
        }

        #project-show-page.project-workspace-redesign > .card:first-child .card-body,
        #project-show-page.project-workspace-redesign > .card:first-child .row,
        #project-show-page.project-workspace-redesign > .card:first-child .col-md-12 {
            max-width: none;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
        }

        #project-show-page.project-workspace-redesign .project-summary-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: flex-start;
            gap: 1rem 1.5rem;
            padding: 0 0 1.5rem;
            border-radius: 0 !important;
            text-align: left;
        }

        #project-show-page.project-workspace-redesign .project-summary-main {
            min-width: 0;
        }

        #project-show-page.project-workspace-redesign .project-stage-meta {
            justify-self: end;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.55rem;
            text-align: right;
        }

        #project-show-page.project-workspace-redesign .project-days-badge {
            min-width: 0;
            padding: 0.32rem 0.62rem;
            background: rgba(69, 26, 3, 0.06);
            color: var(--workspace-ink-60);
            border-radius: 6px;
            box-shadow: none;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        #project-show-page.project-workspace-redesign .project-current-stage {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 0;
            background: transparent;
            color: var(--workspace-amber);
            padding: 0;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1.2;
        }

        #project-show-page.project-workspace-redesign .project-current-stage::after {
            content: "";
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 5px solid currentColor;
            transform: translateY(1px);
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-control {
            width: auto;
            text-align: left;
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-toggle {
            gap: 0.32rem;
            min-height: 0;
            padding: 0;
            background: transparent !important;
            border: 0;
            color: var(--workspace-amber);
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1.2;
            box-shadow: none;
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-toggle:hover,
        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-toggle:focus {
            background: transparent !important;
            color: var(--workspace-ink);
            transform: none;
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-label,
        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-name {
            font-size: inherit;
            font-weight: inherit;
            line-height: inherit;
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-label {
            color: var(--workspace-ink-40);
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-name {
            color: #1d4ed8;
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-toggle::after {
            color: #1d4ed8;
            border-top-color: #1d4ed8;
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .project-assignee-menu {
            min-width: 220px;
            font-size: 0.86rem;
        }

        #project-show-page.project-workspace-redesign .project-summary-title {
            max-width: 48rem;
            color: var(--workspace-ink);
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 600;
            line-height: 0.95;
            letter-spacing: 0;
            text-align: left;
            text-transform: none;
        }

        #project-show-page.project-workspace-redesign .project-customer-address {
            max-width: 56rem;
            margin-top: 0.55rem;
            color: var(--workspace-ink);
            font-size: clamp(1rem, 1.4vw, 1.22rem);
            font-weight: 600;
            line-height: 1.35;
        }

        #project-show-page.project-workspace-redesign .project-customer-contact {
            color: var(--workspace-ink-60);
            font-size: 0.9rem;
        }

        #project-show-page.project-workspace-redesign .project-title-status {
            color: var(--workspace-ink);
            padding: 0;
            border-radius: 0;
            font-weight: 600;
            text-transform: none;
        }

        #project-show-page.project-workspace-redesign .project-title-status:hover,
        #project-show-page.project-workspace-redesign .project-title-status:focus {
            background: transparent;
            color: var(--workspace-amber);
        }

        #project-show-page.project-workspace-redesign .project-tag-panel {
            max-width: none;
            margin: -0.5rem auto 1.5rem;
            padding: 0 var(--workspace-gutter);
            background: transparent;
        }

        #project-show-page.project-workspace-redesign .project-tag-list.is-right {
            justify-content: flex-end;
        }

        #project-show-page.project-workspace-redesign .project-tag-list.is-left {
            justify-content: flex-start;
        }

        #project-show-page.project-workspace-redesign .project-tag-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
        }

        #project-show-page.project-workspace-redesign .project-tag-row .project-tag-list {
            flex: 1 1 0;
            min-width: 0;
        }

        #project-show-page.project-workspace-redesign .project-tag-row .project-tag-list + .project-tag-list {
            margin-top: 0;
        }

        #project-show-page.project-workspace-redesign .project-tag-list + .project-tag-list {
            margin-top: 0.55rem;
        }

        #project-show-page.project-workspace-redesign .project-tag-chip {
            border-radius: 6px;
            background: var(--workspace-soft) !important;
            color: var(--workspace-ink) !important;
            font-size: 0.76rem;
        }

        #project-show-page.project-workspace-redesign .project-tag-chip.project-info-tag {
            background: rgba(37, 99, 235, 0.1) !important;
            border: 1px solid rgba(37, 99, 235, 0.18);
            color: #1d4ed8 !important;
        }

        #project-show-page.project-workspace-redesign .project-tag-chip.adder-tag {
            background: rgba(37, 99, 235, 0.1) !important;
            border: 1px solid rgba(37, 99, 235, 0.18);
            color: #1d4ed8 !important;
        }

        #project-show-page.project-workspace-redesign .project-stage-meta .pto-greenlight-tag {
            align-self: flex-end;
            border-radius: 6px;
            background: rgba(22, 163, 74, 0.1) !important;
            border: 1px solid rgba(22, 163, 74, 0.18);
            color: #15803d !important;
            font-size: 0.76rem;
        }

        #project-show-page.project-workspace-redesign .navbar,
        #project-show-page.project-workspace-redesign .navbar .container-fluid,
        #project-show-page.project-workspace-redesign .navbar-collapse {
            width: 100%;
            display: block !important;
            padding: 0;
        }

        #project-show-page.project-workspace-redesign > .card:first-child .d-flex.justify-content-center {
            max-width: none;
            margin: 0 auto;
            justify-content: stretch !important;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set {
            display: flex;
            flex-wrap: nowrap !important;
            gap: 1px;
            width: 100%;
            margin: 0 !important;
            padding: 1px !important;
            overflow: visible !important;
            background: var(--workspace-line) !important;
            border: 1px solid var(--workspace-line) !important;
            border-radius: 14px !important;
            position: relative;
            z-index: 2000;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-item,
        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-link {
            width: 100%;
            min-width: 0;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-item {
            flex: 1 1 0;
            position: relative;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-item.dropdown:hover,
        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-item.dropdown.show {
            z-index: 2100;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-link {
            min-height: 58px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.32rem;
            padding: 0.65rem 0.35rem !important;
            border-radius: 0 !important;
            background: var(--workspace-cream) !important;
            box-shadow: none !important;
            color: var(--workspace-ink-40) !important;
            text-align: center;
        }

        #project-show-page.project-workspace-redesign .department-pipeline-title {
            display: block;
            color: var(--workspace-ink-40);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.52rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            line-height: 1.1;
            text-transform: uppercase;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #project-show-page.project-workspace-redesign .department-pipeline-status {
            display: block;
            color: rgba(69, 26, 3, 0.2);
            font-size: 0.68rem;
            font-weight: 500;
            line-height: 1.1;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-item.bg-success .nav-link:not(.active)::after {
            content: none !important;
            display: none !important;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-item.bg-success .nav-link:not(.active) {
            background: var(--workspace-cream) !important;
            color: var(--workspace-ink-40) !important;
            box-shadow: none !important;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-item.bg-success .department-pipeline-status {
            color: var(--workspace-ink);
            font-weight: 600;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-link.active {
            background: var(--workspace-soft) !important;
            color: var(--workspace-ink) !important;
            box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.25) !important;
            transform: none;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-link.active .department-pipeline-title {
            color: var(--workspace-amber);
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .nav-link.active .department-pipeline-status {
            color: var(--workspace-ink);
            font-weight: 600;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .dropdown-menu {
            top: calc(100% + 4px) !important;
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) !important;
            z-index: 2200 !important;
            min-width: max(220px, 100%);
            max-width: min(320px, 90vw);
            max-height: 310px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0.45rem 0;
            border: 1px solid var(--workspace-line) !important;
            border-radius: 8px !important;
            box-shadow: 0 18px 45px -26px rgba(69, 26, 3, 0.45) !important;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .dropdown-item {
            white-space: normal;
            line-height: 1.25;
            padding: 0.65rem 0.9rem;
        }

        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .dropdown-item:hover,
        #project-show-page.project-workspace-redesign .project-department-tabs.prtab-set .dropdown-item:focus {
            background: var(--workspace-soft) !important;
            color: var(--workspace-ink) !important;
        }

        #project-show-page.project-workspace-redesign #departmentDetailTabs {
            display: flex;
            flex-wrap: nowrap !important;
            gap: 1px;
            width: 100%;
            margin: 0 0 2rem !important;
            padding: 1px !important;
            overflow: hidden !important;
            background: var(--workspace-line) !important;
            border: 1px solid var(--workspace-line) !important;
            border-radius: 14px !important;
            box-shadow: none !important;
        }

        #project-show-page.project-workspace-redesign #departmentDetailTabs .nav-item,
        #project-show-page.project-workspace-redesign #departmentDetailTabs .nav-link {
            width: 100%;
            min-width: 0;
        }

        #project-show-page.project-workspace-redesign #departmentDetailTabs .nav-item {
            flex: 1 1 0;
        }

        #project-show-page.project-workspace-redesign #departmentDetailTabs .nav-link {
            min-height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.65rem 0.35rem !important;
            border-radius: 0 !important;
            background: var(--workspace-cream) !important;
            box-shadow: none !important;
            color: var(--workspace-ink-40) !important;
            text-align: center;
        }

        #project-show-page.project-workspace-redesign #departmentDetailTabs .nav-link.active {
            background: var(--workspace-soft) !important;
            color: var(--workspace-amber) !important;
            box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.25) !important;
            transform: none;
        }

        #project-show-page.project-workspace-redesign .department-detail-tab-title {
            color: inherit;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.58rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            line-height: 1.2;
            text-transform: uppercase;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #project-show-page.project-workspace-redesign > .row.clearfix.mt-2.mb-2 {
            max-width: none;
            margin: 2rem auto 1.75rem !important;
            padding: 0 var(--workspace-gutter);
        }

        #project-show-page.project-workspace-redesign .project-content-shell {
            position: relative;
            z-index: 1;
            max-width: none;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(240px, 16vw);
            gap: 0.75rem;
            align-items: flex-start;
            margin: 1.5rem auto 1.75rem;
            padding: 0 var(--workspace-gutter);
        }

        #project-show-page.project-workspace-redesign .project-content-main {
            min-width: 0;
        }

        #project-show-page.project-workspace-redesign .project-content-main > .row.clearfix.mt-2.mb-2 {
            max-width: none;
            margin: 0 0 1.75rem !important;
            padding: 0;
        }

        #project-show-page.project-workspace-redesign .project-details-rail {
            min-width: 0;
            position: sticky;
            top: 1rem;
        }

        #project-show-page.project-workspace-redesign .project-primary-tabs {
            justify-content: flex-start !important;
            gap: clamp(0.8rem, 1.8vw, 1.35rem);
            margin: 0 !important;
            padding: 0 !important;
            border-bottom: 1px solid var(--workspace-line) !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            flex-wrap: nowrap !important;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: thin;
        }

        #project-show-page.project-workspace-redesign .project-primary-tabs .nav-item {
            flex: 0 0 auto;
        }

        #project-show-page.project-workspace-redesign .project-primary-tabs .nav-link {
            min-width: 0;
            padding: 0 0 1.05rem !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            color: var(--workspace-ink-40) !important;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.2;
            border-bottom: 2px solid transparent !important;
            transition: color 0.2s ease, border-color 0.2s ease;
            white-space: nowrap;
        }

        #project-show-page.project-workspace-redesign .project-primary-tabs .nav-link:hover,
        #project-show-page.project-workspace-redesign .project-primary-tabs .nav-link:focus {
            background: transparent !important;
            color: var(--workspace-ink-60) !important;
        }

        #project-show-page.project-workspace-redesign .project-primary-tabs .nav-link.active {
            border-bottom: 2px solid #1d4ed8 !important;
            color: var(--workspace-ink) !important;
            transform: none;
            font-weight: 500;
        }

        #project-show-page.project-workspace-redesign .project-secondary-tabs {
            justify-content: flex-start !important;
            gap: 1.5rem;
            margin: 0 0 1.5rem !important;
            padding: 0 !important;
            border-bottom: 1px solid var(--workspace-line) !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            flex-wrap: nowrap !important;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: thin;
        }

        #project-show-page.project-workspace-redesign .project-secondary-tabs .nav-item {
            flex: 0 0 auto;
        }

        #project-show-page.project-workspace-redesign .project-secondary-tabs .nav-link {
            min-width: 0;
            padding: 0 0 0.85rem !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            color: var(--workspace-ink-40) !important;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.2;
            border-bottom: 2px solid transparent !important;
            transition: color 0.2s ease, border-color 0.2s ease;
            white-space: nowrap;
        }

        #project-show-page.project-workspace-redesign .project-secondary-tabs .nav-link:hover,
        #project-show-page.project-workspace-redesign .project-secondary-tabs .nav-link:focus {
            background: transparent !important;
            color: var(--workspace-ink-60) !important;
        }

        #project-show-page.project-workspace-redesign .project-secondary-tabs .nav-link.active {
            border-bottom-color: var(--workspace-amber) !important;
            color: var(--workspace-ink) !important;
            transform: none;
            font-weight: 500;
        }

        #project-show-page.project-workspace-redesign > .tab-content {
            max-width: none;
            margin: 0 auto;
            padding: 0 var(--workspace-gutter);
        }

        #project-show-page.project-workspace-redesign .card:not(.modal .card) {
            background: transparent !important;
            box-shadow: none !important;
        }

        #project-show-page.project-workspace-redesign .card-header:not(.modal .card-header) {
            background: transparent !important;
            color: var(--workspace-ink) !important;
            border-bottom: 1px solid var(--workspace-line) !important;
            border-radius: 0 !important;
        }

        #project-show-page.project-workspace-redesign h3,
        #project-show-page.project-workspace-redesign h4,
        #project-show-page.project-workspace-redesign h5 {
            color: var(--workspace-ink);
        }

        #project-show-page.project-workspace-redesign .form-control,
        #project-show-page.project-workspace-redesign .form-select,
        #project-show-page.project-workspace-redesign .select2-container--default .select2-selection--single,
        #project-show-page.project-workspace-redesign .select2-container--default .select2-selection--multiple,
        #project-show-page.project-workspace-redesign .tags-input {
            background: transparent !important;
            border: 1px solid var(--workspace-line) !important;
            color: var(--workspace-ink) !important;
            border-radius: 8px;
        }

        #project-show-page.project-workspace-redesign .table {
            color: var(--workspace-ink);
            border-color: var(--workspace-line);
        }

        #project-show-page.project-workspace-redesign .table thead,
        #project-show-page.project-workspace-redesign .table thead th {
            background: var(--workspace-soft) !important;
            color: var(--workspace-ink) !important;
            border-color: var(--workspace-line) !important;
        }

        #project-show-page.project-workspace-redesign .btn-dark,
        #project-show-page.project-workspace-redesign .btn-primary,
        #project-show-page.project-workspace-redesign .btn-success {
            background: var(--workspace-amber) !important;
            border-color: var(--workspace-amber) !important;
            color: var(--workspace-cream) !important;
            border-radius: 8px;
            box-shadow: none;
        }

        #project-show-page.project-workspace-redesign .project-assignee-toggle {
            color: var(--workspace-ink);
        }

        #project-show-page.project-workspace-redesign .note-header,
        #project-show-page.project-workspace-redesign .files-header,
        #project-show-page.project-workspace-redesign .project-section-header {
            display: flex;
            align-items: center;
            min-height: 58px;
            margin: 0 0 1rem;
            padding: 1rem 1.15rem;
            background: transparent !important;
            color: var(--workspace-ink) !important;
            border-bottom: 1px solid var(--workspace-line) !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        #project-show-page.project-workspace-redesign .note-header i,
        #project-show-page.project-workspace-redesign .files-header i,
        #project-show-page.project-workspace-redesign .project-section-header i {
            color: var(--workspace-amber);
        }

        #project-show-page.project-workspace-redesign .notes-section {
            padding: 0 0 1.25rem;
            background: transparent !important;
            border-radius: 0;
        }

        #project-show-page.project-workspace-redesign .project-section-panel {
            margin-bottom: 1.5rem;
        }

        #project-show-page.project-workspace-redesign .sample-activity-grid {
            align-items: flex-start;
            row-gap: 2.5rem;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column,
        #project-show-page.project-workspace-redesign .sample-files-column {
            min-width: 0;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-header,
        #project-show-page.project-workspace-redesign .sample-files-column .files-header,
        #project-show-page.project-workspace-redesign .project-details-rail .files-header {
            min-height: 0;
            margin: 0 0 1rem;
            padding: 0;
            border: 0 !important;
            background: transparent !important;
            color: var(--workspace-ink-40) !important;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 0.86rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            line-height: 1.2;
            text-transform: uppercase;
            box-shadow: none !important;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-header i,
        #project-show-page.project-workspace-redesign .sample-files-column .files-header i,
        #project-show-page.project-workspace-redesign .project-details-rail .files-header i {
            display: none;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .notes-section {
            min-height: 260px;
            margin: 0 0 1.75rem;
            padding: 1.25rem;
            background: #ffffff !important;
            border: 1px solid var(--workspace-line);
            border-radius: 12px;
        }

        #project-show-page.project-workspace-redesign .department-fields-frame {
            padding: 1.25rem;
            background: #ffffff !important;
            border: 1px solid var(--workspace-line);
            border-radius: 12px;
        }

        #project-show-page.project-workspace-redesign .design-details-frame {
            margin-top: 1rem;
            padding: 1.25rem;
            background: #ffffff;
            border: 1px solid var(--workspace-line);
            border-radius: 12px;
        }

        #project-show-page.project-workspace-redesign .design-details-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem;
        }

        #project-show-page.project-workspace-redesign .design-detail-item {
            min-height: 64px;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
        }

        #project-show-page.project-workspace-redesign .design-detail-label {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--workspace-ink-50);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        #project-show-page.project-workspace-redesign .design-detail-value {
            color: var(--workspace-ink);
            font-size: 0.92rem;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        @media (max-width: 991.98px) {
            #project-show-page.project-workspace-redesign .design-details-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            #project-show-page.project-workspace-redesign .design-details-grid {
                grid-template-columns: 1fr;
            }
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-textarea {
            min-height: 120px;
            padding: 0;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            color: var(--workspace-ink);
            resize: vertical;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-card {
            margin: 0 0 1px;
            padding: 1.25rem;
            background: #ffffff !important;
            border: 1px solid var(--workspace-line);
            border-left: 0;
            border-radius: 0;
            box-shadow: none;
            transform: none;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-card:first-of-type {
            border-radius: 12px 12px 0 0;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-card:last-child {
            border-radius: 0 0 12px 12px;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-card:hover {
            box-shadow: none;
            transform: none;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-meta {
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-color: var(--workspace-line);
            color: var(--workspace-ink-40);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.68rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        #project-show-page.project-workspace-redesign .sample-notes-column .note-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .files-grid {
            display: flex;
            flex-direction: column;
            gap: 1px;
            margin-top: 0;
            overflow: hidden;
            background: var(--workspace-line);
            border: 1px solid var(--workspace-line);
            border-radius: 12px;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-card {
            min-height: 72px;
            display: grid;
            grid-template-columns: 2.5rem minmax(0, 1fr);
            align-items: center;
            gap: 0.85rem;
            padding: 1rem 3.35rem 1rem 1rem;
            background: #ffffff !important;
            border-radius: 0;
            box-shadow: none;
            overflow: visible;
            position: relative;
            transform: none;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-card:hover {
            box-shadow: none;
            transform: none;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-preview {
            width: 2rem;
            height: 2rem;
            border-radius: 7px;
            background: var(--workspace-soft) !important;
            overflow: hidden;
            position: static;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-preview img,
        #project-show-page.project-workspace-redesign .sample-files-column .file-preview iframe {
            display: none;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-preview::before {
            content: "\ef1a";
            font-family: IcoFont;
            color: var(--workspace-amber);
            font-size: 1rem;
            line-height: 1;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-preview .file-type-icon {
            display: none;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-info {
            min-width: 0;
            padding: 0;
            background: transparent !important;
            color: var(--workspace-ink);
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-header {
            margin: 0 0 0.25rem;
            color: var(--workspace-ink);
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.2;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-name {
            color: var(--workspace-ink-40);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.62rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .file-name a {
            color: var(--workspace-ink-40) !important;
        }

        #project-show-page.project-workspace-redesign .project-detail-list {
            display: flex;
            flex-direction: column;
            gap: 1px;
            overflow: hidden;
            background: var(--workspace-line);
            border: 1px solid var(--workspace-line);
            border-radius: 12px;
        }

        #project-show-page.project-workspace-redesign .project-detail-item {
            min-height: 68px;
            display: grid;
            grid-template-columns: 2.5rem minmax(0, 1fr);
            align-items: center;
            gap: 0.85rem;
            padding: 1rem;
            background: #ffffff;
        }

        #project-show-page.project-workspace-redesign .project-detail-icon {
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: var(--workspace-soft);
            color: #1d4ed8;
            font-size: 1rem;
        }

        #project-show-page.project-workspace-redesign .project-detail-label {
            margin-bottom: 0.25rem;
            color: var(--workspace-ink-40);
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.62rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        #project-show-page.project-workspace-redesign .project-detail-value {
            color: var(--workspace-ink);
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .delete-icon {
            top: 50%;
            right: 0.65rem;
            padding: 0;
            width: 1.65rem;
            height: 1.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(69, 26, 3, 0.08);
            color: var(--workspace-ink-60);
            border-radius: 7px;
            transform: translateY(-50%);
        }

        #project-show-page.project-workspace-redesign .sample-files-column .upload-btn {
            margin-bottom: 1rem;
            padding: 0;
            background: transparent !important;
            color: var(--workspace-amber) !important;
            border-radius: 0;
            box-shadow: none;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        #project-show-page.project-workspace-redesign .sample-files-column .no-files {
            background: #ffffff;
            color: var(--workspace-ink-40);
            font-size: 0.9rem;
        }

        #generateDesignDetailsModal .modal-dialog {
            max-height: calc(100vh - 1rem);
        }

        #generateDesignDetailsModal .modal-content {
            max-height: calc(100vh - 1rem);
            overflow: hidden;
        }

        #generateDesignDetailsModal .modal-body {
            max-height: calc(100vh - 12rem);
            overflow-y: auto;
        }

        #generateDesignDetailsModal .modal-footer {
            flex-shrink: 0;
            background: #ffffff;
        }

        #generateDesignDetailsModal .modal-footer .btn {
            min-height: 32px;
            padding: 0.25rem 0.5rem !important;
            border-radius: 0.2rem !important;
            font-size: 0.875rem;
            line-height: 1.5;
            box-shadow: none !important;
            transform: none !important;
        }

        @media (max-width: 768px) {
            #project-show-page.project-workspace-redesign > .card:first-child {
                padding: 1.25rem 1rem;
            }

            #project-show-page.project-workspace-redesign .project-summary-header {
                grid-template-columns: 1fr;
            }

            #project-show-page.project-workspace-redesign .project-content-shell {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            #project-show-page.project-workspace-redesign .project-details-rail {
                position: static;
            }

            #project-show-page.project-workspace-redesign .project-stage-meta {
                justify-self: start;
                align-items: flex-start;
                text-align: left;
            }

            #project-show-page.project-workspace-redesign .project-stage-meta .pto-greenlight-tag {
                align-self: flex-start;
            }

            #project-show-page.project-workspace-redesign .project-customer-contact {
                gap: 0.35rem 1rem;
            }

            #project-show-page.project-workspace-redesign .project-primary-tabs {
                gap: 1rem;
                overflow-x: auto;
                flex-wrap: nowrap !important;
            }

            #project-show-page.project-workspace-redesign .project-primary-tabs .nav-link {
                white-space: nowrap;
            }

            #project-show-page.project-workspace-redesign .project-secondary-tabs {
                gap: 1.1rem;
            }

            #project-show-page.project-workspace-redesign .project-secondary-tabs .nav-link {
                font-size: 0.86rem;
            }

            #project-show-page.project-workspace-redesign .sample-notes-column .note-meta {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 0.35rem;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/42.0.0/ckeditor5.css">
    <div id="project-show-page" class="project-workspace-redesign">
    <div class="card card-info">
        <div class="card-body">
            <div class="row clearfix">
                <div class="col-md-12">
                    {{-- @if ($alertStatus)
                        <div class="alert alert-{{ $alertClass }} alert-dismissible fade show" role="alert">
                            {{ $message }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif --}}
                    <div class="card border-0 mb-4 no-bg">
                        @php
                            $currentAssignedName = optional($task->employee)->name ?? 'Unassigned';
                            $projectAgeDays = empty($project->pto_approval_date)
                                ? now()->diffInDays(Carbon\Carbon::parse($project->customer->sold_date))
                                : Carbon\Carbon::parse($project->pto_approval_date)->diffInDays(
                                    Carbon\Carbon::parse($project->customer->sold_date),
                                );
                            $customerAddressParts = array_filter([
                                $project->customer->street,
                                $project->customer->city,
                                $project->customer->state,
                                $project->customer->zipcode,
                            ]);
                            $customerAddress = implode(', ', $customerAddressParts);
                            $isPtoGreenlightApproved =
                                $alertStatus &&
                                ($alertClass ?? null) === 'success' &&
                                ($message ?? null) === 'PTO Greenlight approved';
                            $authEmployeeId = optional(auth()->user()->employee)->id;
                            $canManageDesignDetails = auth()->user()->hasAnyRole(['Super Admin', 'Manager']);
                            $designDetailFields = [
                                'name' => 'Name',
                                'phone' => 'Phone',
                                'address' => 'Address',
                                'ahj' => 'AHJ',
                                'roof_area' => 'Roof Area',
                                'mod' => 'MOD',
                                'array_area' => 'Array Area',
                                'inv' => 'INV',
                                'utility_meter' => 'M. #/Utility',
                                'kw_rating' => 'kW Rating',
                                'ac_cec' => 'AC-CEC',
                                'apn' => 'APN',
                                'stories' => 'Stories',
                                'roof_type' => 'Roof Type',
                                'rafter' => 'Rafter',
                                'slope' => 'Slope',
                                'msp' => 'MSP',
                                'array_azi' => 'Array AZI',
                                'design_notes' => 'Notes',
                                'assign_notes' => 'Assign Notes',
                            ];
                        @endphp
                        <div class="card-header project-summary-header border-0">
                            <div class="project-summary-main">
                                <h3 class="project-summary-title">
                                    @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Manager']))
                                        <div class="dropdown d-inline-block">
                                            <button class="project-title-status" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ str_replace('-', ' ', $project->project_name) }}
                                            </button>
                                            <ul class="dropdown-menu project-title-status-menu">
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item project-status-option {{ $task->status == 'In-Progress' ? 'active' : '' }}"
                                                        data-status="In-Progress">In-Progress</button>
                                                </li>
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item project-status-option {{ $task->status == 'Hold' ? 'active' : '' }}"
                                                        data-status="Hold">Hold</button>
                                                </li>
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item project-status-option {{ $task->status == 'Cancelled' ? 'active' : '' }}"
                                                        data-status="Cancelled">Cancelled</button>
                                                </li>
                                            </ul>
                                        </div>
                                    @else
                                        {{ str_replace('-', ' ', $project->project_name) }}
                                    @endif
                                </h3>
                                @if ($customerAddress)
                                    <p class="project-customer-address">{{ $customerAddress }}</p>
                                @endif
                                <div class="project-customer-contact">
                                    @if ($project->customer->phone)
                                        <span>{{ $project->customer->phone }}</span>
                                    @endif
                                    @if ($project->customer->email)
                                        <span>{{ $project->customer->email }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="project-stage-meta">
                                <span class="project-days-badge">{{ $projectAgeDays }} Days in progress</span>
                                @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Manager']))
                                    <div class="dropdown project-assignee-control">
                                        <button class="project-assignee-toggle dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="project-assignee-label">Assigned:</span>
                                            <span class="project-assignee-name" id="currentAssignedEmployeeName">
                                                {{ $currentAssignedName }}
                                            </span>
                                        </button>
                                        <ul class="dropdown-menu project-assignee-menu">
                                            @foreach ($employees as $employee)
                                                <li>
                                                    <button type="button"
                                                        class="dropdown-item project-employee-option {{ $task->employee_id == $employee->id ? 'active' : '' }}"
                                                        data-employee-id="{{ $employee->id }}"
                                                        data-employee-name="{{ $employee->name }}">
                                                        {{ $employee->name }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <span class="project-current-stage">Assigned: {{ $currentAssignedName }}</span>
                                @endif
                                @if ($isPtoGreenlightApproved)
                                    <span class="project-tag-chip pto-greenlight-tag">PTO Greenlight Approved</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @php
                        $inverterTags = optional($project->customer->inverter)->tag_list ?? [];
                        $adderTags = $project->customer->adders
                            ->map(fn($adder) => optional($adder->type)->tag)
                            ->filter()
                            ->unique()
                            ->values();
                        $projectHeaderTags = collect();

                        if (!empty($project->utility_company)) {
                            $projectHeaderTags->push($project->utility_company);
                        }

                        if ($project->hoa == 'yes') {
                            $projectHeaderTags->push('HOA');
                        }

                        if (!empty($project->ahj)) {
                            $projectHeaderTags->push($project->ahj);
                        }
                    @endphp

                    @if (count($inverterTags) || count($adderTags) || $projectHeaderTags->isNotEmpty())
                        <div class="project-tag-panel">
                            <div class="project-tag-row">
                                @if ($projectHeaderTags->isNotEmpty())
                                    <div class="project-tag-list is-left">
                                        @foreach ($projectHeaderTags as $tag)
                                            <span class="project-tag-chip project-info-tag">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if (count($inverterTags) || count($adderTags))
                                    <div class="project-tag-list is-right">
                                        @foreach ($inverterTags as $tag)
                                            <span class="project-tag-chip inverter-tag">{{ $tag }}</span>
                                        @endforeach

                                        @foreach ($adderTags as $tag)
                                            <span class="project-tag-chip adder-tag">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-center align-items-center">
                        <nav class="navbar navbar-expand-lg ">
                            <div class="container-fluid">
                                <div class="collapse navbar-collapse">
                                    <ul class="nav nav-tabs project-department-tabs tab-body-header rounded ms-3 prtab-set w-sm-100"
                                        style="overflow: visible !important;">
                                        @foreach ($departments as $department)
                                            @php
                                                if ($project->customer->is_adu == 0) {
                                                    # code...
                                                    $filtered_collection = $nextSubDepartments
                                                        ->filter(function ($item) use ($department) {
                                                            return $item->department_id == $department->id;
                                                        })
                                                        ->values();
                                                } else {
                                                    $filtered_collection = $nextSubDepartments
                                                        ->filter(function ($item) use ($department) {
                                                            return $item->department_id == $department->id &&
                                                                $item->name == 'New Construction';
                                                        })
                                                        ->values();
                                                }
                                                $departmentProgressStatus = $department->id < $project->department_id
                                                    ? 'Completed'
                                                    : ($department->id == $project->department_id
                                                        ? 'In Progress'
                                                        : 'Pending');
                                                $departmentStepLabel = str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) . ' ' . $department->name;
                                            @endphp
                                            @if ($department->id < $project->department_id)
                                                <li class="nav-item dropdown bg-success">
                                                    <a class="nav-link dropdown-toggle  text-white" id="navbarDropdown"
                                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <span class="department-pipeline-title">{{ $departmentStepLabel }}</span>
                                                        <span class="department-pipeline-status">{{ $departmentProgressStatus }}</span>
                                                    </a>
                                                    @if (count($filtered_collection) > 0)
                                                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                            @foreach ($filtered_collection as $subdepartment)
                                                                <li><a onclick="moveProjectModal('{{ $project->id }}', currentProjectTaskId, '{{ $department->id }}','{{ $subdepartment->id }}')"
                                                                        class="dropdown-item">{{ $subdepartment->name }}</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @elseif($department->id == $project->department_id)
                                                <li class="nav-item dropdown bg-success">
                                                    <a class="nav-link dropdown-toggle active text-white"
                                                        id="navbarDropdown" role="button" data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                        <span class="department-pipeline-title">{{ $departmentStepLabel }}</span>
                                                        <span class="department-pipeline-status">{{ $departmentProgressStatus }}</span>
                                                    </a>
                                                    @if (count($filtered_collection) > 0)
                                                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                            @foreach ($filtered_collection as $subdepartment)
                                                                <li><a onclick="moveProjectModal('{{ $project->id }}', currentProjectTaskId, '{{ $department->id }}','{{ $subdepartment->id }}')"
                                                                        class="dropdown-item">{{ $subdepartment->name }}</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @else
                                                <li class="nav-item dropdown">
                                                    <a class="nav-link dropdown-toggle " id="navbarDropdown" role="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <span class="department-pipeline-title">{{ $departmentStepLabel }}</span>
                                                        <span class="department-pipeline-status">{{ $departmentProgressStatus }}</span>
                                                    </a>
                                                    @if (count($filtered_collection) > 0)
                                                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                                            @foreach ($filtered_collection as $subdepartment)
                                                                <li><a onclick="moveProjectModal('{{ $project->id }}', currentProjectTaskId, '{{ $department->id }}','{{ $subdepartment->id }}')"
                                                                        class="dropdown-item">{{ $subdepartment->name }}</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </li>
                                            @endif
                                        @endforeach

                                    </ul>
                                </div>
                            </div>
                        </nav>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @php
        $projectDetailItems = [
            [
                'icon' => 'icofont-briefcase',
                'label' => 'Sales Partner',
                'value' => optional($project->customer->salespartner)->name ?? '-',
            ],
            [
                'icon' => 'icofont-user-alt-3',
                'label' => 'Sales Person',
                'value' => optional($project->salesPartnerUser)->name ?? '-',
            ],
            [
                'icon' => 'icofont-calendar',
                'label' => 'Sold Date',
                'value' => $project->customer->sold_date
                    ? \Carbon\Carbon::parse($project->customer->sold_date)->format('m/d/Y')
                    : '-',
            ],
            [
                'icon' => 'icofont-sun',
                'label' => 'System Size',
                'value' => $project->customer->module_value ?: '-',
            ],
            [
                'icon' => 'icofont-solar-panel',
                'label' => 'Module Type',
                'value' => optional($project->customer->module)->name ?? '-',
            ],
            [
                'icon' => 'icofont-energy-solar',
                'label' => 'Inverter Type',
                'value' => optional($project->customer->inverter)->name ?? '-',
            ],
            [
                'icon' => 'icofont-layout',
                'label' => 'Panel Qty',
                'value' => $project->customer->panel_qty ?: '-',
            ],
            [
                'icon' => 'icofont-dashboard-web',
                'label' => 'Inverter Qty',
                'value' => $project->customer->inverter_qty ?: '-',
            ],
            [
                'icon' => 'icofont-chart-line',
                'label' => 'Sold Production',
                'value' => $project->customer->sold_production_value ?: '-',
            ],
            [
                'icon' => 'icofont-globe',
                'label' => 'Preferred Language',
                'value' => $project->customer->preferred_language ?: '-',
            ],
            [
                'icon' => 'icofont-credit-card',
                'label' => 'Finance',
                'value' => optional(optional($project->customer->finances)->finance)->name ?? '-',
            ],
        ];
    @endphp

    <div class="project-content-shell">
        <div class="project-content-main">
    <div class="row clearfix mt-2 mb-2">
        <div class="col-md-12">
            <ul class="nav nav-tabs project-primary-tabs px-3 border-bottom-0 justify-content-center flex-wrap" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#default"
                        role="tab">Project Activity</a></li>
                {{-- <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#customer"
                        role="tab">Customer</a></li> --}}
                @can('View Financial Details')
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#financial"
                            role="tab">Financial Ledger</a></li>
                @endcan
                @can('View Tickets')
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tickets"
                            role="tab">Tickets</a></li>
                @endcan
                <li class="nav-item"><a
                        class="nav-link {{ $project->viewed_emails_count > 0 ? 'blink-dot' : '' }}"
                        data-bs-toggle="tab" href="#communication" role="tab">Communication</a></li>
                @can('Project History')
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#history"
                            role="tab">Project
                            History</a></li>
                @endcan
            </ul>
        </div>
    </div>


    <div class="tab-content">
        <div class="tab-pane fade show active" id="default" role="tabpanel">

            <div class="card card-info mt-2">
                <div class="card-body">
                    <div class="row clearfix">
                                {{-- <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center bg-light text-center  justify-content-between border-bottom">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Notes </h3>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="col-md-12">
                                    @php
                                        $activeDepartmentId = $departments->contains('id', $project->department_id)
                                            ? $project->department_id
                                            : optional($departments->first())->id;
                                    @endphp
                                    <ul class="nav nav-tabs project-department-tabs tab-body-header rounded justify-content-center mb-4"
                                        id="departmentDetailTabs" role="tablist">
                                        @foreach ($departments as $department)
                                            @php
                                                $isCurrentDepartment = $department->id == $activeDepartmentId;
                                                $departmentDetailStepLabel = str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) . ' ' . $department->name;
                                            @endphp
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link {{ $isCurrentDepartment ? 'active' : '' }}"
                                                    id="department-detail-tab-{{ $department->id }}"
                                                    data-bs-toggle="tab"
                                                    data-bs-target="#department-detail-{{ $department->id }}"
                                                    type="button"
                                                    role="tab"
                                                    aria-controls="department-detail-{{ $department->id }}"
                                                    aria-selected="{{ $isCurrentDepartment ? 'true' : 'false' }}">
                                                    <span class="department-detail-tab-title">{{ $departmentDetailStepLabel }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="tab-content" id="departmentDetailTabsContent">
                                        @foreach ($departments as $department)
                                            @php
                                                $isCurrentDepartment = $department->id == $activeDepartmentId;
                                            @endphp
                                            <div class="tab-pane fade {{ $isCurrentDepartment ? 'show active' : '' }}"
                                                id="department-detail-{{ $department->id }}"
                                                role="tabpanel"
                                                aria-labelledby="department-detail-tab-{{ $department->id }}">
                                                {{-- <div class="department-detail-heading">
                                                    <span>{{ $department->name }}</span>
                                                </div> --}}

                                                <div class="row clearfix sample-activity-grid">
                                                    <div class="col-lg-8 col-md-12 mb-3 sample-notes-column">
                                                        @livewire('project.notes-section', ['projectId' => $project->id, 'taskId' => $task->id, 'departmentId' => $department->id, 'projectDepartmentId' => $project->department_id, 'ghost' => $ghost,'viewSource' => 'crm'], key('notes-' . $department->id))
                                                        <div class="project-section-panel">
                                                            <div class="project-section-header">
                                                                <i class="icofont-list me-2"></i>Department Fields
                                                            </div>
                                                            <div class="department-fields-frame">
                                                                @livewire('project.project-fields', ['project' => $project, 'taskId' => $task->id, 'departmentId' => $department->id, 'projectDepartmentId' => $project->department_id, 'ghost' => $ghost,'viewSource' => 'crm'], key('fields-' . $department->id))
                                                            </div>
                                                            @php
                                                                $isEngineeringDepartment = strcasecmp($department->name ?? '', 'Engineering') === 0;
                                                                $canViewDesignDetails =
                                                                    !empty($designDetail) &&
                                                                    $isEngineeringDepartment &&
                                                                    (
                                                                        $canManageDesignDetails ||
                                                                        ($authEmployeeId && (int) $task->employee_id === (int) $authEmployeeId)
                                                                    );
                                                                $canGenerateDesignDetails =
                                                                    $canManageDesignDetails &&
                                                                    empty($designDetail) &&
                                                                    $isCurrentDepartment &&
                                                                    strcasecmp(optional($project->department)->name ?? '', 'Engineering') === 0;
                                                            @endphp
                                                            @if ($canViewDesignDetails)
                                                                <div class="design-details-frame">
                                                                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                                                        <div class="project-section-header mb-0">
                                                                            <i class="icofont-paper me-2"></i>Design Details
                                                                        </div>
                                                                        @if ($canManageDesignDetails)
                                                                            <button type="button"
                                                                                class="btn btn-outline-primary btn-sm open-design-details-modal"
                                                                                data-mode="edit"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#generateDesignDetailsModal">
                                                                                <i class="icofont-edit me-2"></i>Edit
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                    <div class="design-details-grid">
                                                                        @foreach ($designDetailFields as $fieldName => $fieldLabel)
                                                                            <div class="design-detail-item">
                                                                                <span class="design-detail-label">{{ $fieldLabel }}</span>
                                                                                <span class="design-detail-value">{{ filled($designDetail->{$fieldName}) ? $designDetail->{$fieldName} : '-' }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                        <div class="design-detail-item">
                                                                            <span class="design-detail-label">Assigned Employee</span>
                                                                            <span class="design-detail-value">{{ optional($designDetail->employee)->name ?? '-' }}</span>
                                                                        </div>
                                                                        <div class="design-detail-item">
                                                                            <span class="design-detail-label">Follow-up</span>
                                                                            <span class="design-detail-value">
                                                                                {{ $designDetail->follow_up ? 'Yes' : 'No' }}
                                                                                @if ($designDetail->follow_up_date)
                                                                                    - {{ $designDetail->follow_up_date->format('m/d/Y') }}
                                                                                @endif
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            @if ($canGenerateDesignDetails)
                                                                <div class="mt-3 text-end">
                                                                    <button type="button" class="btn btn-primary open-design-details-modal"
                                                                        data-mode="create"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#generateDesignDetailsModal">
                                                                        <i class="icofont-paper me-2"></i>Generate Design Details
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-4 col-md-12 mb-3 sample-files-column">
                                                        @livewire('project.enhanced-files-section', ['projectId' => $project->id, 'taskId' => $task->id, 'departmentId' => $department->id, 'projectDepartmentId' => $project->department_id, 'ghost' => $ghost,'viewSource' => 'crm'], key('enhanced-' . $department->id))
                                                        @if ($isCurrentDepartment)
                                                            @can('Department Tools')
                                                                <div class="card border-0 mt-4">
                                                                    <div class="card-header px-3 py-3 d-flex align-items-center border-bottom">
                                                                        <h5 class="fw-bold flex-fill mb-0">
                                                                            <i class="icofont-tools-alt-2 me-2"></i>Department Tools
                                                                        </h5>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        @if (!empty($tools) && count($tools))
                                                                            <ul class="list-group list-group-custom">
                                                                                @foreach ($tools as $tool)
                                                                                    <li class="list-group-item light-primary-bg">
                                                                                        <a target="_blank"
                                                                                            href="{{ asset('storage/tools/' . $tool->file) }}"
                                                                                            class="ml-3">{{ $tool->name }}</a>
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @else
                                                                            <div>No Tools found.</div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endcan
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                    </div>
            </div>
        </div>
    </div>
    {{-- <div class="tab-pane fade" id="customer" role="tabpanel">
        <div class="card mt-1">
            <div class="card-header">
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer Details</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input disabled value="{{ $project->customer->first_name }}" type="text"
                            class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input disabled value="{{ $project->customer->last_name }}" type="text"
                            class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3">
                        <label for="street" class="form-label">Street</label>
                        <input disabled value="{{ $project->customer->street }}" type="text" class="form-control"
                            id="street" name="street" placeholder="Street">
                    </div>
                    <div class="col-sm-3">
                        <label for="city" class="form-label">City</label>
                        <input disabled value="{{ $project->customer->city }}" type="text" class="form-control"
                            id="city" name="city" placeholder="City">
                    </div>
                    <div class="col-sm-3">
                        <label for="state" class="form-label">State</label>
                        <input disabled value="{{ $project->customer->state }}" type="text" class="form-control"
                            id="state" name="state" placeholder="State">
                    </div>
                    <div class="col-sm-3">
                        <label for="zipcode" class="form-label">Zip Code</label>
                        <input disabled value="{{ $project->customer->zipcode }}" type="text"
                            class="form-control" id="zipcode" name="zipcode" placeholder="Zip Code">
                    </div>
                    <div class="col-sm-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input disabled value="{{ $project->customer->phone }}" type="text" class="form-control"
                            id="phone" name="phone" placeholder="phone">
                    </div>
                    <div class="col-sm-3">
                        <label for="email" class="form-label">Email</label>
                        <input disabled value="{{ $project->customer->email }}" type="text" class="form-control"
                            id="email" name="email" placeholder="Email">
                    </div>

                    <div class="col-sm-3">
                        <label for="sold_date" class="form-label">Sold Date</label>
                        <input disabled value="{{ $project->customer->sold_date }}" type="date"
                            class="form-control" id="sold_date" name="sold_date" placeholder="Sold Date">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Sales Partner</label>
                        <input disabled value="{{ $project->customer->salespartner->name }}" type="text"
                            class="form-control" />
                    </div>
                    <div class="col-sm-3">
                        <label for="code" class="form-label">Panel Qty</label>
                        <input disabled value="{{ $project->customer->panel_qty }}" type="text"
                            class="form-control" id="panel_qty" name="panel_qty" placeholder="Panel Qty">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Module Type</label>
                        <input disabled value="{{ $project->customer->module->name }}" type="text"
                            class="form-control" id="module_type_id" name="module_type_id"
                            placeholder="Module Type">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Inverter Type</label>
                        <input disabled value="{{ $project->customer->inverter->name }}" type="text"
                            class="form-control" id="inverter_type_id" name="inverter_type_id"
                            placeholder="Inverter Type">
                    </div>
                    <div class="col-sm-3">
                        <label for="module_qty" class="form-label">System Size</label>
                        <input disabled value="{{ $project->customer->module_value }}" type="text"
                            class="form-control" id="module_qty" name="module_qty" placeholder="System Size">
                    </div>
                    <div class="col-sm-3">
                        <label for="inverter_qty" class="form-label">Inverter Qty</label>
                        <input disabled value="{{ $project->customer->inverter_qty }}" type="text"
                            class="form-control" id="inverter_qty" name="inverter_qty" placeholder="Inverter Qty">
                    </div>
                    @if ($project->customer->loan_id)
                        <div class="col-sm-3">
                            <label for="inverter_qty" class="form-label">Loan Id</label>
                            <input disabled value="{{ $project->customer->loan_id }}" type="text"
                                class="form-control" placeholder="Loan Id">
                        </div>
                    @endif
                    @if ($project->customer->sold_production_value)
                        <div class="col-sm-3">
                            <label for="inverter_qty" class="form-label">Sold Production Value</label>
                            <input disabled value="{{ $project->customer->sold_production_value }}" type="text"
                                class="form-control" placeholder="Sold Production Value">
                        </div>
                    @endif
                    <div class="col-sm-3">
                            <label for="inverter_qty" class="form-label">Preferred Language</label>
                            <input disabled value="{{ $project->customer->preferred_language }}" type="text"
                                class="form-control" placeholder="Preferred Language">
                        </div>
                </div>
            </div>
        </div>
        <div class="card mt-1">
            <div class="card-header">
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Sales Partner Details</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3 mt-1">
                    <div
                        class="col-sm-3d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 w220">
                        <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/users/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                            alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Sales Partner Name</label>
                        <input disabled value="{{ $project->customer->salespartner->name }}" type="text"
                            class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Email</label>
                        <input disabled value="{{ $project->customer->salespartner->email }}" type="text"
                            class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Phone</label>
                        <input disabled value="{{ $project->customer->salespartner->phone }}" type="text"
                            class="form-control" id="street" name="street" placeholder="Street">
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-1">
            <div class="card-body">
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Sales Person</h3>
                </div>
                <div class="row g-3 mb-3 mt-1">
                    <div
                        class="col-sm-3d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 w220">
                        <img src="{{ !empty($project->salesPartnerUser) && $project->salesPartnerUser->image != '' ? asset('storage/users/' . $project->salesPartnerUser->image) : asset('assets/images/profile_av.png') }}"
                            alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Sales Person Name</label>
                        <input disabled value="{{ $project->salesPartnerUser->name ?? '-' }}" type="text"
                            class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Email</label>
                        <input disabled value="{{ $project->salesPartnerUser->email ?? '-' }}" type="text"
                            class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Phone</label>
                        <input disabled value="{{ $project->salesPartnerUser->phone ?? '-' }}" type="text"
                            class="form-control" id="street" name="street" placeholder="Street">
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <div class="tab-pane fade" id="financial" role="tabpanel">
        <div class="card mt-1">
            <div class="card-body">
                @can('View Financial Details')
                    @php
                        $customerFinance = optional($project->customer)->finances;
                        $financeOption = optional($customerFinance)->finance;
                    @endphp
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 px-3" data-bs-toggle="collapse"
                            data-bs-target="#finance" aria-expanded="false" aria-controls="finance">Financial Details
                        </h3>
                    </div>
                    @if (empty($customerFinance) || empty($financeOption))
                        <div class="alert alert-warning mt-3 mb-0">
                            Financial details are not available for this project yet.
                        </div>
                    @else
                    <div class="row g-3 mb-3">
                        <div class="col-sm-3 ">
                            <label for="finance_option_id" class="form-label">Finance Option</label>
                            <input type="text" class="form-control"
                                value="{{ $financeOption->name }}">
                        </div>
                        @if (
                            $financeOption->name != 'Cash' &&
                                $financeOption->name != 'LightReach PPA')
                            <div class="col-sm-3  loandiv">
                                <label for="loan_term_id" class="form-label">Loan Term</label>
                                <input type="text" class="form-control"
                                    value="{{ !empty($customerFinance->term) ? $customerFinance->term->year : '' }}"
                                    id="loan_term_id" name="loan_term_id">
                            </div>
                            <div class="col-sm-3  loandiv">
                                <label for="loan_apr_id" class="form-label">Loan Apr</label>
                                <input type="text" class="form-control"
                                    value="{{ !empty($customerFinance->apr) ? $customerFinance->apr->apr : '' }}"
                                    id="loan_apr_id" name="loan_apr_id">
                            </div>
                        @endif
                        <div class="col-sm-3 ">
                            <label for="contract_amount" class="form-label">Contract Amount</label>
                            <input type="text" class="form-control"
                                value="$ {{ number_format($customerFinance->contract_amount, 2) }}"
                                id="contract_amount" name="contract_amount">
                        </div>
                        @php
                            $totalOverridePanelCost = $project->customer->panel_qty * $project->overwrite_panel_price;
                            $totalOverride = $totalOverridePanelCost + $project->overwrite_base_price;
                            $actualRedlineCost = $customerFinance->redline_costs - $totalOverride;
                            $totalCommission = $totalOverride + $customerFinance->commission;
                            // $project->customer->finances->redline_costs
                        @endphp
                        <div class="col-sm-3 ">
                            <label for="redline_costs" class="form-label">Redline Costs</label>
                            <input type="text" class="form-control"
                                value="$ {{ number_format($actualRedlineCost, 2) }}" id="redline_costs"
                                name="redline_costs">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="adders" class="form-label">Adders</label>
                            <input type="text" class="form-control"
                                value="$ {{ number_format($customerFinance->adders, 2) }}" id="adders_amount"
                                name="adders_amount">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="commission" class="form-label">Commission</label>
                            <input type="text" class="form-control"
                                value="$ {{ number_format($totalCommission, 2) }}" id="commission" name="commission">
                        </div>
                        @if ((int) $customerFinance->finance_option_id === 9)
                            <div class="col-sm-3 ">
                                <label for="third_party_credit" class="form-label">Third Party Credit</label>
                                <input type="text" class="form-control"
                                    value="$ {{ number_format($customerFinance->third_party_credit, 2) }}"
                                    id="third_party_credit" name="third_party_credit">
                            </div>
                            <div class="col-sm-3 ">
                                <label for="customer_portion" class="form-label">Customer Portion</label>
                                <input type="text" class="form-control"
                                    value="$ {{ number_format($customerFinance->customer_portion, 2) }}"
                                    id="customer_portion" name="customer_portion">
                            </div>
                        @endif
                        @if (
                            $financeOption->name != 'Cash' &&
                                $financeOption->name != 'LightReach PPA')
                            <div class="col-sm-3 ">
                                <label for="dealer_fee" class="form-label">Dealer Fee</label>
                                <input type="text" class="form-control"
                                    value="{{ $customerFinance->dealer_fee }}" id="dealer_fee"
                                    name="dealer_fee">
                            </div>
                            <div class="col-sm-3 ">
                                <label for="dealer_fee_amount" class="form-label">Dealer Fee Amount</label>
                                <input type="text" class="form-control"
                                    value="$ {{ number_format($customerFinance->dealer_fee_amount, 2) }}"
                                    id="dealer_fee_amount" name="dealer_fee_amount">
                            </div>
                        @endif
                        @can('Holdback Amount')
                            <div class="col-sm-3 ">
                                <label for="commission" class="form-label">Holdback Amount</label>
                                <input type="text" class="form-control"
                                    value="$ {{ number_format($customerFinance->holdback_amount, 2) }}">
                            </div>
                        @endcan
                    </div>
                    @endif
                    {{-- <div class="col-sm-12 mb-3">
                        <button type="submit" class="btn btn-dark me-1 mt-1 w-sm-100"><i
                                class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                    </div> --}}
                    {{-- @endif --}}
                @endcan
            </div>
        </div>
        @can('View Adder Details')
            @php
                $customerFinance = $customerFinance ?? optional($project->customer)->finances;
                $financeOption = $financeOption ?? optional($customerFinance)->finance;
            @endphp
            <div class="card mt-1">
                <div class="card-body">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 px-3" data-bs-toggle="collapse"
                            data-bs-target="#adderTable" aria-expanded="false" aria-controls="adderTable">Adders
                            Details
                        </h3>
                    </div>
                    <form method="post" action="{{ route('projects.adders') }}">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                        <input type="hidden" name="customer_id" value="{{ $project->customer->id }}">
                        <input type="hidden" name="finance_option_id"
                            value="{{ optional($financeOption)->id }}">
                        @if (auth()->user()->hasAnyRole(['Manager', 'Admin', 'Super Admin']))
                            @if ($isAddersLocked)
                                <div class="alert alert-warning mb-3">
                                    <i class="icofont-lock"></i> Adders section is locked.
                                    @if (auth()->user()->hasAnyRole(['Manager', 'Admin', 'Super Admin']))
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="toggleAddersLock('unlocked')">Unlock</button>
                                    @endif
                                </div>
                            @endif
                            <div class="row g-4 mb-3" id="addersForm"
                                style="{{ $isAddersLocked ? 'pointer-events: none; opacity: 0.6;' : '' }}">
                                <div class="col-sm-2 mt-5">
                                    <div class="col-sm-12 mb-1">
                                        <label for="adders" class="form-label">Adders</label><br />
                                        <select style="width: 100%;" class="form-select select2"
                                            aria-label="Default select Adders" id="adders" name="adders"
                                            {{ $isAddersLocked ? 'disabled' : '' }}>
                                            <option value="">Select Adders</option>
                                            @foreach ($adders as $adder)
                                                <option value="{{ $adder->id }}">
                                                    {{ $adder->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-2 mt-5">
                                    <label for="uom" class="form-label">UOM</label><br />
                                    <select style="width: 100%;" class="form-control select2"
                                        aria-label="Default select UOM" id="uom"
                                        {{ $isAddersLocked ? 'disabled' : '' }}>
                                        <option value="">Select UOM</option>
                                        @foreach ($uoms as $uom)
                                            <option value="{{ $uom->id }}">
                                                {{ $uom->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('uom')
                                        <div class="text-danger message mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-2 mt-5">
                                    <label for="amount" class="form-label">Amount</label>
                                    <input type="text" class="form-control" id="amount" name="amount"
                                        placeholder="Adders Amount" {{ $isAddersLocked ? 'disabled' : '' }}>
                                    @error('amount')
                                        <div class="text-danger message mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-2 mt-5">
                                    <button type="button" id="btnAdder" class="btn btn-primary mt-4"
                                        {{ $isAddersLocked ? 'disabled' : '' }}><i
                                            class="icofont-save me-2 fs-6"></i>Add</button>
                                </div>
                            </div>
                            </hr>
                        @endif
                        <table id="adderTable" class="table table-bordered table-striped text-white">
                            <thead>
                                <tr>
                                    <th class="text-white">No.</th>
                                    <th class="text-white">Adder</th>
                                    <th class="text-white">Unit</th>
                                    <th class="text-white">Amount</th>
                                    <th class="text-white">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($project->customer->adders as $key => $adder)
                                    @php $index = ++$key; @endphp
                                    <tr id="row{{ $key }}">
                                        <input type="hidden" value="{{ $adder->adder_type_id }}" name="adders[]" />
                                        <!-- <input type="hidden" value="{{ $adder->adder_sub_type_id }}" name="subadders[]" /> -->
                                        <input type="hidden" value="{{ $adder->adder_unit_id }}" name="uom[]" />
                                        <input type="hidden" value="{{ $adder->amount }}" name="amount[]" />
                                        <td>{{ $index }}</td>
                                        <td>{{ $adder->type->name }}</td>
                                        <td>{{ $adder->unit->name }}</td>
                                        <td>$ {{ number_format($adder->amount, 2) }}</td>
                                        <td>
                                            <i style='cursor: pointer;' class='icofont-trash text-danger'
                                                onClick="deleteItem('{{ $index }}','{{ $adder->id }}')">
                                                Delete</i>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        @endcan
        @can('Pre and Post Project Cost')
            @livewire('project.project-cost', ['project' => $project], key($project->id))
        @endcan
        {{-- Insert AccountTransactions Livewire component here --}}
        @can('Account Transactions View')
            @livewire('account-transactions', ['project_id' => $project->id], key($project->id))
        @endcan
    </div>

    @include('projects.tickets-tab')

    <div class="tab-pane fade" id="communication" role="tabpanel">
        <div class="card mt-1">
            <div class="card-body">
                <ul class="nav nav-tabs project-secondary-tabs" role="tablist">
                    {{-- <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#calls"
                            role="tab">Calls</a></li> --}}
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#emails"
                            role="tab">Emails</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#acceptance"
                            role="tab">Acceptance</a></li>
                </ul>
                <div class="tab-content">
                    {{-- <div class="tab-pane fade show active" id="calls" role="tabpanel">
                        @if (!in_array('Sales Person', auth()->user()->getRoleNames()->toArray()))
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card card-info mt-2">
                                        <div class="card-body">
                                            <div class="row clearfix">
                                                <div class="col-md-12">
                                                    <div class="card border-0 mb-4 no-bg">
                                                        <div
                                                            class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 m-4">Call
                                                                Logs
                                                            </h3>
                                                        </div>
                                                    </div>
                                                </div>
                                                <form id="call-log-form" method="post"
                                                    action="{{ route('projects.call.logs') }}">
                                                    @csrf
                                                    <input type="hidden" name="id"
                                                        value="{{ $project->id }}">
                                                    <input type="hidden" name="taskid"
                                                        value="{{ $task->id }}">

                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-12">
                                                            <div class="col-sm-12 mb-3">
                                                                <label for="call_no" class="form-label">Select
                                                                    Call</label><br />
                                                                <select class=" form-control select2"
                                                                    aria-label="Default Select call" id="call_no"
                                                                    name="call_no">
                                                                    <option value="">Select Call</option>
                                                                    @foreach ($calls as $call)
                                                                        <option value="{{ $call->id }}">
                                                                            {{ $call->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <div id="call_no_message"
                                                                    class="text-danger message mt-2"></div>
                                                            </div>
                                                            <div class="col-sm-12 mb-3">
                                                                <label for="notes_1"
                                                                    class="form-label">Comments:</label>
                                                                <input type="text" class="form-control"
                                                                    id="notes_1" name="notes_1"
                                                                    value="{{ old('notes_1') }}" />
                                                                <div id="notes_1_message"
                                                                    class="text-danger message mt-2"></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12 mb-3">
                                                            <button type="button"
                                                                class="btn btn-dark me-1 mt-1 w-sm-100"
                                                                id="saveCallLogs"><i
                                                                    class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6" id="call_script"
                                    style="font-size: 15px;text-align: justify;text-justify: inter-word;"></div>

                            </div>
                        @endif
                        <div class="card card-info mt-2">
                            <div class="card-body">
                                <div class="row clearfix">
                                    <div class="col-md-12">
                                        <div class="card border-0 mb-4 no-bg">
                                            <div
                                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between text-center border">
                                                <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Call Logs </h3>
                                            </div>
                                        </div>
                                    </div>
                                    @foreach ($departments as $department)
                                        <div class="col-md-12">
                                            <div class="card border-0 mb-4 no-bg">
                                                <div style="background-color: #ffffff;"
                                                    class="card-header py-3 px-0 d-sm-flex align-items-center   justify-content-between border-bottom border-top">
                                                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 px-2">
                                                        {{ $department->name }}
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                        @php
                                            $logs = $project->logs
                                                ->filter(function ($item) use ($department) {
                                                    return $item->department_id == $department->id;
                                                })
                                                ->values();
                                        @endphp

                                        <input type="hidden" id="{{ $department->id }}_log_count"
                                            value="{{ count($logs) }}" />

                                        @foreach ($logs as $key => $log)
                                            <div class="col-sm-12 mb-3">
                                                <label for="formFileMultipleoneone"
                                                    class="form-label fw-bold flex-fill mb-2 mt-sm-0">
                                                    {{ $log->call->name }} :</label>
                                                <textarea class="form-control" disabled rows="3">{{ $log->notes }}</textarea>
                                                <label
                                                    class="float-right mb-4 fst-italic">{{ (!empty($log->user) ? $log->user->name : '') . ' on ' . date('m/d/Y H:i:s', strtotime($log->created_at)) }}</label>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div> --}}
                    <div class="tab-pane fade show active" id="emails" role="tabpanel">
                        <div class="container">
                            @if (!in_array('Sales Person', auth()->user()->getRoleNames()->toArray()))
                                <form id="emailform" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                                    <input type="hidden" name="customer_id" value="{{ $project->customer_id }}">
                                    <input type="hidden" name="department_id"
                                        value="{{ $project->department_id }}" />
                                    <input type="hidden" name="customer_email"
                                        value="{{ $project->customer->email }}" />
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card card-info mt-2">
                                                <div class="card-body">
                                                    <div class="row clearfix">
                                                        <div class="col-md-12">
                                                            <div class="card border-0 mb-4 no-bg">
                                                                <div
                                                                    class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                                                                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0 m-4">
                                                                        Emails
                                                                    </h3>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12 mb-3">
                                                                    <label for="email_no" class="form-label">Select
                                                                        Email</label><br />
                                                                    <select class=" form-control select2"
                                                                        style="width: 100%;"
                                                                        aria-label="Default Select call"
                                                                        id="email_no" name="email_no">
                                                                        <option value="">Select Email
                                                                        </option>
                                                                        @foreach ($emailTypes as $emailType)
                                                                            <option value="{{ $emailType->id }}">
                                                                                {{ $emailType->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <div id="email_no_message"
                                                                        class="text-danger message mt-2"></div>
                                                                </div>
                                                                <div class="col-md-12 mb-1">
                                                                    <div class="mb-3">
                                                                        <label for="ccEmails" class="form-label">CC
                                                                            Emails</label>
                                                                        <div class="tags-input" id="ccEmails">
                                                                        </div>
                                                                        <input type="hidden" name="ccEmails"
                                                                            id="ccEmailsHidden">
                                                                        <div class="invalid-feedback" id="emailError">
                                                                            Please enter valid email addresses
                                                                            separated
                                                                            by commas.</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12 mb-1">
                                                                    <label for="exampleFormControlInput877"
                                                                        class="form-label">Subject</label>
                                                                    <input type="text" class="form-control"
                                                                        id="subject" name="subject"
                                                                        placeholder="Enter Subject" value="">
                                                                    <div id="name_message"
                                                                        class="text-danger message mt-2"></div>
                                                                </div>
                                                                <div class="mb-1">
                                                                    <label for="exampleFormControlInput877"
                                                                        class="form-label">Attachments</label>
                                                                    <input type="file" multiple
                                                                        class="form-control" id="image"
                                                                        name="images[]">
                                                                    <div id="name_message"
                                                                        class="text-danger message mt-2"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-12 mb-3">
                                                                <button id="btnEmail" type="submit"
                                                                    class="btn btn-dark me-1 mt-1 w-sm-100"><i
                                                                        class="icofont-arrow-left me-2 fs-6"></i>Send
                                                                    Email</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 main-container"
                                            style="font-size: 15px;text-align: justify;text-justify: inter-word;">
                                            <textarea id="editor" name="content" class="form-control" rows="5"></textarea>
                                        </div>
                                    </div>
                                </form>
                            @endif
                            <div
                                class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">

                                <a class="btn  text-white me-1 mt-1 w-sm-100" id="openemployee"></a>
                                <a class="btn btn-dark me-1 mt-1 w-sm-100" onclick="fetchEmails()"><i
                                        class="icofont-refresh me-2 fs-6"></i>Refresh</a>
                            </div>
                            <div id="emailDiv">
                                @include('projects.partial.show-emails', [
                                    'project' => $project,
                                    'departments' => $departments,
                                ])
                            </div>
                            {{-- <div class="row clearfix">
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="chat">
                                                        <div class="chat-history">
                                                            <ul class="m-b-0">
                                                                <li class="clearfix">
                                                                    <div class="message other-message float-right"> Hi Aiden, how
                                                                        are you?
                                                                        How is the project coming along? </div>
                                                                </li>
                                                                <li class="clearfix">
                                                                    <div class="message-data">
                                                                        <span class="message-data-time">10:12 AM, Today</span>
                                                                    </div>
                                                                    <div class="message my-message">Are we meeting today?</div>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="chat-message clearfix">
                                                            <div class="input-group mb-0">
                                                                <input type="text" class="form-control"
                                                                    placeholder="Enter text here...">
                                                                <div class="input-group-prepend"><span class="input-group-text"
                                                                        onclick="openEmailModal()"><i
                                                                            class="fa fa-send"></i></span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> --}}
                        </div>
                    </div>
                    <div class="tab-pane fade" id="acceptance" role="tabpanel">
                        <div class="card mt-1">
                            <div class="card-body">
                                @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Employee']))
                                    <div class="card shadow-sm border-0">
                                        <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            <h5 class="mb-0"><i class="icofont-file-document me-2"></i>Project Acceptance Submission</h5>
                                        </div>
                                        <div class="card-body p-4">
                                            <form id="accept-form" method="post" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="project_id" value="{{ $project->id }}" />
                                                <input type="hidden" name="sales_partner_id"
                                                    value="{{ $project->customer->sales_partner_id }}" />
                                                <input type="hidden" name="mode" value="post" />
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <label for="file" class="form-label fw-bold">
                                                            <i class="icofont-upload-alt me-1"></i>Upload Design <span class="text-danger">*</span>
                                                        </label>
                                                        <input class="form-control form-control-lg" type="file" id="file" name="file"
                                                            accept=".png,.jpg,.pdf">
                                                        <small class="text-muted">Accepted formats: PNG, JPG, PDF</small>
                                                        @error('file')
                                                            <div id="file_message" class="text-danger message mt-2">
                                                                {{ $message }}
                                                            </div>
                                                        @enderror
                                                        <div id="file_message" class="text-danger message mt-2"></div>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-4">
                                                        <label for="notes" class="form-label fw-bold">
                                                            <i class="icofont-ui-note me-1"></i>Notes <span class="text-muted">(Optional)</span>
                                                        </label>
                                                        <textarea class="form-control" id="notes" name="notes" rows="4" 
                                                            placeholder="Add any additional notes or comments..."></textarea>
                                                        <small class="text-muted">Optional field for additional information</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                                    <button type="submit" class="btn btn-lg px-5" 
                                                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;"
                                                        id="saveFiles">
                                                        <i class="icofont-paper-plane me-2"></i>Submit
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                                <div class="row" id="project-acceptance-view"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="history" role="tabpanel">

        <ul class="nav nav-tabs project-secondary-tabs" role="tablist">
            @can('Project Interaction')
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#interaction"
                        role="tab">Interaction</a></li>
            @endcan
            @can('Department Logs')
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#logs" role="tab">Department
                        Logs</a></li>
            @endcan
        </ul>
        <div class="tab-content">
            @can('Project Interaction')
                <div class="tab-pane fade show active" id="interaction" role="tabpanel">
                    <div class="card card-info mt-2">
                        <div class="card-body">
                            <div class="row clearfix">
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center bg-light text-center  justify-content-between border-bottom">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Interaction </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <table class="table">
                                                                <thead class="bg-light">
                                                                    <th>Date Time</th>
                                                                    <th>Description</th>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($interactions as $interaction)
                                                                        <tr>
                                                                            <td>{{ $interaction->created_at }}</td>
                                                                            <td>{{ $interaction->description ?? 'N/A' }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
            @can('Department Logs')
                <div class="tab-pane fade" id="logs" role="tabpanel">
                    <div class="card card-info mt-2">
                        <div class="card-body">
                            <div class="row clearfix">
                                <div class="col-md-12">
                                    <div class="card border-0 mb-4 no-bg">
                                        <div
                                            class="card-header py-3 px-0 d-sm-flex align-items-center bg-light text-center  justify-content-between border-bottom">
                                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Department Logs </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-7">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <table class="table">
                                                                <thead class="bg-light">
                                                                    <th>Department Name</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Exit Date</th>
                                                                    <th>Action By</th>
                                                                    <th>Total Duration</th>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($projectLogs as $log)
                                                                        @php
                                                                            if ($log->status == 'In-Progress') {
                                                                                $exitDate = date('Y-m-d H:i:s');
                                                                            } else {
                                                                                $exitDate = $log->updated_at;
                                                                            }
                                                                        @endphp
                                                                        <tr>
                                                                            <td>{{ $log->department->name }}</td>
                                                                            <td>{{ date('d M Y H:i:s', strtotime($log->created_at)) }}
                                                                            </td>
                                                                            <td>{{ $log->status != 'In-Progress' ? date('d M Y H:i:s', strtotime($log->updated_at)) : 'N/A' }}
                                                                            </td>
                                                                            <td>{{ $log->user->name ?? 'N/A' }}</td>
                                                                            <td>{{ max(1, \Carbon\Carbon::parse($log->created_at)->diffInDays(\Carbon\Carbon::parse($exitDate))) }}
                                                                                Days</td>
                                                                            {{-- max(1,\Carbon\Carbon::parse($log->created_at)->diffInDays(\Carbon\Carbon::parse($exitDate))) --}}
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <table class="table">
                                                                <thead class="bg-light">
                                                                    <th>Department Name</th>
                                                                    <th>Total Days</th>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($totalDaysOfDepartments as $departmentDays)
                                                                        <tr>
                                                                            <td>{{ $departmentDays['department'] ?? 'N/A' }}
                                                                            </td>
                                                                            <td>{{ $departmentDays['days'] ?? 'N/A' }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>

</div>
        </div>
        <aside class="project-details-rail">
            <div class="project-section-panel">
                <div class="files-header">
                    <i class="icofont-info-circle me-2"></i>Project Details
                </div>
                <div class="project-detail-list">
                    @foreach ($projectDetailItems as $detail)
                        <div class="project-detail-item">
                            <span class="project-detail-icon">
                                <i class="{{ $detail['icon'] }}"></i>
                            </span>
                            <div>
                                <div class="project-detail-label">{{ $detail['label'] }}</div>
                                <div class="project-detail-value">{{ $detail['value'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>


<div class="modal fade" id="createemail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="createprojectlLabel"> Send Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="emailform1" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="customer_id" value="{{ $project->customer_id }}">
                <input type="hidden" name="department_id" value="{{ $project->department_id }}" />
                <div class="modal-body">
                    <div class="deadline-form" id="empform">
                        <div class="row g-3 mb-3">
                            <div class="mb-1">
                                <label for="exampleFormControlInput877" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject1" name="subject"
                                    placeholder="Enter Subject" value="">
                                <div id="name_message" class="text-danger message mt-2"></div>
                            </div>
                            <div class="mb-1">
                                <label for="exampleFormControlInput877" class="form-label">Content</label>
                                <textarea type="text" class="form-control" id="content1" name="content" placeholder="Enter Subject"
                                    value=""></textarea>
                                <div id="name_message" class="text-danger message mt-2"></div>
                            </div>
                            <div class="mb-1">
                                <label for="exampleFormControlInput877" class="form-label">Attachments</label>
                                <input type="file" multiple class="form-control" id="image1" name="image[]">
                                <div id="name_message" class="text-danger message mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Send</button>
                    <button type="button" class="btn btn-danger text-white" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="assign-notes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content"
            style="border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header"
                style="background: var(--solen-gradient); border-radius: 20px 20px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold text-white" id="createprojectlLabel">
                    <i class="icofont-ui-note me-2"></i>Assign Notes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="assignNotes" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="customer_id" value="{{ $project->customer_id }}">
                <input type="hidden" name="department_id" value="{{ $project->department_id }}" />
                <div class="modal-body" style="padding: 2rem;">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <label for="assignnotes" class="form-label fw-bold" style="color: var(--solen-warm-text);">
                                <i class="icofont-pencil-alt-2 me-2"></i>Assign Notes
                            </label>
                            <div class="position-relative">
                                <textarea class="form-control" id="assignnotes" name="assignnotes" rows="4"
                                    style="border-radius: 12px; border: 2px solid var(--solen-primary-border-strong); padding: 1rem; transition: all 0.3s; background-color: #ffffff; color: var(--solen-warm-text);"
                                    placeholder="Enter your notes here..."></textarea>
                            </div>
                        </div>

                        <div class="col-sm-12 mb-3">
                            <div class="form-check" style="padding-left: 2rem;">
                                <input class="form-check-input" type="checkbox" id="followUpCheckbox"
                                    name="follow_up"
                                    style="width: 20px; height: 20px; cursor: pointer; border-radius: 6px;">
                                <label class="form-check-label fw-bold" for="followUpCheckbox"
                                    style="color: var(--solen-warm-text); margin-left: 0.5rem; cursor: pointer;">
                                    <i class="icofont-calendar me-2"></i>Set Follow-up Date
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12 mb-3" id="followUpDateContainer" style="display: none;">
                            <label for="followUpDate" class="form-label fw-bold" style="color: var(--solen-warm-text);">
                                <i class="icofont-clock-time me-2"></i>Follow-up Date
                            </label>
                            <input type="date" class="form-control" id="followUpDate" name="follow_up_date"
                                style="border-radius: 12px; border: 2px solid var(--solen-primary-border-strong); padding: 0.75rem; transition: all 0.3s; background-color: #ffffff; color: var(--solen-warm-text);">
                        </div>


                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn w-100" id="assignNotesSubmitBtn"
                                style="background: var(--solen-gradient); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 15px var(--solen-primary-shadow);">
                                <i class="icofont-save me-2"></i>Save Assignment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal  Delete Folder/ File-->
@if (auth()->user()->hasAnyRole(['Super Admin', 'Manager']) && (strcasecmp(optional($project->department)->name ?? '', 'Engineering') === 0 || !empty($designDetail)))
    @php
        $designCustomerName = trim(($project->customer->first_name ?? '') . ' ' . ($project->customer->last_name ?? ''));
        $designAutoDefaults = [
            'name' => $designCustomerName,
            'phone' => $project->customer->phone,
            'address' => $customerAddress,
            'ahj' => $project->ahj,
            'mod' => optional($project->customer->module)->name,
            'inv' => optional($project->customer->inverter)->name,
            'kw_rating' => $project->customer->module_value,
        ];
        $designExistingData = $designDetail ? [
            'design_detail_id' => $designDetail->id,
            'employee_id' => $designDetail->employee_id,
            'name' => $designDetail->name,
            'phone' => $designDetail->phone,
            'address' => $designDetail->address,
            'ahj' => $designDetail->ahj,
            'roof_area' => $designDetail->roof_area,
            'mod' => $designDetail->mod,
            'array_area' => $designDetail->array_area,
            'inv' => $designDetail->inv,
            'utility_meter' => $designDetail->utility_meter,
            'kw_rating' => $designDetail->kw_rating,
            'ac_cec' => $designDetail->ac_cec,
            'apn' => $designDetail->apn,
            'stories' => $designDetail->stories,
            'roof_type' => $designDetail->roof_type,
            'rafter' => $designDetail->rafter,
            'slope' => $designDetail->slope,
            'msp' => $designDetail->msp,
            'array_azi' => $designDetail->array_azi,
            'design_notes' => $designDetail->design_notes,
            'assign_notes' => $designDetail->assign_notes,
            'follow_up' => $designDetail->follow_up,
            'follow_up_date' => optional($designDetail->follow_up_date)->format('Y-m-d'),
        ] : null;
    @endphp
    <div class="modal fade" id="generateDesignDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content"
                style="border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div class="modal-header"
                    style="background: var(--solen-gradient); border-radius: 20px 20px 0 0; padding: 1.5rem; border: none;">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="icofont-paper me-2"></i><span id="designDetailsModalTitle">Generate Design Details</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="generateDesignDetailsForm" method="POST">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <input type="hidden" name="task_id" id="designDetailsTaskId" value="{{ $task->id }}">
                    <input type="hidden" name="design_detail_id" id="designDetailId" value="">
                    <div class="modal-body" style="padding: 2rem;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Name</label>
                                <input type="text" class="form-control design-auto-field" name="name" value="{{ $designCustomerName }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" class="form-control design-auto-field" name="phone" value="{{ $project->customer->phone }}" readonly>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Address</label>
                                <input type="text" class="form-control design-auto-field" name="address" value="{{ $customerAddress }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">AHJ</label>
                                <input type="text" class="form-control design-auto-field" name="ahj" value="{{ $project->ahj }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Roof Area</label>
                                <input type="text" class="form-control" name="roof_area">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">MOD</label>
                                <input type="text" class="form-control design-auto-field" name="mod"
                                    value="{{ optional($project->customer->module)->name }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Array Area</label>
                                <input type="text" class="form-control" name="array_area">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">INV</label>
                                <input type="text" class="form-control design-auto-field" name="inv"
                                    value="{{ optional($project->customer->inverter)->name }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">M. #/Utility</label>
                                <input type="text" class="form-control" name="utility_meter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">kW Rating</label>
                                <input type="text" class="form-control design-auto-field" name="kw_rating"
                                    value="{{ $project->customer->module_value }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">AC-CEC</label>
                                <input type="text" class="form-control" name="ac_cec">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">APN</label>
                                <input type="text" class="form-control" name="apn">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Stories</label>
                                <input type="text" class="form-control" name="stories">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Roof Type</label>
                                <input type="text" class="form-control" name="roof_type">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Rafter</label>
                                <input type="text" class="form-control" name="rafter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Slope</label>
                                <input type="text" class="form-control" name="slope">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">MSP</label>
                                <input type="text" class="form-control" name="msp">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Array AZI</label>
                                <input type="text" class="form-control" name="array_azi">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Notes</label>
                                <textarea class="form-control" name="design_notes" rows="3"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold" style="color: var(--solen-warm-text);">
                                    <i class="icofont-pencil-alt-2 me-2"></i>Assign Notes
                                </label>
                                <textarea class="form-control" id="designAssignNotes" name="assign_notes" rows="4"
                                    style="border-radius: 12px; border: 2px solid var(--solen-primary-border-strong); padding: 1rem; transition: all 0.3s; background-color: #ffffff; color: var(--solen-warm-text);"
                                    placeholder="Enter assignment notes here..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check" style="padding-left: 2rem;">
                                    <input class="form-check-input" type="checkbox" id="designFollowUpCheckbox"
                                        name="follow_up" value="1"
                                        style="width: 20px; height: 20px; cursor: pointer; border-radius: 6px;">
                                    <label class="form-check-label fw-bold" for="designFollowUpCheckbox"
                                        style="color: var(--solen-warm-text); margin-left: 0.5rem; cursor: pointer;">
                                        <i class="icofont-calendar me-2"></i>Set Follow-up Date
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12" id="designFollowUpDateContainer" style="display: none;">
                                <label class="form-label fw-bold" style="color: var(--solen-warm-text);">
                                    <i class="icofont-clock-time me-2"></i>Follow-up Date
                                </label>
                                <input type="date" class="form-control" id="designFollowUpDate" name="follow_up_date"
                                    style="border-radius: 12px; border: 2px solid var(--solen-primary-border-strong); padding: 0.75rem; transition: all 0.3s; background-color: #ffffff; color: var(--solen-warm-text);">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold" style="color: var(--solen-warm-text);">
                                    <i class="icofont-user-alt-3 me-2"></i>Assign Employee
                                </label>
                                <select class="form-select" id="designEmployeeId" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border: 0; padding: 0 2rem 2rem;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="generateDesignDetailsSubmitBtn">
                            <i class="icofont-save me-2"></i><span id="designDetailsSubmitText">Generate Design Details</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="modal fade" id="deletefile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <input type="hidden" id="deleteId" />
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="deleteprojectLabel"> Delete item Permanently?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger color-fff" onclick="deleteFileCall()">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dremoveadders" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="dremovetaskLabel"> Remove Adder?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-ui-rate-remove text-danger display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">This will be permanently remove from Adders</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger color-fff">Remove</button>
            </div>
        </div>
    </div>
</div>

<!-- PROJECT MOVE MODEL -->
<div class="modal fade" id="moveProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <input type="hidden" id="projectId" />
        <input type="hidden" id="taskId" />
        <input type="hidden" id="departmentId" />
        <input type="hidden" id="subDepartmentId" />
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="deleteprojectLabel"> Move Project ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-aim text-success display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">Are you sure you want to move the project ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success color-fff" id="moveProjectConfirmBtn" onclick="moveProject()">Move</button>
            </div>
        </div>
    </div>
</div>

<script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/42.0.0/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/42.0.0/"
        }
    }
</script>
<script type="module">
    import {
        ClassicEditor,
        Essentials,
        Paragraph,
        Bold,
        Italic,
        Font
    } from 'ckeditor5';

    const emailEditorElement = document.querySelector('#editor');

    if (emailEditorElement) {
        ClassicEditor
            .create(emailEditorElement, {
                plugins: [Essentials, Paragraph, Bold, Italic, Font],
                toolbar: [
                    'undo', 'redo', '|', 'bold', 'italic', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'
                ]
            })
            .then(editor => {
                window.editor = editor;
            })
            .catch(error => {
                // console.log(error);
            });
    }
</script>
<!-- A friendly reminder to run on a server, remove this during the integration. -->
{{-- <script>
    window.onload = function() {
        if (window.location.protocol === "file:") {
            alert("This sample requires an HTTP server. Please serve this file with a web server.");
        }
    };
</script> --}}
    @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Manager']))
        <div class="d-none">
            <select id="employee" name="employee">
                <option value="">Select Employee</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
            <select id="status" name="status">
                <option value="">Select Status</option>
                <option {{ $task->status == 'In-Progress' ? 'selected' : '' }} value="In-Progress">
                    In-Progress
                </option>
                <option {{ $task->status == 'Hold' ? 'selected' : '' }} value="Hold">
                    Hold
                </option>
                <option {{ $task->status == 'Cancelled' ? 'selected' : '' }} value="Cancelled">
                    Cancelled
                </option>
            </select>
        </div>
    @endif
    </div>
@endsection
@section('scripts')
<script>
    // Toggle follow-up date field visibility
    $('#followUpCheckbox').change(function() {
        if ($(this).is(':checked')) {
            $('#followUpDateContainer').show();
            $('#followUpDate').attr('required', true);
        } else {
            $('#followUpDateContainer').hide();
            $('#followUpDate').attr('required', false).val('');
        }
    });

    // Form validation
    $('#assignNotes').on('submit', function(e) {
        if ($('#followUpCheckbox').is(':checked') && !$('#followUpDate').val()) {
            e.preventDefault();
            alert('Please select a follow-up date.');
            return false;
        }
    });
    
    $(".additionalFields").css("display", "none");
    $("#back").prop("disabled", true)
    $("#forward").prop("disabled", true)
    $('input[type=radio][name=stage]').change(function() {
        if (this.value == "back") {
            $("#back").prop("disabled", false)
            $("#forward").prop("disabled", true)
        }
        if (this.value == "forward") {
            $("#forward").prop("disabled", false)
            $("#back").prop("disabled", true)
        }

    });
    $("#back").change(function() {
        getSubDepartments($(this).val())
    });

    function moveProjectModal(projectId, taskId, departmentId, subDepartmentId) {
        $('#projectId').val(projectId);
        $('#taskId').val(taskId);
        $('#departmentId').val(departmentId);
        $('#subDepartmentId').val(subDepartmentId);
        $("#moveProjectModal").modal("show");
    }

    function moveProject(projectId, taskId, departmentId, subDepartmentId) {
        const $moveButton = $("#moveProjectConfirmBtn");
        if ($moveButton.prop("disabled")) {
            return;
        }

        $moveButton.prop("disabled", true).text("Moving...");
        $("#moveProjectModal").modal("show");
        $.ajax({
            url: "{{ route('move.project') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                projectId: $('#projectId').val(),
                taskId: $('#taskId').val(),
                departmentId: $('#departmentId').val(),
                subDepartmentId: $('#subDepartmentId').val(),
            },
            success: function(response) {
                // console.log(response);

                if (response.status == 200) {
                    Swal.fire(
                        'Sent!',
                        response.message,
                        'success'
                    )
                    $("#moveProjectModal").modal("hide");
                    location.reload();
                } else if (response.status == 422) {
                    Swal.fire(
                        'Failed!',
                        response.error,
                        'error'
                    )
                    $moveButton.prop("disabled", false).text("Move");
                } else {
                    console.log(500);
                    $moveButton.prop("disabled", false).text("Move");
                }
            },
            error: function(error) {
                $moveButton.prop("disabled", false).text("Move");
                if (error.responseJSON && error.responseJSON.status == 422) {
                    $("#moveProjectModal").modal("hide");
                    Swal.fire(
                        'Failed!',
                        error.responseJSON.error,
                        'error'
                    )
                }
                console.log(error);
            }
        });
    }

    function openEmailModal() {
        $("#createemail").modal("show");
    }
    $("#emailform").submit(function(e) {
        e.preventDefault();

        const $emailButton = $("#btnEmail");
        $emailButton.attr('disabled', true);

        if (window.editor) {
            window.editor.updateSourceElement();
        }

        $.ajax({
            url: "{{ route('send.email') }}",
            type: 'POST',
            data: new FormData(this),
            dataType: 'JSON',
            contentType: false,
            cache: false,
            processData: false,
            success: function(response) {
                if (response.status == 200) {
                    $("#subject").val('');
                    if (window.editor) {
                        window.editor.setData('');
                    }
                    $("#email_no").val('').change();
                    $("#image").val('');
                    $("#ccEmailsHidden").val('');
                    $("#ccEmails .tag").remove();
                    Swal.fire(
                        'Sent!',
                        response.message,
                        'success'
                    )
                    fetchEmails();
                } else {
                    Swal.fire(
                        'Failed!',
                        response.message || 'Unable to send email.',
                        'error'
                    )
                }
                $emailButton.removeAttr("disabled");
            },
            error: function(error) {
                console.log(error);
                Swal.fire(
                    'Failed!',
                    error.responseJSON?.message || 'Unable to send email.',
                    'error'
                )
                $emailButton.removeAttr("disabled");
            }
        });
    });

    $("#forward").change(function() {
        let totalCount = $("#" + $("#forward").val() + "_length").val();
        $("#requiredfiles").html(totalCount + " File Required");
        getSubDepartments($(this).val())
        getDepartmentsFields($(this).val())
    });

    function getDepartmentsFields(id) {
        if (id != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.departments.fields') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                    projectId: "{{ $project->id }}",
                },
                success: function(response) {
                    $("#fieldDiv").html();
                    $("#fieldDiv").html(response);
                },
                error: function(error) {
                    console.log(error);
                }
            })
        }
    }

    $("#status").change(function() {
        $.ajax({
            method: "POST",
            url: "{{ route('projects.status') }}",
            data: {
                _token: "{{ csrf_token() }}",
                status: $(this).val(),
                project_id: "{{ $project->id }}",
            },
            success: function(response) {
                if (response.status == 200) {
                    Swal.fire({
                        title: 'Status Updated',
                        text: 'Project status has been updated successfully.',
                        icon: 'success',
                        confirmButtonColor: 'var(--solen-primary)'
                    });
                } else {
                    Swal.fire({
                        title: 'Update Failed',
                        text: 'Some error occurred while updating the status.',
                        icon: 'error',
                        confirmButtonColor: 'var(--solen-primary)'
                    });
                }
            },
            error: function(error) {
                console.log(error);
            }
        })
    });

    $(".project-status-option").click(function() {
        let selectedStatus = $(this).data("status");
        $("#status").val(selectedStatus).trigger("change");
        $(".project-status-option").removeClass("active");
        $(this).addClass("active");
    });

    let pendingAssignedEmployeeId = '';
    let pendingAssignedEmployeeName = '';
    let currentProjectTaskId = "{{ $task->id }}";
    let currentProjectSubDepartmentId = "{{ $task->sub_department_id }}";

    $(".project-employee-option").click(function() {
        let selectedEmployeeId = $(this).data("employee-id");
        pendingAssignedEmployeeId = selectedEmployeeId;
        pendingAssignedEmployeeName = $(this).data("employee-name");
        $("#employee").val(selectedEmployeeId).trigger("change");
    });

    $("#employee").change(function() {
        if ($(this).val() != "") {
            $("#assign-notes").modal("show");
        }
    });

    // Follow-up checkbox toggle
    $("#followUpCheckbox").change(function() {
        if ($(this).is(':checked')) {
            $("#followUpDateContainer").slideDown(300);
            $("#followUpDate").attr('required', true);
        } else {
            $("#followUpDateContainer").slideUp(300);
            $("#followUpDate").attr('required', false);
            $("#followUpDate").val('');
        }
    });

    $("#assignNotes").submit(function(e) {
        e.preventDefault();

        if ($("#followUpCheckbox").is(':checked') && !$("#followUpDate").val()) {
            alert('Please select a follow-up date.');
            return false;
        }

        const $assignButton = $("#assignNotesSubmitBtn");
        const assignButtonHtml = $assignButton.html();

        if ($assignButton.prop("disabled")) {
            return false;
        }

        $assignButton.prop("disabled", true).html('<i class="icofont-spinner-alt-4 me-2"></i>Saving...');

        var formData = {
            _token: "{{ csrf_token() }}",
            employee: $("#employee").val(),
            project_id: "{{ $project->id }}",
            task_id: currentProjectTaskId,
            sub_department_id: currentProjectSubDepartmentId,
            department_id: "{{ $project->department_id }}",
            notes: $("#assignnotes").val(),
            follow_up: $("#followUpCheckbox").is(':checked') ? 1 : 0,
            follow_up_date: $("#followUpDate").val()
        };

        $.ajax({
            method: "POST",
            url: "{{ route('projects.assign') }}",
            data: formData,
            success: function(response) {
                if (response.status == 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Employee assigned successfully',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    $("#assign-notes").modal("hide");
                    $("#assignnotes").val('');
                    $("#followUpCheckbox").prop('checked', false);
                    $("#followUpDate").val('');
                    $("#followUpDateContainer").hide();
                    if (pendingAssignedEmployeeName) {
                        $("#currentAssignedEmployeeName").text(pendingAssignedEmployeeName);
                        $(".project-employee-option").removeClass("active");
                        $('.project-employee-option[data-employee-id="' + pendingAssignedEmployeeId + '"]').addClass("active");
                    }
                    if (response.task_id) {
                        currentProjectTaskId = response.task_id;
                    }
                    if (response.sub_department_id) {
                        currentProjectSubDepartmentId = response.sub_department_id;
                    }
                    $("#employee").val('').change();
                    $assignButton.prop("disabled", false).html(assignButtonHtml);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Some error occurred!',
                    });
                    $assignButton.prop("disabled", false).html(assignButtonHtml);
                }
            },
            error: function(error) {
                console.log(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to assign employee',
                });
                $assignButton.prop("disabled", false).html(assignButtonHtml);
            }
        })
    })

    const designAutoDefaults = @json($designAutoDefaults ?? []);
    const existingDesignDetails = @json($designExistingData ?? null);

    function setDesignDetailsFormValues(values, mode) {
        const $form = $("#generateDesignDetailsForm");

        if ($form.length) {
            $form[0].reset();
        }
        $form.find("input[type='text'], input[type='date'], textarea").not("[name='_token']").val('');
        $("#designDetailId").val(values && values.design_detail_id ? values.design_detail_id : '');
        $("#designDetailsModalTitle").text(mode === "edit" ? "Edit Design Details" : "Generate Design Details");
        $("#designDetailsSubmitText").text(mode === "edit" ? "Update Design Details" : "Generate Design Details");

        Object.entries(designAutoDefaults).forEach(function([name, value]) {
            $form.find(`[name="${name}"]`).val(value || '');
        });

        if (values) {
            Object.entries(values).forEach(function([name, value]) {
                if (name === "follow_up") {
                    return;
                }
                $form.find(`[name="${name}"]`).val(value || '');
            });
        }

        $(".design-auto-field").prop("readonly", true);
        $("#designFollowUpCheckbox").prop("checked", !!(values && values.follow_up));
        if (values && values.follow_up) {
            $("#designFollowUpDateContainer").show();
            $("#designFollowUpDate").attr("required", true);
        } else {
            $("#designFollowUpDateContainer").hide();
            $("#designFollowUpDate").attr("required", false).val('');
        }
    }

    $(".open-design-details-modal").on("click", function() {
        const mode = $(this).data("mode");
        setDesignDetailsFormValues(mode === "edit" ? existingDesignDetails : null, mode);
    });

    $("#generateDesignDetailsModal").on("show.bs.modal", function(event) {
        const $trigger = $(event.relatedTarget);
        if (!$trigger.hasClass("open-design-details-modal")) {
            setDesignDetailsFormValues(null, "create");
        }
    });

    $("#designFollowUpCheckbox").change(function() {
        if ($(this).is(':checked')) {
            $("#designFollowUpDateContainer").slideDown(300);
            $("#designFollowUpDate").attr('required', true);
        } else {
            $("#designFollowUpDateContainer").slideUp(300);
            $("#designFollowUpDate").attr('required', false).val('');
        }
    });

    $("#generateDesignDetailsForm").submit(function(e) {
        e.preventDefault();

        if (!$("#designEmployeeId").val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Employee Required',
                text: 'Please select an employee before generating design details.',
            });
            return false;
        }

        if ($("#designFollowUpCheckbox").is(':checked') && !$("#designFollowUpDate").val()) {
            Swal.fire({
                icon: 'warning',
                title: 'Follow-up Date Required',
                text: 'Please select a follow-up date.',
            });
            return false;
        }

        const $designButton = $("#generateDesignDetailsSubmitBtn");
        const designButtonHtml = $designButton.html();

        if ($designButton.prop("disabled")) {
            return false;
        }

        $("#designDetailsTaskId").val(currentProjectTaskId);
        $designButton.prop("disabled", true).html('<i class="icofont-spinner-alt-4 me-2"></i>Generating...');

        $.ajax({
            method: "POST",
            url: "{{ route('projects.design-details') }}",
            data: $(this).serialize(),
            success: function(response) {
                if (response.status == 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    $("#generateDesignDetailsModal").modal("hide");
                    $("#designAssignNotes").val('');
                    $("#designFollowUpCheckbox").prop('checked', false);
                    $("#designFollowUpDate").val('');
                    $("#designFollowUpDateContainer").hide();
                    if (response.task_id) {
                        currentProjectTaskId = response.task_id;
                    }
                    if (response.sub_department_id) {
                        currentProjectSubDepartmentId = response.sub_department_id;
                    }
                    const selectedEmployeeId = $("#designEmployeeId").val();
                    const selectedEmployeeName = $("#designEmployeeId option:selected").text();
                    if (selectedEmployeeId) {
                        $("#currentAssignedEmployeeName").text(selectedEmployeeName);
                        $(".project-employee-option").removeClass("active");
                        $('.project-employee-option[data-employee-id="' + selectedEmployeeId + '"]').addClass("active");
                    }
                    setTimeout(function() {
                        location.reload();
                    }, 700);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Some error occurred!',
                    });
                }
            },
            error: function(error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.responseJSON?.message || 'Failed to generate design details',
                });
            },
            complete: function() {
                $designButton.prop("disabled", false).html(designButtonHtml);
            }
        });
    });

    function getSubDepartments(id) {
        if (id != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.sub.departments') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                },
                dataType: 'json',
                success: function(response) {
                    $('#sub_department').empty();
                    $('#sub_department').append($('<option value="">Select Sub Department</soption>'));
                    $.each(response.subdepartments, function(i, value) {
                        $('#sub_department').append($('<option  value="' + value.id + '">' + value
                            .name + '</option>'));
                    });
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    }

    $("#saveProject").click(function(e) {
        $("#file_message").html('');
        let stage = $('input[name="stage"]:checked').val();
        let totalCount = $("#" + $("#forward").val() + "_length").val();
        let alreadyUploaded = "{{ count($filesCount) }}";
        let currentproject = "{{ $project->department->id }}";
        let project = $("#forward").val();
        let logs = $("#" + ($("#forward").val() - 1) + "_log_count").val();
        $("#call_no_1_message").html("");
        $("#call_no_2_message").html("");
        $("#notes_1_message").html("");
        $("#notes_2_message").html("");

        if (project != 1 && project != 8 && logs == 0 && stage == "forward") {
            if (stage == "forward" && (currentproject != $("#forward").val())) {
                $("#form").submit();
            } else {
                $("#form").submit();
            }
        } else {
            if (stage == "forward" && (currentproject != $("#forward").val())) {
                $("#form").submit();
            } else {
                $("#form").submit();
            }
        }

    });

    $("#saveCallLogs").click(function() {
        $("#call_no_message").html("");
        $("#call_no_1_message").html("");
        $("#notes_1_message").html("");
        if ($("#call_no").val() == "") {
            $("#call_no").focus();
            $("#call_no_message").html("Please Select Call No");
        } else if ($("#call_no_1").val() == "") {
            $("#call_no_1").focus();
            $("#call_no_1_message").html("Please select the desired option");
        } else if ($("#notes_1").val() == "") {
            $("#notes_1").focus();
            $("#notes_1_message").html("Please enter results of the call");
        } else {
            $("#call-log-form").submit();
        }
    })

    $("#saveFiles").click(function() {
        $("#files-form").submit();
    })

    $("#adders").change(function() {
        if ($(this).val() != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.adders') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    // subadder: $(this).val(),
                    adder: $(this).val(),
                },
                dataType: 'json',
                success: function(response) {
                    $("#uom").val(response.adders.adder_unit_id).change();
                    $("#amount").val(response.adders.price);
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    })

    $("#sub_type").change(function() {
        if ($(this).val() != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.adders') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    subadder: $(this).val(),
                    adder: $("#adders").val(),
                },
                dataType: 'json',
                success: function(response) {
                    $("#uom").val(response.adders.adder_unit_id).change();
                    $("#amount").val(response.adders.price);
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    })

    $("#btnAdder").click(function() {
        let rowLength = $('#adderTable tbody').find('tr').length;
        let adders_id = $("#adders").val();
        let subadder_id = $("#sub_type").val();
        let unit_id = $("#uom").val();
        let adders_name = $.trim($("#adders option:selected").text());
        let subadder_name = $.trim($("#sub_type option:selected").text());
        let unit_name = $.trim($("#uom option:selected").text());
        let amount = $("#amount").val();
        if (unit_id == 3) {
            let moduleQty = $('#module_qty').val();
            let panelQty = $('#panel_qty').val();
            amount = amount * moduleQty; //* panelQty;
        }
        if (unit_id == 5) {
            let panelQty = $('#panel_qty').val();
            amount = amount * panelQty; //* panelQty;
        }
        let result = checkExistence(adders_id, subadder_id, unit_id);
        if (result == false) {
            addAdderToDB("{{ $project->customer->id }}", adders_id, unit_id, amount);
            emptyControls();
        } else {
            alert("already added")
        }
    });


    function deleteItem(id, adderId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: "{{ route('adders.remove') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: adderId,
                        customer_id: "{{ $project->customer->id }}",
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            Swal.fire(
                                'Deleted!',
                                'Adder has been deleted.',
                                'success'
                            )
                            // $("#row" + id).remove();
                            populateAddersTable(response.adders);
                        }
                    },
                    error: function(error) {
                        Swal.fire(
                            'Error!',
                            'Some error occurred :)',
                            'error'
                        )
                    }
                });
            }
            if (result.dismiss) {
                Swal.fire(
                    'Cancelled!',
                    'Adder is safe :)',
                    'error'
                )
            }
        })
    }

    function addAdderToDB(customerId, adderTypeId, adderUnitId, amount) {
        $.ajax({
            url: "{{ route('adders.store') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                customer_id: customerId,
                adder_type_id: adderTypeId,
                adder_unit_id: adderUnitId,
                amount: amount,
            },
            dataType: 'json',
            success: function(response) {
                if (response.status == 200) {
                    Swal.fire(
                        'Added!',
                        'Adders has been added.',
                        'success'
                    )
                    populateAddersTable(response.adders)
                }
            },
            error: function(error) {
                Swal.fire(
                    'Error!',
                    'Some error occurred :)',
                    'error'
                )
            }
        });
    }

    function populateAddersTable(adders) {
        $("#adderTable > tbody").empty();
        $.each(adders, function(index, item) {
            let newRow = "<tr id='row" + (index + 1) + "'>" +
                '<input type="hidden" value="' + item.adder_type_id + '" name="adders[]" />' +
                '<input type="hidden" value="' + item.adder_sub_type_id + '" name="subadders[]" />' +
                '<input type="hidden" value="' + item.adder_unit_id + '" name="uom[]" />' +
                '<input type="hidden" value="' + item.amount + '" name="amount[]" />' +


                "<td>" + (index + 1) + "</td>" +
                "<td>" + item.type.name + "</td>" +
                "<td>" + item.unit.name + "</td>" +
                "<td>" + item.amount + "</td>" +

                "<td colspan='4'><i style='cursor: pointer;' class='icofont-trash text-danger' onClick=deleteItem(" +
                (index + 1) + "," + item.id + ")>&nbsp;&nbsp;Delete</i></td>" +
                "</tr>";

            $("#adderTable > tbody").append(newRow);
        });

    }

    function editItem(id, addersId, subAdderId, uomId, amount) {
        $("#adders").val(addersId).change();
        $("#sub_type").val(subAdderId).change()
        $("#uom").val(uomId).change();
        $("#amount").val(amount).change();

    }

    function checkExistence(firstval, secondval, thirdval) {
        let result = false;
        $("#adderTable tbody tr").each(function(index) {
            let first = $(this).children().eq(0).val();
            let second = $(this).children().eq(1).val();
            let third = $(this).children().eq(2).val();
            if (firstval == first && secondval == second && thirdval == third) {
                result = true;
            } else {
                result = false;
            }
        });
        return result;
    }

    function calculateAddersAmount() {
        let adders_amount = 0;
        $("#adderTable tbody tr").each(function(index) {
            // console.log($(this).children().eq(8).text() * 1);
            adders_amount += $(this).children().eq(8).text() * 1;
        });
        $("#adders_amount").val(adders_amount);
        calculateCommission();
    }

    function emptyControls() {
        $("#adders").val('').change();
        $("#sub_type").val('').change();
        $("#uom").val('').change();
        $("#amount").val('');
    }

    function calculateCommission() {
        let contractAmount = parseFloat($("#contract_amount").val());
        let dealerFeeAmount = parseFloat($("#dealer_fee_amount").val());
        let redlineFee = parseFloat($("#redline_costs").val());
        let adders = parseFloat($("#adders_amount").val());
        let commission = contractAmount - dealerFeeAmount - redlineFee - adders;
        $("#commission").val(commission.toFixed(2));
    }

    $("#hoa").change(function() {
        if ($(this).val() == "yes") {
            $("#hoa_select").css("display", "block")
        } else {
            $("#hoa_select").css("display", "none")
        }
    })
    // $("#mpu_required").change(function() {
    //     if ($(this).val() == "yes") {
    //         $(".mpuselect").css("display", "block")
    //     } else {
    //         $(".mpuselect").css("display", "none")
    //     }
    // })

    $("#call_no").change(function() {
        // alert($(this).val());
        $.ajax({
            url: "{{ route('projects.call.script') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                call: $(this).val(),
                department: "{{ $project->department_id }}",
                project: "{{ $project->id }}",
            },
            success: function(response) {
                // console.log(response);
                $("#call_script").empty();
                $("#call_script").html(response);
                let customer_name =
                    "{{ $project->customer->first_name . ' ' . $project->customer->last_name }}";
                let salespartner = "{{ $project->customer->salespartner->name }}";
                $('#call_script').html(function(i, old) {
                    return old
                        .replace("user_name", "<b>{{ auth()->user()->name }}</b>")
                        .replace("company_name", "<b>Solen Energy Co.</b>")
                        .replace("customer_name", "<b>" + customer_name + "</b>")
                        .replace("customer_name_1", "<b>" + customer_name + "</b>")
                        .replace("salespartner_name", "<b>" + salespartner + "</b>")
                        .replace("salespartner_name_1", "<b>" + salespartner + "</b>")
                    // let text = $(this).html();
                    // let customer_name = "{{ $project->customer->first_name . ' ' . $project->customer->last_name }}"
                    // console.log(customer_name);
                    // $(this).html(text.replace("user_name", "<b>{{ auth()->user()->name }}</b>"));
                    // $(this).html(text.replace("company_name", "<b>Solen Energy Co.</b>"));
                    // $(this).html(text.replace("customer_name", "<b>"+customer_name+"</b>"));
                });

            },
            error: function(error) {
                Swal.fire(
                    'Error!',
                    'Some error occurred :)',
                    'error'
                )
            }
        });
    })

    $("#email_no").change(function() {
        if (!$(this).val()) {
            return;
        }

        $.ajax({
            url: "{{ route('projects.email.script') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                emailType: $(this).val(),
                department: "{{ $project->department_id }}",
                project: "{{ $project->id }}",
            },
            success: function(response) {
                if (!window.editor) {
                    return;
                }

                let customer_name =
                    "{{ $project->customer->first_name . ' ' . $project->customer->last_name }}";
                let salespartner = "{{ $project->customer->salespartner->name }}";
                let code = "{{ $project->code }}";
                let emailContent = response;

                emailContent = emailContent.split("customer_name_1").join("<b>" + customer_name + "</b>");
                emailContent = emailContent.split("customer_name").join("<b>" + customer_name + "</b>");
                emailContent = emailContent.split("salespartner_name_1").join("<b>" + salespartner + "</b>");
                emailContent = emailContent.split("salespartner_name").join("<b>" + salespartner + "</b>");
                emailContent = emailContent.split("project_code").join("<b>" + code + "</b>");

                window.editor.setData(emailContent);
            },
            error: function(error) {
                Swal.fire(
                    'Error!',
                    'Some error occurred :)',
                    'error'
                )
            }
        });
    })
    // fetchEmails()

    function fetchEmails() {
        $("#emailDiv").empty();
        let loadingDiv =
            '<div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">' +
            '<h3 class=" fw-bold flex-fill mb-0 mt-sm-0 text-center fs-10 text-uppercase">' +
            'Fetching Emails. Please Wait.........' +
            '</h3></div>';
        $("#emailDiv").append(loadingDiv);
        $.ajax({
            method: "POST",
            url: "{{ route('fetch.emails') }}",
            data: {
                _token: "{{ csrf_token() }}",
                project_id: "{{ $project->id }}",
                customer_id: "{{ $project->customer_id }}",
            },
            success: function(response) {

                if (response.status == 200) {
                    showEmails("{{ $project->id }}");
                }
            },
            error: function(error) {
                console.log(error);
            }
        })
    }

    function showEmails(projectId) {
        $.ajax({
            method: "POST",
            url: "{{ route('show.emails') }}",
            data: {
                _token: "{{ csrf_token() }}",
                project_id: projectId,
            },
            success: function(response) {
                $("#emailDiv").empty();
                $("#emailDiv").html(response);
            },
            error: function(error) {
                console.log(error);
            }
        })
    }

    function deleteFile(id) {
        $("#deleteId").val(id);
        $("#deletefile").modal("show")
    }

    function deleteFileCall() {
        // alert();
        $.ajax({
            method: "POST",
            url: "{{ route('delete.file') }}",
            data: {
                _token: "{{ csrf_token() }}",
                id: $("#deleteId").val()
            },
            success: function(response) {
                if (response.status == 200) {
                    location.reload();
                }
            }
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        const tagsInput = document.querySelector('.tags-input');
        const input = document.createElement('input');
        const hiddenInput = document.getElementById('ccEmailsHidden');
        const emailError = document.getElementById('emailError');

        if (!tagsInput || !hiddenInput || !emailError) {
            return;
        }

        tagsInput.appendChild(input);

        function createTag(email) {
            const tag = document.createElement('span');
            tag.classList.add('tag');
            tag.textContent = email;

            const closeIcon = document.createElement('i');
            closeIcon.classList.add('bi', 'bi-x');
            closeIcon.addEventListener('click', () => {
                tagsInput.removeChild(tag);
                updateHiddenInput();
            });

            tag.appendChild(closeIcon);
            tagsInput.insertBefore(tag, input);

            updateHiddenInput();
        }

        function updateHiddenInput() {
            const tags = document.querySelectorAll('.tag');
            const emails = Array.from(tags).map(tag => tag.textContent.trim());
            hiddenInput.value = emails.join(',');
        }

        function validateEmails(emails) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emails.every(email => emailPattern.test(email));
        }

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const email = input.value.trim();
                if (email && validateEmails([email])) {
                    createTag(email);
                    input.value = '';
                    emailError.style.display = 'none';
                } else {
                    emailError.style.display = 'block';
                }
            }
        });

        tagsInput.addEventListener('click', () => {
            input.focus();
        });

    });
    $('#accept-form').on('submit', function(e) {
        e.preventDefault();

        // Create a FormData object
        var formData = new FormData(this); // Automatically collects all form inputs, including files

        // Send the form data using jQuery AJAX
        $.ajax({
            url: "{{ route('project.accept.file') }}", // The URL where the request is sent
            type: 'POST',
            data: formData,
            contentType: false, // Tell jQuery not to set contentType
            processData: false, // Tell jQuery not to process the data (i.e., don't try to convert it into a string)
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                    'content') // Include the CSRF token from meta tag
            },
            success: function(response) {
                $("#project-acceptance-view").empty();
                $("#project-acceptance-view").html(response);
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || xhr.responseText || 'Unable to submit project acceptance.';
                $("#file_message").html(message);
                console.error('Error uploading file: ' + xhr.responseText);
            }
        });
    });
    getAcceptanceForm();

    function getAcceptanceForm() {
        $.ajax({
            url: "{{ route('project.accept.file') }}", // The URL where the request is sent
            type: 'POST',
            data: {
                "_token": "{{ csrf_token() }}",
                project_id: '{{ $project->id }}',
                mode: 'view'
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                    'content') // Include the CSRF token from meta tag
            },
            success: function(response) {
                $("#project-acceptance-view").empty();
                $("#project-acceptance-view").html(response);
                // if (response.success) {
                //     console.log('File uploaded successfully.');
                // } else {
                //     console.error('Error: ' + response.message);
                // }
            },
            error: function(xhr) {
                console.error('Error uploading file: ' + xhr.responseText);
            }
        });
    }

    function acceptanceAction(mode, id, projectId) {
        let reason = $("#reason").val();
        $("#reason_message").html('');

        if (mode == 2 && reason == "") {
            $("#reason_message").html("Please Enter Reason");
        } else {
            $.ajax({
                url: "{{ route('action.project.acceptance') }}", // The URL where the request is sent
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    id: id,
                    projectId: projectId,
                    mode: mode,
                    reason: reason,
                },
                success: function(response) {
                    if (response.status == 200) {
                        getAcceptanceForm();
                    }
                    if (response.status == 500) {
                        alert(response.message)
                    }
                },
                error: function(xhr) {
                    console.error('Error uploading file: ' + xhr.responseText);
                }
            });
        }
    }

    function toggleAddersLock(status) {
        $.ajax({
            url: "{{ route('toggle.adders.lock') }}",
            type: 'POST',
            data: {
                "_token": "{{ csrf_token() }}",
                project_id: {{ $project->id }},
                status: status
            },
            success: function(response) {
                if (response.status == 200) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                console.error('Error: ' + xhr.responseText);
            }
        });
    }

    (function() {
        'use strict';

        $(document).ready(function() {
            // Restore active tab on page load
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                $('.nav-link[href="' + activeTab + '"]').tab('show');
                localStorage.removeItem('activeTab');
            }

            // Ticket form submission
            $('#ticketForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const form = this;
                const assignedTo = $('#assigned_to').val();
                const notes = $('#notes').val().trim();

                $(form).find('.is-invalid').removeClass('is-invalid');

                let isValid = true;

                if (!assignedTo) {
                    $('#assigned_to').addClass('is-invalid');
                    isValid = false;
                }

                if (!notes) {
                    $('#notes').addClass('is-invalid');
                    isValid = false;
                }

                if (!isValid) {
                    return false;
                }

                // Save active tab before reload
                localStorage.setItem('activeTab', '#tickets');

                $('#premiumLoader').css('display', 'flex');

                var formData = new FormData(form);

                $.ajax({
                    url: $(form).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $(form)[0].reset();
                        $('#ticketFilesList').html('');
                        setTimeout(function() {
                            $('#premiumLoader').hide();
                            location.reload();
                        }, 500);
                    },
                    error: function(error) {
                        $('#premiumLoader').hide();
                        localStorage.removeItem('activeTab');
                        alert('Error creating ticket. Please try again.');
                    }
                });

                return false;
            });

            $('#assigned_to, #notes').on('change input', function() {
                $(this).removeClass('is-invalid');
            });

            $('#updateTicketForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                // Save active tab before reload
                localStorage.setItem('activeTab', '#tickets');

                $('#premiumLoader').css('display', 'flex');
                $('.premium-loader-text').text('Updating Ticket...');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        setTimeout(function() {
                            $('#updateTicketModal').modal('hide');
                            $('#premiumLoader').hide();
                            $('.premium-loader-text').text(
                            'Creating Ticket...');
                            location.reload();
                        }, 500);
                    },
                    error: function(error) {
                        $('#premiumLoader').hide();
                        $('.premium-loader-text').text('Creating Ticket...');
                        localStorage.removeItem('activeTab');
                        alert('Error updating ticket. Please try again.');
                    }
                });

                return false;
            });
        });

        window.updateTicket = function(ticketId) {
            $('#updateTicketModal').modal('show');
            $('#updateTicketForm').attr('action', '/service-tickets/' + ticketId);
        };

        window.viewTicketDetails = function(ticketId) {
            $('#ticketModal').modal('show');
            $('#ticketDetailsContent').html(
                '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                );

            $.ajax({
                url: '/service-tickets/' + ticketId + '/details',
                method: 'GET',
                success: function(response) {
                    $('#ticketDetailsContent').html(response);
                },
                error: function(error) {
                    $('#ticketDetailsContent').html(
                        '<div class="alert alert-danger">Error loading ticket details</div>');
                }
            });
        };
    })();
</script>
@endsection
