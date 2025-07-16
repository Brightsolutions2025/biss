<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function download(File $file)
    {
        // Optional auth/authorization logic here
        return Storage::download($file->file_path, $file->file_name);
    }
    public function destroy(File $file)
    {
        try {
            // Optionally delete from disk
            if (\Storage::exists($file->file_path)) {
                \Storage::delete($file->file_path);
            }

            $file->delete();

            return response()->json(['message' => 'File deleted successfully.']);
        } catch (\Throwable $e) {
            \Log::error('File deletion failed: ' . $e->getMessage());
            return response()->json(['error' => 'Could not delete'], 500);
        }
    }
}
