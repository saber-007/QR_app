{{-- resources/views/exit/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Tableau de Bord Agent de Sortie')

@section('content')
    <div class="container">
        <h1>Bienvenue sur le Tableau de Bord des Agents de Sortie</h1>

        <div class="card">
            <div class="card-header">
                <h3>Mes Scans de Sortie</h3>
            </div>
            <div class="card-body">
                <p>Voici les produits que vous avez scannés en sortie :</p>
                <a href="{{ route('exit.scans') }}" class="btn btn-primary">Voir mes Scans de Sortie</a>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3>Statistiques de Sortie</h3>
            </div>
            <div class="card-body">
                <p>Consultez vos statistiques de sortie de produits.</p>
                <a href="{{ route('exit.statistics') }}" class="btn btn-primary">Voir les Statistiques de Sortie</a>
            </div>
        </div>
    </div>
@endsection
