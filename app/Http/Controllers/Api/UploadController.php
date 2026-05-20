<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UploadService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    protected UploadService $uploader;

    public function __construct(UploadService $uploader)
    {
        $this->uploader = $uploader;
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required','file','mimes:jpg,jpeg,png,pdf','max:10240'],
            'folder' => ['nullable','string'],
        ]);

        $file = $request->file('file');
        $folder = $request->get('folder', 'uploads');

        $path = $this->uploader->store($file, $folder);

        return response()->json(['success'=>true,'data'=>['path' => $path, 'url' => $this->uploader->url($path)]]);
    }
}

