<?php

namespace App\Http\Controllers\API\V1\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\API\V1\BaseController;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AuthController extends BaseController
{
    public function signin(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
            $authUser = Auth::user();
            $authUser->tokens()->delete();

            $roleUser = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'user']);

            if (!$authUser->hasRole($roleUser)) {
                $authUser->assignRole($roleUser);
            }

            $success['token'] =  $authUser->createToken($authUser->name)->plainTextToken;
            $success['name'] =  $authUser->name;
            $success['roles'] = $authUser->getRoleNames();

            return $this->sendResponse($success, 'User signed in');
        }
        else{
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        }
    }

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
            // 'confirm_password' => 'required|same:password',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $input = $validator->validated();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $roleAdmin = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'admin']);
        $roleUser = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'user']);
        if (User::count() > 1) {
            $user->assignRole($roleUser);
        } else {
            $user->syncRoles($roleAdmin, $roleUser);
        }

        $success['token'] =  $user->createToken('MyAuthApp', )->plainTextToken;
        $success['name'] =  $user->name;
        $success['roles'] =  $user->getRoleNames();


        return $this->sendResponse($success, 'User created successfully.');

    }

    public function signout(Request $request)
    {
        $success = $request->user()->tokens()->delete();

        return $this->sendResponse([
            'success' => $success,
        ], 'User signed out successfully.');
    }

    public function forbidden()
    {
        return $this->sendError("You don't have permission to access this resource.", null, 403);
    }
}
