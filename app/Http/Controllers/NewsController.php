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
use App\Models\All_Tables;

class NewsController extends Controller
{
	public function checkIfTableExist(){
		if(Schema::hasTable('news_model')){
			return "done";
		}
		else{
			Schema::create('news_model', function (Blueprint $table) {
	            $table->id();
	            $table->text('headline')->unique();
	            $table->text('url')->unique();
	            $table->mediumText('news_details');
	            $table->string('industry');
	            $table->string('thumbnail')->nullable();
	            $table->bigInteger('times_visited');
	            $table->timestamps()->nullable();
	        });
	        return "done";
		}
	}

    public function insertANewsForAdmin(Request $request){
    	$checkTable = $this->checkIfTableExist();

    	if($checkTable != "done"){
    		return response()->json(['message' => 'Table creation error.']);
    	}
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
	    $indiaTime = Carbon::now('Asia/Kolkata');
    	$formattedTime = $indiaTime->format('Y-m-d H:i:s');

	    $news = new NewsModel();
	    $news->headline = $title;
	    $news->url = $titleUrl;
	    $news->industry = $industry;
	    $news->news_details = $description;
	    $news->thumbnail = 'newsImages/'.$fileName; // Store image file paths
	    $news->times_visited = 0;
	    $news->created_at = $formattedTime;
	    $news->updated_at = $formattedTime;
	    $news->save();

	    

	    return response()->json(['success' => 'true', 'message' => 'News saved successfully'], 200);
    }

    public function getCurrentNewsDescription(Request $request){
    	// return $request;
    	$newsid = $request->input('newsid');
    	$news = NewsModel::select('id', 'headline', 'news_details', 'industry', 'created_at', 'thumbnail', 'times_visited')
    		->where('url', $newsid)
    		->first();

	    if (!$news) {
	    	$news = [
			    'id' => "",
			    'headline' => "",
			    'news_details' => "",
			    'industry' => "",
			    'created_at' => "",
			    'thumbnail' => "",
			    'times_visited' => "",
			];
			$sideNews = [
				'id' => "",
			    'headline' => "",
			    'thumbnail' => "",
			    'url' => "",
			];
			$bottomNews = [
				'id' => "",
			    'headline' => "",
			    'thumbnail' => "",
			    'url' => "",
			];
	        return response()->json([
	        	'mainNews' => $news,
	        	'message' => 'News not found',
	        	'success' => 'false',
	        	'bottomNews' => $bottomNews,
	        	'sideNews' => $sideNews,
	        ]);
	    }
		//$news->increment('times_visited');

	    $sideNews = $this->sideNewsOnNewsId($news);
	    $bottomNews = $this->bottomNewsOnNewsId($news);
        				
	    
	    return response()->json([
	    	'mainNews' => $news, 
	    	'sideNews' => $sideNews, 
	    	'success' => 'true',
	    	'bottomNews' => $bottomNews
	    ]);
	    // return $news;
    }

    public function increaseNewsPageVisitCount(Request $request){
    	$newsid = $request->input('newsid');
    	$news = NewsModel::select('id', 'times_visited')
    		->where('url', $newsid)
    		->first();
    	$news->increment('times_visited');

    	return response()->json([ 
	    	'success' => 'true'
	    ]);
    }

    public function sideNewsOnNewsId($news){
    	return NewsModel::select('id', 'headline', 'thumbnail', 'url')
	    	->where('industry', $news->industry)
	    	->where('id', '<>', $news->id)
	    	->orderBy('id', 'DESC')
	    	->take(10)
	    	->get();
    }

    public function bottomNewsOnNewsId($news){
    	return NewsModel::select('id', 'headline', 'thumbnail', 'url')
	    	->where('industry', 'others')
	    	->where('id', '<>', $news->id)
	    	->orderBy('id', 'DESC')
	    	->take(3)
	    	->get();
    }

    public function checkIfNewsTitleUsed(Request $request){
    	$headline = $request->input('title');
    	$url = $request->input('titleUrl');
    	$checkTitlenUrl = NewsModel::where('headline', $headline)
    						->orWhere('url', $url)
    						->first();

    	if(!$checkTitlenUrl){
    		return response()->json(['success' => 'true', 'message' => 'Can be used.']);
    	}

    	return response()->json(['success' => 'false', 'message' => 'It is used already.']);
    }

