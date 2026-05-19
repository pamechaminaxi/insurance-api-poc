<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\QuoteService;
use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class QuoteController extends Controller
{
    protected $quoteService;

    //constructor for quote service 
    public function __construct(QuoteService $quoteService)
    {
        $this->quoteService = $quoteService;
    }

    //list all quotes using service and paginate logs 
    public function index(Request $request)
    {
        try {
            $quotes = $this->quoteService->getAllQuotes($request->all());
            return ApiResponse::success('Quotes fetched successfully', $quotes);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    //create quote using service and validate request using form requests 
    public function store(StoreQuoteRequest $request)
    {
        try {
            $quote = $this->quoteService->createQuote($request->validated());
            return ApiResponse::success('Quote created successfully', $quote, 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
    
    //show quote using service 
    public function show($id)
    {
        try {
            $quote = $this->quoteService->getQuoteById($id);
            return ApiResponse::success('Quote details', $quote);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    //update quote using service and validate request using form requests 
    public function update(StoreQuoteRequest $request, $id)
    {
        try {
            $quote = $this->quoteService->updateQuote($id, $request->validated());
            return ApiResponse::success('Quote updated successfully', $quote);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    //delete quote using service 
    public function destroy($id)
    {
        try {
            $this->quoteService->deleteQuote($id);
            return ApiResponse::success('Quote deleted successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
