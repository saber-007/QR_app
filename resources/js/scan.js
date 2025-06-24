// Avec Instascan
let scanner = new Instascan.Scanner({ video: document.getElementById('qr-scanner') });
scanner.addListener('scan', function(content) {
  alert('QR Code détecté: ' + content);
  // Envoyer le contenu au backend via AJAX si nécessaire
});
Instascan.Camera.getCameras().then(function(cameras) {
  if (cameras.length > 0) {
    scanner.start(cameras[0]);
  } else {
    console.error('Aucune caméra trouvée');
  }
});
