<?php

namespace App\Http\Controllers;

use App;
use App\Http\Controllers\Controller;
use DB;

class UbikeController extends Controller {

	public function dataGet()
	{
		$result = array();
		$sqlData = DB::table('Ubike')->select('ItemId','Total_bikes','Remain_bikes')->get();

    foreach ($sqlData as $resultData)
		{
			array_push($result, $resultData);
		}

		return json_encode($result);
	}

  public function index(){
		$url  = 'https://tcgbusfs.blob.core.windows.net/blobfs/YouBikeTP.gz';
		$path = storage_path().'/app/YouBikeTP.gz';
		$filePath = fopen($path, 'w');
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_FILE, $filePath);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_SSLVERSION,3);
    $returnList = curl_exec($ch);
		echo $error = curl_error($ch);
		fputs($filePath, $returnList);
		fclose($filePath);
    curl_close($ch);
		$this->gzUnpack();
		$string = file_get_contents(storage_path().'/app/YouBikeTP');
    return $string;
  }

	public function gzUnpack(){
		$file_name = storage_path().'/app/YouBikeTP.gz';
		// Raising this value may increase performance
		$buffer_size = 4096; // read 4kb at a time
		$out_file_name = str_replace('.gz', '', $file_name);
		// Open our files (in binary mode)
		$file = gzopen($file_name, 'rb');
		$out_file = fopen($out_file_name, 'wb');
		// Keep repeating until the end of the input file
		while(!gzeof($file)) {
		// Read buffer-size bytes
		// Both fwrite and gzread and binary-safe
		  fwrite($out_file, gzread($file, $buffer_size));
		}
		// Files are done, close files
		fclose($out_file);
		gzclose($file);
  }

	public function store(){

    $stations = DB::table('Station');

    if($stations === null) {
      $this->storeStation();
    }

		$this->storeUbike();

	}

  public function storeUbike(){

    $ubikeList = $this->index();
    $ubikeList = json_decode($ubikeList);
    $ubikeList = $ubikeList->retVal;

    foreach ($ubikeList as $ublikeObj) {
      $ubike = new APP\Ubike;

			$ubike->active = $ublikeObj->act;
	      $ubike->remainBikes = $ublikeObj->sbi;
      $ubike->stationNo = $ublikeObj->sno;
      $ubike->save();

    }
  }

  public function storeStation(){

    $ubikeList = $this->index();

    $ubikeList = json_decode($ubikeList);
    $ubikeList = $ubikeList->retVal;

    foreach ($ubikeList as $ubikeObj) {

      $station = APP\Station::find(array('stationNo' => $ubikeObj->sno))->first();

      if ($station  === null) {
        $station = new APP\Station;
      }

      $station->active = $ubikeObj->act;
      $station->longitude = $ubikeObj->lat;
      $station->latitude = $ubikeObj->lng;
      $station->stationNo = $ubikeObj->sno;
      $station->stationName = $ubikeObj->sna;
			$station->stationArea = $ubikeObj->sarea;
      $station->stationLocation = $ubikeObj->ar;
      $station->totalBikes = $ubikeObj->tot;
      $station->save();


    }
		echo "Done";
  }


}
