<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use App\Http\Controllers\Controller;
use App\Models\BankDetailHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function profile()
    {
        $pageTitle = "Profile Setting";
        $user = auth()->user();
        $bankHistories = BankDetailHistory::where('user_id', $user->id)
            ->latest()
            ->get();
        return view('Template::user.profile_setting', compact('pageTitle', 'user', 'bankHistories'));
    }

    public function profile1()
    {
        $pageTitle = "Profile Setting";
        $user = auth()->user();
        $bankHistories = BankDetailHistory::where('user_id', $user->id)
            ->latest()
            ->get();
        return view('Template::user.profile_setting1', compact('pageTitle', 'user', 'bankHistories'));
    }

    public function submitProfile(Request $request)
    {
        $request->validate([

            'mobile' => 'required|string|min:9|max:11',
            'email'  => 'required|string|email',
            'bname' => 'required|string',
            'aname' => 'required|string',
            'ano' => 'required|numeric',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'image' => ['nullable','image',new FileTypeValidate(['jpg','jpeg','png'])]
        ],[
            'name.required'=>'The Bank name field is required',
            'firstname.required'=>'The first name field is required',
            'lastname.required'=>'The last name field is required',
            'aname.required'=>'The Account name field is required',
            'ano.required'=>'The account number field is required',
            'firstname.required'=>'The first name field is required',
            'lastname.required'=>'The last name field is required'
        ]);

        $user = auth()->user();

        // Save old bank details to history if any field has changed
        if ($user->bname !== $request->bname || $user->aname !== $request->aname || $user->ano !== $request->ano) {
            if ($user->bname || $user->aname || $user->ano) {
                BankDetailHistory::create([
                    'user_id' => $user->id,
                    'bname'   => $user->bname,
                    'aname'   => $user->aname,
                    'ano'     => $user->ano,
                ]);
            }
        }

        //$user->email = $request->email;
        $user->mobile = $request->mobile;
        $user->bname = $request->bname;
        $user->aname = $request->aname;
        $user->ano = $request->ano;
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;

        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;

        if ($request->hasFile('image')) {
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->save();
        $notify[] = ['success', 'Profile updated successfully'];
        return back()->withNotify($notify);
    }

    public function submitProfile1(Request $request)
    {
        $request->validate([

            'bname' => 'required|string',
            'aname' => 'required|string',
            'ano' => 'required|numeric',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'image' => ['nullable','image',new FileTypeValidate(['jpg','jpeg','png'])]
        ],[
            'name.required'=>'The Bank name field is required',
            'aname.required'=>'The Account name field is required',
            'ano.required'=>'The account number field is required',
            'firstname.required'=>'The first name field is required',
            'lastname.required'=>'The last name field is required'
        ]);

        $user = auth()->user();

        // Save old bank details to history if any field has changed
        if ($user->bname !== $request->bname || $user->aname !== $request->aname || $user->ano !== $request->ano) {
            if ($user->bname || $user->aname || $user->ano) {
                BankDetailHistory::create([
                    'user_id' => $user->id,
                    'bname'   => $user->bname,
                    'aname'   => $user->aname,
                    'ano'     => $user->ano,
                ]);
            }
        }

        $user->bname = $request->bname;
        $user->aname = $request->aname;
        $user->ano = $request->ano;
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;

        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;

        if ($request->hasFile('image')) {
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->save();
        $notify[] = ['success', 'Profile updated successfully'];
        return back()->withNotify($notify);
    }

    public function changePassword()
    {
        $pageTitle = 'Change Password';
        return view('Template::user.password', compact('pageTitle'));
    }

    public function changePassword1()
    {
        $pageTitle = 'Change Password';
        return view('Template::user.password1', compact('pageTitle'));
    }

    public function submitPassword(Request $request)
    {

        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $request->validate([
            'current_password' => 'required',
            'password' => ['required','confirmed',$passwordValidation]
        ]);

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = ['success', 'Password changed successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'The password doesn\'t match!'];
            return back()->withNotify($notify);
        }
    }

    public function submitPassword1(Request $request)
    {

        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $request->validate([
            'current_password' => 'required',
            'password' => ['required','confirmed',$passwordValidation]
        ]);

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = ['success', 'Password changed successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'The password doesn\'t match!'];
            return back()->withNotify($notify);
        }
    }


}
