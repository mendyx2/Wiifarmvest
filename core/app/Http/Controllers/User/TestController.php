<?php
namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\Matrix;
use App\Models\MatrixStage;
use App\Services\UserStageProgress;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Services\MatrixPlacementService;
use App\Services\MatrixReconnectionService;


class TestController extends Controller
{

    protected $matrixService;
    
    public function __construct(MatrixPlacementService $matrixService,  MatrixReconnectionService $reconnectService)
    {
        $this->matrixService = $matrixService;
        $this->reconnectService = $reconnectService;
    }

    function anotherTest(){

        $stage_id = returnCurrentMatrixStage(auth()->id())->stage_id;


        // Get your matrix in a specific stage
        $userMatrix = Matrix::where('user_id', auth()->id())
            ->where('stage_id', $stage_id)
            ->where('is_active', true)
            ->first();

        $user = User::find(auth()->id());
        $newStage = MatrixStage::find($stage_id);

        $results = $this->reconnectService->reconnectDownline($user, $newStage);

        //dd($results);
    }

    function level2Test(){

        $stage_id = 2;


        // Get your matrix in a specific stage
        $userMatrix = Matrix::where('user_id', auth()->id())
            ->where('stage_id', $stage_id)
            ->where('is_active', true)
            ->first();

        $user = User::find(auth()->id());
        $newStage = MatrixStage::find($stage_id);
        
        $results = $this->reconnectService->reconnectDownline($user, $newStage);

        //dd($results);
    }



    function test(){

        // Get your starting matrix
         $stage_id = returnCurrentMatrixStage(auth()->id())->stage_id;
        $userMatrix = Matrix::where('user_id', auth()->id())
            ->first();

            //  $userMatrix = Matrix::where('user_id', auth()->id())
            // ->where('stage_id', $stage_id)
            // ->where('is_active', true)
            // ->first();
        //dd($userMatrix);
         
        // Search downline with BFS (up to 3 levels deep)
        $downline = $this->matrixService->searchDownlineBFS($userMatrix, 70);
        //dd($downline);
        // Process results
        foreach ($downline as $entry) {
            echo "User: " . $entry['matrix']->user->username . 
                 " | Depth: " . $entry['depth'] . 
                 " | Position: " . $entry['position'] . "<br>\n";
        }

        
        // $stage_id = returnCurrentMatrixStage(auth()->id())->stage_id;
        // $cl = $this->matrixService->countDescendants(auth()->id(), $stage_id);
        // dd($stage_id);
    }

    function check(){
        
            $stage1 = MatrixStage::where('level', 1)->first();

            //$sponsor = User::find(auth()->id());

            // $parent on line 210

            $test1 = $this->matrixService->checkStageCompletion1($parent, $stage1);
            //var_dump($d1);

            if($test1){

                 UserStageProgress::updateOrCreate(
                    ['user_id' => $parent->id, 'stage_id' => $stage1->id],
                    ['is_completed' => true, 'completed_at' => now()]
                );
                
                // Move to next stage
                $this->advanceToNextStage($parent, $stage1);
                
                // Update matrix status

                //$matrix->update(['is_active' => false]);

                //Stage out commission bonus for user promotion to stage 2
                $details = 'Step out Bonus gotten from completing stage 1';
                promotionCommisionMatrix($parent->id, $details, 1, 2);




            }
    }

    public function test1()
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'sponsor_id' => 'nullable|exists:users,id',
            'matrix_parent_id' => 'required|exists:matrices,id',
            'preferred_position' => 'required|in:left,right',
        ]);
        
        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'sponsor_id' => $validated['sponsor_id'] ?? null,
        ]);
        
        // Place user in stage 1 matrix
        $stage1 = MatrixStage::where('level', 1)->first();
        $parent = Matrix::with(['leftChild', 'rightChild'])
            ->find($validated['matrix_parent_id']);
        
        try {
            $matrix = $this->matrixService->placeUserWithPreference(
                $user, 
                $stage1, 
                $parent, 
                $validated['preferred_position']
            );
            
            // Check sponsor's matrix completion
            if ($user->sponsor_id) {
                $sponsor = User::find($user->sponsor_id);
                $this->matrixService->checkStageCompletion($sponsor, $stage1);
            }
            
            return redirect()->route('dashboard')->with('success', 'Registration successful!');
            
        } catch (\Exception $e) {
            $user->delete();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}