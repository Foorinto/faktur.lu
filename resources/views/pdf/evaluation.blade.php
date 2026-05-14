<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $evaluation->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #334155;
        }
        .container { padding: 30px 35px; }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #7c3aed;
        }
        .company-name {
            font-size: 10pt;
            color: #64748b;
            margin-bottom: 4px;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #7c3aed;
        }
        .meta {
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px 20px;
        }
        .meta-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }
        .meta-row:last-child { margin-bottom: 0; }
        .meta-label {
            display: table-cell;
            width: 35%;
            font-weight: bold;
            font-size: 9pt;
            color: #64748b;
            padding: 3px 0;
        }
        .meta-value {
            display: table-cell;
            width: 65%;
            font-size: 10pt;
            color: #1e293b;
            padding: 3px 0;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .content {
            font-size: 10pt;
            line-height: 1.6;
            color: #334155;
        }
        .content h1, .content h2, .content h3 {
            color: #1e293b;
            margin-top: 12px;
            margin-bottom: 6px;
        }
        .content h1 { font-size: 14pt; }
        .content h2 { font-size: 12pt; }
        .content h3 { font-size: 11pt; }
        .content p { margin-bottom: 8px; }
        .content ul, .content ol {
            margin-bottom: 8px;
            padding-left: 20px;
        }
        .content li { margin-bottom: 3px; }
        .content blockquote {
            border-left: 3px solid #7c3aed;
            padding-left: 12px;
            margin: 8px 0;
            color: #64748b;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 35px;
            right: 35px;
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        @if($companyName)
            <div class="company-name">{{ $companyName }}</div>
        @endif
        <div class="title">{{ $evaluation->title }}</div>
    </div>

    <div class="meta">
        <div class="meta-row">
            <div class="meta-label">{{ __('app.pdf_employee') }}</div>
            <div class="meta-value">{{ $employee->full_name }}</div>
        </div>
        @if($employee->job_title)
            <div class="meta-row">
                <div class="meta-label">{{ __('app.pdf_job_title') }}</div>
                <div class="meta-value">{{ $employee->job_title }}</div>
            </div>
        @endif
        @if($employee->department)
            <div class="meta-row">
                <div class="meta-label">{{ __('app.pdf_department') }}</div>
                <div class="meta-value">{{ $employee->department->name }}</div>
            </div>
        @endif
        @if($evaluation->evaluator)
            <div class="meta-row">
                <div class="meta-label">{{ __('app.pdf_evaluator') }}</div>
                <div class="meta-value">{{ $evaluation->evaluator->first_name }} {{ $evaluation->evaluator->last_name }}</div>
            </div>
        @endif
        <div class="meta-row">
            <div class="meta-label">{{ __('app.pdf_date') }}</div>
            <div class="meta-value">{{ $evaluation->date->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="section-title">{{ __('app.pdf_evaluation_content') }}</div>
    <div class="content">
        {!! $evaluation->description !!}
    </div>
</div>

<div class="footer">
    {{ __('app.pdf_generated_at_simple', ['date' => $generatedAt]) }}
</div>
</body>
</html>
