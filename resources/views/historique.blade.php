<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تاريخ المسح</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F7F9FB;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .container:hover {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        h1 {
            font-size: 36px;
            color: #4CAF50;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 16px;
        }

        th {
            background-color: #4CAF50;
            color: white;
            font-weight: 600;
        }

        td {
            background-color: #f9f9f9;
        }

        td span {
            font-weight: 600;
        }

        /* Style des boutons */
        .back-btn {
            padding: 12px 25px;
            background-color: #2196F3;
            color: white;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 30px;
            transition: background-color 0.3s, transform 0.3s ease;
        }

        .back-btn:hover {
            background-color: #1976D2;
            transform: scale(1.05);
        }

        .status-success {
            color: #388E3C;
            font-weight: bold;
        }

        .status-error {
            color: #D32F2F;
            font-weight: bold;
        }

        /* Animation de fade-in */
        .container {
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Styles de réactivité */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>تاريخ المسح</h1>
        <table>
            <thead>
                <tr>
                    <th>رمز الاستجابة السريعة</th>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السائق</th>
                    <th>تاريخ المسح</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scans as $scan)
                <tr>
                    <td>{{ $scan->code }}</td>
                    <td>{{ $scan->produit }}</td>
                    <td>{{ $scan->quantite }}</td>
                    <td>{{ $scan->chauffeur }}</td>
                    <td>{{ \Carbon\Carbon::parse($scan->date_scan)->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="{{ $scan->status == 'Validé' ? 'status-success' : 'status-error' }}">
                            {{ $scan->status }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Bouton pour revenir à la page Scan -->
        <a href="{{ url('/') }}" class="back-btn">الرجوع إلى صفحة المسح</a>
    </div>

</body>

</html>
