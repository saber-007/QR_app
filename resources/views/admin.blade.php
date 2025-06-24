{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Tableau de Bord Administrateur')

@section('content')
    <div class="container">
        <h1>Bienvenue sur le Tableau de Bord Administrateur</h1>

        <div class="card">
            <div class="card-header">
                <h3>Gestion des Agents</h3>
            </div>
            <div class="card-body">
                <p>Gérez tous les agents (entrées, sorties, administrateurs)</p>
                <a href="{{ route('admin.agents') }}" class="btn btn-primary">Voir les Agents</a>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3>Historique des Scans</h3>
            </div>
            <div class="card-body">
                <p>Consulter tous les scans effectués par les agents.</p>
                <a href="{{ route('admin.scans') }}" class="btn btn-primary">Voir l'Historique des Scans</a>
            </div>
        </div>
    </div>
@endsection
