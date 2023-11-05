<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\NewsModel;

class NewsController extends Controller
{
    public function updateAPollForAdmin(Request $request){
    	// return $request;
    	$title = $request->input('title');
	    $industry = $request->input('industry');
	    $description = $request->input('description');
	    $titleUrl = $request->input('titleUrl');

	    // Handle image uploads
	    // $images = [];
	    // foreach ($request->allFiles() as $key => $file) {
	    //     if (strpos($key, 'image_') === 0) {
	    //         // This is an image file, process and store it
	    //         $imageName = $file->store('images'); // Store the image in a storage directory
	    //         $images[] = $imageName;
	    //     }
	    // }


	    $images = $request->file('image_0');
	    $fileName = "";
	    // return $images;
	    // Create a folder to store images, if it doesn't exist
	    $folderPath = public_path('newsImages');
	    if (!file_exists($folderPath)) {
	        mkdir($folderPath, 0777, true);
	    }

	    // Loop through the images and store them in the database and local storage
	    // foreach ($images as $imageData) {

	        // Generate a unique file name
	        $fileName = time() . '_' . uniqid() . '.' . 'webp';

	        // Check if a file with the same name exists, if so, generate a new name
	        while (file_exists($folderPath . '/' . $fileName)) {
	            $fileName = time() . '_' . uniqid() . '.' . 'webp';
	        }
	        
	        // Store the image in the local storage
	        // file_put_contents($folderPath . '/' . $fileName, $imageData);
	        $images->move($folderPath, $fileName);

	        // Store the image path in the database
	        // $image = new Image_file();
	        // $image->uploaded_images = 'images/' . $fileName;
	        // $image->save();

	    // }





	    // Store the data in the SQL database
	    // You can create a new model and use Eloquent to insert the data
	    // For example:
	    $news = new NewsModel();
	    $news->headline = $title;
	    $news->url = $titleUrl;
	    $news->industry = $industry;
	    $news->news_details = $description;
	    $news->thumbnail = 'newsImages/'.$fileName; // Store image file paths

	    $news->save();

	    return response()->json(['message' => 'News saved successfully'], 200);
    }

    public function getCurrentNewsDescription(Request $request){
    	// return $request;
    	$newsid = $request->input('newsid');
    	$news = NewsModel::select('id', 'headline', 'news_details', 'industry', 'created_at', 'thumbnail')
    		->where('url', $newsid)
    		->first();

	    if (!$news) {
	        return response()->json(['message' => 'News not found'], 404);
	    }

	    $extraNews = NewsModel::select('id', 'headline', 'thumbnail', 'url')
	    	->where('industry', $news->industry)
	    	->where('id', '<>', $news->id)
	    	->orderBy('id', 'DESC')
	    	->take(10)
	    	->get();
	    
	    return response()->json(['mainNews' => $news, 'sideNews' => $extraNews], 200);
    }
}
