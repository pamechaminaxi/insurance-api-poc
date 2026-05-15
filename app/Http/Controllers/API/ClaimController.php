<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ClaimService;
use App\Services\DocumentService;
use App\Http\Requests\Claim\StoreClaimRequest;
use App\Http\Requests\Claim\UpdateClaimRequest;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    protected $claimService;
    protected $documentService;

    public function __construct(ClaimService $claimService, DocumentService $documentService)
    {
        $this->claimService = $claimService;
        $this->documentService = $documentService;
    }

    public function index(Request $request)
    {
        try {
            $claims = $this->claimService->getAllClaims($request->all());
            return ApiResponse::success('Claims fetched successfully', $claims);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function store(StoreClaimRequest $request)
    {
        try {
            $claim = $this->claimService->createClaim($request->validated());

            if ($request->hasFile('documents')) {
                $this->documentService->uploadDocuments($claim->id, $request->file('documents'));
            }

            return ApiResponse::success('Claim filed successfully', $claim->load('documents'), 201);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $claim = $this->claimService->getClaimById($id);
            return ApiResponse::success('Claim details', $claim);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:Pending,Under Review,Approved,Rejected,Settled'
            ]);

            $claim = $this->claimService->updateClaimStatus($id, $request->status);
            return ApiResponse::success('Claim status updated', $claim);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function update(UpdateClaimRequest $request, $id)
    {
        try {
            $claim = $this->claimService->updateClaim($id, $request->validated());
            return ApiResponse::success('Claim updated successfully', $claim);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
