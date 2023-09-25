<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\User;
use Illuminate\Validation\Rules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {   
        $this->middleware('auth:api', ['except' => [
            'login', 
            'store',
            'logout',
        ]]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $rules = [
            'email' => ['sometimes', 'email',  'unique:'.User::class],
            'phone' => ['sometimes', 'digits:10', 'min:10', 'unique:'.User::class],
            'username' => ['required', 'min:6', 'max:8',  'unique:'.User::class],
            'asset_password' => ['required', 'min:5', 'max:6'],
            'password' => ['required', 'min:8', "max:30", 'confirmed', Rules\Password::defaults()],
            'ref_code' => ['sometimes'],
        ];
        $validation = Validator::make( $request->all(), $rules );
        if ( $validation->fails() ) {
            return ApiResponse::validationError([

                    "message" => $validation->errors()->first()
                ]);
        }

        // $referrer = User::where('ref_code', $request->ref_code);
        // $u
        $randomNumber = random_int(100000, 999999);
        $user = User::create([
            'ref_code' => $randomNumber,
            'referer_code' => $request->ref_code,
            'username' => $request->username,
            'phone' => $request->phone,
            'email'  => $request->email,
            'password' => Hash::make($request->password),
            'asset_password' => Hash::make($request->asset_password)
        ]);
        
        $token = Auth::login($user);

        return ApiResponse::successResponse([
            "data" => [
                'message'=> 'Signed up successfully',
                "user"=> $user,
                'token' => $token
                ]
            ], 201);
                
    }

    /**
     * Display the specified resource.
     */
    public function login (Request $request)
    {
        $rules = [
            'username' => ['required'],
            // 'phone' => ['sometimes'],
            'password' => ['required'],
        ];
        $validation = Validator::make( $request->all(), $rules );
        if ( $validation->fails() ) {
            return ApiResponse::validationError([

                    "message" => $validation->errors()->first()
                ]);
        }
        $credentials = $request->only(['username', 'password']);

        $token = Auth::attempt($credentials);

        if($token) {

            return ApiResponse::successResponse($token, 200);
                    
        } else{
            return ApiResponse::errorResponse("User doesn't exist");
        }
        } 

    public function show(string $id)
    {
        //
    }

    public function logout(Request $request)
    {   
        Auth::logout(true);
        return ApiResponse::successResponse('Logged out');
    }   

    public function createToken(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            JWTAuth::checkOrFail($token);
            return response()->json(['message' => 'Token valid'], 200);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function changeAssetPassword(Request $request)
    {
        $rules = [
            'old_password' => 'required',
            'new_password' =>  ['required', 'min:5', 'max:6'],
        ];
        $id = Auth::id();

        $user = User::where('id', $id)->first();
        if(!Hash::check($request->old_password, $user->asset_password)){
            return ApiResponse::errorResponse('Incorrect asset password');
        } 
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }


        $user->update([
            'asset_password' => Hash::make($request->new_password)
        ]);
    }
    public function changePassword(Request $request)
    {
        $rules = [
            'old_password' => 'required',
            'new_password' => ['required', 'min:8', "max:30", 'confirmed', Rules\Password::defaults()],
        ];
        $id = Auth::id();

        $user = User::where('id', $id)->first();
        if(!Hash::check($request->old_password, $user->password)){
            return ApiResponse::errorResponse('Incorrect password');
        } 
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }


        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
    }
    public function bindEmail(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }

        $id = Auth::id();
        
        $user = User::where('id', $id)->first();
        if(!Hash::check($request->password, $user->password)){
            return ApiResponse::errorResponse('Incorrect password');
        } 

        $user->update([
            'email' => $request->email
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
