{{-- resources/views/entry/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Tableau de Bord Agent d\'Entrée')

@section('content')
    <div class="container">
        <h1>Bienvenue sur le Tableau de Bord des Agents d'Entrée</h1>

        <div class="card">
            <div class="card-header">
                <h3>Mes Scans d'Entrée</h3>
            </div>
            <div class="card-body">
                <p>Voici les produits que vous avez scannés aujourd'hui :</p>
                <a href="{{ route('entry.scans') }}" class="btn btn-primary">Voir mes Scans</a>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3>Statistiques</h3>
            </div>
            <div class="card-body">
                <p>Consultez vos statistiques d'entrée de produits.</p>
                <a href="{{ route('entry.statistics') }}" class="btn btn-primary">Voir les Statistiques</a>
            </div>
        </div>
    </div>
@endsection
