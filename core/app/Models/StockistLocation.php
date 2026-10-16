<?php
// app/Models/StockistLocation.php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;

class StockistLocation extends Model
{
    protected $fillable = [
        'stockist_id', 'address_line_1', 'address_line_2', 'city', 'state', 
        'country', 'postal_code', 'latitude', 'longitude', 'phone', 'email',
        'opening_hours', 'is_primary', 'is_active'
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }

   

    

    public function getFullAddressAttribute()
    {
        $address = $this->address_line_1;
        if ($this->address_line_2) {
            $address .= ', ' . $this->address_line_2;
        }
        $address .= ', ' . $this->city . ', ' . $this->state . ', ' . $this->country;
        
        if ($this->postal_code) {
            $address .= ' - ' . $this->postal_code;
        }
        
        return $address;
    }

    public function getFormattedOpeningHoursAttribute()
    {
        if (!$this->opening_hours) {
            return [];
        }

        $hours = $this->opening_hours;
        $formatted = [];

        $days = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday'
        ];

        foreach ($days as $key => $day) {
            if (isset($hours[$key]) && $hours[$key]['open'] && $hours[$key]['close']) {
                $formatted[] = $day . ': ' . $hours[$key]['open'] . ' - ' . $hours[$key]['close'];
            }
        }

        return $formatted;
    }
}