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
<table border="1" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th>Name</th>
            <th>NIM</th>
            <th>Title</th>
            <th>Design Theme</th>
            <th>Group</th>
            <th>Assessment Stage</th>
            <th>Lecturer</th>
            <th>Score</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $assessment)
            <tr>
                <td>{{ optional($assessment->student)->name ?? '-' }}</td>
                <td>{{ optional($assessment->student)->nim ?? '-' }}</td>
                <td>{{ optional($assessment->student)->title_of_the_final_project_proposal ?? '-' }}</td>
                <td>{{ optional($assessment->student)->design_theme ?? '-' }}</td>
                <td>{{ optional($assessment->student->group)->name ?? '-' }}</td>
                <td>{{ $assessment->assessment_stage ?? '-' }}</td>
                <td>{{ optional($assessment->user)->name ?? '-' }}</td>
                <td>
                    @php
                        $total = 0;
                        if(is_array($assessment->assessment)) {
                            foreach($assessment->assessment as $a) {
                                $total += $a['score'] ?? 0;
                            }
                        }
                        echo $total;
                    @endphp
                </td>
                <td>{{ $assessment->notes ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
