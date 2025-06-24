<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    use HasFactory;

    // Ajoutez ici vos propriétés et relations selon votre base de données
    // Par exemple, si votre table s'appelle 'scans', vous pouvez définir les propriétés comme suit :

    protected $table = 'scans';  // Si le nom de la table est différent, définissez-le ici.

    // Si vous avez des colonnes spécifiques que vous souhaitez remplir massivement :
    protected $fillable = [
        'code', 'produit', 'quantite', 'chauffeur', 'status', 'date_scan', // Ajoutez les autres colonnes ici
    ];
    
public function agent()
{
    return $this->belongsTo(User::class, 'agent_id');
}
}
