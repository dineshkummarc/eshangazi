<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ad extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 
        'description',
        'thumbnail',
        'minimum_age',
        'gender',
        'status',
        'mode',
        'starts',
        'ends',
        'partner_id', 
        'created_by',
        'updated_by',
    ];

    /**
     * Ad belong to a partner.
     * 
     * @return BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Ad may be displayed to one or more platforms.
     * 
     * @return BelongsTo
     */
    public function platforms()
    {
        return $this->belongsTo(Platform::class);
    }   

    /**
     * Ad may be have one or more payments.
     * 
     * @return HasMany
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    } 

    /**
     * Ad has many Targets.
     * 
     * @return HasMany
     */
    public function targets()
    {
        return $this->hasMany(Target::class);
    }

    /**
     * Ad created by a user.
     * 
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }    

    /**
     * Ad updated by a user.
     * 
     * @return BelongsTo
     */
    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
