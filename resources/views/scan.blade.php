<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مسح رمز الاستجابة السريعة</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #F3F4F6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .container {
            text-align: center;
            background-color: #fff;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            border: 1px solid #E0E0E0;
            transition: all 0.3s ease;
        }

        .container:hover {
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.1);
        }

        .container h1 {
            font-size: 36px;
            margin-bottom: 40px;
            color: #4CAF50;
            font-weight: 600;
            text-transform: uppercase;
        }

        .qr-input {
            width: 80%;
            padding: 15px;
            margin-bottom: 30px;
            border: 1px solid #D1D3D8;
            border-radius: 10px;
            font-size: 18px;
            outline: none;
            transition: all 0.3s ease;
            background-color: #F9F9F9;
        }

        .qr-input:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
        }

        .scan-btn {
            padding: 15px 30px;
            background-color: #4CAF50;
            color: white;
            font-size: 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            font-weight: 500;
        }

        .scan-btn:hover {
            background-color: #388E3C;
            transform: scale(1.05);
        }

        .status {
            margin-top: 30px;
            font-weight: 600;
            font-size: 16px;
            display: none;
        }

        .status .success {
            color: #388E3C;
        }

        .status .error {
            color: #D32F2F;
        }

        .status .loading {
            color: #FF9800;
        }

        .message {
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            font-weight: normal;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .message.success {
            background-color: #C8E6C9;
            color: #388E3C;
        }

        .message.error {
            background-color: #FFEBEE;
            color: #D32F2F;
        }

        .historique-btn {
            padding: 15px 30px;
            background-color: #2196F3;
            color: white;
            font-size: 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 30px;
            display: inline-block;
            transition: background-color 0.3s ease, transform 0.3s ease;
            font-weight: 500;
        }

        .historique-btn:hover {
            background-color: #1976D2;
            transform: scale(1.05);
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
    </style>
</head>

<body>

    <div class="container">
        <h1>مسح رمز الاستجابة السريعة</h1>
        <input type="text" id="qrCodeInput" class="qr-input" placeholder="امسح رمز الاستجابة السريعة هنا" autofocus />
        <button id="scanBtn" class="scan-btn">مسح</button>

        <div id="status" class="status"></div>

        <a href="{{ url('/historique') }}" class="historique-btn">عرض التاريخ</a>
    </div>

    <script>
        // Fonction pour appeler l'API Laravel et envoyer le code scanné
        function scanQRCode(qrCode) {
            // Afficher le message de chargement
            document.getElementById('status').style.display = 'block';
            document.getElementById('status').innerHTML = `<div class="message loading">جاري المعالجة...</div>`;

            fetch('/api/scan-qrcode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ code: qrCode }),
            })
                .then(response => response.json())
                .then(data => {
                    const statusDiv = document.getElementById('status');
                    if (data.status === 'ok') {
                        statusDiv.innerHTML = `<div class="message success">${data.message}</div>`;
                    } else if (data.status === 'inconnue') {
                        statusDiv.innerHTML = `<div class="message error">تحذير ! : ${data.message}</div>`;
                    } else if (data.status === 'refuse') {
                        statusDiv.innerHTML = `<div class="message error">تحذير ! : ${data.message}</div>`;
                    } else if (data.status === 'fraude') {
                        statusDiv.innerHTML = `<div class="message error">تحذير ! : ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la requête:', error);
                    const statusDiv = document.getElementById('status');
                    statusDiv.innerHTML = `<div class="message error">حدث خطأ أثناء المسح. يرجى المحاولة مرة أخرى.</div>`;
                });
        }

        // Action du bouton Scan
        document.getElementById('scanBtn').addEventListener('click', function () {
            const qrCode = document.getElementById('qrCodeInput').value.trim();
            if (qrCode) {
                scanQRCode(qrCode);
            } else {
                alert("يرجى إدخال رمز الاستجابة السريعة.");
            }
        });

        // Optionnel : vous pouvez également gérer l'événement 'Enter' pour déclencher le scan
        document.getElementById('qrCodeInput').addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                document.getElementById('scanBtn').click();
            }
        });
    </script>

</body>

</html>
