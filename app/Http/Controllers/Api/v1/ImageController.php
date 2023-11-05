<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    /**
     * @OA\Info(
     *   title="Image Upload API Documentation",
     *   version="1.0.0"
     * )
     */

    /**
     * @OA\Post(
     *     path="/api/v1/upload-image",
     *     summary="Tải lên hình ảnh",
     *     operationId="uploadImage",
     *     tags={"Images"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Hình ảnh đã được tải lên thành công"),
     *     @OA\Response(response=400, description="Không có tệp nào được tải lên.")
     * )
     */
    public function uploadImagePost(Request $request)
    {
        $uploadedImages = $request->file("files");

        $uploadPath = public_path('/uploads/post/');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $uploadedUrls = [];

        foreach ($uploadedImages as $uploadedImage) {
            if ($uploadedImage && $uploadedImage->isValid()) {
                $imageName = time() . '.' . $uploadedImage->getClientOriginalExtension();
                $uploadedImage->move($uploadPath, $imageName);
                $imageUrl = '/uploads/post/' . $imageName;
                $uploadedUrls[] = asset($imageUrl);
            }
        }

        if (count($uploadedUrls) > 0) {
            // Lưu các URL hợp lệ vào session hoặc làm điều gì đó khác với chúng
            $tempImages = session('tempImages', []);
            $tempImages = array_merge($tempImages, $uploadedUrls);
            session(['tempImages' => $tempImages]);

            return response()->json(['messages'=>'success','urls' => $uploadedUrls, 'uploaded' => true], 200);
        } else {
            return response()->json(['error' => 'Không có tệp hợp lệ nào được tải lên.'], 400);
        }
    }
    public function removeImage(Request $request)
    {
        $url = $request->input('removeUrl');
        $tempImages = session('tempImages', []);

        if (in_array($url, $tempImages)) {
            if (file_exists(public_path($url))) {
                unlink(public_path($url));
            }
            $tempImages = array_diff($tempImages, [$url]);
            session(['tempImages' => $tempImages]);

            return response()->json(['message' => 'Tệp tin đã được xóa'], 200);
        } else {
            return response()->json(['error' => 'Tệp tin không tồn tại hoặc không thể xóa'], 400);
        }
    }

    public function uploadImageBanner(Request $request)
    {
        $fileaa = $request->file("image");

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $uploadedImage = $request->file('file');

            $uploadPath = public_path('/uploads/banner/');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $imageName = time() . '.' . $uploadedImage->getClientOriginalExtension();
            $uploadedImage->move($uploadPath, $imageName);
            $imageUrl = '/uploads/banner/' . $imageName;

            $tempImages = session('tempImages', []);
            $tempImages[] = $imageUrl;
            session(['tempImages' => $tempImages]);

            return response()->json(['url' => asset($imageUrl), 'uploaded' => true], 200);
        } else {
            return response()->json(['error' => 'Không có tệp hợp lệ nào được tải lên.', 'file' => $fileaa], 400);
        }
    }

    public function clearTempImages()
    {
        $tempImages = session('tempImages', []);
        foreach ($tempImages as $tempImage) {
            if (file_exists(public_path($tempImage))) {
                unlink(public_path($tempImage));
            }
        }
        session(['tempImages' => []]);
    }


}