<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaTemplate extends Model
{
    use HasFactory;

    protected $table = 'wa_templates';

    protected $fillable = [
        'name',
        'category',
        'language',
        'status',
        'header_type',
        'header_content',
        'body_text',
        'footer_text',
        'buttons',
        'variables',
        'whatsapp_template_id',
        'rejection_reason',
        'created_by',
        'submitted_at',
        'approved_at',
    ];

    protected $casts = [
        'buttons' => 'array',
        'variables' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the admin who created the template.
     */
    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Get campaigns using this template.
     */
    public function campaigns()
    {
        return $this->hasMany(WaCampaign::class, 'template_id');
    }

    /**
     * Scope for approved templates.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for specific category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if template is editable.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if template can be submitted.
     */
    public function canSubmit(): bool
    {
        return $this->status === 'draft' && !empty($this->body_text);
    }
}
