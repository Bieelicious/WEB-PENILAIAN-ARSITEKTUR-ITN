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
            padding: 2px;
            text-align: left;
        }

        th {
            text-align: center;
            background-color: #4CAF50;
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
<table>
    <thead>
    <tr>
        <th>No</th>
        <th>Nama</th>
        <th>NIM</th>
        <th>Judul Proposal Tugas Akhir</th>
        <th>Tema Rancangan</th>
        <th>Kelompok</th>
        <th>Tahap Penilaian</th>
        <th>Dosen Penilai</th>
        <th>Nilai</th>
        <th>Catatan</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($records as $index => $record)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $record->student->name }}</td>
            <td>{{ $record->student->nim }}</td>
            <td>{{ $record->student->title_of_the_final_project_proposal }}</td>
            <td>{{ $record->student->design_theme }}</td>
            <td>{{ $record->student->group->name }}</td>
            <td>{{ $record->assessment_stage }}</td>
            <td>{{ $record->user->name }}</td>
            <td>{{ is_array($record->assessment) ? implode(', ', $record->assessment) : $record->assessment }}</td>
            <td>{{ $record->notes}}</td>


        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
