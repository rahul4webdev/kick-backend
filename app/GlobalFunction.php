<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Util\GlobalState;

class GlobalFunction extends Model
{
    use HasFactory;

    public static function createMediaUrl($media)
    {
        $url = env('ITEM_BASE_URL') . $media;
        return $url;
    }

    public static function uploadFilToS3($file)
    {
        $s3 = Storage::disk('s3');
        $fileName = time() . $file->getClientOriginalName();
        $fileName = str_replace(array(' ', ':'), '_', $fileName);
        $destinationPath = env('DEFAULT_IMAGE_PATH');
        $filePath = $destinationPath . $fileName;
        $result =  $s3->put($filePath, file_get_contents($file), 'public-read');
        return $fileName;
    }

    public static function cleanString($string)
    {

        return  str_replace(array('<', '>', '{', '}', '[', ']', '`'), '', $string);
    }

    public static function generateRandomString($length)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
