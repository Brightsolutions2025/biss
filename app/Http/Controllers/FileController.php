<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\LeaveRequest;
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
            $fileable = $file->fileable;

            if ($fileable instanceof LeaveRequest && $fileable->isEmergencyLeave()) {
                $otherAttachmentCount = $fileable->files()
                    ->where('id', '!=', $file->id)
                    ->count();

                if ($otherAttachmentCount < 1) {
                    return response()->json([
                        'error' => 'Emergency Leave (EL) must keep at least one supporting document.',
                    ], 422);
                }
            }

            if (Storage::exists($file->file_path)) {
                Storage::delete($file->file_path);
            }

            $file->delete();

            return response()->json(['message' => 'File deleted successfully.']);
        } catch (\Throwable $e) {
            \Log::error('File deletion failed: ' . $e->getMessage());

            return response()->json(['error' => 'Could not delete'], 500);
        }
    }
}
