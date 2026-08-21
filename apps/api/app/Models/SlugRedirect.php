<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Records a slug an admin has renamed away from, so the storefront can 301
 * the old URL to the current one instead of 404ing (see the
 * 2026_08_20_000003 migration). `type` is 'product' or 'category';
 * `entity_id` points at the current Product/Category row.
 */
class SlugRedirect extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['type', 'old_slug', 'entity_id'];
}
