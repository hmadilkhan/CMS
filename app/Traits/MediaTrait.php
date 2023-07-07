<?php
namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait MediaTrait {
	
	public function uploads($file, $path, $previousImage="")
    {
        if($file) {
			$this->removeImage($path,$previousImage);
            $fileName   = time() ."-". str_replace(' ', '',$file->getClientOriginalName());
            Storage::disk('public')->put($path . $fileName, File::get($file));
			$file_size = $this->fileSize($file);
			// $file->move(public_path('images/'.$path), $fileName);
            $file_name  = $file->getClientOriginalName();
            $file_type  = $file->getClientOriginalExtension();
            $filePath   = "https://sabsoft.com.pk/SabifyProject/public/images/".$path.$fileName;

            return $file = [
                'fileName' => $fileName,
                'fileType' => $file_type,
                'filePath' => $filePath,
                'fileSize' => $file_size,
            ];
        }
        // else{
        //     return 0;
        // }
    }
	
	public function fileSize($file, $precision = 2)
    {   
        $size = $file->getSize();

        if ( $size > 0 ) {
            $size = (int) $size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');
            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        }

        return $size;
    }
	
	public function removeImage($path,$filename)
	{
		if($filename != ""){
			if(File::exists(public_path('images/'.$path.$filename) )){
				File::delete(public_path('images/'.$path.$filename));
				return 1;
			}
		}
	}
	
}