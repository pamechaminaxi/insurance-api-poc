<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    //boot logs activity method 
    use HasFactory, LogsActivity, SoftDeletes;

    // fillable properties
    protected $fillable = [
        'quote_number',
        'insurance_type',
        'premium_amount',
        'coverage_amount',
        'status',
        'customer_name',
        'customer_user_id',
        'created_by',
        'is_delete',
    ];

    /**
     * Override runSoftDelete to also update the is_delete column.
     */
    protected function runSoftDelete()
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->fromDateTime($this->freshTimestamp());

        $columns = [
            $this->getDeletedAtColumn() => $time,
            'is_delete' => 1,
        ];

        $this->{$this->getDeletedAtColumn()} = $time;
        $this->is_delete = 1;

        if ($this->timestamps && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));
    }

    /**
     * Override restore to reset is_delete to 0.
     */
    public function restore()
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;
        $this->is_delete = 0;

        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    // customer relationship
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    // creator relationship
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // claims relationship
    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}
