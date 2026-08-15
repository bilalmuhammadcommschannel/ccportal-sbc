<?php

namespace App\Models\Ov500;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Links a tariff to a ratecard for a direction + day/time window + priority. */
class TariffRatecardMap extends Model
{
    protected $connection = 'switch';
    protected $table = 'tariff_ratecard_map';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'ratecard_id', 'tariff_id', 'start_day', 'start_time', 'end_day', 'end_time',
        'priority', 'status', 'ratecard_for', 'account_id',
        'created_by', 'updated_by', 'created_dt', 'updated_dt',
    ];

    public function ratecard(): BelongsTo
    {
        return $this->belongsTo(Ratecard::class, 'ratecard_id', 'ratecard_id');
    }
}
