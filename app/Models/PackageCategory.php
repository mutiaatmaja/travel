<?php

namespace App\Models;

use Database\Factories\PackageCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageCategory extends Model
{
    /** @use HasFactory<PackageCategoryFactory> */
    use HasFactory;

    public const TYPES = ['package' => 'Kategori Paket', 'financial' => 'Kategori Keuangan'];

    protected $fillable = ['name', 'type'];

    public function packageColumn(): string
    {
        return $this->type === 'financial' ? 'financial_category' : 'category';
    }
}
