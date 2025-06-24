@extends('layouts.app')

@section('title', 'Gestion des Rôles')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestion des Rôles Utilisateurs</h1>
                    <p class="text-gray-600 mt-1">Gérez les rôles et permissions des utilisateurs</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                        {{ $users->count() }} utilisateur(s)
                    </span>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <div id="alert-container" class="px-6 pt-4" style="display: none;">
            <div id="alert-message" class="p-4 rounded-md mb-4"></div>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Utilisateur
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rôle Actuel
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nouveau Rôle
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date de Création
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr id="user-row-{{ $user->id }}" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center">
                                        <span class="text-white font-medium text-sm">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $user->name }}
                                        @if($user->id === auth()->id())
                                            <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Vous</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @switch($user->role ?? 'user')
                                    @case('admin')
                                        bg-red-100 text-red-800
                                        @break
                                    @case('entry')
                                        bg-blue-100 text-blue-800
                                        @break
                                    @case('exit')
                                        bg-yellow-100 text-yellow-800
                                        @break
                                    @default
                                        bg-gray-100 text-gray-800
                                @endswitch
                            ">
                                {{ $availableRoles[$user->role ?? 'user'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->id !== auth()->id())
                                <select
                                    id="role-select-{{ $user->id }}"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    data-user-id="{{ $user->id }}"
                                    data-current-role="{{ $user->role ?? 'user' }}"
                                >
                                    @foreach($availableRoles as $roleKey => $roleLabel)
                                        <option value="{{ $roleKey }}"
                                                @if(($user->role ?? 'user') === $roleKey) selected @endif>
                                            {{ $roleLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span class="text-sm text-gray-500 italic">Modification interdite</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->created_at ? $user->created_at->format('d/m/Y') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                @if($user->id !== auth()->id())
                                    <button
                                        onclick="updateUserRole({{ $user->id }})"
                                        id="update-btn-{{ $user->id }}"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm transition-colors duration-200"
                                        style="display: none;"
                                    >
                                        Modifier
                                    </button>
                                    <button
                                        onclick="confirmDeleteUser({{ $user->id }}, '{{ $user->name }}')"
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors duration-200"
                                    >
                                        Supprimer
                                    </button>
                                @else
                                    <span class="text-gray-400 text-sm">Actions indisponibles</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Aucun utilisateur trouvé
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Confirmation de Suppression -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" style="display: none;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Confirmer la suppression</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="delete-confirmation-text">
                    Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button
                    id="confirm-delete-btn"
                    class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300"
                >
                    Supprimer
                </button>
                <button
                    onclick="closeDeleteModal()"
                    class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                >
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let userToDelete = null;

// Surveiller les changements de rôle
document.addEventListener('DOMContentLoaded', function() {
    const roleSelects = document.querySelectorAll('[id^="role-select-"]');

    roleSelects.forEach(select => {
        select.addEventListener('change', function() {
            const userId = this.dataset.userId;
            const currentRole = this.dataset.currentRole;
            const newRole = this.value;
            const updateBtn = document.getElementById(`update-btn-${userId}`);

            if (newRole !== currentRole) {
                updateBtn.style.display = 'inline-block';
            } else {
                updateBtn.style.display = 'none';
            }
        });
    });
});

// Fonction pour mettre à jour le rôle
function updateUserRole(userId) {
    const select = document.getElementById(`role-select-${userId}`);
    const newRole = select.value;
    const updateBtn = document.getElementById(`update-btn-${userId}`);

    // Désactiver le bouton pendant la requête
    updateBtn.disabled = true;
    updateBtn.textContent = 'Modification...';

    fetch('{{ route("admin.users.update-role") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userId,
            role: newRole
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert(data.message, 'success');

            // Mettre à jour l'affichage du rôle actuel
            const currentRoleSpan = document.querySelector(`#user-row-${userId} td:nth-child(3) span`);
            currentRoleSpan.textContent = getRoleLabel(newRole);
            currentRoleSpan.className = `inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getRoleClasses(newRole)}`;

            // Mettre à jour le dataset et cacher le bouton
            select.dataset.currentRole = newRole;
            updateBtn.style.display = 'none';
        } else {
            showAlert(data.message, 'error');
            // Remettre l'ancienne valeur
            select.value = select.dataset.currentRole;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Une erreur est survenue lors de la modification', 'error');
        select.value = select.dataset.currentRole;
    })
    .finally(() => {
        updateBtn.disabled = false;
        updateBtn.textContent = 'Modifier';
    });
}

// Fonction pour confirmer la suppression
function confirmDeleteUser(userId, userName) {
    userToDelete = userId;
    document.getElementById('delete-confirmation-text').textContent =
        `Êtes-vous sûr de vouloir supprimer l'utilisateur "${userName}" ? Cette action est irréversible.`;
    document.getElementById('delete-modal').style.display = 'block';
}

// Fonction pour fermer le modal
function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
    userToDelete = null;
}

// Fonction pour supprimer l'utilisateur
document.getElementById('confirm-delete-btn').addEventListener('click', function() {
    if (!userToDelete) return;

    this.disabled = true;
    this.textContent = 'Suppression...';

    fetch('{{ route("admin.users.delete") }}', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userToDelete
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert(data.message, 'success');
            // Supprimer la ligne de l'utilisateur
            document.getElementById(`user-row-${userToDelete}`).remove();
            closeDeleteModal();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Une erreur est survenue lors de la suppression', 'error');
    })
    .finally(() => {
        this.disabled = false;
        this.textContent = 'Supprimer';
        closeDeleteModal();
    });
});

// Fonction pour afficher les alertes
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    const alertMessage = document.getElementById('alert-message');

    const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';

    alertMessage.className = `p-4 rounded-md mb-4 border ${bgColor}`;
    alertMessage.textContent = message;
    alertContainer.style.display = 'block';

    // Masquer l'alerte après 5 secondes
    setTimeout(() => {
        alertContainer.style.display = 'none';
    }, 5000);
}

// Fonctions utilitaires pour les rôles
function getRoleLabel(role) {
    const labels = {
        'admin': 'Administrateur',
        'entry': 'Agent d\'entrée',
        'exit': 'Agent de sortie',
        'user': 'Utilisateur standard'
    };
    return labels[role] || role;
}

function getRoleClasses(role) {
    const classes = {
        'admin': 'bg-red-100 text-red-800',
        'entry': 'bg-blue-100 text-blue-800',
        'exit': 'bg-yellow-100 text-yellow-800',
        'user': 'bg-gray-100 text-gray-800'
    };
    return classes[role] || 'bg-gray-100 text-gray-800';
}

// Fermer le modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modal = document.getElementById('delete-modal');
    if (event.target === modal) {
        closeDeleteModal();
    }
}
</script>
@endsection
