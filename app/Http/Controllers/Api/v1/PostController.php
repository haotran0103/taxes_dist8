<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Post_Tag;
use App\Models\subcategory;
use App\Models\Tag;
use Google\Cloud\Core\ExponentialBackoff;
use Google\Cloud\Core\Timestamp;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Post; // Import model Post

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::all();

        return response()->json(['message'=>'success','data'=> $posts], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $post = new Post;
        $post->title = $request->input('title');
        $post->content = $request->input('content');
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('postImage', $imageName);
            $post->image = 'postImage'. $imageName;
        }
        if($request->has('serial_number')){
            $post->serial_number = $request->input('serial_number');
        }
        if ($request->has('Issuance_date')) {
            $post->Issuance_date = $request->input('Issuance_date');
        }
        $post->subcategory_id = $request->input('subcategory_id');
        $post->user_id = auth()->user()->id;

        $post->save();

        
        return response()->json(['message' => 'success', 'post' => $post], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Không tìm thấy bài viết'], 404);
        }

        $tags = $post->tags;

        return response()->json(['post' => $post, 'tags' => $tags], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'title' => 'string',
            'content' => 'string',
            'image' => 'string',
            'user_id' => 'integer',
            'tags' => 'nullable|array', 
        ]);

        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Không tìm thấy bài viết'], 404);
        }

        $post->update($validatedData);

        if (isset($validatedData['tags'])) {
            $tags = $validatedData['tags'];

            Post_Tag::where('post_id', $post->id)->delete();

            foreach ($tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]); 
                Post_Tag::create(['post_id' => $post->id, 'tag_id' => $tag->id]);
            }
        }

        return response()->json(['message' => 'Bài viết đã được cập nhật!', 'post' => $post], 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $post = Post::find($id); 
        if (!$post) {
            return response()->json(['message' => 'Không tìm thấy bài viết'], 404);
        }
        $post->status = 'inactive';
        $post->save();

        return response()->json(['message' => 'Bài viết đã được xóa thành công'], 204);
    }


}
