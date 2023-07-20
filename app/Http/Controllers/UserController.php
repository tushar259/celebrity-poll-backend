<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\CustomUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
	public function _construct(){
		$this->middleware('auth:api', ['except'=>['createAccount', 'loginAccount', 'checkIfUserLoggedIn']]);
	}

    public function createCustomAccount(Request $request){
    	
    	$request->validate([
	        'email' => 'required|email|unique:custom_users,email',
	        'password' => 'required|min:5|max:20',
	        'selectedQuestion' => 'required',
	        'selectedAnswer' => 'required',
	        'isChecked' => 'required'
	    ]);

    	$email = $request->input("email");
    	$password = $request->input("password");
    	$selectedQuestion = $request->input("selectedQuestion");
    	$selectedAnswer = $request->input("selectedAnswer");
    	$isChecked = $request->input("isChecked");

    	if($isChecked == true){
	    	$customUser = new CustomUser();
	    	$customUser->email = $email;
	    	$customUser->password = bcrypt($password);
	    	$customUser->password_recovery_ques = $selectedQuestion;
	    	$customUser->password_recovery_ans = $selectedAnswer;
	    	if($customUser->save()){

	    		return response()->json([
		    		'message' => 'Account created.',
		    		'success' => true]);
	    	}
	    	else{
	    		return response()->json([
		    		'message' => 'Account counld not be created.',
		    		'success' => false]);
	    	}
    	}
    	else{
    		return response()->json([
	    		'message' => 'Account counld not be created.',
	    		'success' => false]);
    	}
    }

    public function loginCustomUser(Request $request){
	    // $credentials = $request->only('email', 'password');

	    $email = $request->input("email");
	    $password = $request->input("password");

	    $customUserPassword = CustomUser::select("password")
	    	->where("email", $email)
	    	->first();

	    if(Hash::check($password, $customUserPassword->password)){
	    	return "success";
	    }
	    else{
	    	return "failed";
	    }

	    // (Hash::check($password, $hashedPassword))

	    // $credentials = ['email' => $email,'password' => bcrypt($password)];

	    // if($token = auth()->attempt($credentials)){
	    //     return response()->json(['token' => $token]);
	    // }

	    // return response()->json(['error' => 'Unauthorized'], 401);


	}

	public function createAccount(Request $request){
		return $this->createCustomAccount($request);
	}

	public function loginAccount(Request $request){
		$request->validate([
	        'email' => 'required|email',
	        'password' => 'required'
	    ]);

	    $credentials = $request->only('email', 'password');
	    $token = "";
	    
	    if($token = JWTAuth::attempt($credentials)){
	        return response()->json([
	        	'token' => $token,
				'userEmail' => $request->input("email"),
				'token_type' => 'bearer',
				'message' => 'User verified',
				'success' => true]);
	    }
	    else{
	        return response()->json([
	        	'error' => 'Invalid credentials',
	        	'message' => 'Invalid user',
	        	'success' => false]);
	    }

	    // if($token = auth()->attempt($credentials)){
	    // 	return $token;
	    // }
	    // else{
	    // 	return "failed";
	    // }

	    // return $this->createToken($token);
	}

	public function createToken($token){
		return response()->json([
			'access_token' => $token,
			'token_type' => 'bearer',
    		'message' => 'User verified',
    		// 'expires_in' => auth()->factory()->getTTL()*60,
    		'success' => true]);
	}

	public function checkIfUserLoggedIn(Request $request){
		$token = $request->header('Authorization');
		$user = JWTAuth::parseToken()->authenticate();
		
		if($user){
			return response()->json([
				'userInfoFromTk' => $user,
	    		'message' => 'User logged in',
	    		'success' => true]);
		}
		else{
			return response()->json([
	    		'message' => 'User is not logged in',
	    		'success' => false]);
		}
	}

	public function checkIfEmailExist(Request $request){
		$email = $request->input("email");
		$data = DB::table("custom_users")->where("email", $email)->first();

		if($data !== null){
			return response()->json([
				'you_can_find_question_and_ans_here' => $data,
	    		'message' => 'Email found',
	    		'success' => true]);
		}
		else{
			return response()->json([
	    		'message' => 'Email not found',
	    		'success' => false]);
		}
	}

	public function changePasswordNow(Request $request){
		$email = trim($request->input("email"));
		$userId = $request->input("userId");
		$newPassword = trim($request->input("newPassword"));

		if($email == "" || $userId == "" || strlen($newPassword) < 5 || strlen($newPassword) > 20){
			return response()->json([
	    		'message' => 'Please check password requirements',
	    		'success' => false]);
		}

		$data = DB::table("custom_users")->where("id", $userId)
			->where("email", $email)
			->update(["password" => bcrypt($newPassword)]);

		if($data){
			return response()->json([
	    		'message' => 'Password changed.',
	    		'success' => true]);
		}
		else{
			return response()->json([
	    		'message' => 'Password could not be changed!',
	    		'success' => false]);
		}

	}

	public function checkIfEmailExistCreatingAccount(Request $request){
		$checkEmail = $request->input("email");

		$request->validate([
	        'email' => 'required|email|unique:custom_users,email'
	    ]);

		$data = DB::table("custom_users")->where("email", $checkEmail)->first();

		if($data){
			return response()->json([
	    		'message' => 'Email used already.',
	    		'success' => true]);
		}
		else{
			return response()->json([
	    		'message' => 'Email available.',
	    		'success' => false]);
		}
	}

	public function reportAProblem(Request $request){
		$name = $request->input("name");
		$subject = $request->input("subject");
		$description = $request->input("description");

		// now send it to email

		//if email sent
		return response()->json([
    		'message' => 'Report sent! Thank you.',
    		'success' => true]);
	}
}
