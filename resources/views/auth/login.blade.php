@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <!-- Message d'erreur/succès -->
                    <div id="message" class="alert" style="display: none;"></div>

                    <form id="loginForm" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                <span id="email-error" class="invalid-feedback"></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">
                                <span id="password-error" class="invalid-feedback"></span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" id="loginBtn" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault();

    // Récupérer les éléments
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const remember = document.getElementById('remember').checked;
    const loginBtn = document.getElementById('loginBtn');
    const messageDiv = document.getElementById('message');

    // Désactiver le bouton
    loginBtn.disabled = true;
    loginBtn.textContent = 'Connexion...';

    // Effacer les erreurs précédentes
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
        el.previousElementSibling.classList.remove('is-invalid');
    });
    messageDiv.style.display = 'none';

    // IMPORTANT: Utiliser la route API, pas la route web
   /* fetch('/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',  // CRUCIAL pour forcer le JSON
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            email: email,
            password: password,
            remember: remember
        })
    }) */
   fetch('/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({
        email: email,
        password: password,
        remember: remember
    })
})
    .then(response => {
        console.log('Status de la réponse:', response.status);
        console.log('Content-Type:', response.headers.get('content-type'));

        // Si ce n'est pas du JSON, afficher le contenu brut pour debug
        if (!response.headers.get('content-type')?.includes('application/json')) {
            return response.text().then(text => {
                console.error('Réponse non-JSON reçue:', text);
                throw new Error('Réponse du serveur non-JSON: ' + text.substring(0, 100) + '...');
            });
        }

        return response.json();
    })
    .then(data => {
        console.log('Données reçues:', data);

        if (data.status === 'success') {
            // Succès
            messageDiv.className = 'alert alert-success';
            messageDiv.textContent = data.message;
            messageDiv.style.display = 'block';

            // Redirection après 1 seconde
            setTimeout(() => {
                window.location.href = data.redirect_url || '/dashboard';
            }, 1000);

        } else {
            // Erreur
            messageDiv.className = 'alert alert-danger';
            messageDiv.textContent = data.message || 'Erreur de connexion';
            messageDiv.style.display = 'block';

            // Afficher les erreurs de validation
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.getElementById(field);
                    const errorSpan = document.getElementById(field + '-error');

                    if (input && errorSpan) {
                        input.classList.add('is-invalid');
                        errorSpan.textContent = data.errors[field][0];
                        errorSpan.style.display = 'block';
                    }
                });
            }
        }
    })
    .catch(error => {
        console.error('Erreur complète:', error);
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = 'Erreur de connexion: ' + error.message;
        messageDiv.style.display = 'block';
    })
    .finally(() => {
        // Réactiver le bouton
        loginBtn.disabled = false;
        loginBtn.textContent = '{{ __("Login") }}';
    });
});
</script>
@endsection
