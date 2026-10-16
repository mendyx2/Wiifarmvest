<?php

namespace App\Http\Controllers\User;


use App\Models\User;
use App\Models\Order;
use App\Models\Deposit;
use App\Models\Product;
use App\Constants\Status;
use App\Models\Transaction;
use App\Models\Matrix;
use App\Models\MatrixStage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Services\MatrixPlacementService;

class MatrixController extends Controller
{
	protected $matrixService;
    
    public function __construct(MatrixPlacementService $matrixService)
    {
        $this->matrixService = $matrixService;
    }


	public function activateMatrix(Request $request)
    { 
         
         $stage1 = MatrixStage::where('level', 1)->first();
         dd($stage1);

        $availableParents = Matrix::where('stage_id', $stage1->id)
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->map(function($matrix) {
                return [
                    'id' => $matrix->id,
                    'name' => $matrix->user->name,
                    'available_positions' => $this->getAvailablePositions($matrix)
                ];
            });
            
        return view('auth.register', [
            'availableParents' => $availableParents,
            'sponsors' => User::all() // For sponsor selection
        ]);

    }
        protected function getAvailablePositions(Matrix $parent)
	    {
	        $available = ['left', 'right'];
	        $stage = $parent->stage;
	        
	        // Check which positions are already taken
	        $children = $parent->children;
	        
	        foreach ($children as $child) {
	            if (($key = array_search($child->position, $available)) !== false) {
	                unset($available[$key]);
	            }
	        }
	        
	        return array_values($available);
	    }



}