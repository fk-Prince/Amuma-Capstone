<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, Auditable;

    protected string $auditModule = 'services';

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'branch_id',
        'category_name',
    ];


    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id', 'category_id');
    }
}
