<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner QR Code</title>
</head>
<body>
    <h1>Scanner QR Code</h1>
    <div id="reader"></div>
    <p>Résultat : <span id="result"></span></p>

    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script>
        const html5QrCode = new Html5Qrcode("reader");

        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById("result").textContent = decodedText;
            html5QrCode.stop().then(() => {
                console.log("Scanner arrêté.");
            }).catch((err) => {
                console.error("Erreur lors de l'arrêt du scanner.", err);
            });
        }

        const config = {
            fps: 10,
            qrbox: 250
        };

        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess);
    </script>
</body>
</html>