    public function getAllCurrentNews(){
    	// ->selectRaw('TIMESTAMPDIFF(SECOND, created_at, NOW()) as created_at_diff')
    	$topLeftNews = NewsModel::select('id', 'headline', 'thumbnail', 'url', 'created_at')
	    	->orderBy('id', 'DESC')
	    	->take(20)
	    	->get();

	    $mostViewedNews = NewsModel::select('id', 'headline', 'thumbnail', 'url', 'created_at')
	    	->where('created_at', '>=', now()->subMonths(2))
	    	->orderBy('times_visited', 'DESC')
	    	->take(10)
	    	->get();

	    $bollywoodNews = NewsModel::select('id', 'headline', 'thumbnail', 'url', 'created_at')
	    	->where('industry', 'bollywood')
	    	->orderBy('id', 'DESC')
	    	->take(10)
	    	->get();

    	return response()->json([
    		'success' => 'true', 
    		'message' => 'Results found.',
    		'topLeftNews' => $topLeftNews,
    		'mostViewedNews' => $mostViewedNews,
    		'bollywoodNews' => $bollywoodNews
    	]);

    }

    public function getThisIndustryNews(Request $request){
    	$industry = $request->input('industry');
    	$industryNews = NewsModel::select('id', 'headline', 'thumbnail', 'url', 'created_at')
	    	->where('industry', $industry)
	    	->orderBy('id', 'DESC')
	    	->take(10)
	    	->get();

	    if(count($industryNews) > 0){
	    	return response()->json([
	    		'success' => 'true', 
	    		'message' => 'Results found.',
	    		'industryNews' => $industryNews
	    	]);
	    }
	    else{
	    	return response()->json([
	    		'success' => 'false', 
	    		'message' => 'Results not found.'
	    	]);
	    }
	    

    }

    public function showNextAmountTopNews(Request $request){
    	$showAmount = 20;
    	$skipAmount = 20 * $request->input("showAmount");

    	$currentNews = NewsModel::select('id', 'headline', 'thumbnail', 'url', 'created_at')
	    	->orderBy('id', 'DESC')
	    	->skip($skipAmount)
	    	->take($showAmount)
	    	->get();

	    if(count($currentNews) > 0){
	    	return response()->json([
	    		'success' => 'true', 
	    		'message' => 'Results found.',
	    		'currentNews' => $currentNews
	    	]);
	    }
	    else{
	    	return response()->json([
	    		'success' => 'false', 
	    		'message' => 'Results not found.'
	    	]);
	    }


    }

    public function getAllDynamicSitemap(){

    	$currentDate = date('Y-m-d');
        $routesFromPollTables = All_Tables::select('poll_title')
            ->where('ending_date','>',$currentDate)
            ->where('winner_added', 'no')
            ->get() ?? [];

    	// $routesFromPollTables = All_Tables::select("poll_title")->get() ?? [];
    	$routesFromNewsTables = NewsModel::select("url")->get() ?? [];
    	$routesFromPollIndustry = DB::table("all_tables")
            ->distinct()
            ->select("which_industry")
            ->get() ?? [];

    	$routesFromPollWinning = All_Tables::select("poll_title")
    		->where("ending_date", "<", $currentDate)
    		->where("winner_added", "yes")
    		->get() ?? [];

    	return response()->json([
    		'pollTables' => $routesFromPollTables,
    		'newsTables' => $routesFromNewsTables,
    		'pollIndustry' => $routesFromPollIndustry,
    		'pollWinning' => $routesFromPollWinning
    	]);
    	// return $routesFromPollTables;
    }

    public function getAllDynamicSitemapNew(){

    	$pollRoutes = [];
    	$currentDate = date('Y-m-d');

        $routesFromPollTables = All_Tables::select('poll_title')
            ->where('ending_date','>',$currentDate)
            ->where('winner_added', 'no')
            ->get() ?? [];

		foreach ($routesFromPollTables as $row) {
		    $pollRoutes[] = ['url' => '/poll/' . $row->poll_title];
		}

    	// $routesFromPollTables = All_Tables::select("poll_title")->get() ?? [];
    	$routesFromNewsTables = NewsModel::select("url")->get() ?? [];

    	foreach ($routesFromNewsTables as $row) {
		    $pollRoutes[] = ['url' => '/article/' . $row->url];
		}

    	$routesFromPollIndustry = DB::table("all_tables")
            ->distinct()
            ->select("which_industry")
            ->get() ?? [];

        foreach ($routesFromPollIndustry as $row) {
		    $pollRoutes[] = ['url' => '/industry/' . $row->which_industry];
		}

    	$routesFromPollWinning = All_Tables::select("poll_title")
    		->where("ending_date", "<", $currentDate)
    		->where("winner_added", "yes")
    		->get() ?? [];

    	foreach ($routesFromPollWinning as $row) {
		    $pollRoutes[] = ['url' => '/poll-winner/' . $row->poll_title];
		}

    	// return response()->json([
    	// 	'pollTables' => $routesFromPollTables,
    	// 	'newsTables' => $routesFromNewsTables,
    	// 	'pollIndustry' => $routesFromPollIndustry,
    	// 	'pollWinning' => $routesFromPollWinning
    	// ]);
    	return $pollRoutes;
    	// return response()->json([
    	// 	'all_url' => $pollRoutes
    	// ]);
    }

}
