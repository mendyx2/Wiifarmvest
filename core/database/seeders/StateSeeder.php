<?php
namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run()
    {
        $states = [
            ['name' => 'Abia', 'code' => 'ABI'],
            ['name' => 'Adamawa', 'code' => 'ADA'],
            ['name' => 'Akwa Ibom', 'code' => 'AKW'],
            ['name' => 'Anambra', 'code' => 'ANA'],
            ['name' => 'Bauchi', 'code' => 'BAU'],
            ['name' => 'Bayelsa', 'code' => 'BAY'],
            ['name' => 'Benue', 'code' => 'BEN'],
            ['name' => 'Borno', 'code' => 'BOR'],
            ['name' => 'Cross River', 'code' => 'CRO'],
            ['name' => 'Delta', 'code' => 'DEL'],
            ['name' => 'Ebonyi', 'code' => 'EBO'],
            ['name' => 'Edo', 'code' => 'EDO'],
            ['name' => 'Ekiti', 'code' => 'EKI'],
            ['name' => 'Enugu', 'code' => 'ENU'],
            ['name' => 'Federal Capital Territory', 'code' => 'FCT'],
            ['name' => 'Gombe', 'code' => 'GOM'],
            ['name' => 'Imo', 'code' => 'IMO'],
            ['name' => 'Jigawa', 'code' => 'JIG'],
            ['name' => 'Kaduna', 'code' => 'KAD'],
            ['name' => 'Kano', 'code' => 'KAN'],
            ['name' => 'Katsina', 'code' => 'KAT'],
            ['name' => 'Kebbi', 'code' => 'KEB'],
            ['name' => 'Kogi', 'code' => 'KOG'],
            ['name' => 'Kwara', 'code' => 'KWA'],
            ['name' => 'Lagos', 'code' => 'LAG'],
            ['name' => 'Nasarawa', 'code' => 'NAS'],
            ['name' => 'Niger', 'code' => 'NIG'],
            ['name' => 'Ogun', 'code' => 'OGU'],
            ['name' => 'Ondo', 'code' => 'OND'],
            ['name' => 'Osun', 'code' => 'OSU'],
            ['name' => 'Oyo', 'code' => 'OYO'],
            ['name' => 'Plateau', 'code' => 'PLA'],
            ['name' => 'Rivers', 'code' => 'RIV'],
            ['name' => 'Sokoto', 'code' => 'SOK'],
            ['name' => 'Taraba', 'code' => 'TAR'],
            ['name' => 'Yobe', 'code' => 'YOB'],
            ['name' => 'Zamfara', 'code' => 'ZAM'],
        ];
        
        foreach ($states as $state) {
            State::create($state);
        }
    }
}
