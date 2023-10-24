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
use App\Models\Image_file;
use App\Models\All_Tables;
use App\Models\HistoryVotes;

class PollController extends Controller
{
    public function uploadImages(Request $request, $tableNameStartsWith){
    	// Get the image data from the request
	    $images = $request->file('images');
	    
	    // Create a folder to store images, if it doesn't exist
	    $folderPath = public_path('images');
	    if (!file_exists($folderPath)) {
	        mkdir($folderPath, 0777, true);
	    }

	    // Loop through the images and store them in the database and local storage
	    foreach ($images as $imageData) {

	        // Generate a unique file name
	        $fileName = time() . '_' . uniqid() . '.' . $imageData->getClientOriginalExtension();

	        // Check if a file with the same name exists, if so, generate a new name
	        while (file_exists($folderPath . '/' . $fileName)) {
	            $fileName = time() . '_' . uniqid() . '.' . $imageData->getClientOriginalExtension();
	        }
	        
	        // Store the image in the local storage
	        // file_put_contents($folderPath . '/' . $fileName, $imageData);
	        $imageData->move($folderPath, $fileName);

	        // Store the image path in the database
	        // $image = new Image_file();
	        // $image->uploaded_images = 'images/' . $fileName;
	        // $image->save();

	        DB::table($tableNameStartsWith.'_images')->insert([
			    'placeholder' => 'images/' . $fileName
			]);
	    }

	    return response()->json(['message' => 'Images saved successfully.']);
    }

    public function checkIfHeadlineUsed(Request $request){
    	$headline = $request->input('headline');
    	if (All_Tables::where('poll_title', $headline)->where('winner_added', '<>', 'yes')->exists()) {
		    return response()->json(['message' => 'headline exist']);
		}
		else{
			return response()->json(['message' => 'headline does not exist']);
		}
    }

    public function uploadNewlyAddedPoll(Request $request){
    	// return $request;
    	$headline = $request->input('headline');
    	$tableNameStartsWith = time() . '_' . uniqid();
    	
    	$imageExist = $request->input('imageExist');
    	
    	
    	$valueEntered = $request->input('valueEntered');
    	$endingDate = $request->input('endingDate');

    	if($valueEntered == "1a5b10c"){

    		try{

		        Schema::create($tableNameStartsWith.'_polls', function (Blueprint $table) {
		            $table->id();
		            $table->string('polls');
                    $table->integer('votes');
		            $table->integer('extra_votes');
		            $table->timestamps();
		        });

		        if($imageExist == "yes"){
			        Schema::create($tableNameStartsWith.'_images', function (Blueprint $table) {
			            $table->id();
			            $table->string('placeholder');
			            $table->timestamps();
			        });
		    	}

		    	// Schema::create($tableNameStartsWith.'_win', function (Blueprint $table) {
		     //        $table->id();
		     //        $table->string('title')->nullable();
		     //        $table->text('description')->nullable();
		     //        $table->integer('votes')->nullable();
		     //        $table->string('winners_name')->nullable();
		     //        $table->integer('total_votes')->nullable();
		     //        $table->timestamps();
		     //    });

		        Schema::create($tableNameStartsWith.'_users_voted', function (Blueprint $table) {
		            $table->id();
		            $table->string('email');
		            $table->timestamps();
		        });

		        $beforeDetails = $request->input('beforeDetails');
		        if($beforeDetails == ""){
		    		$beforeDetails = null;
		    	}

		    	$afterDetails = $request->input('afterDetails');
		        if($afterDetails == ""){
		    		$afterDetails = null;
		    	}

		    	$whichIndustry = $request->input('whichIndustry');

		        $allTables = new All_Tables();
		        $allTables->poll_title = $headline;
		        $allTables->table_name_starts_with = $tableNameStartsWith;
		        $allTables->before_poll_description = $beforeDetails;
		        $allTables->after_poll_description = $afterDetails;
		        $allTables->which_industry = $whichIndustry;
		        $allTables->starting_date = now();
		        $allTables->ending_date = $endingDate;
		        $allTables->winner_added = "no";
		        $allTables->save();

		        // ...........before poll ends..........
		        //title and description in "all_tables" table
		        //images in "abcd_images" table
		        //polls in "abcd_polls" table
		        //ending date in "all_tables" table
		        //.....
		        //voting information in "abcd_users_voted" table
		        // ...........before poll ends..........

		        // ...........after poll ends..........
		        //voting information in "abcd_users_voted" table
		        //winner information in "abcd_win" table
		        //poll ended information in "all_tables" table
		        //delete all tables after a month from "ending_date" of "all_tables" table
		        // ...........after poll ends..........


		        if($imageExist == "yes"){
		    		$this->uploadImages($request, $tableNameStartsWith);
		    	}
		    	$this->uploadPolls($request, $tableNameStartsWith);

	    		

		    	return response()->json(['message' => 'Successfully uploaded a poll',
		    	'success' => true]);
	    	
	    	}
	    	catch(\Exception $e){
	    		return response()->json(['message' => 'Something went wrong',
	    		'success' => false]);
	    	}
	    	
    	}
    	

    }

