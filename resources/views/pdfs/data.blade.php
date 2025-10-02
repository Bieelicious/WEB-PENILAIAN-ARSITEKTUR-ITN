<!DOCTYPE html>
<html>
<head>
    <title>Data Mahasiswa</title>
    <meta charset="UTF-8">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            padding: 0;
            margin: 0;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 4px;
            text-align: left;
        }

        th {
            text-align: center;
            background-color: #f78c00ff;
            color: black;
        }

        h1 {
            text-align: center;
            padding-bottom: 30px;
        }
    </style>
</head>
<body>
<h1>Data Nilai Mahasiswa</h1>
<table border="1" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th>Nama Mahasiswa</th>
            <th>NIM</th>
            <th>Judul Proposal</th>
            <th>Design Theme</th>
            <th>Group</th>
            <th>Assessment Stage</th>
            <th>Dosen</th>
            <th>Kriteria</th>
            <th>Nilai</th>
            <th>Catatan Item</th>
            <th>Catatan Umum</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $assessment)
            @foreach($assessment->items as $item)
                <tr>
                    <td>{{ optional($assessment->student)->name ?? '-' }}</td>
                    <td>{{ optional($assessment->student)->nim ?? '-' }}</td>
                    <td>{{ optional($assessment->student)->title_of_the_final_project_proposal ?? '-' }}</td>
                    <td>{{ optional($assessment->student)->design_theme ?? '-' }}</td>
                    <td>{{ optional($assessment->student->group)->name ?? '-' }}</td>
                    <td>{{ $assessment->assessment_stage ?? '-' }}</td>
                    <td>{{ optional($assessment->user)->name ?? '-' }}</td>
                    <td>{{ $item->label ?? '-' }}</td>
                    <td>{{ $item->score ?? '-' }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td>{{ $assessment->notes ?? '-' }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
</body>
</html>
