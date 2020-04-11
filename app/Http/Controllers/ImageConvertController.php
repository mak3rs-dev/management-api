<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImageConvertController extends Controller
{
    public function __construct()
    {
        $this->middleware(['jwt.auth', 'privacy.policy']);
    }

    /**
     * @OA\POST(
     *     path="/converts/img-to-base64",
     *     tags={"Converts"},
     *     description="Conversión",
     *     @OA\RequestBody( required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="image", description="", type="binray"),
     *       ),
     *     ),
     *     ),
     *     @OA\Response(response=200, description=""),
     *      @OA\Response(response=422, description=""),
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ImgToBase64(Request $request) {
        // Validate request
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'image.required' => 'La imagen es requerida',
            'image.image' => 'No has enviado una imagen',
            'image.mimes' => 'La imagen no tiene el formato jpeg o png o jpg',
            'image.max' => 'El tamaño máximo de la imagen es 2MB (2048KB)'
        ]);

        // We check that the validation is correct
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Add headers base64 img
        $base64_image = "data:image/jpeg;base64,";

        // Convert Img to base64
        $base64_image .= base64_encode(file_get_contents($request->file('image')));

        return response()->json([
            'image_base64' => $base64_image,
            'message' => 'La imagen se ha convertido correctamente'
        ], 200);
    }
}