    public function uploadPolls(Request $request, $tableNameStartsWith){
    	$tags = $request->input('tags');

    	foreach ($tags as $tag) {
    		DB::table($tableNameStartsWith.'_polls')->insert([
			    'polls' => $tag,
			    'votes' => 0,
                'extra_votes' => 0
			]);
    	}

    }

    public function getPollInfo(Request $request){
    	$pollId = $request->input('pollId');
    	$tableNameStartsWith = "";
    	$pollsFromTable = "";
    	$imagesFromTable = "";
    	$numberOfImages = "";
    	$i = 0;
    	$currentDate = date('Y-m-d');
    	$imageSrc = "";
    	if($tableNameStartsWith = All_Tables::select('poll_title','table_name_starts_with','before_poll_description','after_poll_description','which_industry','starting_date','ending_date')
    		->where('table_name_starts_with', $pollId)
            ->orWhere('poll_title', $pollId)
    		->where('ending_date','>',$currentDate)
    		->first()){
    		// return $tableNameStartsWith["table_name_starts_with"];
    		if(Schema::hasTable($tableNameStartsWith["table_name_starts_with"].'_images')){
    			// return $tableNameStartsWith["table_name_starts_with"];
    			$imagesFromTable = DB::table($tableNameStartsWith["table_name_starts_with"].'_images')
    			->select('placeholder')->get();
    			$numberOfImages = $imagesFromTable->count();
    			// return $imagesFromTable[0]->placeholder;
    			


    			$i = 0;
    			foreach ($imagesFromTable as $key) {
    				++$i;
                    if($i >= 3){
        				if(strpos($tableNameStartsWith["before_poll_description"], "<pic$i>") >= 0){
        					$tableNameStartsWith["before_poll_description"] = str_replace("<pic$i>", "<img src=\"/../".$key->placeholder."\">", $tableNameStartsWith["before_poll_description"]);
        				}
        				if(strpos($tableNameStartsWith["after_poll_description"], "<pic$i>") >= 0){
        					$tableNameStartsWith["after_poll_description"] = str_replace("<pic$i>", "<img src=\"/../".$key->placeholder."\">", $tableNameStartsWith["after_poll_description"]);
        				}
                    }
    				
    			}



    			// return $tableNameStartsWith["before_poll_description"];
    			
    		}

            $pollsFromTable = "";
            $totalVotesGiven = "";

            if(Schema::hasTable($tableNameStartsWith["table_name_starts_with"].'_polls')){
                // $pollsFromTable = DB::table($tableNameStartsWith["table_name_starts_with"].'_polls')
                //     ->select('id','polls', 'votes', 'extra_votes')
                //     ->orderBy('polls')
                //     ->get();

                $pollsFromTable = DB::table($tableNameStartsWith["table_name_starts_with"].'_polls')
                    ->select('id','polls', DB::raw('votes + extra_votes as votes'))
                    ->orderBy('polls')
                    ->get();

                // $totalVotesGiven = DB::table($tableNameStartsWith["table_name_starts_with"].'_polls')
                //     ->sum('votes');
                    $totalVotesGiven = DB::table($tableNameStartsWith["table_name_starts_with"].'_polls')
                        ->select(DB::raw('SUM(votes + extra_votes) as total_votes'))
                        ->value('total_votes');
            }
    		

    		if($pollsFromTable != "" && $pollsFromTable->count() > 0){
    			return response()->json([
    				'title_n_other_info' => $tableNameStartsWith,
    				'polls_n_counts' => $pollsFromTable,
    				'images_uploaded' => $imagesFromTable,
    				'total_votes' => $totalVotesGiven,
    				'message' => 'Data received',
	    			'success' => true]);
    		}
    		else{
    			return response()->json(['message' => 'Found error while fetching data',
	    		'success' => false]);
    		}

    	}
    	else{
    		return response()->json(['message' => 'Poll not found',
	    		'success' => false]);
    	}
    }

