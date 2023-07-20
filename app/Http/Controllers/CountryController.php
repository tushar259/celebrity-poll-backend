<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CountryController extends Controller
{
    public function insertDataIntoCountryTable(){
    	
    	$currentDate = date('Y-m-d');

    	if($this->checkIfCountryTableExist() == "existedNow"){
    		$checkIfInsertOrUpdate = $this->checkIfLastUpdatedInAWeek();
    		if($checkIfInsertOrUpdate == "insertNow" || $checkIfInsertOrUpdate == "updateNow"){

    			try{
		    		$client = new Client();
			        $response = $client->get('https://restcountries.com/v3.1/all');
			        $data = json_decode($response->getBody(), true);

			        $value = [];
					foreach ($data as $countryData) {
					    $value[] = [
					        'name' => $countryData['name']['common'],
					        'population' => $countryData['population'],
					        'updated_at' => $currentDate
					    ];
					}
					if($checkIfInsertOrUpdate == "insertNow"){
						DB::table('countries')->insert($value);
					}
					else if($checkIfInsertOrUpdate == "updateNow"){
						foreach ($value as $row) {
					        DB::table('countries')
					            ->where('name', $row['name'])
					            ->update($row);
					    }
					}
				}
				catch(RequestException $e){
					$statusCode = $e->getResponse()->getStatusCode();
    				$message = $e->getMessage();

					$data = DB::table("countries")->select("name","population")
	    				->orderBy("name")
	    				->get();
	    			return response()->json([
		    			'countries_list' => $data,
		                'message' => $message,
		                'success' => true]);
				}

	    	}
	    	$data = DB::table("countries")->select("name","population")
	    				->orderBy("name")
	    				->get();

	    	if($data->count() > 0){
	    		return response()->json([
	    			'countries_list' => $data,
	                'message' => 'List of countries given.',
	                'success' => true]);
	    	}
	    	else{
	    		return response()->json([
		            'message' => 'Server error.',
		            'success' => false]);
	    	}
    	}
    	else{
    		return response()->json([
                'message' => 'Server error.',
                'success' => false]);
    	}
    }

    public function checkIfLastUpdatedInAWeek(){
    	$sevenDaysAgo = Carbon::now()->subDays(7)->toDateTimeString();
    	if(DB::table('countries')->count() > 0){
    		$records = DB::table('countries')
					    ->where('updated_at', '<', $sevenDaysAgo)
					    ->first();

			if($records !== null){
				return "updateNow";
			}
			else{
				return "doNotInsert";
			}
    	}

    	return "insertNow";

    }

    public function checkIfCountryTableExist(){
    	if (!Schema::hasTable('countries')) {
	        Schema::create('countries', function (Blueprint $table) {
	            $table->id();
	            $table->string('name');
	            $table->integer('population');
	            $table->timestamps();
	        });
	    }

	    return "existedNow";
    }
}
