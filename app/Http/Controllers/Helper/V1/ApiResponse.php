<?php

namespace App\Http\Controllers\Helper\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class ApiResponse extends Controller {
	
	public static function validationError($payload) {
		return response()->json([
			'success' => false,
			'message' => $payload['message'],
			'data' => []
		], Response::HTTP_UNPROCESSABLE_ENTITY);
	}

	public static function unauthorizedResponse($message) {
		return response()->json([
			'success' => false,
			'message' => $message,
			'data' => []
		], Response::HTTP_UNAUTHORIZED);
	}

	public static function notFoundResponse($message) {
		return response()->json([
			'success' => false,
			'message' => $message,
			'data' => []
		], Response::HTTP_NOT_FOUND);
	}

	public static function errorResponse($message) {
		return response()->json([
			'success' => false,
			'message' => $message,
			'data' => []
		], Response::HTTP_BAD_REQUEST);
	}

	public static function serverErrorResponse($message) {
		return response()->json([
			'success' => false,
			'message' => $message,
			'data' => []
		], Response::HTTP_INTERNAL_SERVER_ERROR);
	}

	public static function successResponse($payload) {

		if(is_array($payload)){
			return response()->json([
				'success' => true,
				'message' => isset($payload['message'])? $payload['message'] : null,
				'data' => isset($payload['data'])? $payload['data'] : []
			], Response::HTTP_CREATED);
		}else{
			return response()->json([
				'success' => true,
				'message' => $payload,
				'data' => []
			], Response::HTTP_OK);
		}
	}
}