    public function getAllPoll(){
    	$currentDate = date('Y-m-d');
    	$allPolls = DB::table('all_tables')->select('which_industry','poll_title','table_name_starts_with','before_poll_description','starting_date','ending_date')
    		->where('ending_date','>',$currentDate)
    		->where('winner_added', 'no')
    		->orderBy('ending_date')
    		->skip(0)
    		->take(10)
    		->get();
    	

    	if($allPolls->count() > 0){
    		foreach($allPolls as $value){
    			$pollTags = "";
                if(Schema::hasTable($value->table_name_starts_with."_polls")){
            //         $pollTags = DB::table($value->table_name_starts_with."_polls")
        				// ->select('id','polls','votes',DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
        				// ->get();
                    $pollTags = DB::table($value->table_name_starts_with."_polls")
                        ->select('id', 'polls', DB::raw('SUM(votes + extra_votes) as votes'), DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
                        ->groupBy('id', 'polls', 'table_name_starts_with')
                        ->get();
                }
    			$value->poll_tags = $pollTags;

    			if(Schema::hasTable($value->table_name_starts_with."_images")){
	    			$pollThumbnail = DB::table($value->table_name_starts_with."_images")
	    				->select('placeholder')
	    				->where('id', '>', 1)
	    				->first();
	    			if($pollThumbnail !== null){
	    				$value->thumbnail_image = $pollThumbnail->placeholder;
	    			}
	    			else{
	    				$value->thumbnail_image = "images/test.jpg";
	    			}
    			}
    			else{
    				$value->thumbnail_image = "images/test.jpg";
    			}
    		}
    		return response()->json([
				'all_polls' => $allPolls,
				'message' => 'Data received',
    			'success' => true]);
    	}
    	else if($allPolls->count() == 0){
    		return response()->json([
				'message' => 'No polls uploaded yet',
    			'success' => false]);
    	}
    	else{
    		return response()->json([
				'message' => 'Something went wrong',
    			'success' => false]);
    	}
    }

    public function getAllPollIndustryWise(Request $request){
    	$industryName = $request->input("industryName");
    	$currentDate = date('Y-m-d');
    	$allPolls = DB::table('all_tables')->select('which_industry','poll_title','table_name_starts_with','before_poll_description','starting_date','ending_date')
    		->where('which_industry', $industryName)
    		->where('ending_date','>',$currentDate)
    		->orderBy('ending_date')
    		->get();
    	

    	if($allPolls->count() > 0){
    		foreach($allPolls as $value){
    			$pollTags = "";
                if(Schema::hasTable($value->table_name_starts_with."_polls")){
            //         $pollTags = DB::table($value->table_name_starts_with."_polls")
        				// ->select('id','polls','votes',DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
        				// ->get();
                    $pollTags = DB::table($value->table_name_starts_with."_polls")
                        ->select('id', 'polls', DB::raw('SUM(votes + extra_votes) as votes'), DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
                        ->groupBy('id', 'polls', 'table_name_starts_with')
                        ->get();
                }
    			$value->poll_tags = $pollTags;

    			if(Schema::hasTable($value->table_name_starts_with."_images")){
    				$dataFromImageTable = DB::table($value->table_name_starts_with."_images")
    					->select("placeholder")
    					->where("id", ">", 1)
    					->first();

    				if($dataFromImageTable !== null){
    					$value->thumbnail_image = $dataFromImageTable->placeholder;
    				}
    				else{
    					$value->thumbnail_image = "images/test.jpg";
    				}
    			}
    			else{
    				$value->thumbnail_image = "images/test.jpg";
    			}
    		}
    		return response()->json([
				'all_polls' => $allPolls,
				'message' => 'Data received',
    			'success' => true]);
    	}
    	else if($allPolls->count() == 0){
    		return response()->json([
				'message' => 'No polls uploaded yet',
    			'success' => false]);
    	}
    	else{
    		return response()->json([
				'message' => 'Something went wrong',
    			'success' => false]);
    	}
    }

    public function getPollForWinningList(){
    	$currentDate = date('Y-m-d');
    	$data = DB::table("all_tables")->select("id","poll_title","table_name_starts_with","which_industry","ending_date")
    	->where("ending_date", "<=", $currentDate)
    	->where("winner_added", "=", "no")
    	->orderBy("ending_date")
    	->get();

    	if($data->count() > 0){
    		foreach($data as $value){
    			$pollsFound = DB::table($value->table_name_starts_with."_polls")
    			->select("id","polls","votes")
    			->get();

    			$value->poll_tags = $pollsFound;
    			$value->percent = 0;
    		}
    	}

    	if($data->count() > 0){
    		return response()->json([
    			'polls_finished' => $data,
				'message' => 'Data found',
    			'success' => true]);
    	}
    	else{
    		return response()->json([
				'message' => 'Data not found',
    			'success' => false]);
    	}

    }

    public function saveToHistoryTable(Request $request){
        $table_name_starts_with = $request->input("table_name_starts_with");
        $winners_votes = $request->input("winners_votes");
        $winners_name = $request->input("winners_name");
        $winners_name = trim($winners_name);
        $winners_array = [];

        if (strpos($winners_name, ',') !== false) {
            $winners_array = explode(',', $winners_name);
            $winners_array = array_map('trim', $winners_array);
        }
        else{
            $winners_array = [$winners_name];
        }


        $allVotes = "";

        if (Schema::hasTable($table_name_starts_with.'_polls')){
            $allVotes = DB::table($table_name_starts_with.'_polls')
                        ->select("polls", "votes")
                        ->get();
        }

        if(Schema::hasTable('history_votes')){

            foreach ($allVotes as $value) {
                $data = HistoryVotes::where('star_name', $value->polls)
                    ->first();
                if($data){
                    // $data->total_votes_received += (int)$value->votes;
                    // $data->total_nominations += 1;
                    // $data->save();
                    $data->update([
                        'total_votes_received' => $data->total_votes_received + (int)$value->votes,
                        'total_nominations' => $data->total_nominations + 1,
                    ]);
                }
                else{
                    DB::table('history_votes')->insert([
                        'star_name' => $value->polls,
                        'total_votes_received' => $value->votes,
                        'total_nominations' => 1, // You may adjust these values accordingly
                        'total_won' => 0,         // based on your requirements
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        else{
            Schema::create('history_votes', function (Blueprint $table) {
                $table->id();
                $table->string('star_name');
                $table->bigInteger('total_votes_received');
                $table->integer('total_nominations');
                $table->integer('total_won');
                $table->timestamps();
            });

            foreach ($allVotes as $value) {
                $data = HistoryVotes::where('star_name', $value->polls)
                    ->first();
                if($data){
                    // $data->total_votes_received += (int)$value->votes;
                    // $data->total_nominations += 1;
                    // $data->save();
                    $data->update([
                        'total_votes_received' => $data->total_votes_received + (int)$value->votes,
                        'total_nominations' => $data->total_nominations + 1,
                    ]);
                    
                }
                else{
                    DB::table('history_votes')->insert([
                        'star_name' => $value->polls,
                        'total_votes_received' => $value->votes,
                        'total_nominations' => 1, // You may adjust these values accordingly
                        'total_won' => 0,         // based on your requirements
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        foreach ($winners_array as $value) {
            DB::table('history_votes')
                ->where('star_name', $value)
                ->update([
                    'total_won' => DB::raw('total_won + 1')
                ]);
        }

        return 1;
    }

    public function uploadNewlyWinnerPoll(Request $request){
    	$poll_id_in_all_tables = $request->input("poll_id_in_all_tables");
    	$description_or_afterDetails = $request->input("description_or_afterDetails");
    	$poll_title_in_all_tables = $request->input("poll_title_in_all_tables");
    	$table_name_starts_with = $request->input("table_name_starts_with");
    	$value_entered = $request->input("value_entered");
    	$winners_name = $request->input("winners_name");
    	$winners_votes = $request->input("winners_votes");
    	$total_votes = $request->input("total_votes");
        $save_data = $request->input("save_data");
    	$currentDate = date('Y-m-d H:i:s');

        

    	if($value_entered == "1a5b10c"){
            if($save_data == true){
                $this->saveToHistoryTable($request);
            }

    		if (Schema::hasTable($table_name_starts_with.'_images')){
    			
    		}
    		else{
    			Schema::create($table_name_starts_with.'_images', function (Blueprint $table) {
		            $table->id();
		            $table->string('placeholder');
		            $table->timestamps();
		        });
    		}


    		$imagePaths = DB::table($table_name_starts_with.'_images')
			    ->where('id', '>', 2)
			    ->pluck('placeholder')
			    ->toArray();
			// Storage::disk('public')->delete($imagePaths);
			if(count($imagePaths) > 0){
				foreach ($imagePaths as $imagePath) {
				    $absolutePath = public_path($imagePath);
				    if (file_exists($absolutePath)) {
				        unlink($absolutePath);
				    }
				}

				DB::table($table_name_starts_with.'_images')
				    ->where('id', '>', 2)
				    ->delete();
			}

    		$this->uploadImages($request, $table_name_starts_with);

   //  		DB::table($table_name_starts_with.'_win')->insert([
			//     'title' => $poll_title_in_all_tables,
			//     'description' => $description_or_afterDetails,
			//     ''

			// ]);

    		$all_tables = All_Tables::where("id", $poll_id_in_all_tables)
    		->where("table_name_starts_with", $table_name_starts_with)
    		->first();

    		if($all_tables !== null){
	    		$all_tables->after_poll_description = $description_or_afterDetails;
	    		$all_tables->winner_added = "yes";
	    		$all_tables->winners_name = $winners_name;
	    		$all_tables->winners_votes = $winners_votes;
	    		$all_tables->total_votes = $total_votes;
	    		$all_tables->updated_at = $currentDate;
	    		$all_tables->save();
    		}
    		else{
    			return response()->json(['message' => 'Entry does not exist',
	    		'success' => false]);
    		}

    		Schema::dropIfExists($table_name_starts_with.'_polls');
    		Schema::dropIfExists($table_name_starts_with.'_users_voted');

    		return response()->json(['message' => 'Winner added',
	    		'success' => true]);
    	}
    	else{
    		return response()->json(['message' => 'Enter a value',
	    		'success' => false]);
    	}
    }

    public function testDelete(){
    	$imagePaths = DB::table('1683366807_6456239798f6d_images')
		    ->where('id', '>', -1)
		    ->pluck('placeholder')
		    ->toArray();

		// Storage::disk('public')->delete($imagePaths);


		foreach ($imagePaths as $imagePath) {
			// return public_path($imagePath);
		    $absolutePath = public_path($imagePath);
		    if (file_exists($absolutePath)) {
		        unlink($absolutePath);
		    }
		}

		if(DB::table('1683366807_6456239798f6d_images')
		    ->where('id', '>', -1)
		    ->delete()){

			return "success";
		}
		else{
			return "failed";	
		}
    }

    public function getResultListPoll(){
    	$currentDate = date('Y-m-d');
    	$data = DB::table("all_tables")->select("id","poll_title","table_name_starts_with","which_industry","total_votes","updated_at")
    		->where("ending_date", "<", $currentDate)
    		->where("winner_added", "yes")
    		->orderBy('updated_at', "DESC")
    		->skip(0)
    		->take(10)
    		->get();

    	if($data->count() > 0){
    		foreach ($data as $value) {
    			if(Schema::hasTable($value->table_name_starts_with."_images")){
    				$imgTable = DB::table($value->table_name_starts_with."_images")
	    				->select("placeholder")
	    				->where("id", ">", 1)
	    				->first();
	    			if($imgTable !== null){
	    				$value->thumbnail_image = $imgTable->placeholder;
	    			}
	    			else{
	    				$value->thumbnail_image = "images/test.jpg";
	    			}
    			}
    			else{
    				$value->thumbnail_image = "images/test.jpg";
    			}
    		}
    	}
    	else{
    		return response()->json(['message' => 'No poll found',
	    		'success' => false]);
    	}

    	return response()->json([
    			'all_poll_result' => $data,
    			'message' => 'Poll found',
	    		'success' => true]);
    }

    public function getResultListPollIndustryWise(Request $request){
    	$industryName = $request->input("industryName");
    	$currentDate = date('Y-m-d');
    	$data = DB::table("all_tables")->select("id","poll_title","table_name_starts_with","which_industry","total_votes","updated_at")
    		->where("ending_date", "<", $currentDate)
    		->where("winner_added", "yes")
    		->where("which_industry", $industryName)
    		->orderBy('updated_at', "DESC")
    		->skip(0)
    		->take(10)
    		->get();

    	if($data->count() > 0){
    		foreach ($data as $value) {
    			if(Schema::hasTable($value->table_name_starts_with."_images")){
    				$imgTable = DB::table($value->table_name_starts_with."_images")
	    				->select("placeholder")
	    				->where("id", ">", 1)
	    				->first();
	    			if($imgTable !== null){
	    				$value->thumbnail_image = $imgTable->placeholder;
	    			}
	    			else{
	    				$value->thumbnail_image = "images/test.jpg";
	    			}
    			}
    			else{
    				$value->thumbnail_image = "images/test.jpg";
    			}
    		}
    	}
    	else{
    		return response()->json(['message' => 'No poll found',
	    		'success' => false]);
    	}

    	return response()->json([
    			'all_poll_result' => $data,
    			'message' => 'Poll found',
	    		'success' => true]);
    }

    public function getPollWinnerInfo(Request $request){
    	$pollId = $request->input('pollId');
    	$tableNameStartsWith = "";
    	$pollsFromTable = "";
    	$imagesFromTable = "";
    	$numberOfImages = "";
    	$i = 0;
    	$currentDate = date('Y-m-d');
    	$imageSrc = "";
    	if($tableNameStartsWith = All_Tables::select('poll_title','table_name_starts_with','after_poll_description','which_industry','starting_date','ending_date','winners_name','winners_votes','total_votes','updated_at')
    		->where('table_name_starts_with', $pollId)
    		->where('ending_date','<',$currentDate)
    		->where('winner_added', 'yes')
            ->orWhere(function ($query) use ($pollId) {
                $query->where('poll_title', $pollId);
                // You can add additional OR conditions here
            })
    		->first()){
    		// return $tableNameStartsWith["table_name_starts_with"];
    		if(Schema::hasTable($tableNameStartsWith["table_name_starts_with"].'_images')){
    			// return $tableNameStartsWith["table_name_starts_with"];
    			$imagesFromTable = DB::table($tableNameStartsWith["table_name_starts_with"].'_images')
    			->select('placeholder')->get();
    			// $numberOfImages = $imagesFromTable->count();
    			// return $imagesFromTable[0]->placeholder;
    			


    			$i = 3;
    			foreach ($imagesFromTable as $key) {
    				++$i;
    				
    				if(strpos($tableNameStartsWith["after_poll_description"], "<pic$i>") >= 0){
    					$tableNameStartsWith["after_poll_description"] = str_replace("<pic$i>", "<img src=\"/../".$key->placeholder."\">", $tableNameStartsWith["after_poll_description"]);
    				}
    				
    			}



    			// return $tableNameStartsWith["before_poll_description"];
    			
    		}
    		else{
    			$imagesFromTable->placeholder[0] = "images/test.jpg";
    		}

    		return response()->json([
    				'title_n_other_info' => $tableNameStartsWith,
    				'images_uploaded' => $imagesFromTable,
    				'message' => 'Data received',
	    			'success' => true]);

    	}
    	else{
    		return response()->json(['message' => 'Winning poll not found',
	    		'success' => false]);
    	}
    }

    public function checkIfUserVotedBefore(Request $request){
        $email = $request->input("email");
        $table_name_starts_with = $request->input("table_name_starts_with");

        $data = null;
        
        if(Schema::hasTable($table_name_starts_with."_users_voted")){
            $data = DB::table($table_name_starts_with."_users_voted")
                ->where("email", $email)
                ->first();
        }

        if($data !== null){
            return "voted";
        }
        else{
            return "no";
        }
    }

    public function voteSelectedCandidate(Request $request){
        $table_name_starts_with = $request->input("table_name_starts_with");
        /*if($this->checkIfUserVotedBefore($request) == "voted"){
            $returnData = "";
            $totalVotesGiven = "";
            if(Schema::hasTable($table_name_starts_with."_polls")){
                $returnData = DB::table($table_name_starts_with."_polls")
                    ->select('id', 'polls', DB::raw('SUM(votes + extra_votes) as votes'))
                    ->groupBy('id', 'polls')
                    ->orderBy('polls')
                    ->get();

                $totalVotesGiven = DB::table($table_name_starts_with.'_polls')
                    ->sum(DB::raw('votes + extra_votes'));
                    // ->sum('votes');
            }
            
            return response()->json([
                'new_polls' => $returnData,
                'total_votes' => $totalVotesGiven,
                'message' => 'You have already voted.',
                'success' => true]);
        }
        $email = $request->input("email");*/ // vote using email
    	$selected_id = $request->input("selected_id");
    	
        $data = null;
        if(Schema::hasTable($table_name_starts_with."_polls")){
        	$data = DB::table($table_name_starts_with."_polls")
        		->where("id", $selected_id)
        		->increment('votes');
        }

        /*if($data){
            if(Schema::hasTable($table_name_starts_with."_users_voted")){
                DB::table($table_name_starts_with."_users_voted")->insert([
                    'email' => $email
                ]);
            }
            else{
                // create a table named $table_name_starts_with."_users_voted"
            }
        }*/ // vote using email
    	// $data->polls = $data->polls;
    	// $data->votes = $data->votes + 1;

    	// if($data->save()){
            $returnData = "";
            $totalVotesGiven = "";

            if(Schema::hasTable($table_name_starts_with."_polls")){
        		$returnData = DB::table($table_name_starts_with."_polls")
                    ->select('id', 'polls', DB::raw('SUM(votes + extra_votes) as votes'))
                    ->groupBy('id', 'polls')
                    ->orderBy("polls")
                    ->get();
        		$totalVotesGiven = DB::table($table_name_starts_with.'_polls')
        		    ->sum(DB::raw('votes + extra_votes'));
                    // ->sum('votes');
            }
    		return response()->json([
    			'new_polls' => $returnData,
    			'total_votes' => $totalVotesGiven,
    			'message' => 'Thank you for voting.',
	    		'success' => true]);
    	// }
    	// else{
    	// 	return response()->json(['message' => 'Polls could not updated',
	    // 		'success' => false]);
    	// }


    }

    public function getAllRecentUploadedPoll(){

        $currentDate = date('Y-m-d');
        $allPolls = DB::table('all_tables')->select('which_industry','poll_title','table_name_starts_with','before_poll_description','starting_date','ending_date')
            ->where('ending_date','>',$currentDate)
            ->where('winner_added', 'no')
            ->orderBy('id', 'DESC')
            ->skip(0)
            ->take(20)
            ->get();

        if($allPolls->count() > 0){
            foreach($allPolls as $value){
                $pollTags = "";
                if(Schema::hasTable($value->table_name_starts_with."_polls")){
                    // $pollTags = DB::table($value->table_name_starts_with."_polls")
                    //     ->select('id','polls','votes',DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
                    //     ->get();
                    $pollTags = DB::table($value->table_name_starts_with."_polls")
                        ->select('id', 'polls', DB::raw('SUM(votes + extra_votes) as votes'), DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
                        ->groupBy('id', 'polls', 'table_name_starts_with')
                        ->get();
                }
                $value->poll_tags = $pollTags;

                if(Schema::hasTable($value->table_name_starts_with."_images")){
                    $pollThumbnail = DB::table($value->table_name_starts_with."_images")
                        ->select('placeholder')
                        ->where('id', '>', 1)
                        ->first();
                    if($pollThumbnail !== null){
                        $value->thumbnail_image = $pollThumbnail->placeholder;
                    }
                    else{
                        $value->thumbnail_image = "images/test.jpg";
                    }
                }
                else{
                    $value->thumbnail_image = "images/test.jpg";
                }
            }
            return response()->json([
                'all_polls' => $allPolls,
                'message' => 'Data received',
                'success' => true]);
        }
        else if($allPolls->count() == 0){
            return response()->json([
                'message' => 'No polls uploaded yet',
                'success' => false]);
        }
        else{
            return response()->json([
                'message' => 'Something went wrong',
                'success' => false]);
        }
    }

    public function getAllRecentUploadedPollIndustryWise(Request $request){
        $industryName = $request->input("industryName");
        $currentDate = date('Y-m-d');
        $allPolls = DB::table('all_tables')->select('which_industry','poll_title','table_name_starts_with','before_poll_description','starting_date','ending_date')
            ->where('ending_date','>',$currentDate)
            ->where('winner_added', 'no')
            ->where('which_industry', $industryName)
            ->orderBy('id', 'DESC')
            ->get();

        if($allPolls->count() > 0){
            foreach($allPolls as $value){
                $pollTags = "";
                if(Schema::hasTable($value->table_name_starts_with."_polls")){
                    // $pollTags = DB::table($value->table_name_starts_with."_polls")
                    //     ->select('id','polls','votes',DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
                    //     ->get();
                    $pollTags = DB::table($value->table_name_starts_with."_polls")
                        ->select('id', 'polls', DB::raw('SUM(votes + extra_votes) as votes'), DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
                        ->groupBy('id', 'polls', 'table_name_starts_with')
                        ->get();
                }
                $value->poll_tags = $pollTags;

                if(Schema::hasTable($value->table_name_starts_with."_images")){
                    $pollThumbnail = DB::table($value->table_name_starts_with."_images")
                        ->select('placeholder')
                        ->where('id', '>', 1)
                        ->first();
                    if($pollThumbnail !== null){
                        $value->thumbnail_image = $pollThumbnail->placeholder;
                    }
                    else{
                        $value->thumbnail_image = "images/test.jpg";
                    }
                }
                else{
                    $value->thumbnail_image = "images/test.jpg";
                }
            }
            return response()->json([
                'all_polls' => $allPolls,
                'message' => 'Data received',
                'success' => true]);
        }
        else if($allPolls->count() == 0){
            return response()->json([
                'message' => 'No polls uploaded yet',
                'success' => false]);
        }
        else{
            return response()->json([
                'message' => 'Something went wrong',
                'success' => false]);
        }
    }

    public function getListOfIndustries(){
        $data = DB::table("all_tables")
            ->distinct()
            ->select("which_industry")
            ->orderBy("which_industry")
            ->get();

        if($data->count() > 0){
            return response()->json([
                'all_industry' => $data,
                'message' => 'Data received',
                'success' => true]);
        }
        else{
            return response()->json([
                'message' => 'Data not received',
                'success' => false]);
        }
    }

    public function deleteAllJunkFiles(){
        $sixtyDaysAgo = Carbon::now()->subDays(60)->toDateTimeString();
        // $sixtyDaysAgo = Carbon::now()->addDays(80)->toDateTimeString();
        $data = DB::table("all_tables")
            ->select("table_name_starts_with")
            ->where("updated_at", "<", $sixtyDaysAgo)
            ->where("updated_at" , "<>", null)
            ->where("updated_at", "<>", "")
            ->get();

        if($data->count() > 0){
            foreach ($data as $value) {

                if (Schema::hasTable($value->table_name_starts_with."_polls")) {
                    Schema::dropIfExists($value->table_name_starts_with."_polls");
                }
                if (Schema::hasTable($value->table_name_starts_with."_users_voted")) {
                    Schema::dropIfExists($value->table_name_starts_with."_users_voted");
                }
                if (Schema::hasTable($value->table_name_starts_with."_win")) {
                    Schema::dropIfExists($value->table_name_starts_with."_win");
                }
                if (Schema::hasTable($value->table_name_starts_with."_images")) {
                    $allImages = DB::table($value->table_name_starts_with."_images")
                        ->select("placeholder")
                        ->get();

                    if($allImages->count() > 0){
                        foreach ($allImages as $image) {
                            $absolutePath = public_path($image->placeholder);
                            if (file_exists($absolutePath)) {
                                unlink($absolutePath);
                            }
                        }
                        
                    }
                    
                    Schema::dropIfExists($value->table_name_starts_with."_images");
                }

                DB::table("all_tables")
                    ->where("table_name_starts_with", $value->table_name_starts_with)
                    ->delete();
                
            }
            return response()->json([
                'message' => 'Successfully deleted.',
                'success' => true]);
        }
        else{
            return response()->json([
                'message' => 'Already deleted.',
                'success' => true]);
        }



        // $absolutePath = public_path($imagePath);
        //             if (file_exists($absolutePath)) {
        //                 unlink($absolutePath);
        //             }
    }

    public function getTempDataFromApi(){
        // return "abcd";
        return response()->json([
            'message' => 'Successfully deleted.',
            'success' => true]);
    }

    public function getListOfHistoryVotes(){
        $data = HistoryVotes::select("id", "star_name")->get();
        if($data){
            return response()->json([
                'value' => $data,
                'message' => 'Data received.',
                'success' => true]);    
        }
        else{
            return response()->json([
                'value' => '',
                'message' => 'Data not found.',
                'success' => false]);
        }
        
    }

    public function getListOfAllPollHistory(){
        $data = DB::table("history_votes")
            ->select("star_name", "total_votes_received", "total_nominations", "total_won")
            ->orderBy("total_votes_received", "DESC")
            ->get();

        if($data){
            return response()->json([
                'value' => $data,
                'message' => 'Data received.',
                'success' => true]); 
        }
        else{
            return response()->json([
                'value' => '',
                'message' => 'Data not found.',
                'success' => false]);
        }
    }

    public function searchHistoryPollBy(Request $request){
        $whichColumn = $request->input("whichColumn");
        $data = null;

        if ($whichColumn) {
            $query = DB::table("history_votes")
                ->select("star_name", "total_votes_received", "total_nominations", "total_won");

            if ($whichColumn == "starName" && $request->input("starName") != "") {
                $query->where("star_name", "LIKE", "%" . $request->input("starName") . "%");
            } 
            else if ($whichColumn == "totalVotes" && $request->input("totalVotes") != "") {
                $query->where("total_votes_received", ">=", $request->input("totalVotes"));
            } 
            else if ($whichColumn == "nominated" && $request->input("nominated") != "") {
                $query->where("total_nominations", ">=", $request->input("nominated"));
            } 
            else if ($whichColumn == "wonPoll" && $request->input("wonPoll") != "") {
                $query->where("total_won", ">=", $request->input("wonPoll"));
            }

            $data = $query->orderBy("total_votes_received", "DESC")->get();

            if($data->isNotEmpty()) {
                return response()->json([
                    'value' => $data,
                    'message' => 'Data found.',
                    'success' => true
                ]);
            } 
            else{
                return response()->json([
                    'value' => null,
                    'message' => 'No data found.',
                    'success' => false
                ]);
            }
        } 
        else{
            return response()->json([
                'value' => null,
                'message' => 'No data found.',
                'success' => false
            ]);
        }
    }


    public function getAllRecentUploadedPollForAdmin(){

        $currentDate = date('Y-m-d');
        $allPolls = DB::table('all_tables')->select('which_industry','poll_title','table_name_starts_with','before_poll_description','starting_date','ending_date')
            ->where('ending_date','>',$currentDate)
            ->where('winner_added', 'no')
            ->orderBy('id', 'DESC')
            ->get();

        if($allPolls->count() > 0){
            foreach($allPolls as $value){
                $pollTags = "";
                if(Schema::hasTable($value->table_name_starts_with."_polls")){
                    // $pollTags = DB::table($value->table_name_starts_with."_polls")
                    //     ->select('id','polls','votes',DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
                    //     ->get();
                    $pollTags = DB::table($value->table_name_starts_with."_polls")
                        ->select('id', 'polls', DB::raw('SUM(votes + extra_votes) as votes'), DB::raw("'".$value->table_name_starts_with."' as table_name_starts_with"))
                        ->groupBy('id', 'polls', 'table_name_starts_with')
                        ->get();
                }
                $value->poll_tags = $pollTags;

                
            }
            return response()->json([
                'all_polls' => $allPolls,
                'message' => 'Data received',
                'success' => true]);
        }
        else if($allPolls->count() == 0){
            return response()->json([
                'message' => 'No polls uploaded yet',
                'success' => false]);
        }
        else{
            return response()->json([
                'message' => 'Something went wrong',
                'success' => false]);
        }
    }


    public function updatePollForAdmin(Request $request){
        // return $request;
        $table_name_starts_with = $request->input("tableNameStartsWith");
        
        $selected_id = $request->input("starId");
        
        $data = null;
        if(Schema::hasTable($table_name_starts_with."_polls")){
            $data = DB::table($table_name_starts_with."_polls")
                ->where("id", $selected_id)
                ->increment('votes');
        }

        return response()->json(['success' => true]);
        
    }
}
