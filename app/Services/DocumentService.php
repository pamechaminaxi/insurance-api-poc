<?php

namespace App\Services;

use App\Models\ClaimDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    // upload documents for a claim with DB transaction and file rollback
    public function uploadDocuments($claimId, $files)
    {
        $storedFiles = [];
        
        try {
            //DB Transaction for atomicity (all or nothing)
            $uploadedDocs = DB::transaction(function () use ($claimId, $files, &$storedFiles) {

                
                $uploadedDocs = [];
                // loop through files and upload
                foreach ($files as $file) {
                    
                    // store file
                    $path = $file->store('claims', 'public');
                    
                    // track stored files
                    $storedFiles[] = $path;
                    
                    // create DB record
                    $uploadedDocs[] = ClaimDocument::create([
                        'claim_id' => $claimId,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                    ]);
                }
                
                return $uploadedDocs;
            });
            
            return $uploadedDocs;
            
        } catch (\Exception $e) {
            
            // delete uploaded files if DB fails
            foreach ($storedFiles as $path) {
                // check if file exists before deleting
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            
            throw $e;
        }
    }

    // delete document
    public function deleteDocument($id)
    {
        return DB::transaction(function () use ($id) {
            $doc = ClaimDocument::findOrFail($id);

            // delete physical file
            if (Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }

            // delete db record
            return $doc->delete();
        });
    }
}