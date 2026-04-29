<?php
// File: app/Models/SalesPage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_name',
        'description',
        'features',
        'target_audience',
        'price',
        'unique_selling_points',
        'generated_html',
        'generated_data',
        'style',
    ];

    protected $casts = [
        'generated_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper: get features as array
    public function getFeaturesArrayAttribute(): array
    {
        return array_filter(array_map('trim', explode(',', $this->features)));
    }
}
