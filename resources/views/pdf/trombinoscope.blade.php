<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Trombinoscope</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.4;
            color: #334155;
        }
        .container { padding: 20px 25px; }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #7c3aed;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 2px;
        }
        .subtitle {
            font-size: 9pt;
            color: #64748b;
        }
        .department-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 4px;
            margin-bottom: 12px;
            margin-top: 20px;
            page-break-after: avoid;
        }
        .department-title:first-child {
            margin-top: 0;
        }
        .row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .card {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
            padding: 5px;
        }
        .card-inner {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 8px;
            text-align: center;
            page-break-inside: avoid;
        }
        .photo {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            margin: 0 auto 6px;
        }
        .initials {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background-color: #ede9fe;
            color: #7c3aed;
            font-size: 16pt;
            font-weight: bold;
            line-height: 55px;
            text-align: center;
            margin: 0 auto 6px;
        }
        .name {
            font-weight: bold;
            font-size: 9pt;
            color: #1e293b;
            margin-bottom: 1px;
        }
        .job-title {
            font-size: 7.5pt;
            color: #7c3aed;
            margin-bottom: 4px;
        }
        .detail {
            font-size: 7pt;
            color: #64748b;
            margin-bottom: 1px;
        }
        .footer {
            position: fixed;
            bottom: 15px;
            left: 25px;
            right: 25px;
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
        <div class="title">Trombinoscope</div>
    </div>

    @foreach($grouped as $departmentName => $deptEmployees)
        <div class="department-title">{{ $departmentName }}</div>
        @foreach($deptEmployees->chunk(3) as $row)
            <div class="row">
                @foreach($row as $employee)
                    <div class="card">
                        <div class="card-inner">
                            @if($employee->photo_data_uri)
                                <img src="{{ $employee->photo_data_uri }}" class="photo" alt="">
                            @else
                                <div class="initials">{{ mb_substr($employee->first_name, 0, 1) }}{{ mb_substr($employee->last_name, 0, 1) }}</div>
                            @endif
                            <div class="name">{{ $employee->full_name }}</div>
                            @if($employee->job_title)
                                <div class="job-title">{{ $employee->job_title }}</div>
                            @endif
                            @if($employee->phone)
                                <div class="detail">{{ $employee->phone }}</div>
                            @endif
                            @if($employee->email_pro)
                                <div class="detail">{{ $employee->email_pro }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
                @for($i = $row->count(); $i < 3; $i++)
                    <div class="card"></div>
                @endfor
            </div>
        @endforeach
    @endforeach
</div>
</body>
</html>
