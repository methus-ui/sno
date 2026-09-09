<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaSegmentFilterRule extends Model
{
    use HasFactory;

    protected $table = 'wa_segment_filter_rules';

    protected $fillable = [
        'segment_id',
        'field',
        'operator',
        'value',
        'logic_operator',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get the segment that owns this rule.
     */
    public function segment()
    {
        return $this->belongsTo(WaCustomerSegment::class, 'segment_id');
    }

    /**
     * Scope for specific segment.
     */
    public function scopeForSegment($query, $segmentId)
    {
        return $query->where('segment_id', $segmentId)->orderBy('sort_order');
    }

    /**
     * Get the SQL condition for this rule.
     */
    public function getSqlCondition(): string
    {
        $field = $this->field;
        $operator = $this->operator;
        $value = $this->value;

        switch ($operator) {
            case 'equals':
                return "{$field} = {$value[0]}";
            case 'gt':
                return "{$field} > {$value[0]}";
            case 'lt':
                return "{$field} < {$value[0]}";
            case 'between':
                return "{$field} BETWEEN {$value[0]} AND {$value[1]}";
            case 'in':
                $values = implode(',', $value);
                return "{$field} IN ({$values})";
            case 'not_in':
                $values = implode(',', $value);
                return "{$field} NOT IN ({$values})";
            case 'contains':
                return "{$field} LIKE '%{$value[0]}%'";
            default:
                return '';
        }
    }
}
