<?php

namespace App\Traits;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Storage;

trait UploadFilesTrait
{
    function uploadThumbnail($file, $path, $name = '', $deleteCallback = null) {
        $new_thumbnail_name = Str::slug($name, '-') . '_' .  Str::random(5)  .  '.' . $file->getClientOriginalExtension();

        if(is_callable($deleteCallback)) {
            $deleteCallback();
        }

        $file->move(public_path($path), $new_thumbnail_name);

        return $new_thumbnail_name;
    }

    function deleteFile($filePath) {
        if(file_exists(public_path($filePath) )&& is_file(public_path($filePath))) {
            @unlink(public_path($filePath));
            return true;
        }
        return false;
    }
}
