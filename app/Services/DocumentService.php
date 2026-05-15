<?php

namespace App\Services;

use App\Models\ClaimDocument;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function uploadDocuments($claimId, $files)
    {
        $uploadedDocs = [];

        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $path = $file->store('claims', 'public');

            $uploadedDocs[] = ClaimDocument::create([
                'claim_id' => $claimId,
                'file_name' => $fileName,
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
            ]);
        }

        return $uploadedDocs;
    }

    public function deleteDocument($id)
    {
        $doc = ClaimDocument::findOrFail($id);
        Storage::disk('public')->delete($doc->file_path);
        return $doc->delete();
    }
}
